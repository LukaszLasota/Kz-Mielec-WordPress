<?php
/**
 * Uninstall script — runs when the plugin is deleted from wp-admin > Plugins.
 *
 * Cleans up all plugin data from the database:
 *   1. Deletes all comparison_topic posts (including revisions, permanently).
 *   2. Deletes all comparison_category taxonomy terms.
 *
 * Note: Transient cache entries (cor_accordion_*) are also stored in wp_options
 * but will expire naturally. They could be cleaned here too if needed.
 *
 * This file is NOT called on deactivation — only on full deletion.
 *
 * @package ComparisonOfReligions
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Uninstall scripts run in global scope — prefix variables per WordPress coding standards.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

// Delete all comparison_topic posts permanently (force_delete = true).
$comparison_posts = get_posts(
	[
		'post_type'      => 'comparison_topic',
		'posts_per_page' => -1,
		'post_status'    => 'any',
		'fields'         => 'ids',
	]
);
foreach ( $comparison_posts as $comparison_post_id ) {
	wp_delete_post( $comparison_post_id, true );
}

// Delete taxonomy terms.
$terms = get_terms(
	[
		'taxonomy'   => 'comparison_category',
		'hide_empty' => false,
		'fields'     => 'ids',
	]
);
if ( ! is_wp_error( $terms ) ) {
	foreach ( $terms as $term_id ) {
		wp_delete_term( $term_id, 'comparison_category' );
	}
}
