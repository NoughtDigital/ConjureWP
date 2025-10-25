<?php
/**
 * WP-CLI command to fix taxonomy term and comment counts
 * 
 * Usage:
 *   wp eval-file cli-fix-counts.php
 *   or
 *   wp eval-file cli-fix-counts.php check
 *   wp eval-file cli-fix-counts.php fix
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	die( 'This script can only be run via WP-CLI' );
}

$action = isset( $args[0] ) ? $args[0] : 'check';

/**
 * Check if counts are correct
 */
function cli_check_counts() {
	global $wpdb;
	
	WP_CLI::line( '' );
	WP_CLI::line( WP_CLI::colorize( '%G=== CHECKING TAXONOMY TERM COUNTS ===%n' ) );
	WP_CLI::line( '' );
	
	$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
	$term_issues = 0;
	
	foreach ( $taxonomies as $taxonomy ) {
		WP_CLI::line( "Checking: {$taxonomy->label} ({$taxonomy->name})" );
		
		$terms = get_terms( array(
			'taxonomy' => $taxonomy->name,
			'hide_empty' => false,
		) );
		
		if ( is_wp_error( $terms ) ) {
			WP_CLI::warning( "Could not retrieve terms for {$taxonomy->name}" );
			continue;
		}
		
		foreach ( $terms as $term ) {
			$actual = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->term_relationships} WHERE term_taxonomy_id = %d",
				$term->term_taxonomy_id
			) );
			
			if ( $actual != $term->count ) {
				WP_CLI::warning( "  {$term->name}: stored={$term->count}, actual={$actual}" );
				$term_issues++;
			}
		}
	}
	
	if ( $term_issues === 0 ) {
		WP_CLI::success( 'All term counts are correct!' );
	} else {
		WP_CLI::warning( "Found {$term_issues} term(s) with incorrect counts" );
	}
	
	WP_CLI::line( '' );
	WP_CLI::line( WP_CLI::colorize( '%G=== CHECKING COMMENT COUNTS ===%n' ) );
	WP_CLI::line( '' );
	
	$comment_issues = $wpdb->get_var(
		"SELECT COUNT(*)
		FROM {$wpdb->posts} p
		WHERE p.comment_count != (
			SELECT COUNT(*) 
			FROM {$wpdb->comments} 
			WHERE comment_post_ID = p.ID 
			AND comment_approved = '1'
		)"
	);
	
	if ( $comment_issues === 0 ) {
		WP_CLI::success( 'All comment counts are correct!' );
	} else {
		WP_CLI::warning( "Found {$comment_issues} post(s) with incorrect comment counts" );
	}
	
	WP_CLI::line( '' );
	
	return ( $term_issues + $comment_issues ) > 0;
}

/**
 * Fix counts
 */
function cli_fix_counts() {
	global $wpdb;
	
	WP_CLI::line( '' );
	WP_CLI::line( WP_CLI::colorize( '%G=== FIXING TAXONOMY TERM COUNTS ===%n' ) );
	WP_CLI::line( '' );
	
	$taxonomies = get_taxonomies( array(), 'names' );
	$total_terms = 0;
	
	foreach ( $taxonomies as $taxonomy ) {
		$terms = $wpdb->get_col( $wpdb->prepare(
			"SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE taxonomy = %s",
			$taxonomy
		) );
		
		if ( ! empty( $terms ) ) {
			wp_update_term_count_now( $terms, $taxonomy );
			$total_terms += count( $terms );
			WP_CLI::line( "✓ {$taxonomy}: " . count( $terms ) . " terms" );
		}
	}
	
	WP_CLI::success( "Updated counts for {$total_terms} terms across " . count( $taxonomies ) . " taxonomies" );
	
	WP_CLI::line( '' );
	WP_CLI::line( WP_CLI::colorize( '%G=== FIXING COMMENT COUNTS ===%n' ) );
	WP_CLI::line( '' );
	
	$post_ids = $wpdb->get_col(
		"SELECT DISTINCT post_id FROM {$wpdb->comments} WHERE comment_approved = '1'"
	);
	
	if ( ! empty( $post_ids ) ) {
		$progress = \WP_CLI\Utils\make_progress_bar( 'Updating comment counts', count( $post_ids ) );
		
		foreach ( $post_ids as $post_id ) {
			wp_update_comment_count_now( $post_id );
			$progress->tick();
		}
		
		$progress->finish();
		WP_CLI::success( "Updated comment counts for " . count( $post_ids ) . " posts" );
	} else {
		WP_CLI::line( 'No posts with comments found' );
	}
	
	WP_CLI::line( '' );
}

// Run the requested action
if ( $action === 'fix' ) {
	cli_fix_counts();
	WP_CLI::success( 'All counts have been fixed!' );
} else {
	$has_issues = cli_check_counts();
	
	if ( $has_issues ) {
		WP_CLI::line( WP_CLI::colorize( '%YTo fix these issues, run:%n' ) );
		WP_CLI::line( WP_CLI::colorize( '%Bwp eval-file ' . basename( __FILE__ ) . ' fix%n' ) );
		WP_CLI::line( '' );
	}
}

