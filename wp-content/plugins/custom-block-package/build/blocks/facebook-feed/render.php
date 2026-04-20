<?php
/**
 * Facebook Feed Block Render Template
 *
 * Renders initial batch of posts from the configured Facebook page.
 * Additional posts are loaded via infinite scroll (view.js → REST endpoint).
 *
 * @package CustomBlockPackage
 *
 * @var array $attributes Block attributes.
 */

use CustomBlockPackage\Services\FacebookFeedService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$fb_posts_count   = isset( $attributes['postsCount'] ) ? (int) $attributes['postsCount'] : 5;
$show_images      = ! isset( $attributes['showImages'] ) || (bool) $attributes['showImages'];
$show_date        = ! isset( $attributes['showDate'] ) || (bool) $attributes['showDate'];
$columns          = isset( $attributes['columns'] ) ? max( 1, min( 3, (int) $attributes['columns'] ) ) : 1;
$container_height = isset( $attributes['containerHeight'] ) ? max( 200, (int) $attributes['containerHeight'] ) : 700;

$service     = new FacebookFeedService();
$fb_posts    = $service->get_posts( $fb_posts_count );
$total       = $service->get_total_count();
$has_more    = count( $fb_posts ) < $total;
$initial_cnt = count( $fb_posts );

$wrapper_extra = array(
	'class'            => 'has-columns-' . $columns,
	'style'            => '--cbp-fb-height: ' . (int) $container_height . 'px;',
	'data-endpoint'    => rest_url( 'custom-block-package/v1/facebook-feed' ),
	'data-offset'      => (string) $initial_cnt,
	'data-has-more'    => $has_more ? 'true' : 'false',
	'data-show-images' => $show_images ? 'true' : 'false',
	'data-show-date'   => $show_date ? 'true' : 'false',
	'data-batch-size'  => (string) $fb_posts_count,
);
if ( ! empty( $attributes['anchor'] ) ) {
	$wrapper_extra['id'] = $attributes['anchor'];
}

$page_id      = (string) get_option( FacebookFeedService::OPTION_PAGE_ID, '' );
$fallback_url = $page_id ? 'https://www.facebook.com/' . rawurlencode( $page_id ) : 'https://www.facebook.com/';
?>
<div <?php echo get_block_wrapper_attributes( $wrapper_extra ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Returns pre-escaped HTML. ?>>
	<?php if ( empty( $fb_posts ) ) : ?>
		<p class="facebook-feed__empty">
			<?php esc_html_e( 'Nie udało się pobrać postów.', 'custom-block-package' ); ?>
			<a href="<?php echo esc_url( $fallback_url ); ?>" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'Odwiedź naszą stronę na Facebooku', 'custom-block-package' ); ?>
			</a>
		</p>
	<?php else : ?>
		<div class="facebook-feed__scroll">
		<div class="facebook-feed__grid">
			<?php foreach ( $fb_posts as $fb_post ) : ?>
				<?php
				$message   = isset( $fb_post['message'] ) ? (string) $fb_post['message'] : '';
				$image     = isset( $fb_post['image'] ) ? (string) $fb_post['image'] : '';
				$permalink = isset( $fb_post['permalink_url'] ) ? (string) $fb_post['permalink_url'] : '';
				$created   = isset( $fb_post['created_time'] ) ? (string) $fb_post['created_time'] : '';

				$date_formatted = '';
				if ( $created ) {
					$timestamp = strtotime( $created );
					if ( false !== $timestamp ) {
						$date_formatted = wp_date( get_option( 'date_format', 'Y-m-d' ), $timestamp );
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
			<?php endforeach; ?>
		</div>

		<?php if ( $has_more ) : ?>
			<div
				class="facebook-feed__sentinel"
				aria-hidden="true"
			></div>
			<p class="facebook-feed__loading" aria-live="polite">
				<?php esc_html_e( 'Ładowanie kolejnych postów…', 'custom-block-package' ); ?>
			</p>
		<?php endif; ?>
		</div>
	<?php endif; ?>
</div>
