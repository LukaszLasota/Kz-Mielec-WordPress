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

use CustomBlockPackage\I18n\Locale;
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

	/*
	 * Carried to the REST route by view.js. The route cannot work the language
	 * out for itself — `/wp-json/…` has no language prefix, so Polylang answers
	 * in the default language and the scrolled-in posts came back Polish on
	 * every translated page.
	 */
	'data-lang'        => Locale::current_slug(),
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
$page_info    = $service->get_page_info();
?>
<div <?php echo get_block_wrapper_attributes( $wrapper_extra ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Returns pre-escaped HTML. ?>>
	<?php if ( $page_info['name'] ) : ?>
		<div class="facebook-feed__header">
			<?php if ( $page_info['picture'] ) : ?>
				<img
					src="<?php echo esc_url( $page_info['picture'] ); ?>"
					alt=""
					class="facebook-feed__avatar"
					loading="lazy"
					aria-hidden="true"
				/>
			<?php endif; ?>
			<div class="facebook-feed__header-body">
				<div class="facebook-feed__page-name"><?php echo esc_html( $page_info['name'] ); ?></div>
				<div class="facebook-feed__subtitle">
					<svg
						xmlns="http://www.w3.org/2000/svg"
						viewBox="0 0 24 24"
						width="14"
						height="14"
						fill="#1877f2"
						aria-hidden="true"
					>
						<path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
					</svg>
					<span><?php esc_html_e( 'Strona na Facebooku', 'custom-block-package' ); ?></span>
				</div>
			</div>
			<a
				href="<?php echo esc_url( $fallback_url ); ?>"
				class="facebook-feed__follow-btn"
				target="_blank"
				rel="noopener noreferrer"
			>
				<?php esc_html_e( 'Odwiedź stronę', 'custom-block-package' ); ?>
				<span class="screen-reader-text"><?php esc_html_e( '(otwiera się w nowej karcie)', 'custom-block-package' ); ?></span>
			</a>
		</div>
	<?php endif; ?>

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
						$now  = time();
						$diff = $now - $timestamp;

						if ( $diff < MINUTE_IN_SECONDS ) {
							$date_formatted = __( 'przed chwilą', 'custom-block-package' );
						} elseif ( $diff < WEEK_IN_SECONDS ) {
							/* translators: %s: relative time e.g. "2 godziny" */
							$date_formatted = sprintf(
								/* translators: %s: relative time */
								__( '%s temu', 'custom-block-package' ),
								human_time_diff( $timestamp, $now )
							);
						} else {
							$date_formatted = wp_date( get_option( 'date_format', 'Y-m-d' ), $timestamp );
						}
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
							aria-hidden="true"
							tabindex="-1"
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
