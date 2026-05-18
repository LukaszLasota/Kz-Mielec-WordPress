<?php
/**
 * Meta box and meta field registration for CPT meetings.
 *
 * @package CustomBlockPackage
 */

declare(strict_types=1);

namespace CustomBlockPackage\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MeetingMeta
 *
 * Registers post meta and admin meta box for the meetings CPT.
 */
class MeetingMeta {

	/**
	 * Meta key: hover image attachment ID.
	 */
	public const META_HOVER_IMAGE = '_meeting_hover_image';

	/**
	 * Meta key: day and hour text.
	 */
	public const META_DAY_HOUR = '_meeting_day_hour';

	/**
	 * Meta key: place text.
	 */
	public const META_PLACE = '_meeting_place';

	/**
	 * Meta key: anchor ID for cross-page links.
	 */
	public const META_ANCHOR = '_meeting_anchor';

	/**
	 * Nonce action name.
	 */
	private const NONCE_ACTION = 'cbp_meeting_meta_save';

	/**
	 * Nonce field name.
	 */
	private const NONCE_FIELD = 'cbp_meeting_meta_nonce';

	/**
	 * Constructor — register hooks.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_meta_fields' ) );
		add_action( 'add_meta_boxes_meetings', array( $this, 'add_meta_box' ) );
		add_action( 'save_post_meetings', array( $this, 'save_meta' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register meta fields with REST support.
	 *
	 * @return void
	 */
	public function register_meta_fields(): void {
		$common = array(
			'object_subtype' => 'meetings',
			'single'         => true,
			'show_in_rest'   => true,
			'auth_callback'  => static function (): bool {
				return current_user_can( 'edit_posts' );
			},
		);

		register_post_meta(
			'meetings',
			self::META_HOVER_IMAGE,
			array_merge(
				$common,
				array(
					'type'    => 'integer',
					'default' => 0,
				)
			)
		);
		register_post_meta(
			'meetings',
			self::META_DAY_HOUR,
			array_merge(
				$common,
				array(
					'type'    => 'string',
					'default' => '',
				)
			)
		);
		register_post_meta(
			'meetings',
			self::META_PLACE,
			array_merge(
				$common,
				array(
					'type'    => 'string',
					'default' => '',
				)
			)
		);
		register_post_meta(
			'meetings',
			self::META_ANCHOR,
			array_merge(
				$common,
				array(
					'type'    => 'string',
					'default' => '',
				)
			)
		);
	}

	/**
	 * Register meta box.
	 *
	 * @return void
	 */
	public function add_meta_box(): void {
		add_meta_box(
			'meeting_details',
			__( 'Szczegóły spotkania', 'custom-block-package' ),
			array( $this, 'render_meta_box' ),
			'meetings',
			'side',
			'high'
		);
	}

	/**
	 * Enqueue media library scripts on meetings edit screen.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'meetings' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_media();
	}

	/**
	 * Render meta box content.
	 *
	 * @param \WP_Post $post Current post.
	 * @return void
	 */
	public function render_meta_box( \WP_Post $post ): void {
		$hover_image_id = (int) get_post_meta( $post->ID, self::META_HOVER_IMAGE, true );
		$day_hour       = (string) get_post_meta( $post->ID, self::META_DAY_HOUR, true );
		$place          = (string) get_post_meta( $post->ID, self::META_PLACE, true );
		$anchor         = (string) get_post_meta( $post->ID, self::META_ANCHOR, true );

		$hover_image_url = $hover_image_id ? (string) wp_get_attachment_image_url( $hover_image_id, 'thumbnail' ) : '';

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );
		?>
		<p>
			<label for="cbp_meeting_day_hour"><strong><?php esc_html_e( 'Dzień i godzina', 'custom-block-package' ); ?></strong></label>
			<input type="text" id="cbp_meeting_day_hour" name="cbp_meeting_day_hour" value="<?php echo esc_attr( $day_hour ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'np. Niedziela 10:30', 'custom-block-package' ); ?>">
		</p>

