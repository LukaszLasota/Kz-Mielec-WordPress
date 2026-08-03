<?php
/**
 * Restores the option groups the database import overwrote.
 *
 * Writes `option_value` as raw bytes for the same reason the exporter reads it
 * that way: the values are serialized PHP and must survive unchanged. Rows are
 * deleted and re-inserted rather than updated, because the imported table may
 * hold a different `option_id` for the same name.
 *
 * Run with: wp eval-file scripts/deploy/restore-plugin-options.php <input-path>
 *
 * @package Kzmielec\Deploy
 */

$source = $args[0] ?? '';
if ( '' === $source || ! is_readable( $source ) ) {
	WP_CLI::error( 'Pass a readable input path as the first argument.' );
}

$data = json_decode( (string) file_get_contents( $source ), true );
if ( ! is_array( $data ) ) {
	WP_CLI::error( 'Input is not a JSON object.' );
}

global $wpdb;
$restored = 0;
foreach ( $data as $name => $entry ) {
	if ( ! is_array( $entry ) || ! array_key_exists( 'value', $entry ) ) {
		WP_CLI::warning( 'Skipping malformed entry: ' . $name );
		continue;
	}
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s", $name ) );
	$wpdb->insert(
		$wpdb->options,
		array(
			'option_name'  => $name,
			'option_value' => $entry['value'],
			'autoload'     => $entry['autoload'] ?? 'yes',
		)
	);
	++$restored;
}

wp_cache_flush();
WP_CLI::success( $restored . ' options restored.' );
