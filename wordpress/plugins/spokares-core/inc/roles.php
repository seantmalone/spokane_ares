<?php
/**
 * The ARES Editor role, custom capabilities, the per-user Net details grant
 * and the meta-capability rules for the members pages (§4.1).
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
 * The members pages have no content; their templates hold everything, so
 * editing them needs edit_theme_options (administrators only).
 *
 * @param string[] $caps    Primitive caps.
 * @param string   $cap     Meta cap.
 * @param int      $user_id User.
 * @param array    $args    Args (post ID first).
 */
function spokares_map_meta_cap( $caps, $cap, $user_id, $args ) {
	// edit_page and delete_page too: core's Quick Edit handler asks for
	// edit_page before it asks for edit_post.
	if ( in_array( $cap, array( 'edit_post', 'edit_page', 'delete_post', 'delete_page', 'publish_post' ), true ) && ! empty( $args[0] ) ) {
		$post_id = (int) $args[0];
		if ( 'page' === get_post_type( $post_id ) && in_array( $post_id, spokares_members_page_ids(), true ) ) {
			return array( 'edit_theme_options' );
		}
	}
	return $caps;
}
add_filter( 'map_meta_cap', 'spokares_map_meta_cap', 10, 4 );

/**
 * Only administrators may create posts or pages: the six pages are fixed.
 * The must-use plugin does the same (content-surface.php); this copy keeps
 * the rule if the must-use plugin ever fails to deploy.
 *
 * @param array  $args      Post type args.
 * @param string $post_type Post type.
 */
function spokares_create_caps( $args, $post_type ) {
	if ( in_array( $post_type, array( 'post', 'page' ), true ) && is_array( $args ) ) {
		$args['capabilities']                 = isset( $args['capabilities'] ) && is_array( $args['capabilities'] ) ? $args['capabilities'] : array();
		$args['capabilities']['create_posts'] = 'manage_options';
	}
	return $args;
}
add_filter( 'register_post_type_args', 'spokares_create_caps', 10, 2 );

/**
 * The Net details grant, shown only on ANOTHER user's profile and only to
 * someone who can promote users.
 *
 * @param WP_User $user The user being edited.
 */
function spokares_profile_grant_field( $user ): void {
	if ( ! current_user_can( 'promote_users' ) || ! $user instanceof WP_User ) {
		return;
	}
	if ( user_can( $user, 'manage_options' ) ) {
		return; // Administrators already have it.
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
					<?php esc_html_e( 'May change the repeater settings, net times and weeks, and the regular meeting rules.', 'spokares-core' ); ?>
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
	if ( ! empty( $_POST['spokares_net_details'] ) ) {
		$user->add_cap( 'spokares_edit_net_details' );
	} else {
		$user->remove_cap( 'spokares_edit_net_details' );
	}
}
add_action( 'edit_user_profile_update', 'spokares_profile_grant_save' );
