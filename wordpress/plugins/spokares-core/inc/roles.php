<?php
/**
 * The ARES Editor role, custom capabilities, the per-user Net details grant,
 * the roles the site offers, and the meta-capability rules for the fixed
 * pages, Media Library files and synced patterns (§4.1).
 *
 * @package spokares-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * The primitive caps generated for a capability_type pair.
 *
 * @param string $one  Singular.
 * @param string $many Plural.
 */
function spokares_type_caps( string $one, string $many ): array {
	unset( $one );
	return array(
		"edit_{$many}",
		"edit_others_{$many}",
		"edit_published_{$many}",
		"edit_private_{$many}",
		"publish_{$many}",
		"delete_{$many}",
		"delete_others_{$many}",
		"delete_published_{$many}",
		"delete_private_{$many}",
		"read_private_{$many}",
	);
}

/**
 * Create or refresh the ARES Editor role and give administrators every custom
 * capability. Runs on activation and whenever the plugin version changes.
 */
function spokares_sync_roles(): void {
	$caps = array(
		'read'                 => true,
		'upload_files'         => true,
		'edit_posts'           => true,
		'edit_pages'           => true,
		'edit_others_pages'    => true,
		'edit_published_pages' => true,
		'publish_pages'        => true,
		'spokares_edit_rota'   => true,
	);
	foreach ( array_merge( spokares_type_caps( 'spk_event', 'spk_events' ), spokares_type_caps( 'spk_document', 'spk_documents' ) ) as $cap ) {
		$caps[ $cap ] = true;
	}

	$role = get_role( 'ares_editor' );
	if ( ! $role ) {
		add_role( 'ares_editor', __( 'ARES Editor', 'spokares-core' ), $caps );
	} else {
		// Make the role exactly this list: add what's missing, drop anything else.
		foreach ( array_keys( $role->capabilities ) as $cap ) {
			if ( ! isset( $caps[ $cap ] ) ) {
				$role->remove_cap( $cap );
			}
		}
		foreach ( $caps as $cap => $grant ) {
			if ( empty( $role->capabilities[ $cap ] ) ) {
				$role->add_cap( $cap, $grant );
			}
		}
	}

	$admin = get_role( 'administrator' );
	if ( $admin ) {
		$custom = array_merge(
			array( 'spokares_edit_rota', 'spokares_edit_net_details' ),
			spokares_type_caps( 'spk_event', 'spk_events' ),
			spokares_type_caps( 'spk_document', 'spk_documents' )
		);
		foreach ( $custom as $cap ) {
			if ( empty( $admin->capabilities[ $cap ] ) ) {
				$admin->add_cap( $cap );
			}
		}
	}

	// New accounts are ARES Editors unless an administrator picks otherwise.
	if ( ! in_array( get_option( 'default_role' ), spokares_offered_roles(), true ) ) {
		update_option( 'default_role', 'ares_editor' );
	}
}

/**
 * The roles the site gives accounts (§4.1): Administrator and ARES Editor.
 * WordPress's own Editor, Author, Contributor and Subscriber roles still
 * exist, but they are never offered.
 *
 * @return string[]
 */
function spokares_offered_roles(): array {
	return array( 'administrator', 'ares_editor' );
}

/**
 * Page IDs of the three members pages (by path), cached per request.
 */
function spokares_members_page_ids(): array {
	static $ids = null;
	if ( null !== $ids ) {
		return $ids;
	}
	$ids = array();
	foreach ( array( 'members', 'members/documents', 'members/exercises' ) as $path ) {
		$page = get_page_by_path( $path, OBJECT, 'page' );
		if ( $page ) {
			$ids[] = (int) $page->ID;
		}
	}
	return $ids;
}

/**
 * Meta-capability rules (§4.1):
 *
 * - The members pages have no content; their templates hold everything, so
 *   editing them needs edit_theme_options (administrators only).
 * - The six pages are fixed: only administrators delete or trash a page.
 *   WordPress's own Editor role has delete_pages, delete_others_pages and
 *   delete_published_pages; without this it could delete About for good, or
 *   trash How it works (which renames its slug to how-it-works__trashed).
 * - A Media Library file can be edited or deleted only by the person who
 *   uploaded it, or by an administrator. The core Editor role's
 *   edit_others_posts and delete_others_posts would otherwise let it crop,
 *   rotate or delete anyone's file, the Home hero photo included.
 * - unfiltered_html needs manage_options as well, whatever wp-config.php
 *   says, so an Editor-level account never stores script in a page.
 *
 * @param string[] $caps    Primitive caps.
 * @param string   $cap     Meta cap.
 * @param int      $user_id User.
 * @param array    $args    Args (post ID first).
 */
