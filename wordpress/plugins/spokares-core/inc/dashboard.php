<?php
/**
 * Dashboard › "Site tasks" (§3.4): the five jobs, what's posted, and what
 * needs attention. The only widget editors see.
 *
 * @package spokares-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the widget (at the top for everyone).
 */
function spokares_dashboard_setup(): void {
	if ( ! spokares_has_site_tasks() ) {
		// An account with nothing to do here gets one plain sentence, not an
		// empty Dashboard that says "Add boxes from the Screen Options menu".
		wp_add_dashboard_widget( 'spokares_no_tasks', __( 'Your account', 'spokares-core' ), 'spokares_dashboard_no_tasks', null, null, 'normal', 'high' );
		return;
	}
	wp_add_dashboard_widget( 'spokares_site_tasks', __( 'Site tasks', 'spokares-core' ), 'spokares_dashboard_widget', null, null, 'normal', 'high' );
}
add_action( 'wp_dashboard_setup', 'spokares_dashboard_setup' );

/**
 * Does the current user have any of the five site tasks?
 */
function spokares_has_site_tasks(): bool {
	return current_user_can( 'spokares_edit_rota' ) || current_user_can( 'edit_spk_events' ) || current_user_can( 'edit_spk_documents' ) || current_user_can( 'edit_pages' );
}

/**
 * The Dashboard for an account with no site tasks.
 */
function spokares_dashboard_no_tasks(): void {
	?>
	<p><?php esc_html_e( 'This account can’t change anything on the site. You can change your own name, e-mail and password, and set up two-step sign-in, on your profile.', 'spokares-core' ); ?></p>
	<p class="spk-account-actions"><a class="button button-primary" href="<?php echo esc_url( admin_url( 'profile.php' ) ); ?>"><?php esc_html_e( 'Your profile', 'spokares-core' ); ?></a>
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Go to the site', 'spokares-core' ); ?></a></p>
	<p class="spk-stuck"><?php esc_html_e( 'Need to update the rota, events or documents? Ask the webmaster:', 'spokares-core' ); ?> <a href="mailto:webmaster@spokares.org">webmaster@spokares.org</a></p>
	<?php
}

/**
 * The Dashboard's Screen Options offer nothing to non-admins (their one box
 * can't be moved or hidden usefully): no tab.
 *
 * @param bool      $show   Show the tab.
 * @param WP_Screen $screen Screen.
 */
function spokares_dashboard_no_screen_options( $show, $screen ) {
	if ( $screen instanceof WP_Screen && 'dashboard' === $screen->id && ! current_user_can( 'manage_options' ) ) {
		return false;
	}
	return $show;
}
add_filter( 'screen_options_show_screen', 'spokares_dashboard_no_screen_options', 10, 2 );

/**
 * The rota summary: the last Tuesday posted, open slots in the next 13, and
 * whether it runs out within 3 weeks.
 */
function spokares_rota_summary(): array {
	$rows    = spokares_rota_rows( 13 );
	$through = '';
	$open    = array();
	foreach ( $rows as $row ) {
		if ( 'tbd' !== $row['state'] ) {
			$through = $row['date'];
		}
		if ( 'open' === $row['state'] ) {
			$open[] = $row['date'];
		}
	}
	return array(
		'through' => $through,
		'open'    => $open,
		'short'   => '' === $through || $through < spokares_add_days( spokares_today(), 21 ),
	);
}

/**
 * Where the "Needs checking" items are, for the Dashboard's links: the events
 * and documents lists filtered to them, and the settings screens that hold
 * the rest. Only places with items are listed.
 *
 * @return array<int,array{label:string,url:string,n:int}>
 */
