<?php
/**
 * BeliefMetaBox class
 *
 * Registers meta box for belief overlay icon on pages.
 *
 * @package Kzmielec\Admin
 */

namespace Kzmielec\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Kzmielec\Interfaces\ActionHookInterface;

/**
 * Class BeliefMetaBox
 *
 * Adds a meta box to pages for selecting an overlay icon
 * displayed on belief circle navigation items.
 */
class BeliefMetaBox implements ActionHookInterface {

	/**
	 * Meta key for the overlay icon attachment ID.
	 */
	public const META_KEY = 'belief_overlay_icon';

	/**
	 * Nonce action name.
	 */
	private const NONCE_ACTION = 'save_belief_overlay_icon';

	/**
	 * Nonce field name.
	 */
	private const NONCE_NAME = 'belief_overlay_nonce';

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
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post_page', array( $this, 'save_meta_box' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Add meta box to page editor.
	 *
	 * @return void
	 */
	public function add_meta_box(): void {
		add_meta_box(
			'belief_overlay_icon_meta',
			__( 'Ikona nakładki (strona wiary)', 'kzmielec' ),
			array( $this, 'render_meta_box' ),
			'page',
			'side',
			'default'
		);
	}

	/**
	 * Render meta box content.
	 *
	 * @param \WP_Post $post Current post object.
	 * @return void
	 */
	public function render_meta_box( \WP_Post $post ): void {
		$icon_id  = get_post_meta( $post->ID, self::META_KEY, true );
		$icon_url = $icon_id ? wp_get_attachment_image_url( (int) $icon_id, 'thumbnail' ) : '';

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		?>
		<p>
			<img id="belief-icon-preview"
				src="<?php echo esc_url( (string) $icon_url ); ?>"
				style="max-width: 100%; display: <?php echo $icon_url ? 'block' : 'none'; ?>; margin-bottom: 10px;"
				alt=""
			/>
			<input type="hidden"
					id="belief_overlay_icon"
					name="belief_overlay_icon"
					value="<?php echo esc_attr( (string) $icon_id ); ?>"
			/>
		</p>
		<p>
			<button type="button" id="belief-icon-upload" class="button">
				<?php esc_html_e( 'Wybierz ikone', 'kzmielec' ); ?>
			</button>
			<button type="button" id="belief-icon-remove" class="button" style="<?php echo $icon_url ? '' : 'display:none;'; ?>">
				<?php esc_html_e( 'Usun', 'kzmielec' ); ?>
			</button>
		</p>
		<?php
	}

	/**
	 * Save meta box data.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function save_meta_box( int $post_id ): void {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		}

		$icon_id = isset( $_POST[ self::META_KEY ] ) ? absint( $_POST[ self::META_KEY ] ) : 0;

		if ( $icon_id ) {
			update_post_meta( $post_id, self::META_KEY, $icon_id );
		} else {
			delete_post_meta( $post_id, self::META_KEY );
		}
	}

	/**
	 * Enqueue media uploader scripts on page editor.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_scripts( string $hook ): void {
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'page' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_media();

		wp_localize_script(
			'media-upload',
			'kzmielecBeliefMeta',
			array(
				'selectTitle' => __( 'Wybierz ikonę nakładki', 'kzmielec' ),
				'useImage'    => __( 'Użyj tego obrazu', 'kzmielec' ),
			)
		);

		wp_add_inline_script(
			'media-upload',
			"
			document.addEventListener('DOMContentLoaded', function() {
				var frame;
				var uploadBtn = document.getElementById('belief-icon-upload');
				var removeBtn = document.getElementById('belief-icon-remove');
				var input     = document.getElementById('belief_overlay_icon');
				var preview   = document.getElementById('belief-icon-preview');

				if (!uploadBtn) return;

				uploadBtn.addEventListener('click', function(e) {
					e.preventDefault();
					if (frame) { frame.open(); return; }
					frame = wp.media({
						title: kzmielecBeliefMeta.selectTitle,
						button: { text: kzmielecBeliefMeta.useImage },
						multiple: false
					});
					frame.on('select', function() {
						var attachment = frame.state().get('selection').first().toJSON();
						input.value = attachment.id;
						preview.src = attachment.url;
						preview.style.display = 'block';
						removeBtn.style.display = '';
					});
					frame.open();
				});

				removeBtn.addEventListener('click', function(e) {
					e.preventDefault();
					input.value = '';
					preview.style.display = 'none';
					this.style.display = 'none';
				});
			});
			"
		);
	}
}
