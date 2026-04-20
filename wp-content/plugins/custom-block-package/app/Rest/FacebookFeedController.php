<?php
/**
 * Facebook Feed REST Controller
 *
 * Exposes paginated feed data for infinite scroll on the frontend.
 *
 * @package CustomBlockPackage
 */

declare(strict_types=1);

namespace CustomBlockPackage\Rest;

use CustomBlockPackage\Services\FacebookFeedService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FacebookFeedController
 */
class FacebookFeedController {

	/**
	 * REST namespace.
	 */
	private const NAMESPACE = 'custom-block-package/v1';

	/**
	 * REST route.
	 */
	private const ROUTE = '/facebook-feed';

	/**
	 * Constructor — register hooks.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST route.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			self::ROUTE,
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_posts' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'offset'     => array(
						'type'              => 'integer',
						'default'           => 0,
						'sanitize_callback' => 'absint',
					),
					'limit'      => array(
						'type'              => 'integer',
						'default'           => 5,
						'sanitize_callback' => 'absint',
					),
					'showImages' => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'showDate'   => array(
						'type'    => 'boolean',
						'default' => true,
					),
				),
			)
		);
	}

	/**
	 * Return paginated posts as rendered HTML + metadata.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response
	 */
	public function get_posts( \WP_REST_Request $request ): \WP_REST_Response {
		$offset      = (int) $request->get_param( 'offset' );
		$limit       = max( 1, min( 20, (int) $request->get_param( 'limit' ) ) );
		$show_images = (bool) $request->get_param( 'showImages' );
		$show_date   = (bool) $request->get_param( 'showDate' );

		$service  = new FacebookFeedService();
		$posts    = $service->get_posts_range( $offset, $limit );
		$total    = $service->get_total_count();
		$has_more = ( $offset + count( $posts ) ) < $total;

		$html = $this->render_posts( $posts, $show_images, $show_date );

		return new \WP_REST_Response(
			array(
				'html'    => $html,
				'count'   => count( $posts ),
				'total'   => $total,
				'offset'  => $offset,
				'hasMore' => $has_more,
			),
			200
		);
	}

	/**
	 * Render posts HTML (same structure as main render.php).
	 *
	 * @param array<int, array<string, mixed>> $posts       Posts.
	 * @param bool                             $show_images Show images flag.
	 * @param bool                             $show_date   Show date flag.
	 * @return string
	 */
	private function render_posts( array $posts, bool $show_images, bool $show_date ): string {
		if ( empty( $posts ) ) {
			return '';
		}

		$date_format = get_option( 'date_format', 'Y-m-d' );

		ob_start();
		foreach ( $posts as $fb_post ) {
			$message   = isset( $fb_post['message'] ) ? (string) $fb_post['message'] : '';
			$image     = isset( $fb_post['image'] ) ? (string) $fb_post['image'] : '';
			$permalink = isset( $fb_post['permalink_url'] ) ? (string) $fb_post['permalink_url'] : '';
			$created   = isset( $fb_post['created_time'] ) ? (string) $fb_post['created_time'] : '';

			$date_formatted = '';
			if ( $created ) {
				$timestamp = strtotime( $created );
				if ( false !== $timestamp ) {
					$date_formatted = wp_date( $date_format, $timestamp );
				}
			}
			?>
			<article class="facebook-feed__post">
				<?php if ( $show_images && $image ) : ?>
					<a
						href="<?php echo esc_url( $permalink ); ?>"
						class="facebook-feed__image-link"
						target="_blank"
						rel="noopener noreferrer"
					>
						<img
							src="<?php echo esc_url( $image ); ?>"
							alt=""
							loading="lazy"
							class="facebook-feed__image"
						/>
					</a>
				<?php endif; ?>

				<div class="facebook-feed__body">
					<?php if ( $show_date && $date_formatted ) : ?>
						<time class="facebook-feed__date" datetime="<?php echo esc_attr( $created ); ?>">
							<?php echo esc_html( $date_formatted ); ?>
						</time>
					<?php endif; ?>

					<?php if ( $message ) : ?>
						<div class="facebook-feed__message">
							<?php echo wp_kses_post( wpautop( $message ) ); ?>
						</div>
					<?php endif; ?>

					<?php if ( $permalink ) : ?>
						<a
							href="<?php echo esc_url( $permalink ); ?>"
							class="facebook-feed__link"
							target="_blank"
							rel="noopener noreferrer"
						>
							<?php esc_html_e( 'Zobacz na Facebooku', 'custom-block-package' ); ?>
							<span class="screen-reader-text"><?php esc_html_e( '(otwiera się w nowej karcie)', 'custom-block-package' ); ?></span>
						</a>
					<?php endif; ?>
				</div>
			</article>
			<?php
		}

		$output = ob_get_clean();
		return false !== $output ? $output : '';
	}
}
