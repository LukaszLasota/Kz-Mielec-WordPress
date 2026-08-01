<?php
/**
 * Yoast fallbacks and church schema
 *
 * Fills the gaps an audit found in what Yoast emits, without taking over from
 * it: every filter here yields to a value an editor has set and only supplies
 * one when the field is empty.
 *
 * @package Kzmielec\Seo
 */

namespace Kzmielec\Seo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Kzmielec\Interfaces\FilterHookInterface;

/**
 * Class YoastFallbacks
 *
 * Three gaps, measured across all 19 published pages:
 *
 *   - `og:image` was missing on 19 of 19, so a link shared to Facebook or
 *     WhatsApp rendered without a picture;
 *   - `meta description` was missing on 12 of 19, leaving Google to invent the
 *     snippet from whatever it found first;
 *   - the schema graph described an Organization but not a church with an
 *     address and service times, which is what local search actually matches.
 *
 * The first two are per-page fields an editor can fill in, and this class steps
 * aside the moment they do. The third is site-level data that has no admin
 * screen yet, so it lives here behind filters.
 */
class YoastFallbacks implements FilterHookInterface {

	/**
	 * Length of a generated description, in characters.
	 *
	 * Google truncates the snippet around 155-160 characters on desktop and
	 * shorter on mobile; going past that wastes the words that get cut.
	 */
	private const DESCRIPTION_LENGTH = 155;

