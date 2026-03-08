<?php
/**
 * Shared helpers for ConjureWP example files.
 * Use WP_Query instead of deprecated get_page_by_title().
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'conjurewp_example_get_page_by_title' ) ) {
	/**
	 * Get a published page by title (replacement for deprecated get_page_by_title).
	 *
	 * @param string $title Page title.
	 * @return WP_Post|null Post object or null.
	 */
	function conjurewp_example_get_page_by_title( $title ) {
		$filter = function( $where, $query ) use ( $title ) {
			global $wpdb;
			$where .= $wpdb->prepare( " AND {$wpdb->posts}.post_title = %s", $title );
			return $where;
		};
		add_filter( 'posts_where', $filter, 10, 2 );
		$query = new WP_Query(
			array(
				'post_type'              => 'page',
				'post_status'            => 'publish',
				'posts_per_page'         => 1,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);
		remove_filter( 'posts_where', $filter, 10 );
		return $query->have_posts() ? $query->next_post() : null;
	}
}