function spokares_needs_check_places(): array {
	$out = array();
	foreach ( array(
		'spk_event'    => __( 'Events', 'spokares-core' ),
		'spk_document' => __( 'Documents', 'spokares-core' ),
	) as $type => $label ) {
		$q = new WP_Query(
			array(
				'post_type'      => $type,
				'post_status'    => array( 'publish', 'draft' ),
				'posts_per_page' => 1,
				'fields'         => 'ids',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- dashboard count, small tables.
				'meta_query'     => array(
					array(
						'key'   => 'spk_needs_check',
						'value' => '1',
					),
				),
			)
		);
		if ( $q->found_posts ) {
			$out[] = array(
				'label' => $label,
				'url'   => admin_url( 'edit.php?post_type=' . $type . '&spk_check=1' ),
				'n'     => (int) $q->found_posts,
			);
		}
	}
	$nets  = spokares_opt( 'spk_nets' );
	$radio = spokares_opt( 'spk_radio' );
	$site  = spokares_opt( 'spk_site' );
	$net_n = ( $nets['needs_check'] ? 1 : 0 ) + ( $radio['alternate']['needs_check'] ? 1 : 0 );
	if ( $net_n ) {
		$out[] = array(
			'label' => __( 'Net details', 'spokares-core' ),
			'url'   => admin_url( 'admin.php?page=spokares-net-details' ),
			'n'     => $net_n,
		);
	}
	$meet_n = count( array_filter( spokares_opt( 'spk_meetings' )['meetings'], static fn( $m ) => ! empty( $m['needs_check'] ) ) );
	if ( $meet_n ) {
		$out[] = array(
			'label' => __( 'Meeting rules', 'spokares-core' ),
			'url'   => admin_url( 'admin.php?page=spokares-meeting-rules' ),
			'n'     => $meet_n,
		);
	}
	if ( $site['place']['needs_check'] ) {
		$out[] = array(
			'label' => __( 'ARES site settings', 'spokares-core' ),
			'url'   => admin_url( 'options-general.php?page=spokares-site' ),
			'n'     => 1,
		);
	}
	return $out;
}

/**
 * The three text pages and whether their text ('hits') or their excerpt
 * ('excerpt_hits': the page's meta description, public too) trips the
 * never-publish, phone or e-mail checks.
 */
function spokares_page_text_status(): array {
	$out = array();
	foreach ( array( 'home', 'how-it-works', 'about' ) as $path ) {
		$page = get_page_by_path( $path, OBJECT, 'page' );
		if ( ! $page ) {
			continue;
		}
		$out[] = array(
			'page'         => $page,
			'hits'         => spokares_check_text( (string) preg_replace( '/<!--.*?-->/s', ' ', $page->post_content ) ),
			'excerpt_hits' => spokares_check_text( (string) $page->post_excerpt ),
		);
	}
	return $out;
}

/**
 * The widget.
 */