		<p>
			<label for="cbp_meeting_place"><strong><?php esc_html_e( 'Miejsce', 'custom-block-package' ); ?></strong></label>
			<input type="text" id="cbp_meeting_place" name="cbp_meeting_place" value="<?php echo esc_attr( $place ); ?>" class="widefat" placeholder="ul. Dąbrowskiego 1a">
		</p>

		<p>
			<label for="cbp_meeting_anchor"><strong><?php esc_html_e( 'Anchor ID', 'custom-block-package' ); ?></strong></label>
			<input type="text" id="cbp_meeting_anchor" name="cbp_meeting_anchor" value="<?php echo esc_attr( $anchor ); ?>" class="widefat" placeholder="10">
			<span class="description"><?php esc_html_e( 'Liczba lub identyfikator do linku /zaplanuj-wizyte/#anchor', 'custom-block-package' ); ?></span>
		</p>

		<p>
			<label><strong><?php esc_html_e( 'Hover image', 'custom-block-package' ); ?></strong></label>
			<input type="hidden" id="cbp_meeting_hover_image" name="cbp_meeting_hover_image" value="<?php echo esc_attr( (string) $hover_image_id ); ?>">
			<span class="cbp-meeting-hover-preview" style="display:block;margin:6px 0;">
				<?php if ( $hover_image_url ) : ?>
					<img src="<?php echo esc_url( $hover_image_url ); ?>" style="max-width:120px;height:auto;" alt="">
				<?php endif; ?>
			</span>
			<button type="button" class="button cbp-meeting-hover-select"><?php esc_html_e( 'Wybierz obraz', 'custom-block-package' ); ?></button>
			<button type="button" class="button cbp-meeting-hover-remove"<?php echo $hover_image_id ? '' : ' style="display:none"'; ?>><?php esc_html_e( 'Usuń', 'custom-block-package' ); ?></button>
		</p>

		<script>
		(function ($) {
			$(function () {
				var frame;
				$('.cbp-meeting-hover-select').on('click', function (e) {
					e.preventDefault();
					if (frame) { frame.open(); return; }
					frame = wp.media({
						title: <?php echo wp_json_encode( __( 'Wybierz hover image', 'custom-block-package' ) ); ?>,
						button: { text: <?php echo wp_json_encode( __( 'Użyj', 'custom-block-package' ) ); ?> },
						multiple: false,
						library: { type: 'image' }
					});
					frame.on('select', function () {
						var attachment = frame.state().get('selection').first().toJSON();
						$('#cbp_meeting_hover_image').val(attachment.id);
						$('.cbp-meeting-hover-preview').html('<img src="' + attachment.url + '" style="max-width:120px;height:auto;" alt="">');
						$('.cbp-meeting-hover-remove').show();
					});
					frame.open();
				});
				$('.cbp-meeting-hover-remove').on('click', function (e) {
					e.preventDefault();
					$('#cbp_meeting_hover_image').val('0');
					$('.cbp-meeting-hover-preview').empty();
					$(this).hide();
				});
			});
		})(jQuery);
		</script>
		<?php
	}

	/**
	 * Save meta box data.
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

		$day_hour = isset( $_POST['cbp_meeting_day_hour'] ) ? sanitize_text_field( wp_unslash( $_POST['cbp_meeting_day_hour'] ) ) : '';
		$place    = isset( $_POST['cbp_meeting_place'] ) ? sanitize_text_field( wp_unslash( $_POST['cbp_meeting_place'] ) ) : '';
		$anchor   = isset( $_POST['cbp_meeting_anchor'] ) ? sanitize_html_class( wp_unslash( $_POST['cbp_meeting_anchor'] ) ) : '';
		$hover    = isset( $_POST['cbp_meeting_hover_image'] ) ? absint( $_POST['cbp_meeting_hover_image'] ) : 0;

		update_post_meta( $post_id, self::META_DAY_HOUR, $day_hour );
		update_post_meta( $post_id, self::META_PLACE, $place );
		update_post_meta( $post_id, self::META_ANCHOR, $anchor );
		update_post_meta( $post_id, self::META_HOVER_IMAGE, $hover );
	}
}
