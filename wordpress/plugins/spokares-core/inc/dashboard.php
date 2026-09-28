<?php
/**
 * Dashboard › "Site tasks" (§3.4, UX spec §3.1): what needs attention first,
 * then the five jobs under the names of their screens, each with what is
 * posted and the buttons that change it. The only widget editors see.
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
	<p class="spk-stuck"><?php esc_html_e( 'Need to change the Net Control Schedule, events or documents? Ask the webmaster:', 'spokares-core' ); ?> <a href="mailto:webmaster@spokares.org">webmaster@spokares.org</a></p>
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
 * The Net Control Schedule summary over the next 52 Tuesdays: the last
 * Tuesday posted (a call sign, Volunteer needed or No net), the Volunteer
 * needed Tuesdays up to it, the Not posted yet Tuesdays before it, and
 * whether it runs out within 3 weeks.
 *
 * @return array{through:string,open:string[],tbd:string[],short:bool}
 */
function spokares_rota_summary(): array {
	$rows    = spokares_rota_rows( 52 );
	$posted  = static fn( array $row ): bool => ( 'call' === $row['state'] && '' !== $row['call'] ) || in_array( $row['state'], array( 'open', 'none' ), true );
	$through = '';
	foreach ( $rows as $row ) {
		if ( $posted( $row ) ) {
			$through = $row['date'];
		}
	}
	$open = array();
	$tbd  = array();
	foreach ( $rows as $row ) {
		if ( '' === $through || $row['date'] > $through ) {
			break;
		}
		if ( 'open' === $row['state'] ) {
			$open[] = $row['date'];
		} elseif ( ! $posted( $row ) && $row['date'] < $through ) {
			$tbd[] = $row['date'];
		}
	}
	return array(
		'through' => $through,
		'open'    => $open,
		'tbd'     => $tbd,
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
		'spk_event'    => spokares_edit_screen( 'events' )[0],
		'spk_document' => spokares_edit_screen( 'documents' )[0],
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
			'label' => spokares_edit_screen( 'net-details' )[0],
			'url'   => admin_url( 'admin.php?page=spokares-net-details' ),
			'n'     => $net_n,
		);
	}
	$meet_n = count( array_filter( spokares_opt( 'spk_meetings' )['meetings'], static fn( $m ) => ! empty( $m['needs_check'] ) ) );
	if ( $meet_n ) {
		$out[] = array(
			'label' => spokares_edit_screen( 'meeting-rules' )[0],
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
 * ('excerpt_hits': the page's search-engine description, public too) trips
 * the never-publish, phone or e-mail checks.
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
 * Dates as "Oct 6, Oct 13 and Oct 20".
 *
 * @param string[] $dates Y-m-d dates.
 */
function spokares_dashboard_days( array $dates ): string {
	return spokares_and_list( array_map( static fn( $d ) => spokares_fmt_date( $d, 'day' ), $dates ) );
}

/**
 * One line of text with a link after it, escaped.
 *
 * @param string $text  Plain text.
 * @param string $url   Link.
 * @param string $label Link words.
 */
function spokares_dashboard_line( string $text, string $url = '', string $label = '' ): string {
	return esc_html( $text ) . ( '' !== $url ? ' <a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>' : '' );
}

/**
 * The widget: Needs attention first (each line with the link that fixes it),
 * then one section per job, named as its screen.
 */
function spokares_dashboard_widget(): void {
	$attention = array();
	$admin     = current_user_can( 'manage_options' );
	$rota_url  = admin_url( 'admin.php?page=spokares-rota' );

	$rota = current_user_can( 'spokares_edit_rota' ) ? spokares_rota_summary() : null;
	if ( $rota && $rota['short'] ) {
		$attention[] = spokares_dashboard_line( __( 'The Net Control Schedule runs out in under 3 weeks.', 'spokares-core' ), $rota_url, __( 'Fill in more Tuesdays', 'spokares-core' ) );
	}

	$pages = current_user_can( 'edit_pages' ) ? spokares_page_text_status() : array();
	foreach ( $pages as $item ) {
		$title = html_entity_decode( get_the_title( $item['page'] ), ENT_QUOTES, 'UTF-8' );
		$edit  = (string) get_edit_post_link( $item['page']->ID, 'raw' );
		if ( $item['hits'] ) {
			$attention[] = spokares_dashboard_line(
				sprintf(
					/* translators: 1: page title, 2: what it looks like, e.g. "a phone number", 3: the text found. */
					__( '%1$s has what looks like %2$s (%3$s).', 'spokares-core' ),
					$title,
					$item['hits'][0]['what'],
					$item['hits'][0]['match'] ?? ''
				),
				$edit,
				__( 'Open the page', 'spokares-core' )
			);
		}
		// The search-engine description is the webmaster's (editors can't see it).
		if ( $admin && $item['excerpt_hits'] ) {
			$attention[] = spokares_dashboard_line(
				sprintf(
					/* translators: 1: page title, 2: what it looks like, e.g. "a phone number", 3: the text found. */
					__( 'The search-engine description of %1$s has what looks like %2$s (%3$s).', 'spokares-core' ),
					$title,
					$item['excerpt_hits'][0]['what'],
					$item['excerpt_hits'][0]['match'] ?? ''
				),
				$edit,
				__( 'Open the page', 'spokares-core' )
			);
		}
	}

	$checks = $admin ? spokares_needs_check_places() : array();
	if ( $checks ) {
		$n     = array_sum( wp_list_pluck( $checks, 'n' ) );
		$links = array();
		foreach ( $checks as $place ) {
			$links[] = '<a href="' . esc_url( $place['url'] ) . '">' . esc_html( $place['label'] ) . '</a> (' . esc_html( (string) $place['n'] ) . ')';
		}
		/* translators: %d: number of items. */
		$attention[] = esc_html( sprintf( _n( '%d item marked “Needs checking”:', '%d items marked “Needs checking”:', $n, 'spokares-core' ), $n ) ) . ' ' . implode( ' · ', $links );
	}

	$line_kses = array( 'a' => array( 'href' => true ) );
	?>
	<div class="spk-tasks">
	<?php if ( $attention ) : ?>
		<div class="spk-attention">
			<h3><?php esc_html_e( 'Needs attention', 'spokares-core' ); ?></h3>
			<ul>
				<?php foreach ( $attention as $line ) : ?>
					<li><?php echo wp_kses( $line, $line_kses ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<?php if ( $rota ) : ?>
		<section class="spk-task">
			<h3><?php echo esc_html( spokares_edit_screen( 'rota' )[0] ); ?></h3>
			<p>
				<?php
				if ( '' !== $rota['through'] ) {
					/* translators: %s: date, e.g. "Tue, Dec 29". */
					echo esc_html( sprintf( __( 'Posted through %s', 'spokares-core' ), spokares_fmt_date( $rota['through'], 'short' ) ) );
					if ( $rota['open'] ) {
						/* translators: %s: dates, e.g. "Oct 6 and Oct 20". */
						echo ' · ' . esc_html( sprintf( __( 'Volunteer needed: %s', 'spokares-core' ), spokares_dashboard_days( $rota['open'] ) ) );
					}
					if ( $rota['tbd'] ) {
						/* translators: %s: dates, e.g. "Nov 3". */
						echo ' · ' . esc_html( sprintf( __( 'Not posted yet: %s', 'spokares-core' ), spokares_dashboard_days( $rota['tbd'] ) ) );
					}
				} else {
					esc_html_e( 'Nothing posted for the coming Tuesdays.', 'spokares-core' );
				}
				?>
			</p>
			<p class="spk-task__actions"><a class="button button-primary" href="<?php echo esc_url( $rota_url ); ?>"><?php esc_html_e( 'Update the schedule', 'spokares-core' ); ?></a>
				<?php if ( current_user_can( 'spokares_edit_net_details' ) ) : ?>
					<?php $net = spokares_edit_screen( 'net-details' ); ?>
					<a href="<?php echo esc_url( $net[1] ); ?>"><?php echo esc_html( $net[0] ); ?></a>
				<?php endif; ?>
			</p>
			<?php if ( ! current_user_can( 'spokares_edit_net_details' ) ) : ?>
				<p class="description"><?php esc_html_e( 'Repeater and net times: ask the webmaster.', 'spokares-core' ); ?></p>
			<?php endif; ?>
		</section>
	<?php endif; ?>

	<?php if ( current_user_can( 'edit_spk_events' ) ) : ?>
		<?php
		// A cancelled event is not happening, and not next.
		$events = array_values( array_filter( spokares_events( 'all-upcoming' ), static fn( $e ) => empty( $e['cancelled'] ) ) );
		$now    = array_values( array_filter( $events, 'spokares_event_is_now' ) );
		$next   = array_values( array_filter( $events, static fn( $e ) => ! spokares_event_is_now( $e ) && 'date' === $e['mode'] ) );
		?>
		<section class="spk-task">
			<h3><?php echo esc_html( spokares_edit_screen( 'events' )[0] ); ?></h3>
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
					$name = esc_html( html_entity_decode( $next[0]['title'], ENT_QUOTES, 'UTF-8' ) );
					$edit = (string) get_edit_post_link( $next[0]['id'], 'raw' );
					echo wp_kses(
						sprintf(
							/* translators: 1: event name (a link to its form), 2: when. */
							esc_html__( 'Next: %1$s, %2$s', 'spokares-core' ),
							'' !== $edit ? '<a href="' . esc_url( $edit ) . '">' . $name . '</a>' : $name,
							esc_html( str_replace( "\u{00A0}", ' ', spokares_fmt_when( $next[0], 'card' ) ) )
						),
						$line_kses
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
			<p class="spk-task__actions">
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=spk_event' ) ); ?>"><?php esc_html_e( 'Add an event', 'spokares-core' ); ?></a>
				<a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=spk_event' ) ); ?>"><?php esc_html_e( 'Change or cancel an event', 'spokares-core' ); ?></a>
			</p>
		</section>
	<?php endif; ?>

	<?php if ( current_user_can( 'spokares_edit_rota' ) ) : ?>
		<?php
		// Only the meetings the site shows, after cancellations and moves.
		$soon = null;
		foreach ( spokares_next_meetings( 1 ) as $item ) {
			$shown = ! empty( $item['meeting']['active'] ) && ! empty( $item['meeting']['show_home'] );
			if ( $shown && ! empty( $item['dates'][0] ) && ( ! $soon || $item['dates'][0]['date'] < $soon['dates'][0]['date'] ) ) {
				$soon = $item;
			}
		}
		?>
		<section class="spk-task">
			<h3><?php echo esc_html( spokares_edit_screen( 'meetings' )[0] ); ?></h3>
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
			<p class="spk-task__actions"><a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=spokares-meetings' ) ); ?>"><?php esc_html_e( 'Cancel or move a meeting', 'spokares-core' ); ?></a>
				<?php if ( current_user_can( 'spokares_edit_net_details' ) ) : ?>
					<?php $rules = spokares_edit_screen( 'meeting-rules' ); ?>
					<a href="<?php echo esc_url( $rules[1] ); ?>"><?php echo esc_html( $rules[0] ); ?></a>
				<?php endif; ?>
			</p>
		</section>
	<?php endif; ?>

	<?php if ( current_user_can( 'edit_spk_documents' ) ) : ?>
		<?php
		$library = spokares_library_documents();
		$total   = 0;
		$stale   = 0;
		$cutoff  = spokares_add_days( spokares_today(), -365 );
		// The number the Soon view lists (a FEMA course with its course links shows those, not "Soon").
		$marked = function_exists( 'spokares_document_soon_count' ) ? spokares_document_soon_count() : 0;
		foreach ( $library as $docs ) {
			foreach ( $docs as $doc ) {
				++$total;
				$r = (string) get_post_meta( $doc['id'], 'spk_reviewed', true );
				if ( ! spokares_is_ymd( $r ) || $r < $cutoff ) {
					++$stale;
				}
			}
		}
		$list  = admin_url( 'edit.php?post_type=spk_document' );
		$tiles = spokares_edit_screen( 'tiles' );
		?>
		<section class="spk-task">
			<h3><?php echo esc_html( spokares_edit_screen( 'documents' )[0] ); ?></h3>
			<p>
				<?php
				/* translators: %d: number of documents. */
				echo esc_html( sprintf( _n( '%d document', '%d documents', $total, 'spokares-core' ), $total ) );
				if ( $marked ) {
					/* translators: %d: number of documents. */
					echo ' · <a href="' . esc_url( add_query_arg( 'spk_source', 'soon', $list ) ) . '">' . esc_html( sprintf( __( '%d marked Soon', 'spokares-core' ), $marked ) ) . '</a>';
				}
				if ( $stale ) {
					$oldest = add_query_arg(
						array(
							'orderby' => 'spk_reviewed',
							'order'   => 'asc',
						),
						$list
					);
					/* translators: %d: number of documents. */
					echo ' · <a href="' . esc_url( $oldest ) . '">' . esc_html( sprintf( __( '%d not reviewed in 12 months', 'spokares-core' ), $stale ) ) . '</a>';
				}
				?>
			</p>
			<p class="spk-task__actions">
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=spk_document' ) ); ?>"><?php esc_html_e( 'Add a document', 'spokares-core' ); ?></a>
				<a class="button" href="<?php echo esc_url( $list ); ?>"><?php esc_html_e( 'Replace or change a document', 'spokares-core' ); ?></a>
				<a href="<?php echo esc_url( $tiles[1] ); ?>"><?php echo esc_html( $tiles[0] ); ?></a>
			</p>
		</section>
	<?php endif; ?>

	<?php if ( current_user_can( 'edit_pages' ) ) : ?>
		<section class="spk-task">
			<h3><?php esc_html_e( 'Page Text', 'spokares-core' ); ?></h3>
			<p class="spk-page-links">
				<?php
				$links = array();
				foreach ( $pages as $item ) {
					$edit = get_edit_post_link( $item['page']->ID );
					if ( $edit ) {
						$links[] = '<a href="' . esc_url( $edit ) . '">' . esc_html( get_the_title( $item['page'] ) ) . '</a>';
					}
				}
				echo wp_kses( implode( ' · ', $links ), $line_kses );
				?>
			</p>
		</section>
	<?php endif; ?>
		<p class="spk-stuck"><?php esc_html_e( 'Stuck?', 'spokares-core' ); ?> <a href="mailto:webmaster@spokares.org">webmaster@spokares.org</a></p>
	</div>
	<?php
}

/**
 * The Site tasks box is never drawn folded ("closed"): for an editor it is
 * the only box, and a folded one looked like an empty Dashboard.
 *
 * @param string[] $classes Box classes.
 */
function spokares_site_tasks_open( $classes ) {
	return is_array( $classes ) ? array_values( array_diff( $classes, array( 'closed' ) ) ) : $classes;
}
add_filter( 'postbox_classes_dashboard_spokares_site_tasks', 'spokares_site_tasks_open' );
