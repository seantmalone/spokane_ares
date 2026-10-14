<?php
/**
 * Regression tests for the workflow review's finding D3 (ux/workflows, "The
 * Dashboard and the list count reviews differently").
 *
 * The Dashboard counted published, privacy-checked documents not reviewed in
 * 365 days and linked to the list sorted by Reviewed, which wasn't filtered
 * and coloured drafts red too. Now one function
 * (spokares_documents_needing_review(), with the cadence in
 * spokares_document_review_days()) feeds the Dashboard's number, a "Needs
 * review" list view and the list's red dates, and the Dashboard links to that
 * view.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * Four documents: published and due, published and reviewed lately, a draft
 * that is due, and published but not privacy-checked. Returns their IDs.
 */
function wf_d3_docs(): array {
	$due   = uxdoc_published( 'WF-D3 due', 'link' );
	$fresh = uxdoc_published( 'WF-D3 fresh', 'link' );
	update_post_meta( $due, 'spk_reviewed', spokares_add_days( spokares_today(), -( \spokares_document_review_days() + 1 ) ) );
	update_post_meta( $fresh, 'spk_reviewed', spokares_add_days( spokares_today(), -( \spokares_document_review_days() - 1 ) ) );
	$draft = uxdoc_published( 'WF-D3 draft', 'link' );
	wp_update_post(
		array(
			'ID'          => $draft,
			'post_status' => 'draft',
		)
	);
	$unchecked = uxdoc_published( 'WF-D3 unchecked', 'link' );
	delete_post_meta( $unchecked, 'spk_privacy_ok' );
	return array( $due, $fresh, $draft, $unchecked );
}

/**
 * The IDs the Documents list's main query returns for these query args.
 *
 * @param array $get $_GET.
 */
function wf_d3_list( array $get ): array {
	$r = call_request(
		'GET',
		$get + array( 'post_type' => 'spk_document' ),
		array(),
		static function () use ( $get ) {
			global $wp_the_query;
			$saved        = $wp_the_query;
			$q            = new \WP_Query();
			$wp_the_query = $q; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- is_main_query() for the list's pre_get_posts; put back below.
			try {
				return $q->query(
					$get + array(
						'post_type'      => 'spk_document',
						'post_status'    => 'any',
						'posts_per_page' => 200, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- the list shows every document on one page.
						'fields'         => 'ids',
					)
				);
			} finally {
				$wp_the_query = $saved; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restored.
			}
		}
	);
	return array_map( 'intval', (array) $r['returned'] );
}

test(
	'the review cadence is one number, used by the due check',
	function () {
		assert_same( 365, \spokares_document_review_days(), 'reviews are yearly' );
		assert_true( \spokares_document_review_due( '' ), 'never reviewed is due' );
		assert_true( \spokares_document_review_due( spokares_add_days( spokares_today(), -366 ) ), '366 days ago is due' );
		assert_false( \spokares_document_review_due( spokares_add_days( spokares_today(), -365 ) ), '365 days ago is not due yet' );
		assert_false( \spokares_document_review_due( spokares_today() ), 'today is not due' );
	}
);

test(
	'Needs review lists exactly the documents the Dashboard counts, oldest first',
	function () {
		list( $due, $fresh, $draft, $unchecked ) = wf_d3_docs();
		as_role( 'ares-editor' );
		$ids = \spokares_documents_needing_review();
		assert_contains( $due, $ids, 'a published, checked document not reviewed in a year' );
		assert_false( in_array( $fresh, $ids, true ), 'reviewed within the year: not counted' );
		assert_false( in_array( $draft, $ids, true ), 'a draft: not counted' );
		assert_false( in_array( $unchecked, $ids, true ), 'not privacy-checked (not on the site): not counted' );

		$listed = wf_d3_list( array( 'spk_review' => '1' ) );
		sort( $listed );
		$want = $ids;
		sort( $want );
		assert_same( $want, $listed, 'the view lists exactly the counted documents' );
		$sorted = wf_d3_list( array( 'spk_review' => '1' ) );
		$dates  = array_map( static fn( $id ) => (string) get_post_meta( $id, 'spk_reviewed', true ), $sorted );
		$asc    = $dates;
		sort( $asc );
		assert_same( $asc, $dates, 'oldest review first (never reviewed at the top)' );
	}
);

test(
	'the views offer "Needs review (n)" with the Dashboard\'s number, and the Dashboard links to it',
	function () {
		wf_d3_docs();
		as_role( 'ares-editor' );
		$n = count( \spokares_documents_needing_review() );
		$r = call_request( 'GET', array( 'post_type' => 'spk_document' ), array(), static fn() => \spokares_document_views( array( 'all' => '<a href="#" class="current" aria-current="page">All</a>' ) ) );
		assert_true( isset( $r['returned']['spk_review'] ), 'the view is offered' );
		assert_contains( 'spk_review=1', $r['returned']['spk_review'], 'its link' );
		assert_contains( 'Needs review <span class="count">(' . $n . ')</span>', $r['returned']['spk_review'], 'its count' );

		$r = call_request(
			'GET',
			array(
				'post_type'  => 'spk_document',
				'spk_review' => '1',
			),
			array(),
			static fn() => \spokares_document_views( array( 'all' => '<a href="#" class="current" aria-current="page">All</a>' ) )
		);
		assert_contains( 'class="current"', $r['returned']['spk_review'], 'the view is current when open' );
		assert_not_contains( 'current', $r['returned']['all'], 'All is not' );

		ob_start();
		\spokares_dashboard_widget();
		$html = (string) ob_get_clean();
		assert_contains( esc_url( admin_url( 'edit.php?post_type=spk_document&spk_review=1' ) ) . '">' . $n . ' not reviewed in 12 months</a>', $html, 'the Dashboard line links to the view with the same number' );
	}
);

test(
	'the list colours a Reviewed date red only for a document the Dashboard counts',
	function () {
		list( $due, $fresh, $draft ) = wf_d3_docs();
		as_role( 'ares-editor' );
		$cell = static function ( int $id ): string {
			ob_start();
			\spokares_document_column( 'spk_reviewed', $id );
			return (string) ob_get_clean();
		};
		assert_contains( 'class="spk-flag"', $cell( $due ), 'due: red' );
		assert_not_contains( 'spk-flag', $cell( $fresh ), 'reviewed lately: not red' );
		assert_not_contains( 'spk-flag', $cell( $draft ), 'a draft is not on the Dashboard: not red' );
	}
);
