<?php
/**
 * Telling Polylang that this plugin's content is translatable.
 *
 * Without this, Polylang does not filter the comparison topics by language at
 * all: the data can be imported and even linked correctly, and the accordion
 * still shows all four language versions at once, because Polylang only narrows
 * queries for the post types and taxonomies it has been told to translate.
 *
 * On a site where somebody ticked the boxes in Polylang's settings it works
 * either way. On a fresh site nobody has ticked anything - `post_types` is an
 * empty array - and that is exactly the case this plugin has to survive, since
 * the whole point of the seed files is to be usable somewhere else. Measured on
 * a clean install: without these filters an export wrote 148 topics into each of
 * the four language files instead of 37.
 *
 * Declaring the types here rather than asking the user to configure them also
 * makes the behaviour the same on every site, which is worth more than the
 * checkbox: Polylang hides the option when a plugin provides it, because the
 * plugin knows better than the person clicking.
 *
 * @package ComparisonOfReligions
 */

declare(strict_types=1);

namespace ComparisonOfReligions\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ComparisonOfReligions\PostTypes\ComparisonTopic;
use ComparisonOfReligions\Taxonomies\ComparisonCategory;

/**
 * Registers the plugin's post type and taxonomy with Polylang.
 */
class PolylangTypes {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'pll_get_post_types', array( $this, 'add_post_type' ), 10, 2 );
		add_filter( 'pll_get_taxonomies', array( $this, 'add_taxonomy' ), 10, 2 );
	}

	/**
	 * Add the comparison topic to Polylang's translatable post types.
	 *
	 * @param array<string, string> $types       Post types Polylang translates.
	 * @param bool                  $is_settings True when the list is being built for the settings screen.
	 * @return array<string, string>
	 */
	public function add_post_type( array $types, bool $is_settings ): array {
		/*
		 * On the settings screen the type is left out on purpose. Polylang shows
		 * that list as checkboxes, and offering a box that cannot change anything
		 * - because this filter puts the type back regardless - is worse than not
		 * showing it.
		 */
		if ( $is_settings ) {
			return $types;
		}

		$types[ ComparisonTopic::POST_TYPE ] = ComparisonTopic::POST_TYPE;

		return $types;
	}

	/**
	 * Add the comparison category to Polylang's translatable taxonomies.
	 *
	 * @param array<string, string> $taxonomies Taxonomies Polylang translates.
	 * @param bool                  $is_settings True when the list is being built for the settings screen.
	 * @return array<string, string>
	 */
	public function add_taxonomy( array $taxonomies, bool $is_settings ): array {
		if ( $is_settings ) {
			return $taxonomies;
		}

		$taxonomies[ ComparisonCategory::TAXONOMY ] = ComparisonCategory::TAXONOMY;

		return $taxonomies;
	}
}
