<?php
/**
 * Third-party feed styles inside the block editor canvas.
 *
 * @package Kzmielec
 */

declare(strict_types=1);

namespace Kzmielec\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Kzmielec\Interfaces\ActionHookInterface;

/**
 * Class EditorFeedStyles
 *
 * The Instagram feed block rendered nothing recognisable in the editor: its
 * inline "carousel" SVG filled the whole column in solid blue and the caption
 * appeared as a bare list of links. It is not the block's markup that is wrong
 * — it is unstyled.
 *
 * Smash Balloon loads its stylesheet two ways, and neither reaches the canvas.
 * `sbi_styles` is enqueued on the front-end hook, which the editor never fires,
 * and `sbi-blocks-styles` goes out through `enqueue_block_editor_assets` — a
 * hook that, since the editor became an iframe, lands OUTSIDE the canvas. That
 * second file is 45 bytes of `pointer-events: none` anyway.
 *
 * `enqueue_block_assets` is the hook that runs inside the iframe, so that is
 * where the real stylesheet has to be added. Our own blocks do not need this:
 * a `style` declared in `block.json` is put in the canvas by WordPress itself.
 *
 * Everything here is guarded on the plugin being installed, because the theme
 * has to work without it.
 */
class EditorFeedStyles implements ActionHookInterface {

	/**
	 * Handle for the copy loaded into the canvas.
	 *
	 * Deliberately not `sbi_styles`: that handle belongs to the plugin, and
	 * reusing it would make our enqueue silently do nothing the day the plugin
	 * starts registering it in admin too.
	 */
	private const HANDLE = 'kzmielec-editor-instagram';

	/**
	 * Stylesheet, relative to the plugin directory.
	 *
	 * The non-legacy file on purpose: it is what the plugin itself picks when
	 * `is_admin()` is true.
	 */
	private const RELATIVE_PATH = 'css/sbi-styles.min.css';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register_add_action();
	}

	/**
	 * Register WordPress action hooks.
	 *
	 * @return void
	 */
	public function register_add_action(): void {
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_canvas_styles' ) );
	}

	/**
	 * Put the Instagram feed's stylesheet into the editor canvas.
	 *
	 * @return void
	 */
	public function enqueue_canvas_styles(): void {
		// The front end already gets this from the plugin; this hook fires there too.
		if ( ! is_admin() ) {
			return;
		}

		if ( ! defined( 'SBI_PLUGIN_DIR' ) || ! defined( 'SBI_PLUGIN_URL' ) ) {
			return;
		}

		$path = trailingslashit( (string) constant( 'SBI_PLUGIN_DIR' ) ) . self::RELATIVE_PATH;

		if ( ! file_exists( $path ) ) {
			return;
		}

		wp_enqueue_style(
			self::HANDLE,
			trailingslashit( (string) constant( 'SBI_PLUGIN_URL' ) ) . self::RELATIVE_PATH,
			array(),
			(string) filemtime( $path )
		);
	}
}
