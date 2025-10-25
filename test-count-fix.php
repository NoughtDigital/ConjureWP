<?php
/**
 * Test script to verify taxonomy term and comment counts after import
 * 
 * Usage: Place this file in the plugin directory and run it via WP-CLI or browser
 * WP-CLI: wp eval-file test-count-fix.php
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) && ! defined( 'WP_CLI' ) ) {
	exit;
}

/**
 * Check and display taxonomy term counts
 */
function conjure_check_term_counts() {
	echo "\n=== CHECKING TAXONOMY TERM COUNTS ===\n\n";
	
	$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
	$issues_found = false;
	
	foreach ( $taxonomies as $taxonomy ) {
		echo "Checking taxonomy: {$taxonomy->label} ({$taxonomy->name})\n";
		
		$terms = get_terms( array(
			'taxonomy' => $taxonomy->name,
			'hide_empty' => false,
		) );
		
		if ( is_wp_error( $terms ) ) {
			echo "  ERROR: Could not retrieve terms\n";
			continue;
		}
		
		foreach ( $terms as $term ) {
			// Get actual post count
			$posts = get_posts( array(
				'post_type' => 'any',
				'post_status' => 'publish',
				'tax_query' => array(
					array(
						'taxonomy' => $taxonomy->name,
						'field' => 'term_id',
						'terms' => $term->term_id,
					),
				),
				'fields' => 'ids',
				'posts_per_page' => -1,
			) );
			
			$actual_count = count( $posts );
			$stored_count = $term->count;
			
			if ( $actual_count != $stored_count ) {
				echo "  [ISSUE] Term: '{$term->name}' (ID: {$term->term_id})\n";
				echo "    Stored count: {$stored_count}\n";
				echo "    Actual count: {$actual_count}\n";
				$issues_found = true;
			} else {
				echo "  [OK] Term: '{$term->name}' - Count: {$stored_count}\n";
			}
		}
		echo "\n";
	}
	
	if ( ! $issues_found ) {
		echo "✓ All taxonomy term counts are correct!\n\n";
	} else {
		echo "✗ Issues found with term counts. Running recount...\n\n";
		conjure_fix_term_counts();
	}
}

/**
 * Fix taxonomy term counts
 */
function conjure_fix_term_counts() {
	global $wpdb;
	
	echo "=== FIXING TAXONOMY TERM COUNTS ===\n\n";
	
	$taxonomies = get_taxonomies( array(), 'names' );
	
	foreach ( $taxonomies as $taxonomy ) {
		// Get all terms for this taxonomy
		$terms = $wpdb->get_col( $wpdb->prepare(
			"SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE taxonomy = %s",
			$taxonomy
		) );
		
		if ( ! empty( $terms ) ) {
			wp_update_term_count_now( $terms, $taxonomy );
			echo "✓ Updated term count for taxonomy: {$taxonomy} (" . count( $terms ) . " terms)\n";
		}
	}
	
	echo "\n";
}

/**
 * Check and display comment counts
 */
function conjure_check_comment_counts() {
	global $wpdb;
	
	echo "=== CHECKING COMMENT COUNTS ===\n\n";
	
	// Get posts with comments
	$posts_with_comments = $wpdb->get_results(
		"SELECT p.ID, p.post_title, p.comment_count, COUNT(c.comment_ID) as actual_count
		FROM {$wpdb->posts} p
		LEFT JOIN {$wpdb->comments} c ON p.ID = c.comment_post_ID AND c.comment_approved = '1'
		WHERE p.post_status = 'publish'
		GROUP BY p.ID
		HAVING p.comment_count != COUNT(c.comment_ID) OR COUNT(c.comment_ID) > 0
		LIMIT 20"
	);
	
	$issues_found = false;
	
	if ( empty( $posts_with_comments ) ) {
		echo "✓ All comment counts are correct!\n\n";
		return;
	}
	
	foreach ( $posts_with_comments as $post ) {
		if ( $post->comment_count != $post->actual_count ) {
			echo "[ISSUE] Post: '{$post->post_title}' (ID: {$post->ID})\n";
			echo "  Stored count: {$post->comment_count}\n";
			echo "  Actual count: {$post->actual_count}\n";
			$issues_found = true;
		} else {
			echo "[OK] Post: '{$post->post_title}' - Count: {$post->comment_count}\n";
		}
	}
	
	if ( $issues_found ) {
		echo "\n✗ Issues found with comment counts. Running recount...\n\n";
		conjure_fix_comment_counts();
	}
}

/**
 * Fix comment counts
 */
function conjure_fix_comment_counts() {
	global $wpdb;
	
	echo "=== FIXING COMMENT COUNTS ===\n\n";
	
	// Get all post IDs that have comments
	$post_ids = $wpdb->get_col(
		"SELECT DISTINCT post_id FROM {$wpdb->comments} WHERE comment_approved = '1'"
	);
	
	if ( ! empty( $post_ids ) ) {
		$count = 0;
		foreach ( $post_ids as $post_id ) {
			wp_update_comment_count_now( $post_id );
			$count++;
		}
		
		echo "✓ Updated comment counts for {$count} posts\n\n";
	} else {
		echo "No posts with comments found\n\n";
	}
}

/**
 * Display summary statistics
 */
function conjure_display_summary() {
	global $wpdb;
	
	echo "\n=== IMPORT STATISTICS ===\n\n";
	
	// Count taxonomies and terms
	$taxonomies = get_taxonomies( array( 'public' => true ), 'names' );
	echo "Public Taxonomies: " . count( $taxonomies ) . "\n";
	
	foreach ( $taxonomies as $taxonomy ) {
		$term_count = wp_count_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) );
		echo "  - {$taxonomy}: {$term_count} terms\n";
	}
	
	// Count comments
	$comment_count = wp_count_comments();
	echo "\nComments:\n";
	echo "  - Approved: {$comment_count->approved}\n";
	echo "  - Pending: {$comment_count->moderated}\n";
	echo "  - Spam: {$comment_count->spam}\n";
	echo "  - Trash: {$comment_count->trash}\n";
	echo "  - Total: {$comment_count->total_comments}\n";
	
	// Count posts
	$post_types = get_post_types( array( 'public' => true ), 'names' );
	echo "\nPosts:\n";
	foreach ( $post_types as $post_type ) {
		$count = wp_count_posts( $post_type );
		echo "  - {$post_type}: {$count->publish} published\n";
	}
	
	echo "\n";
}

// Run the checks
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	conjure_display_summary();
	conjure_check_term_counts();
	conjure_check_comment_counts();
	
	WP_CLI::success( 'Count verification complete!' );
} else {
	// Running in browser (only for admins)
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Unauthorized' );
	}
	
	echo '<pre>';
	conjure_display_summary();
	conjure_check_term_counts();
	conjure_check_comment_counts();
	echo '</pre>';
}

