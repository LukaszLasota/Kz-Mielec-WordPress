<?php
/**
 * Custom search form (overrides the html5 default from get_search_form()).
 *
 * @package Kzmielec
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$kzmielec_search_id = wp_unique_id( 'search-form-' );
?>
<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label for="<?php echo esc_attr( $kzmielec_search_id ); ?>" class="search-form__label">
		<?php esc_html_e( 'Szukaj na stronie', 'kzmielec' ); ?>
	</label>
	<div class="search-form__row">
		<input
			type="search"
			id="<?php echo esc_attr( $kzmielec_search_id ); ?>"
			class="search-form__input"
			name="s"
			value="<?php echo esc_attr( get_search_query() ); ?>"
			placeholder="<?php esc_attr_e( 'Czego szukasz?', 'kzmielec' ); ?>"
			required
		/>
		<button type="submit" class="search-form__submit">
			<span><?php esc_html_e( 'Szukaj', 'kzmielec' ); ?></span>
		</button>
	</div>
</form>