function spokares_map_meta_cap( $caps, $cap, $user_id, $args ) {
	if ( in_array( $cap, array( 'delete_pages', 'delete_others_pages', 'delete_published_pages', 'delete_private_pages' ), true ) ) {
		return array( 'manage_options' );
	}
	if ( 'unfiltered_html' === $cap ) {
		return array_merge( (array) $caps, array( 'manage_options' ) );
	}
	// edit_page and delete_page too: core's Quick Edit handler asks for
	// edit_page before it asks for edit_post.
	if ( ! in_array( $cap, array( 'edit_post', 'edit_page', 'delete_post', 'delete_page', 'publish_post' ), true ) || empty( $args[0] ) ) {
		return $caps;
	}
	$post_id = (int) $args[0];
	$type    = get_post_type( $post_id );
	if ( 'page' === $type && in_array( $post_id, spokares_members_page_ids(), true ) ) {
		return array( 'edit_theme_options' );
	}
	$deletes = in_array( $cap, array( 'delete_post', 'delete_page' ), true );
	if ( 'page' === $type && $deletes ) {
		return array( 'manage_options' );
	}
	if ( 'attachment' === $type && ( $deletes || in_array( $cap, array( 'edit_post', 'edit_page' ), true ) ) ) {
		if ( (int) get_post_field( 'post_author', $post_id ) !== (int) $user_id ) {
			return array_merge( (array) $caps, array( 'manage_options' ) );
		}
	}
	return $caps;
}
add_filter( 'map_meta_cap', 'spokares_map_meta_cap', 10, 4 );

/**
 * Only administrators may create posts or pages: the six pages are fixed.
 * Synced patterns (wp_block) are administrators' only too: core lets anyone
 * who can publish posts create one, and lets its author (or anyone with
 * edit_others_posts) change it later, which would change every page an
 * administrator inserted it in, round the layout lock (§4.3 layer 6).
 * The must-use plugin does the same (content-surface.php); this copy keeps
 * the rule if the must-use plugin ever fails to deploy.
 *
 * @param array  $args      Post type args.
 * @param string $post_type Post type.
 */
function spokares_create_caps( $args, $post_type ) {
	if ( ! is_array( $args ) ) {
		return $args;
	}
	if ( in_array( $post_type, array( 'post', 'page' ), true ) ) {
		$args['capabilities']                 = isset( $args['capabilities'] ) && is_array( $args['capabilities'] ) ? $args['capabilities'] : array();
		$args['capabilities']['create_posts'] = 'manage_options';
	} elseif ( 'wp_block' === $post_type ) {
		$args['capabilities'] = array_merge(
			isset( $args['capabilities'] ) && is_array( $args['capabilities'] ) ? $args['capabilities'] : array(),
			array_fill_keys( spokares_pattern_cap_keys(), 'manage_options' )
		);
	}
	return $args;
}
add_filter( 'register_post_type_args', 'spokares_create_caps', 10, 2 );

/**
 * The synced-pattern capabilities that are administrators' only (all but
 * "read", which lets the block editor show a pattern already on a page).
 *
 * @return string[]
 */
function spokares_pattern_cap_keys(): array {
	return array( 'create_posts', 'edit_posts', 'edit_others_posts', 'edit_published_posts', 'edit_private_posts', 'publish_posts', 'delete_posts', 'delete_others_posts', 'delete_published_posts', 'delete_private_posts', 'read_private_posts' );
}

/* ------------------------------------------------------- offered roles */

/**
 * The existing account whose role is being changed right now, as core asks
 * "may I promote this user?" just before it reads the editable roles
 * (edit_user(), the REST users route). Read once, then forgotten.
 *
 * @param int|null $set An account ID to remember, 0 to forget, null to read.
 */
function spokares_role_change_account( ?int $set = null ): int {
	static $account = 0;
	if ( null !== $set ) {
		$account = $set;
	}
	return $account;
}

/**
 * Remember the account behind a promote_user check.
 *
 * @param string[] $caps    Primitive caps.
 * @param string   $cap     Meta cap.
 * @param int      $user_id User asking.
 * @param array    $args    Args (the account first).
 */
