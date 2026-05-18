<?php
/**
 * Meta box and meta field for belief subpages.
 *
 * @package Kzmielec
 */

declare(strict_types=1);

namespace Kzmielec\Admin;

use Kzmielec\Interfaces\ActionHookInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BeliefPageMeta
 *
 * Adds hover image meta field to pages using "page-belief.php" template.
 */
class BeliefPageMeta implements ActionHookInterface {

	/**
	 * Meta key for hover image attachment ID.
	 */
	public const META_HOVER_IMAGE = '_belief_hover_image';

	/**
	 * Template filename for belief pages.
	 */
	private const TEMPLATE_FILE = 'page-belief.php';

	/**
	 * Nonce action name.
	 */
	private const NONCE_ACTION = 'kzmielec_belief_meta_save';

	/**
	 * Nonce field name.
	 */
	private const NONCE_FIELD = 'kzmielec_belief_meta_nonce';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register_add_action();
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register_add_action(): void {
		add_action( 'init', array( $this, 'register_meta_field' ) );
		add_action( 'add_meta_boxes_page', array( $this, 'add_meta_box' ) );
		add_action( 'save_post_page', array( $this, 'save_meta' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register hover image meta with REST support.
	 *
	 * @return void
	 */
	public function register_meta_field(): void {
		register_post_meta(
			'page',
			self::META_HOVER_IMAGE,
			array(
				'object_subtype' => 'page',
				'single'         => true,
				'type'           => 'integer',
				'default'        => 0,
				'show_in_rest'   => true,
				'auth_callback'  => static function (): bool {
					return current_user_can( 'edit_pages' );
				},
			)
		);
	}

	/**
	 * Register meta box only for pages using the belief template.
	 *
	 * @param \WP_Post $post Current post.
	 * @return void
	 */
	public function add_meta_box( \WP_Post $post ): void {
		$template = get_page_template_slug( $post->ID );
		if ( self::TEMPLATE_FILE !== $template ) {
			return;
		}

		add_meta_box(
			'belief_page_meta',
			__( 'Strona wiary', 'kzmielec' ),
			array( $this, 'render_meta_box' ),
			'page',
			'side',
			'default'
		);
	}

	/**
	 * Enqueue media library on page edit screens.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}
		wp_enqueue_media();
	}

	/**
	 * Render meta box.
	 *
	 * @param \WP_Post $post Current post.
	 * @return void
	 */
	public function render_meta_box( \WP_Post $post ): void {
		$hover_image_id  = (int) get_post_meta( $post->ID, self::META_HOVER_IMAGE, true );
		$hover_image_url = $hover_image_id ? (string) wp_get_attachment_image_url( $hover_image_id, 'thumbnail' ) : '';

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );
		?>
		<p>
			<label><strong><?php esc_html_e( 'Hover image', 'kzmielec' ); ?></strong></label>
			<input type="hidden" id="kzmielec_belief_hover_image" name="kzmielec_belief_hover_image" value="<?php echo esc_attr( (string) $hover_image_id ); ?>">
			<span class="kzmielec-belief-hover-preview" style="display:block;margin:6px 0;">
				<?php if ( $hover_image_url ) : ?>
					<img src="<?php echo esc_url( $hover_image_url ); ?>" style="max-width:120px;height:auto;" alt="">
				<?php endif; ?>
			</span>
			<button type="button" class="button kzmielec-belief-hover-select"><?php esc_html_e( 'Wybierz obraz', 'kzmielec' ); ?></button>
			<button type="button" class="button kzmielec-belief-hover-remove"<?php echo $hover_image_id ? '' : ' style="display:none"'; ?>><?php esc_html_e( 'Usuń', 'kzmielec' ); ?></button>
			<span class="description" style="display:block;margin-top:6px;">
				<?php esc_html_e( 'Drugi obraz wyświetlany przy najechaniu myszką na kafelek w nawigacji wiary.', 'kzmielec' ); ?>
			</span>
		</p>

		<script>
		(function ($) {
			$(function () {
				var frame;
				$('.kzmielec-belief-hover-select').on('click', function (e) {
					e.preventDefault();
					if (frame) { frame.open(); return; }
					frame = wp.media({
						title: <?php echo wp_json_encode( __( 'Wybierz hover image', 'kzmielec' ) ); ?>,
						button: { text: <?php echo wp_json_encode( __( 'Użyj', 'kzmielec' ) ); ?> },
						multiple: false,
						library: { type: 'image' }
					});
					frame.on('select', function () {
						var a = frame.state().get('selection').first().toJSON();
						$('#kzmielec_belief_hover_image').val(a.id);
						$('.kzmielec-belief-hover-preview').html('<img src="' + a.url + '" style="max-width:120px;height:auto;" alt="">');
						$('.kzmielec-belief-hover-remove').show();
					});
					frame.open();
				});
				$('.kzmielec-belief-hover-remove').on('click', function (e) {
					e.preventDefault();
					$('#kzmielec_belief_hover_image').val('0');
					$('.kzmielec-belief-hover-preview').empty();
					$(this).hide();
				});
			});
		})(jQuery);
		</script>
		<?php
	}

	/**
	 * Save meta on page save.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function save_meta( int $post_id ): void {
		if ( ! isset( $_POST[ self::NONCE_FIELD ] ) ) {
			return;
		}
		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) );
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$hover = isset( $_POST['kzmielec_belief_hover_image'] ) ? absint( $_POST['kzmielec_belief_hover_image'] ) : 0;
		update_post_meta( $post_id, self::META_HOVER_IMAGE, $hover );
	}
}
