#!/usr/bin/env bash
# UpdraftPlus backups to S3 (PLAN.md §5.9 layer 2). Run from the admin's
# machine, signed in to AWS (aws sso login). Never run from CI: the stack makes
# an IAM user, and GitHub holds no AWS credential on purpose.
#
#   wordpress/ops/aws-backups-deploy.sh [--rotate-key] [--configure-site] [--staging]
#
# (no flag)         deploy wordpress/ops/aws/backups.yml as stack "spokares-backups"
#                   (a versioned private bucket and the UpdraftPlus IAM user)
# --rotate-key      also make a new access key for that user, pipe it over SSH
#                   into UpdraftPlus's S3 settings, then delete the user's older
#                   keys. The key is never printed or written to disk.
# --configure-site  also set UpdraftPlus to S3 with the §5.9 schedule: database
#                   weekly (keep 8), uploads monthly (keep 3), plugins/themes/
#                   others off (git and wordpress.org cover them), local copies
#                   deleted after upload
#
# ops.env: SPOKARES_AWS_PROFILE (the SSO profile), SPOKARES_AWS_REGION
# (default us-west-2).
set -euo pipefail
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/lib.sh"

ROTATE=0; CONFIGURE=0; TARGET=production
for arg in "$@"; do
  case $arg in
    --rotate-key) ROTATE=1 ;;
    --configure-site) CONFIGURE=1 ;;
    --staging) TARGET=staging ;;
    *) echo "usage: $0 [--rotate-key] [--configure-site] [--staging]" >&2; exit 2 ;;
  esac
done
spk_load_env
spk_target "$TARGET"
WP=$SPOKARES_WP_PATH
STACK=spokares-backups
export AWS_REGION=${SPOKARES_AWS_REGION:-us-west-2}
[[ -n ${SPOKARES_AWS_PROFILE:-} ]] && export AWS_PROFILE=$SPOKARES_AWS_PROFILE
command -v aws >/dev/null || spk_die "the AWS CLI is not installed"
aws sts get-caller-identity >/dev/null 2>&1 || spk_die "not signed in to AWS: aws sso login${AWS_PROFILE:+ --profile $AWS_PROFILE}"

echo "aws-backups-deploy: stack $STACK in $AWS_REGION"
aws cloudformation deploy --stack-name "$STACK" \
  --template-file "$SPK_OPS_DIR/aws/backups.yml" \
  --capabilities CAPABILITY_NAMED_IAM --no-fail-on-empty-changeset
out() { aws cloudformation describe-stacks --stack-name "$STACK" --query "Stacks[0].Outputs[?OutputKey=='$1'].OutputValue" --output text; }
BUCKET=$(out BucketName); USER_NAME=$(out UserName)
echo "aws-backups-deploy: bucket $BUCKET, user $USER_NAME"

if (( ROTATE )); then
  OLD_KEYS=$(aws iam list-access-keys --user-name "$USER_NAME" --query 'AccessKeyMetadata[].AccessKeyId' --output text)
  (( $(wc -w <<<"$OLD_KEYS") < 2 )) || spk_die "$USER_NAME already has two keys; delete one in IAM first"
  # The new key goes from the AWS CLI straight into ssh's stdin; the PHP on the
  # host reads it from php://stdin and stores it in the existing S3 instance.
  aws iam create-access-key --user-name "$USER_NAME" --query 'AccessKey.[AccessKeyId,SecretAccessKey]' --output text \
    | ssh "$SPK_SSH" "cd $WP && BUCKET='$BUCKET' wp eval '
        [\$id, \$secret] = preg_split(\"/\\s+/\", trim(file_get_contents(\"php://stdin\")));
        if (!\$id || !\$secret) { WP_CLI::error(\"no key on stdin\"); }
        \$opt = get_option(\"updraft_s3\");
        if (!is_array(\$opt) || empty(\$opt[\"settings\"])) {
          \$opt = array(\"version\" => 1, \"settings\" => array(\"s-\" . md5(wp_generate_password(32, false)) => array()));
        }
        foreach (\$opt[\"settings\"] as &\$s) {
          \$s = array_merge((array) \$s, array(\"accesskey\" => \$id, \"secretkey\" => \$secret, \"path\" => getenv(\"BUCKET\"), \"rrs\" => \"\", \"server_side_encryption\" => \"1\"));
        }
        update_option(\"updraft_s3\", \$opt);
        WP_CLI::success(\"UpdraftPlus S3 key stored (\" . substr(\$id, 0, 4) . \"…)\");
      '"
  for k in $OLD_KEYS; do
    aws iam delete-access-key --user-name "$USER_NAME" --access-key-id "$k"
    echo "aws-backups-deploy: deleted the old key ${k:0:4}…"
  done
fi

if (( CONFIGURE )); then
  ssh "$SPK_SSH" "cd $WP && wp eval '
      update_option(\"updraft_service\", array(\"s3\"));
      update_option(\"updraft_include_plugins\", 0);
      update_option(\"updraft_include_themes\", 0);
      update_option(\"updraft_include_uploads\", 1);
      update_option(\"updraft_include_others\", 0);
      update_option(\"updraft_delete_local\", 1);
      update_option(\"updraft_retain\", 3);
      update_option(\"updraft_retain_db\", 8);
      update_option(\"updraft_interval_database\", \"weekly\");
      update_option(\"updraft_interval\", \"monthly\");
      // The schedule is made by these callbacks, which only run from the wp-admin form.
      \$GLOBALS[\"updraftplus\"]->schedule_backup(\"monthly\");
      \$GLOBALS[\"updraftplus\"]->schedule_backup_database(\"weekly\");
      WP_CLI::success(\"UpdraftPlus: S3, database weekly keep 8, uploads monthly keep 3\");
    ' && wp cron event list --fields=hook,next_run_gmt --format=csv | grep -i updraft_backup || true"
fi