function spokares_note_role_change_account( $caps, $cap, $user_id, $args ) {
	unset( $user_id );
	if ( 'promote_user' === $cap && ! empty( $args[0] ) ) {
		spokares_role_change_account( (int) $args[0] );
	}
	return $caps;
}
add_filter( 'map_meta_cap', 'spokares_note_role_change_account', 10, 4 );
add_action( 'set_current_user', static fn() => spokares_role_change_account( 0 ) );

/**
 * Users › Add User, Settings › General's default role and the Users screen's
 * "Change role to…" offer only Administrator and ARES Editor (§4.1).
 * When an existing account is edited (its profile screen, or core changing
 * its role), the role it already has stays in the list, so re-saving a
 * legacy account neither fails nor silently changes its role, and so does
 * Subscriber, which holds no powers: demoting to it (or to "No role for this
 * site") is how an administrator takes an editor's access away. A crafted
 * request for any other role gets core's own 403 ("Sorry, you are not
 * allowed to give users that role.").
 *
 * @param array $roles Role key => role details.
 */
function spokares_editable_roles( $roles ) {
	if ( ! is_array( $roles ) ) {
		return $roles;
	}
	$account_id = spokares_role_change_account();
	spokares_role_change_account( 0 );
	global $pagenow;
	if ( 'user-edit.php' === $pagenow ) {
		// phpcs:disable WordPress.Security.NonceVerification -- read-only: which account's screen this is; core checks the form's nonce.
		if ( isset( $_POST['user_id'] ) ) {
			$account_id = absint( $_POST['user_id'] );
		} elseif ( isset( $_GET['user_id'] ) ) {
			$account_id = absint( $_GET['user_id'] );
		}
		// phpcs:enable WordPress.Security.NonceVerification
	}
	$keep    = spokares_offered_roles();
	$account = $account_id ? get_userdata( $account_id ) : false;
	if ( $account ) {
		$keep = array_merge( $keep, (array) $account->roles, array( 'subscriber' ) );
	}
	return array_intersect_key( $roles, array_flip( $keep ) );
}
add_filter( 'editable_roles', 'spokares_editable_roles' );

/**
 * The new-user default role is never a role the site doesn't offer, so Add
 * User preselects ARES Editor and a new account is never a Subscriber.
 *
 * @param mixed $role Stored default role.
 */
function spokares_default_role( $role ) {
	return in_array( $role, spokares_offered_roles(), true ) ? $role : 'ares_editor';
}
add_filter( 'option_default_role', 'spokares_default_role' );

/* ------------------------------------------------ the Net details grant */

/**
 * Does this account hold the ARES Editor role?
 *
 * @param WP_User $user User.
 */
function spokares_is_ares_editor( WP_User $user ): bool {
	return in_array( 'ares_editor', (array) $user->roles, true );
}

/**
 * The per-user Net details grant counts only while the account is an ARES
 * Editor (§4.1). WordPress keeps per-user capabilities when a role changes,
 * so without this an ex-editor demoted to Subscriber (or to no role) could
 * still change the public repeater frequency, below the two-factor floor
 * and the weekly audit, which look at accounts that can edit_posts.
 * Administrators have it from their role.
 *
 * @param bool[]  $allcaps All caps of the user.
 * @param array   $caps    Caps asked for.
 * @param array   $args    Arguments.
 * @param WP_User $user    User.
 */
function spokares_net_grant_needs_role( $allcaps, $caps, $args, $user ) {
	unset( $caps, $args );
	if ( empty( $allcaps['spokares_edit_net_details'] ) || ! $user instanceof WP_User || spokares_is_ares_editor( $user ) ) {
		return $allcaps;
	}
	foreach ( (array) $user->roles as $name ) {
		$role = get_role( $name );
		if ( $role && $role->has_cap( 'spokares_edit_net_details' ) ) {
			return $allcaps;
		}
	}
	$allcaps['spokares_edit_net_details'] = false;
	return $allcaps;
}
add_filter( 'user_has_cap', 'spokares_net_grant_needs_role', 10, 4 );

/**
 * Changing an ARES Editor to another role (or to none) ends the grant, so
 * making the account an ARES Editor again later doesn't quietly restore it.
 *
 * @param int $user_id User whose role changed.
 */