function spokares_dashboard_widget(): void {
	$attention = array();
	// Tasks are numbered as they are printed: an account without some of
	// them never sees a gap (a lone "Page text" is 1, not 5).
	$step = 0;
	?>
	<div class="spk-tasks">
	<?php if ( current_user_can( 'spokares_edit_rota' ) ) : ?>
		<?php $rota = spokares_rota_summary(); ?>
		<section class="spk-task">
			<h3><span class="spk-task__n"><?php echo esc_html( (string) ++$step ); ?></span> <?php esc_html_e( 'Net rota', 'spokares-core' ); ?></h3>
			<p>
				<?php
				if ( '' !== $rota['through'] ) {
					/* translators: %s: date. */
					echo esc_html( sprintf( __( 'Posted through %s', 'spokares-core' ), spokares_fmt_date( $rota['through'], 'short' ) ) );
				} else {
					esc_html_e( 'Nothing posted for the coming Tuesdays.', 'spokares-core' );
				}
				if ( $rota['open'] ) {
					echo ' · ' . esc_html(
						sprintf(
							/* translators: 1: number, 2: dates. */
							_n( '%1$d open slot (%2$s)', '%1$d open slots (%2$s)', count( $rota['open'] ), 'spokares-core' ),
							count( $rota['open'] ),
							implode( ', ', array_map( static fn( $d ) => spokares_fmt_date( $d, 'day' ), $rota['open'] ) )
						)
					);
				}
				?>
			</p>
			<p><a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=spokares-rota' ) ); ?>"><?php esc_html_e( 'Update the rota', 'spokares-core' ); ?></a>
				<?php if ( current_user_can( 'spokares_edit_net_details' ) ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=spokares-net-details' ) ); ?>"><?php esc_html_e( 'Net details', 'spokares-core' ); ?></a>
				<?php endif; ?>
			</p>
			<?php if ( ! current_user_can( 'spokares_edit_net_details' ) ) : ?>
				<p class="description"><?php esc_html_e( 'Repeater details and net times: ask the webmaster.', 'spokares-core' ); ?></p>
			<?php endif; ?>
		</section>
		<?php
		if ( $rota['short'] ) {
			$attention[] = __( 'The rota runs out in under 3 weeks.', 'spokares-core' );
		}
		?>
	<?php endif; ?>

	<?php if ( current_user_can( 'edit_spk_events' ) ) : ?>
		<?php
		$events = spokares_events( 'all-upcoming' );
		$now    = array_values( array_filter( $events, 'spokares_event_is_now' ) );
		$next   = array_values( array_filter( $events, static fn( $e ) => ! spokares_event_is_now( $e ) && 'date' === $e['mode'] ) );
		?>
		<section class="spk-task">
			<h3><span class="spk-task__n"><?php echo esc_html( (string) ++$step ); ?></span> <?php esc_html_e( 'Events', 'spokares-core' ); ?></h3>
			<?php if ( $now ) : ?>
				<p>
					<?php
					/* translators: %s: event names. */
					echo esc_html( sprintf( __( 'Happening now: %s', 'spokares-core' ), implode( ', ', array_map( static fn( $e ) => html_entity_decode( $e['title'], ENT_QUOTES, 'UTF-8' ), $now ) ) ) );
					?>
				</p>
			<?php endif; ?>
			<p>
				<?php
				if ( $next ) {
					$more = count( $events ) - count( $now ) - 1;
					echo esc_html(
						sprintf(
							/* translators: 1: event, 2: when. */
							__( 'Next: %1$s, %2$s', 'spokares-core' ),
							html_entity_decode( $next[0]['title'], ENT_QUOTES, 'UTF-8' ),
							str_replace( "\u{00A0}", ' ', spokares_fmt_when( $next[0], 'card' ) )
						)
					);
					if ( $more > 0 ) {
						/* translators: %d: number of events. */
						echo ' · ' . esc_html( sprintf( __( '%d more', 'spokares-core' ), $more ) );
					}
				} else {
					esc_html_e( 'No dated events coming up.', 'spokares-core' );
				}
				?>
			</p>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=spk_event' ) ); ?>"><?php esc_html_e( 'Add an event', 'spokares-core' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=spk_event' ) ); ?>"><?php esc_html_e( 'All events', 'spokares-core' ); ?></a>
			</p>
		</section>
	<?php endif; ?>

	<?php if ( current_user_can( 'spokares_edit_rota' ) ) : ?>
		<?php
		$soon = null;
		foreach ( spokares_next_meetings( 1 ) as $item ) {
			if ( ! empty( $item['dates'][0] ) && ( ! $soon || $item['dates'][0]['date'] < $soon['dates'][0]['date'] ) ) {
				$soon = $item;
			}
		}
		?>
		<section class="spk-task">
			<h3><span class="spk-task__n"><?php echo esc_html( (string) ++$step ); ?></span> <?php esc_html_e( 'Meetings', 'spokares-core' ); ?></h3>
			<?php if ( $soon ) : ?>
				<p>
					<?php
					$m    = $soon['meeting'];
					$when = '' !== $m['start'] ? spokares_fmt_time( $m['start'] ) : $m['time_text'];
					/* translators: 1: meeting, 2: date, 3: time. */
					echo esc_html( str_replace( "\u{00A0}", ' ', sprintf( __( 'Next: %1$s, %2$s, %3$s', 'spokares-core' ), $m['name'], spokares_fmt_date( $soon['dates'][0]['date'], 'short' ), $when ) ) );
					?>
				</p>
			<?php endif; ?>
			<p><a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=spokares-meetings' ) ); ?>"><?php esc_html_e( 'Cancel or move a meeting', 'spokares-core' ); ?></a>
				<?php if ( current_user_can( 'spokares_edit_net_details' ) ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=spokares-meeting-rules' ) ); ?>"><?php esc_html_e( 'Meeting rules', 'spokares-core' ); ?></a>
				<?php endif; ?>
			</p>
		</section>
	<?php endif; ?>

	<?php if ( current_user_can( 'edit_spk_documents' ) ) : ?>
		<?php
		$library = spokares_library_documents();
		$total   = array_sum( array_map( 'count', $library ) );
		$stale   = 0;
		$cutoff  = spokares_add_days( spokares_today(), -365 );
		foreach ( $library as $docs ) {
			foreach ( $docs as $doc ) {
				$r = (string) get_post_meta( $doc['id'], 'spk_reviewed', true );
				if ( ! spokares_is_ymd( $r ) || $r < $cutoff ) {
					++$stale;
				}
			}
		}
		?>
		<section class="spk-task">
			<h3><span class="spk-task__n"><?php echo esc_html( (string) ++$step ); ?></span> <?php esc_html_e( 'Documents', 'spokares-core' ); ?></h3>
			<p>
				<?php
				/* translators: %d: number of documents. */
				echo esc_html( sprintf( _n( '%d in the library', '%d in the library', $total, 'spokares-core' ), $total ) );
				if ( $stale ) {
					/* translators: %d: number of documents. */
					echo ' · ' . esc_html( sprintf( __( '%d not reviewed in 12 months', 'spokares-core' ), $stale ) );
				}
				?>
			</p>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=spk_document' ) ); ?>"><?php esc_html_e( 'Add a document', 'spokares-core' ); ?></a>
				<a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=spk_document' ) ); ?>"><?php esc_html_e( 'Replace a document', 'spokares-core' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=spokares-tiles' ) ); ?>"><?php esc_html_e( 'Hub tiles', 'spokares-core' ); ?></a>
			</p>
		</section>
	<?php endif; ?>

	<?php if ( current_user_can( 'edit_pages' ) ) : ?>
		<?php $pages = spokares_page_text_status(); ?>
		<section class="spk-task">
			<h3><span class="spk-task__n"><?php echo esc_html( (string) ++$step ); ?></span> <?php esc_html_e( 'Page text', 'spokares-core' ); ?></h3>
			<p class="spk-page-links">
				<?php
				$links = array();
				foreach ( $pages as $item ) {
					$edit = get_edit_post_link( $item['page']->ID );
					if ( $edit ) {
						$links[] = '<a href="' . esc_url( $edit ) . '">' . esc_html( get_the_title( $item['page'] ) ) . '</a>';
					}
					if ( $item['hits'] ) {
						$attention[] = sprintf(
							/* translators: %s: page title. */
							__( '%s may contain something we never publish. Check it.', 'spokares-core' ),
							html_entity_decode( get_the_title( $item['page'] ), ENT_QUOTES, 'UTF-8' )
						);
					}
					if ( $item['excerpt_hits'] ) {
						$attention[] = sprintf(
							/* translators: %s: page title. */
							__( 'The excerpt of %s (the description search engines show) may contain something we never publish. Check it in the Page panel.', 'spokares-core' ),
							html_entity_decode( get_the_title( $item['page'] ), ENT_QUOTES, 'UTF-8' )
						);
					}
				}
				echo wp_kses( implode( ' · ', $links ), array( 'a' => array( 'href' => true ) ) );
				?>
			</p>
		</section>
	<?php endif; ?>

	<?php
	$checks = current_user_can( 'manage_options' ) ? spokares_needs_check_places() : array();
	?>
	<?php if ( $attention || $checks ) : ?>
		<div class="spk-attention">
			<h3><?php esc_html_e( 'Needs attention', 'spokares-core' ); ?></h3>
			<ul>
				<?php foreach ( $attention as $line ) : ?>
					<li><?php echo esc_html( $line ); ?></li>
				<?php endforeach; ?>
				<?php if ( $checks ) : ?>
					<li>
						<?php
						$n = array_sum( wp_list_pluck( $checks, 'n' ) );
						/* translators: %d: number of items. */
						echo esc_html( sprintf( _n( '%d item marked “Needs checking”:', '%d items marked “Needs checking”:', $n, 'spokares-core' ), $n ) ) . ' ';
						$links = array();
						foreach ( $checks as $place ) {
							$links[] = '<a href="' . esc_url( $place['url'] ) . '">' . esc_html( $place['label'] ) . '</a> (' . esc_html( (string) $place['n'] ) . ')';
						}
						echo wp_kses( implode( ' · ', $links ), array( 'a' => array( 'href' => true ) ) );
						?>
					</li>
				<?php endif; ?>
			</ul>
		</div>
	<?php endif; ?>
		<p class="spk-stuck"><?php esc_html_e( 'Stuck?', 'spokares-core' ); ?> <a href="mailto:webmaster@spokares.org">webmaster@spokares.org</a></p>
	</div>
	<?php
}
