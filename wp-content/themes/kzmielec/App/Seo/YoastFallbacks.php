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
	 *
	 * Raised from 60 to 75 once the site name itself was translated. The Spanish
	 * name is 44 characters, so at 60 the suffix survived on 4 of 21 Spanish pages
	 * against 9 of 21 Polish ones — the same site advertising its name in one
	 * language and not the other. Measured across all four languages, 75 keeps the
	 * suffix on 10-15 pages each while still dropping it on the long-sentence
	 * titles, which run 104-128 characters and where appending anything is absurd.
	 *
	 * 75 is past what Google displays, and deliberately so: the site name sits at
	 * the end, so an over-long title loses the name rather than the page's own
	 * words. That is the right thing to lose.
	 */
	private const TITLE_LENGTH = 75;

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
		add_filter( 'wpseo_breadcrumb_links', array( $this, 'translate_breadcrumbs' ) );
		add_filter( 'option_wpseo_titles', array( $this, 'translate_yoast_templates' ) );
	}

	/**
	 * Polish wording sitting inside Yoast's global title templates.
	 *
	 * Option key => the literal to translate. Only the words are replaced, so the
	 * `%%sep%%`, `%%sitename%%`, `%%page%%` and `%%searchphrase%%` placeholders
	 * survive untouched — which is why this filters the TEMPLATE rather than the
	 * finished title.
	 *
	 * @var array<string, string>
	 */
	private const TEMPLATE_STRINGS = array(
		'title-404-wpseo'           => 'Strony nie znaleziono',
		'breadcrumbs-404crumb'      => 'Błąd 404: Strony nie znaleziono',
		'title-search-wpseo'        => 'Wyniki wyszukiwania',
		'breadcrumbs-searchprefix'  => 'Wyniki wyszukiwania',
		'breadcrumbs-archiveprefix' => 'Archiwum dla',
	);

	/**
	 * Translate the Polish words in Yoast's global title templates.
	 *
	 * Yoast keeps one template per page kind for the whole site, with the wording
	 * typed in by hand — so the 404 page and the search results page announced
	 * themselves in Polish in every language. Neither page is in the sitemap, which
	 * is why the audit of 76 URLs could not see them; they were found on 13 August
	 * 2026 by requesting them directly.
	 *
	 * The search wording leaks widest: it lands in `<title>`, `og:title` and the
	 * Twitter title from that single option, so five copies of "Wyniki wyszukiwania"
	 * sat on every foreign search page.
	 *
	 * @param mixed $titles The `wpseo_titles` option.
	 * @return mixed
	 */
	public function translate_yoast_templates( $titles ) {
		if ( ! is_array( $titles ) ) {
			return $titles;
		}

		foreach ( self::TEMPLATE_STRINGS as $key => $polish ) {
			if ( ! isset( $titles[ $key ] ) || ! is_string( $titles[ $key ] ) || '' === $titles[ $key ] ) {
				continue;
			}

			// phpcs:ignore WordPress.WP.I18n.LowLevelTranslationFunction,WordPress.WP.I18n.NonSingularStringLiteralText -- the literals are the const above, not user input.
			$translated = translate( $polish, 'kzmielec' );

			if ( $translated === $polish ) {
				continue;
			}

			$titles[ $key ] = str_replace( $polish, $translated, $titles[ $key ] );
		}

		return $titles;
	}

	/**
	 * Translate the breadcrumb structured data, which Yoast leaves in one language.
	 *
	 * The visible breadcrumb trail is switched off on this site, which is why nobody
	 * saw any of this — but Yoast still writes a `BreadcrumbList` into the JSON-LD of
	 * every page. The audit of 13 August 2026 found Polish in it on all 57
	 * foreign-language pages, for two different reasons:
	 *
	 * 1. THE HOME CRUMB comes from ONE global option, `breadcrumbs-home`, so every
	 *    language was served the Polish "Strona główna".
	 *
	 * 2. THE POST TYPE ARCHIVE CRUMB comes from Yoast's indexable table, which holds a
	 *    SINGLE row per archive with no language column. Its `breadcrumb_title` is
	 *    therefore frozen in whichever language happened to rebuild it first — clearing
	 *    the row does not fix that, it only re-runs the race. Reading the label from
	 *    the post type object instead resolves it per request, so each language gets
	 *    its own.
	 *
	 * Both values are read rather than assumed, and the home crumb is only touched when
	 * its text still matches the option: an editor who renames it keeps their wording.
	 *
	 * @param mixed $crumbs Crumbs Yoast assembled; documented as an array, filtered by
	 *                      other plugins too, so the shape is checked rather than trusted.
	 * @return mixed
	 */
	public function translate_breadcrumbs( $crumbs ) {
		if ( ! is_array( $crumbs ) ) {
			return $crumbs;
		}

		$titles     = get_option( 'wpseo_titles' );
		$configured = is_array( $titles ) && isset( $titles['breadcrumbs-home'] ) && is_string( $titles['breadcrumbs-home'] )
			? $titles['breadcrumbs-home']
			: '';
		$home       = __( 'Strona główna', 'kzmielec' );

		foreach ( $crumbs as $kz_i => $crumb ) {
			if ( ! is_array( $crumb ) ) {
				continue;
			}

			if ( '' !== $configured && isset( $crumb['text'] ) && $configured === $crumb['text'] ) {
				$crumbs[ $kz_i ]['text'] = $home;
				continue;
			}

			if ( empty( $crumb['ptarchive'] ) || ! is_string( $crumb['ptarchive'] ) ) {
				continue;
			}

			$type = get_post_type_object( $crumb['ptarchive'] );

			if ( ! $type instanceof \WP_Post_Type ) {
				continue;
			}

			$label = $type->labels->name ?? '';

			if ( is_string( $label ) && '' !== $label ) {
				$crumbs[ $kz_i ]['text'] = $label;
			}
		}

		return $crumbs;
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

		/*
		 * A sentence first, then the timetable. On its own the timetable came to 71-87
		 * characters depending on the language — well under the 120 Google will show,
		 * and it read as a fragment rather than an invitation. This is the archive
		 * every visitor planning a first visit lands on, so it is worth a written
		 * opening; the times still follow, because that is what the reader is looking
		 * for. Translated per language through the theme's .mo files rather than
		 * through Yoast's own archive setting, which is a single global value and
		 * would give all four languages the same Polish text.
		 *
		 * The address arrives from the one source instead of being written into the
		 * translated string. While it lived inside the string, the Ukrainian catalogue
		 * carried «вул. Промислова, 2» — a Cyrillic street name that does not exist in
		 * the Polish postal system, and one that had already been removed from the page
		 * content without anybody noticing this second copy.
		 */
		$lead = sprintf(
			/* translators: %s: the congregation's address, supplied by the theme. */
			__( 'Kiedy się spotykamy w Mielcu, %s:', 'kzmielec' ),
			wp_strip_all_tags( (string) \Kzmielec\Contact\ContactBindings::line( 'address' ) )
		);

		return rtrim(
			mb_strimwidth( $lead . ' ' . implode( ', ', $parts ), 0, self::DESCRIPTION_LENGTH, '…', 'UTF-8' )
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

		$contact = \Kzmielec\Contact\ContactData::all();

		$church = array(
			'@type'     => 'Church',
			'@id'       => home_url( '/#church' ),
			'name'      => get_bloginfo( 'name' ),
			'url'       => home_url( '/' ),
			'address'   => array(
				'@type'           => 'PostalAddress',
				// The street keeps its Polish form in every language: this is a postal
				// address, not prose, and it has to work on an envelope and in a map.
				'streetAddress'   => 'ul. ' . $contact['street'],
				'postalCode'      => $contact['postcode'],
				'addressLocality' => $contact['city'],
				'addressCountry'  => 'PL',
			),
			'email'     => $contact['email'],
			'telephone' => \Kzmielec\Contact\ContactData::phone_e164(),
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
	 * Describe each published meeting as a series of dated Events.
	 *
	 * This used to put "Niedziela 10:30" into `description` and point `location`
	 * at the Church node by reference. Google's Rich Results Test called both
	 * events invalid, and it was right on two counts: `startDate` is required
	 * and there was none, and `location.address` is required where an `@id`
	 * reference does not satisfy it.
	 *
	 * Both now come from MeetingSchedule, which holds the weekday and the hour
	 * as data rather than prose. Each occurrence is emitted separately, which is
	 * what Google asks for with a recurring event — and see
	 * MeetingSchedule::OCCURRENCES for why a single "next Sunday" would have
	 * been a date in the past for anyone served a cached page.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function meeting_events(): array {
		if ( ! class_exists( '\CustomBlockPackage\Services\MeetingSchedule' ) ) {
			return array();
		}

		$meetings = get_posts(
			array(
				'post_type'        => 'meetings',
				'post_status'      => 'publish',
				'numberposts'      => 20,
				'suppress_filters' => false,
			)
		);

		$location = $this->event_location();
		$events   = array();

		foreach ( $meetings as $meeting ) {
			$dates = \CustomBlockPackage\Services\MeetingSchedule::occurrences( $meeting->ID );

			if ( array() === $dates ) {
				continue;
			}

			$label     = \CustomBlockPackage\Services\MeetingSchedule::label( $meeting->ID );
			$permalink = get_permalink( $meeting );
			$image     = $this->meeting_image( $meeting->ID );

			foreach ( $dates as $start ) {
				$event = array(
					'@type'               => 'Event',
					'name'                => get_the_title( $meeting ),
					'startDate'           => $start,
					'eventStatus'         => 'https://schema.org/EventScheduled',
					'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
					'location'            => $location,
					'organizer'           => array( '@id' => home_url( '/#church' ) ),
					// A church service is free to attend, and saying so plainly
					// keeps Google from looking for an `offers` node.
					'isAccessibleForFree' => true,
				);

				if ( '' !== $label ) {
					$event['description'] = $label;
				}

				if ( is_string( $permalink ) && '' !== $permalink ) {
					$event['url'] = $permalink;
				}

				if ( '' !== $image ) {
					$event['image'] = $image;
				}

				$events[] = $event;
			}
		}

		return $events;
	}

	/**
	 * The picture that stands for a meeting in a search result.
	 *
	 * Google lists `image` as recommended on an Event, and the meetings already
	 * carry a featured image — the same one the tiles use — so nothing has to be
	 * invented. A meeting without one falls back to the site icon, which is what
	 * the Church node already advertises.
	 *
	 * @param int $meeting_id Meeting post ID.
	 * @return string Image URL, or an empty string when there is none to give.
	 */
	private function meeting_image( int $meeting_id ): string {
		$attachment_id = (int) get_post_thumbnail_id( $meeting_id );

		if ( 0 === $attachment_id ) {
			$attachment_id = (int) get_option( 'site_icon' );
		}

		if ( 0 === $attachment_id ) {
			return '';
		}

		$url = wp_get_attachment_image_url( $attachment_id, 'full' );

		return is_string( $url ) ? $url : '';
	}

	/**
	 * The one place every meeting happens, with the address written out in full.
	 *
	 * Deliberately WITHOUT an `@id`. It carried `#church` at first, the same
	 * identifier as the Church node, which is wrong: in JSON-LD one `@id` names
	 * one thing, so a consumer merges the Place into the Church. Google did
	 * exactly that — its preview reported this location as a Church and then
	 * resolved the reference back into the Church's own list of events, over and
	 * over. The events validated anyway, but the graph described two things as
	 * one. The link to the congregation is carried by `organizer` instead, which
	 * is what that property is for.
	 *
	 * @return array<string, mixed>
	 */
	private function event_location(): array {
		$contact = \Kzmielec\Contact\ContactData::all();

		return array(
			'@type'   => 'Place',
			'name'    => get_bloginfo( 'name' ),
			'address' => array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => 'ul. ' . $contact['street'],
				'postalCode'      => $contact['postcode'],
				'addressLocality' => $contact['city'],
				'addressCountry'  => 'PL',
			),
		);
	}
}
