<?php
/**
 * Exports the option groups a wholesale database import would destroy.
 *
 * Values are read straight out of `option_value` and written as raw strings, so
 * serialized PHP survives the round trip byte for byte — which passing them
 * through `wp option get` on a command line does not.
 *
 * Run with: wp eval-file scripts/deploy/export-plugin-options.php <output-path>
 *
 * @package Kzmielec\Deploy
 */

$target = $args[0] ?? '';
if ( '' === $target ) {
	WP_CLI::error( 'Pass the output path as the first argument.' );
}

$prefixes = array( 'litespeed', 'wpseo', 'sb_instagram', 'wordfence' );

global $wpdb;
$out = array();
foreach ( $prefixes as $prefix ) {
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT option_name, option_value, autoload FROM {$wpdb->options} WHERE option_name LIKE %s",
			$wpdb->esc_like( $prefix ) . '%'
		),
		ARRAY_A
	);
	foreach ( $rows as $row ) {
		$out[ $row['option_name'] ] = array(
			'value'    => $row['option_value'],
			'autoload' => $row['autoload'],
		);
	}
}

$json = wp_json_encode( $out );
if ( false === $json ) {
	WP_CLI::error( 'Could not encode the options as JSON.' );
}
if ( false === file_put_contents( $target, $json ) ) {
	WP_CLI::error( 'Could not write ' . $target );
}

WP_CLI::success( count( $out ) . ' options exported to ' . $target );
