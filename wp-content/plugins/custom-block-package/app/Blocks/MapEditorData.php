<?php
/**
 * Map Editor Data
 *
 * Tells the map block's editor script where the contact settings point.
 *
 * @package CustomBlockPackage
 */

declare(strict_types=1);

namespace CustomBlockPackage\Blocks;

use CustomBlockPackage\Services\MapLocation;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MapEditorData
 *
 * The editor preview used to read block.json's placeholder pair, so after the
 * address moved the sidebar and the preview kept showing the old spot while the
 * published page showed the new one. The editor now gets the coordinates from
 * the settings and a link to the screen that changes them.
 */
class MapEditorData {

	/**
	 * Admin page of the theme's contact settings screen.
	 */
	private const SETTINGS_PAGE = 'kzmielec-contact';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'enqueue_block_editor_assets', array( $this, 'print_data' ) );
	}

	/**
	 * Attach `window.cbpMapContact` before the map block's editor script.
	 *
	 * @return void
	 */
	public function print_data(): void {
		$handle = generate_block_asset_handle( 'custom-block-package/map-block', 'editorScript' );
		if ( ! wp_script_is( $handle, 'registered' ) ) {
			return;
		}

		$contact = MapLocation::from_contact();

		$data = array(
			'lat'         => null === $contact ? null : $contact['lat'],
			'lng'         => null === $contact ? null : $contact['lng'],
			'settingsUrl' => null === $contact || ! current_user_can( 'manage_options' )
				? ''
				: admin_url( 'admin.php?page=' . self::SETTINGS_PAGE ),
		);

		wp_add_inline_script( $handle, 'window.cbpMapContact = ' . wp_json_encode( $data ) . ';', 'before' );
	}
}