function spokares_net_grant_role_changed( $user_id ): void {
	$user = get_userdata( (int) $user_id );
	if ( ! $user || spokares_is_ares_editor( $user ) || empty( $user->caps['spokares_edit_net_details'] ) ) {
		return;
	}
	$user->remove_cap( 'spokares_edit_net_details' );
}
add_action( 'set_user_role', 'spokares_net_grant_role_changed' );
add_action( 'remove_user_role', 'spokares_net_grant_role_changed' );

/**
 * The Net details grant, shown only on ANOTHER user's profile, only to
 * someone who can promote users, and only when that user is an ARES Editor.
 *
 * @param WP_User $user The user being edited.
 */
function spokares_profile_grant_field( $user ): void {
	if ( ! current_user_can( 'promote_users' ) || ! $user instanceof WP_User ) {
		return;
	}
	if ( user_can( $user, 'manage_options' ) || ! spokares_is_ares_editor( $user ) ) {
		return; // Administrators already have it; other roles can't hold it.
	}
	wp_nonce_field( 'spokares_grant_' . $user->ID, 'spokares_grant_nonce' );
	?>
	<h2><?php esc_html_e( 'ARES site', 'spokares-core' ); ?></h2>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><?php esc_html_e( 'Net details and Meeting rules', 'spokares-core' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="spokares_net_details" value="1" <?php checked( ! empty( $user->allcaps['spokares_edit_net_details'] ) ); ?>>
					<?php esc_html_e( 'May change the repeaters’ frequency, offset and tone (not the club’s call sign), the net times and weeks, and the regular meeting rules.', 'spokares-core' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Only for someone the Emergency Coordinator has named. Everyone with the ARES Editor role can already edit the rota, events, meetings and documents.', 'spokares-core' ); ?></p>
			</td>
		</tr>
	</table>
	<?php
}
add_action( 'edit_user_profile', 'spokares_profile_grant_field' );

/**
 * Save the Net details grant (another user's profile only).
 *
 * @param int $user_id User being saved.
 */
function spokares_profile_grant_save( $user_id ): void {
	$user_id = (int) $user_id;
	if ( ! current_user_can( 'promote_users' ) || get_current_user_id() === $user_id ) {
		return;
	}
	if ( ! isset( $_POST['spokares_grant_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['spokares_grant_nonce'] ) ), 'spokares_grant_' . $user_id ) ) {
		return;
	}
	$user = get_userdata( $user_id );
	if ( ! $user || user_can( $user, 'manage_options' ) ) {
		return;
	}
	if ( ! empty( $_POST['spokares_net_details'] ) && spokares_is_ares_editor( $user ) ) {
		$user->add_cap( 'spokares_edit_net_details' );
	} elseif ( ! empty( $user->caps['spokares_edit_net_details'] ) ) {
		$user->remove_cap( 'spokares_edit_net_details' );
	}
}
add_action( 'edit_user_profile_update', 'spokares_profile_grant_save' );

/**
 * Users screen: a "Net details" column, so an administrator can see who
 * holds the grant without opening every profile.
 *
 * @param string[] $columns Column key => title.
 */
function spokares_users_grant_column( $columns ) {
	if ( ! is_array( $columns ) || ! current_user_can( 'promote_users' ) ) {
		return $columns;
	}
	$columns['spokares_net_details'] = __( 'Net details', 'spokares-core' );
	return $columns;
}
add_filter( 'manage_users_columns', 'spokares_users_grant_column' );

/**
 * The "Net details" column's cell: who may change Net details and Meeting
 * rules, and why.
 *
 * @param string $output  Cell so far.
 * @param string $column  Column key.
 * @param int    $user_id User on this row.
 */
function spokares_users_grant_cell( $output, $column, $user_id ) {
	if ( 'spokares_net_details' !== $column ) {
		return $output;
	}
	if ( user_can( (int) $user_id, 'manage_options' ) ) {
		return esc_html__( 'Yes (administrator)', 'spokares-core' );
	}
	if ( user_can( (int) $user_id, 'spokares_edit_net_details' ) ) {
		return esc_html__( 'Yes', 'spokares-core' );
	}
	return '<span aria-hidden="true">—</span><span class="screen-reader-text">' . esc_html__( 'No', 'spokares-core' ) . '</span>';
}
add_filter( 'manage_users_custom_column', 'spokares_users_grant_cell', 10, 3 );
