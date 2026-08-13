<?php
/**
 * Meta box and meta field registration for CPT meetings.
 *
 * @package CustomBlockPackage
 */

declare(strict_types=1);

namespace CustomBlockPackage\Admin;

use CustomBlockPackage\Services\MeetingSchedule;

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
	 *
	 * Derived, not authored. Regenerated from the weekday/time pair held by
	 * MeetingSchedule every time a meeting is saved. It stays in the database
	 * because the theme indexes it for site search, so a visitor searching
	 * "niedziela" still finds the Sunday service.
	 */
	public const META_DAY_HOUR = '_meeting_day_hour';

	/**
	 * Meta key: place text.
	 */
	public const META_PLACE = '_meeting_place';

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
		// Priority 1 on the generic hook registers this box before Yoast SEO
		// (which uses add_meta_boxes), so it renders above the Yoast box.
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ), 1 );
		add_action( 'save_post_meetings', array( $this, 'save_meta' ), 10, 1 );

		/*
		 * Deliberately a second hook, and deliberately not inside save_meta():
		 * the derived label has to be rebuilt after a programmatic save too —
		 * the migration plugin creates the three translations with
		 * wp_insert_post() and sends no nonce, so the guarded save path returns
		 * early and would leave them with no searchable day and hour at all.
		 */
		add_action( 'save_post_meetings', array( $this, 'refresh_derived_label' ), 20, 1 );
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
			MeetingSchedule::META_WEEKDAY,
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
			MeetingSchedule::META_TIME,
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
	 * Rebuild the derived, searchable day-and-hour label after any save.
	 *
	 * @param int $post_id Post being saved.
	 * @return void
	 */
	public function refresh_derived_label( int $post_id ): void {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		MeetingSchedule::refresh_index( $post_id );
	}

	/**
	 * Register meta box.
	 *
	 * @param string $post_type Current post type.
	 * @return void
	 */
	public function add_meta_box( string $post_type ): void {
		if ( 'meetings' !== $post_type ) {
			return;
		}

		add_meta_box(
			'meeting_details',
			__( 'Szczegóły spotkania', 'custom-block-package' ),
			array( $this, 'render_meta_box' ),
			'meetings',
			'normal',
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

		/*
		 * The pair is edited on the Polish post and read from there by every
		 * translation. Showing live fields on a translation would invite an
		 * editor to change a Sunday that is not theirs to change, so the
		 * translations get the same controls disabled, with a link to the post
		 * that owns them.
		 */
		$source_id  = MeetingSchedule::source_post_id( $post->ID );
		$is_source  = $source_id === $post->ID;
		$source_url = $is_source ? '' : (string) get_edit_post_link( $source_id, '' );
		$weekday    = (int) get_post_meta( $source_id, MeetingSchedule::META_WEEKDAY, true );
		$time       = (string) get_post_meta( $source_id, MeetingSchedule::META_TIME, true );
		$weekdays   = MeetingSchedule::weekday_names();

		$hover_image_url = $hover_image_id ? (string) wp_get_attachment_image_url( $hover_image_id, 'thumbnail' ) : '';

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );
		?>
		<style>
			#meeting_details .inside p { margin-bottom: 1.25rem; }
			#meeting_details .inside label { display: block; margin-bottom: 0.5rem; }
			#meeting_details .inside label strong { font-size: 0.9375rem; }
			#meeting_details .inside .description { display: block; margin-bottom: 0.5rem; }
		</style>
		<p>
			<label for="cbp_meeting_weekday"><strong><?php esc_html_e( 'Dzień i godzina', 'custom-block-package' ); ?></strong></label>
			<span class="description">
				<?php if ( $is_source ) : ?>
					<?php esc_html_e( 'Wspólne dla wszystkich języków. Opis pod kafelkiem i daty dla Google powstają z tych dwóch pól.', 'custom-block-package' ); ?>
				<?php else : ?>
					<?php esc_html_e( 'Ustawiane na polskiej wersji spotkania i wspólne dla wszystkich języków.', 'custom-block-package' ); ?>
					<?php if ( '' !== $source_url ) : ?>
						<a href="<?php echo esc_url( $source_url ); ?>"><?php esc_html_e( 'Przejdź do polskiej wersji', 'custom-block-package' ); ?></a>
					<?php endif; ?>
				<?php endif; ?>
			</span>
			<select id="cbp_meeting_weekday" name="cbp_meeting_weekday"<?php echo $is_source ? '' : ' disabled'; ?>>
				<option value="0"<?php selected( 0, $weekday ); ?>><?php esc_html_e( '— bez stałego terminu —', 'custom-block-package' ); ?></option>
				<?php foreach ( $weekdays as $number => $name ) : ?>
					<option value="<?php echo esc_attr( (string) $number ); ?>"<?php selected( $number, $weekday ); ?>><?php echo esc_html( $name ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php // The visible label belongs to the select, so the hour needs its own name. ?>
			<input type="time" id="cbp_meeting_time" name="cbp_meeting_time" value="<?php echo esc_attr( $time ); ?>" aria-label="<?php esc_attr_e( 'Godzina rozpoczęcia', 'custom-block-package' ); ?>"<?php echo $is_source ? '' : ' disabled'; ?>>
		</p>

		<?php if ( '' !== $day_hour ) : ?>
			<p>
				<span class="description">
					<?php
					printf(
						/* translators: %s: the generated label, e.g. "Sunday 10.30 am". */
						esc_html__( 'Na stronie w tym języku wyświetla się: %s', 'custom-block-package' ),
						'<strong>' . esc_html( $day_hour ) . '</strong>'
					);
					?>
				</span>
			</p>
		<?php endif; ?>

		<p>
			<label for="cbp_meeting_place"><strong><?php esc_html_e( 'Miejsce', 'custom-block-package' ); ?></strong></label>
			<input type="text" id="cbp_meeting_place" name="cbp_meeting_place" value="<?php echo esc_attr( $place ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'np. ul. Przemysłowa 2', 'custom-block-package' ); ?>">
		</p>

		<p>
			<label><strong><?php esc_html_e( 'Obraz po najechaniu', 'custom-block-package' ); ?></strong></label>
			<span class="description"><?php esc_html_e( 'Obraz nakładki, który znika po najechaniu myszką.', 'custom-block-package' ); ?></span>
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

		$place = isset( $_POST['cbp_meeting_place'] ) ? sanitize_text_field( wp_unslash( $_POST['cbp_meeting_place'] ) ) : '';
		$hover = isset( $_POST['cbp_meeting_hover_image'] ) ? absint( $_POST['cbp_meeting_hover_image'] ) : 0;

		update_post_meta( $post_id, self::META_PLACE, $place );
		update_post_meta( $post_id, self::META_HOVER_IMAGE, $hover );

		/*
		 * META_DAY_HOUR is not read from the form any more — it is generated by
		 * refresh_derived_label() further down the same hook.
		 *
		 * The pair itself is only accepted from the post that owns it. On a
		 * translation the two controls are rendered disabled, and a disabled
		 * control submits nothing, so without the isset() guard every save of an
		 * English meeting would quietly reset the shared weekday to 0.
		 */
		if ( MeetingSchedule::source_post_id( $post_id ) !== $post_id ) {
			return;
		}

		if ( isset( $_POST['cbp_meeting_weekday'] ) ) {
			$weekday = absint( $_POST['cbp_meeting_weekday'] );
			update_post_meta( $post_id, MeetingSchedule::META_WEEKDAY, $weekday > 7 ? 0 : $weekday );
		}

		if ( isset( $_POST['cbp_meeting_time'] ) ) {
			$time = sanitize_text_field( wp_unslash( $_POST['cbp_meeting_time'] ) );

			// A browser's time input sends `HH:MM`, but nothing stops a crafted
			// request, and an unparseable time would silently drop the meeting
			// out of the schema graph rather than fail loudly.
			if ( 1 !== preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $time ) ) {
				$time = '';
			}

			update_post_meta( $post_id, MeetingSchedule::META_TIME, $time );
		}
	}
}
