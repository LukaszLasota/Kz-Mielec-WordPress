<?php
/**
 * The one screen where the congregation's contact details are entered.
 *
 * @package Kzmielec\Admin
 */

declare(strict_types=1);

namespace Kzmielec\Admin;

use Kzmielec\Contact\ContactData;
use Kzmielec\Interfaces\ActionHookInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings screen for the contact data.
 *
 * Seven fields, all of them data that is identical in every language. The words printed
 * next to them are translations, not settings, which is why the screen says where to
 * change them instead of offering a box per language — offering both would recreate the
 * very problem this screen exists to remove.
 */
class ContactSettings implements ActionHookInterface {

	/**
	 * Nonce action name.
	 */
	private const NONCE_ACTION = 'kzmielec_save_contact';

	/**
	 * Nonce field name.
	 */
	private const NONCE_NAME = 'kzmielec_contact_nonce';

	/**
	 * Submenu slug.
	 */
	private const PAGE_SLUG = 'kzmielec-contact';

	/**
	 * Field labels, in display order.
	 *
	 * @var array<string, string>
	 */
	private const FIELDS = array(
		'street'     => 'Ulica i numer',
		'postcode'   => 'Kod pocztowy',
		'city'       => 'Miasto',
		'phone'      => 'Telefon',
		'phone_text' => 'Tekst przy telefonie',
		'nip'        => 'NIP',
		'regon'      => 'REGON',
		'email'      => 'E-mail',
		'iban'       => 'Numer konta',
		'latitude'   => 'Mapa — szerokość',
		'longitude'  => 'Mapa — długość',
	);

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
		add_action( 'admin_menu', array( $this, 'add_page' ) );
	}

	/**
	 * Add the screen under the theme's own menu.
	 *
	 * @return void
	 */
	public function add_page(): void {
		add_submenu_page(
			ThemeSettingsPage::MENU_SLUG,
			__( 'Dane kontaktowe', 'kzmielec' ),
			__( 'Dane kontaktowe', 'kzmielec' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Render the screen, handling a submission first.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Nie masz wystarczających uprawnień, aby uzyskać dostęp do tej strony.', 'kzmielec' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified in save().
		if ( isset( $_POST['kzmielec_contact_submit'] ) ) {
			$this->save();
		}

		$this->form( ContactData::all() );
	}

	/**
	 * Verify, sanitise and store the submitted fields.
	 *
	 * @return void
	 */
	private function save(): void {
		if (
			! isset( $_POST[ self::NONCE_NAME ] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION )
		) {
			wp_die( esc_html__( 'Weryfikacja bezpieczeństwa nie powiodła się. Spróbuj ponownie.', 'kzmielec' ) );
		}

		$clean = array();

		foreach ( array_keys( self::FIELDS ) as $key ) {
			$field = 'kzmielec_contact_' . $key;

			if ( ! isset( $_POST[ $field ] ) ) {
				$clean[ $key ] = '';
				continue;
			}

			// The e-mail gets its own sanitiser: `sanitize_text_field()` would happily
			// keep an address that is not one, and this value ends up in a `mailto:`
			// link on four pages and in the structured data.
			if ( 'email' === $key ) {
				$clean[ $key ] = sanitize_email( wp_unslash( $_POST[ $field ] ) );
			} elseif ( 'phone_text' === $key ) {
				// Keeps the line breaks: each line is a line on the page.
				$clean[ $key ] = sanitize_textarea_field( wp_unslash( $_POST[ $field ] ) );
			} else {
				$clean[ $key ] = sanitize_text_field( wp_unslash( $_POST[ $field ] ) );
			}
		}

		update_option( ContactData::OPTION, $clean );

		/*
		 * The data is printed on every page - the four front pages through Block
		 * Bindings and the maps, every page through the structured data - and a
		 * page can stay in LiteSpeed's cache for seven days. Saving an option fires
		 * none of its purge rules, so without this the site kept showing the old
		 * address. A full purge, unlike FeedCachePurge's per-URL one, because every
		 * URL is affected; it also re-versions the combined CSS, which is acceptable
		 * for a change made a few times a year. A no-op without LiteSpeed.
		 */
		do_action( 'litespeed_purge_all' );

		add_settings_error(
			'kzmielec_contact',
			'contact_saved',
			__( 'Dane kontaktowe zostały zapisane. Zmiana obowiązuje na wszystkich wersjach językowych.', 'kzmielec' ),
			'updated'
		);
	}

	/**
	 * Render the form.
	 *
	 * @param array<string, string> $data Current values.
	 * @return void
	 */
	private function form( array $data ): void {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php settings_errors( 'kzmielec_contact' ); ?>

			<p>
				<?php esc_html_e( 'Te dane pokazują się w sekcji „Znajdź nas” na wszystkich czterech wersjach językowych strony głównej, w danych strukturalnych dla wyszukiwarek oraz w opisie archiwum spotkań. Wpisujesz je tutaj raz.', 'kzmielec' ); ?>
			</p>
			<p>
				<?php esc_html_e( 'Słowa obok danych — „ul.”, „konto:”, „NIP:” — są tłumaczeniami, nie danymi. Zmienia się je w Języki → Tłumaczenia napisów, w grupie „Motyw kzmielec”. Wyjątkiem jest tekst przy telefonie, który wpisujesz tutaj.', 'kzmielec' ); ?>
			</p>

			<form method="post" action="">
				<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME ); ?>

				<table class="form-table" role="presentation">
					<tbody>
					<?php foreach ( self::FIELDS as $key => $label ) : ?>
						<tr>
							<th scope="row">
								<label for="kzmielec_contact_<?php echo esc_attr( $key ); ?>">
									<?php echo esc_html( $label ); ?>
								</label>
							</th>
							<td>
								<?php if ( 'phone_text' === $key ) : ?>
									<textarea id="kzmielec_contact_<?php echo esc_attr( $key ); ?>"
											name="kzmielec_contact_<?php echo esc_attr( $key ); ?>"
											rows="4"
											class="large-text"
									><?php echo esc_textarea( $data[ $key ] ?? '' ); ?></textarea>
									<p class="description">
										<?php esc_html_e( '{telefon} zamienia się na numer z pola „Telefon”. Każda linia to osobna linia na stronie. Puste pole = sam numer („tel.: …”).', 'kzmielec' ); ?>
									</p>
									<p class="description">
										<?php esc_html_e( 'Wersje en, uk i es biorą tłumaczenie z Języki → Tłumaczenia napisów („Kontakt: tekst przy telefonie”). Po zmianie tekstu tutaj trzeba tam wpisać tłumaczenia od nowa — do tego czasu obce wersje pokazują sam numer.', 'kzmielec' ); ?>
									</p>
								<?php else : ?>
									<input type="<?php echo 'email' === $key ? 'email' : 'text'; ?>"
											id="kzmielec_contact_<?php echo esc_attr( $key ); ?>"
											name="kzmielec_contact_<?php echo esc_attr( $key ); ?>"
											value="<?php echo esc_attr( $data[ $key ] ?? '' ); ?>"
											class="regular-text"
									/>
								<?php endif; ?>
								<?php if ( 'street' === $key ) : ?>
									<p class="description">
										<?php esc_html_e( 'Bez słowa „ul.” — ono jest tłumaczone. Nazwa ulicy zostaje po polsku we wszystkich językach, bo w innej formie nie istnieje na kopercie ani w mapach.', 'kzmielec' ); ?>
									</p>
								<?php endif; ?>
								<?php if ( 'longitude' === $key ) : ?>
									<p class="description">
										<?php esc_html_e( 'Współrzędne punktu na mapie, wspólne dla wszystkich wersji językowych. Bloki mapy wstawione na stronach biorą je stąd — chyba że w konkretnym bloku wpisano inne miejsce.', 'kzmielec' ); ?>
									</p>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>

				<p class="submit">
					<button type="submit" name="kzmielec_contact_submit" class="button button-primary">
						<?php esc_html_e( 'Zapisz', 'kzmielec' ); ?>
					</button>
				</p>
			</form>
		</div>
		<?php
	}
}