	/**
	 * Length past which the site name is dropped from the document title.
	 *
	 * Google shows roughly 60 characters. Several of these page titles are long
	 * sentences on their own ("w sprawie małżeństwa, rozwodu, powtórnego
	 * małżeństwa oraz planowania rodziny"), and appending the site name pushed
	 * one to 117 characters — half of it invisible in the result.
	 */
	private const TITLE_LENGTH = 60;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register_add_filter();
	}

	/**
	 * Register the filters.
	 *
	 * @return void
	 */
	public function register_add_filter(): void {
		add_filter( 'wpseo_title', array( $this, 'shorten_long_title' ) );
		add_filter( 'wpseo_metadesc', array( $this, 'fallback_description' ) );
		// The social description is a separate value in Yoast; the same fallback fits.
		add_filter( 'wpseo_opengraph_desc', array( $this, 'fallback_description' ) );
		add_filter( 'wpseo_add_opengraph_images', array( $this, 'fallback_social_image' ) );
		add_filter( 'wpseo_schema_graph', array( $this, 'add_church_schema' ), 10, 1 );
	}

	/**
	 * Supply a description built from the content when the field is empty.
	 *
	 * @param string $description Description Yoast resolved, possibly empty.
	 * @return string
	 */
	public function fallback_description( string $description ): string {
		if ( '' !== trim( $description ) ) {
			return $description;
		}

		if ( is_post_type_archive( 'meetings' ) ) {
			return $this->meetings_description();
		}

		if ( ! is_singular() ) {
			return $description;
		}

		$post = get_post();

		if ( ! $post instanceof \WP_Post ) {
			return $description;
		}

		$excerpt = has_excerpt( $post ) ? get_the_excerpt( $post ) : $post->post_content;

		// Blocks first — a server-rendered block contributes nothing to a
		// description, and its comment delimiters would otherwise leak into it.
		$text = wp_strip_all_tags( strip_shortcodes( excerpt_remove_blocks( $excerpt ) ) );
		$text = trim( preg_replace( '/\s+/u', ' ', $text ) ?? '' );

		if ( '' === $text ) {
			return $description;
		}

		return rtrim( mb_strimwidth( $text, 0, self::DESCRIPTION_LENGTH, '…', 'UTF-8' ) );
	}

	/**
	 * Drop the site name from a title that is already long enough on its own.
	 *
	 * Yoast builds `%%title%% %%sep%% %%sitename%%`, which is right for a short
	 * title and wasteful for a long one: the suffix is what gets truncated, so it
	 * costs characters without ever being read. Only the tail is removed, and only
	 * past the length Google shows.
	 *
	 * @param string $title Title Yoast assembled.
	 * @return string
	 */
	public function shorten_long_title( string $title ): string {
		if ( mb_strlen( $title ) <= self::TITLE_LENGTH ) {
			return $title;
		}

		$site_name = (string) get_bloginfo( 'name' );

		if ( '' === $site_name ) {
			return $title;
		}

		$position = mb_strrpos( $title, $site_name );

		// Position 0 means the title *is* the site name — the front page.
		if ( false === $position || 0 === $position ) {
			return $title;
		}

		$shortened = rtrim( mb_substr( $title, 0, $position ) );
		$shortened = rtrim( $shortened, ' -–—|·:/\\' );

		return '' !== $shortened ? $shortened : $title;
	}

	/**
	 * Describe the meetings archive by what it lists.
	 *
	 * The archive has no content of its own, so without this Yoast emits no
	 * description at all and the result snippet is whatever Google picks out of
	 * the markup. The meetings and their times are the useful summary.
	 *
	 * @return string
	 */
	private function meetings_description(): string {
		$parts = array();

		foreach ( $this->meeting_events() as $event ) {
			$parts[] = $event['name'] . ' — ' . $event['description'];
		}

		if ( array() === $parts ) {
			return '';
		}

		return rtrim(
			mb_strimwidth( implode( ', ', $parts ), 0, self::DESCRIPTION_LENGTH, '…', 'UTF-8' )
		);
	}

	/**
	 * Add the site logo to the Open Graph image queue when nothing else filled it.
	 *
	 * Yoast collects candidate images before rendering; adding one here is only
	 * reached when the page has no featured image and no manual social image, so
	 * an editor's choice always wins.
	 *
	 * @param mixed $image_container Yoast's image container object.
	 * @return mixed
	 */
	public function fallback_social_image( $image_container ) {
		if ( ! is_object( $image_container ) || ! method_exists( $image_container, 'has_images' ) ) {
			return $image_container;
		}

		if ( $image_container->has_images() ) {
			return $image_container;
		}

		$logo_id = (int) get_option( 'site_icon' );

		if ( $logo_id && method_exists( $image_container, 'add_image_by_id' ) ) {
			$image_container->add_image_by_id( $logo_id );
		}

		return $image_container;
	}

	/**
	 * Append a Church node describing the congregation to Yoast's schema graph.
	 *
	 * Yoast describes the site as an Organization; this says what kind, where it
	 * is and when it meets, which is the part local search can act on. Service
	 * times come from the meetings CPT rather than being restated, so editing a
	 * meeting updates the markup.
	 *
	 * @param array<int, array<string, mixed>> $graph Schema pieces Yoast built.
	 * @return array<int, array<string, mixed>>
	 */
	public function add_church_schema( array $graph ): array {
		if ( ! is_front_page() ) {
			return $graph;
		}

		$church = array(
			'@type'     => 'Church',
			'@id'       => home_url( '/#church' ),
			'name'      => get_bloginfo( 'name' ),
			'url'       => home_url( '/' ),
			'address'   => array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => 'ul. Przemysłowa 2',
				'postalCode'      => '39-300',
				'addressLocality' => 'Mielec',
				'addressCountry'  => 'PL',
			),
			'email'     => 'zbor@kzmielec.pl',
			'telephone' => '+48669189992',
		);

		$logo_id = (int) get_option( 'site_icon' );
		$logo    = $logo_id ? wp_get_attachment_image_url( $logo_id, 'full' ) : '';

		if ( is_string( $logo ) && '' !== $logo ) {
			$church['logo']  = $logo;
			$church['image'] = $logo;
		}

		$events = $this->meeting_events();

		if ( array() !== $events ) {
			$church['event'] = $events;
		}

		$graph[] = $church;

		return $graph;
	}

	/**
	 * Describe each published meeting as an Event.
	 *
	 * The day and hour are stored as free text ("Niedziela 10:30"), which is not
	 * a machine-readable schedule, so it goes into `description` rather than a
	 * `startDate` this code would have to guess at.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function meeting_events(): array {
		$meetings = get_posts(
			array(
				'post_type'        => 'meetings',
				'post_status'      => 'publish',
				'numberposts'      => 20,
				'suppress_filters' => false,
			)
		);

		$events = array();

		foreach ( $meetings as $meeting ) {
			$when = (string) get_post_meta( $meeting->ID, '_meeting_day_hour', true );

			if ( '' === trim( $when ) ) {
				continue;
			}

			$events[] = array(
				'@type'       => 'Event',
				'name'        => get_the_title( $meeting ),
				'description' => $when,
				'location'    => array( '@id' => home_url( '/#church' ) ),
			);
		}

		return $events;
	}
}
