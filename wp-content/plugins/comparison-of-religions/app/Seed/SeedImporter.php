<?php
/**
 * Writing seed files into the database.
 *
 * The import runs once and then lets go: it creates categories and topics, and
 * from that moment the database is the state of the site. Editing happens in the
 * admin, not in the files. Running it again finds what it made and leaves it
 * alone.
 *
 * It is written to work in four different states, because all four are real:
 *
 *   1. No Polylang at all - only the source-language file is imported, and
 *      nothing is assigned a language. A single-language site is not a broken
 *      multilingual one.
 *   2. Polylang present and configured - every file whose language the site has,
 *      then the translations are linked to each other.
 *   3. Polylang switched on after the fact - the source-language posts already
 *      exist without a language. They are given one and the other languages are
 *      linked TO them, rather than duplicated beside them. This is the case that
 *      makes the whole thing safe to start without Polylang.
 *   4. Fewer languages on the site than files on disk - the extra files are
 *      reported as unused. That is not an error.
 *
 * Nothing here ever destroys hand-written work. A post the import created carries
 * a hash of what it was given; if the current content no longer matches, a person
 * has edited it and the import refuses to touch it even with --overwrite.
 *
 * @package ComparisonOfReligions
 */

declare(strict_types=1);

namespace ComparisonOfReligions\Seed;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ComparisonOfReligions\PostTypes\ComparisonTopic;
use ComparisonOfReligions\Taxonomies\ComparisonCategory;
use WP_Post;
use WP_Term;

/**
 * Creates categories and topics from the seed files.
 */
class SeedImporter {

	/**
	 * Meta holding the cross-language identity of a record.
	 *
	 * This is what lets a second run recognise its own work, and what lets the
	 * languages be linked to each other without relying on ids.
	 */
	public const KEY_META = '_cor_seed_key';

	/**
	 * Meta holding a hash of the content as the import wrote it.
	 *
	 * Its only job is to answer one question: has a person changed this since?
	 */
	public const HASH_META = '_cor_seed_hash';

	/**
	 * Report nothing, change nothing.
	 *
	 * @var bool
	 */
	private bool $dry_run;

	/**
	 * Update records the import itself created and nobody has edited since.
	 *
	 * @var bool
	 */
	private bool $overwrite;

	/**
	 * Key => language => post id, filled as the import goes, used for linking.
	 *
	 * @var array<string, array<string, int>>
	 */
	private array $post_map = [];

	/**
	 * Key => language => term id.
	 *
	 * @var array<string, array<string, int>>
	 */
	private array $term_map = [];

	/**
	 * Constructor.
	 *
	 * @param bool $dry_run   Report only.
	 * @param bool $overwrite Refresh untouched records from the files.
	 */
	public function __construct( bool $dry_run = false, bool $overwrite = false ) {
		$this->dry_run   = $dry_run;
		$this->overwrite = $overwrite;
	}

	/**
	 * Import the seed.
	 *
	 * @param array<int, string> $languages Slugs to import; empty means everything the site can hold.
	 * @return array<string, mixed> Report.
	 * @throws \RuntimeException When --overwrite is asked for on a database whose languages cannot be told apart.
	 */
	public function import( array $languages = [] ): array {
		/*
		 * Plain import is safe in every state: it only creates what is missing.
		 * Overwriting is not. With Polylang inactive on a database that still
		 * holds four languages, nothing can tell which post is the Polish one, so
		 * refreshing "the Polish record" would write Polish text over whichever
		 * translation happened to be found first. Export is refused in that state
		 * for the same reason; this is the other half of the same rule.
		 */
		if ( $this->overwrite && Languages::stale_translations() ) {
			throw new \RuntimeException(
				'This database holds more than one language but Polylang is not active, so --overwrite '
				. 'cannot tell the translations apart. Import without --overwrite is safe, or activate Polylang.'
			);
		}

		$site      = Languages::site();
		$on_disk   = SeedFile::languages();
		$requested = [] === $languages ? $site : array_values( array_intersect( $languages, $site ) );

		/*
		 * Site language => the file that serves it. Not a plain intersection: a
		 * site may call its English `en-gb`, and `SeedFile::for_language()` knows
		 * that `en.json` is the answer.
		 */
		$targets = [];
		$missing = [];

		foreach ( $requested as $lang ) {
			$file = SeedFile::for_language( $lang );

			if ( null === $file ) {
				$missing[] = $lang;
				continue;
			}

			$targets[ $lang ] = $file;
		}

		$serving = array_values( $targets );

		$report = [
			'polylang'      => Languages::available(),
			'languages'     => [],
			'missing_files' => $missing,
			'unused_files'  => array_values( array_diff( $on_disk, $serving ) ),
			'linked_posts'  => 0,
			'linked_terms'  => 0,
			'dry_run'       => $this->dry_run,
		];

		/*
		 * The source language goes first. Without it the other languages have
		 * nothing to be linked to in state 3, and Polylang refuses a translation
		 * group that has no entry for the default language.
		 */
		uksort(
			$targets,
			static fn( string $a, string $b ): int => ( Languages::SOURCE === $a ? -1 : 0 ) <=> ( Languages::SOURCE === $b ? -1 : 0 )
		);

		foreach ( $targets as $lang => $file ) {
			$payload = SeedFile::read( $file );

			if ( null === $payload ) {
				$report['languages'][ $lang ] = [ 'error' => 'file missing or not the expected format' ];
				continue;
			}

			$report['languages'][ $lang ] = array_merge(
				$this->import_categories( $lang, $payload['categories'] ),
				$this->import_topics( $lang, $payload['topics'] )
			);
		}

		if ( ! $this->dry_run && Languages::available() ) {
			$report['linked_terms'] = $this->link_terms();
			$report['linked_posts'] = $this->link_posts();
		}

		return $report;
	}

	/**
	 * Create or refresh the categories of one language.
	 *
	 * @param string                           $lang Language slug.
	 * @param array<int, array<string, mixed>> $rows Category rows from the file.
	 * @return array<string, int>
	 */
	private function import_categories( string $lang, array $rows ): array {
		$counts = [
			'categories_created'  => 0,
			'categories_updated'  => 0,
			'categories_existing' => 0,
			'categories_edited'   => 0,
		];

		$parents = [];

		foreach ( $rows as $row ) {
			$key  = isset( $row['key'] ) ? (string) $row['key'] : '';
			$name = isset( $row['name'] ) ? (string) $row['name'] : '';

			if ( '' === $key || '' === $name ) {
				continue;
			}

			$slug     = isset( $row['slug'] ) ? (string) $row['slug'] : sanitize_title( $name );
			$order    = isset( $row['order'] ) ? (int) $row['order'] : 0;
			$existing = $this->find_term( $key, $slug, $lang );

			if ( $existing instanceof WP_Term ) {
				$this->term_map[ $key ][ $lang ] = $existing->term_id;

				if ( ! $this->dry_run ) {
					$this->adopt_term( $existing, $key, $lang );
				}

				if ( ! $this->overwrite ) {
					++$counts['categories_existing'];
				} elseif ( $this->term_hand_edited( $existing->term_id ) ) {
					++$counts['categories_edited'];
				} else {
					if ( ! $this->dry_run ) {
						$this->write_term( $existing->term_id, $name, $order );
					}

					++$counts['categories_updated'];
				}

				if ( isset( $row['parent'] ) && is_string( $row['parent'] ) ) {
					$parents[ $existing->term_id ] = $row['parent'];
				}

				continue;
			}

			++$counts['categories_created'];

			if ( $this->dry_run ) {
				continue;
			}

			$created = wp_insert_term( $name, ComparisonCategory::TAXONOMY, [ 'slug' => $slug ] );

			if ( is_wp_error( $created ) ) {
				--$counts['categories_created'];
				continue;
			}

			$term_id = (int) $created['term_id'];

			update_term_meta( $term_id, self::KEY_META, $key );
			$this->set_term_language( $term_id, $lang, true );
			$this->write_term( $term_id, $name, $order );

			$this->term_map[ $key ][ $lang ] = $term_id;

			if ( isset( $row['parent'] ) && is_string( $row['parent'] ) ) {
				$parents[ $term_id ] = $row['parent'];
			}
		}

		$this->apply_parents( $parents, $lang );

		return $counts;
	}

	/**
	 * Write a category's name and order, and record the hash of what was written.
	 *
	 * @param int    $term_id Category id.
	 * @param string $name    Display name.
	 * @param int    $order   Display order.
	 */
	private function write_term( int $term_id, string $name, int $order ): void {
		$term = get_term( $term_id, ComparisonCategory::TAXONOMY );

		/*
		 * Only when the name actually differs. `wp_update_term()` under Polylang
		 * rebuilds the term's translation group from whatever context it can find,
		 * and outside the admin there is none - so a call that changes nothing can
		 * still cost the term its links.
		 */
		if ( $term instanceof WP_Term && $term->name !== $name ) {
			wp_update_term( $term_id, ComparisonCategory::TAXONOMY, [ 'name' => $name ] );
		}

		update_term_meta( $term_id, 'sort_order', $order );
		update_term_meta( $term_id, self::HASH_META, $this->term_hash( $name, $order ) );
	}

	/**
	 * Has a person renamed or reordered this category since the import wrote it?
	 *
	 * The same rule as for topics, for the same reason: a category with no stored
	 * hash was not made here and is somebody else's to change.
	 *
	 * @param int $term_id Category id.
	 */
	private function term_hand_edited( int $term_id ): bool {
		$stored = (string) get_term_meta( $term_id, self::HASH_META, true );

		if ( '' === $stored ) {
			return true;
		}

		$term = get_term( $term_id, ComparisonCategory::TAXONOMY );

		if ( ! $term instanceof WP_Term ) {
			return true;
		}

		return $stored !== $this->term_hash( $term->name, (int) get_term_meta( $term_id, 'sort_order', true ) );
	}

	/**
	 * Hash of the two fields the seed owns on a category.
	 *
	 * @param string $name  Display name.
	 * @param int    $order Display order.
	 */
	private function term_hash( string $name, int $order ): string {
		$json = wp_json_encode(
			[
				'name'  => $name,
				'order' => $order,
			]
		);

		return md5( false === $json ? $name : $json );
	}

	/**
	 * Attach child categories to their parents, once every term of the language exists.
	 *
	 * @param array<int, string> $parents Term id => parent key.
	 * @param string             $lang    Language slug.
	 */
	private function apply_parents( array $parents, string $lang ): void {
		if ( $this->dry_run ) {
			return;
		}

		foreach ( $parents as $term_id => $parent_key ) {
			$parent_id = $this->term_map[ $parent_key ][ $lang ] ?? 0;

			if ( $parent_id > 0 && $parent_id !== $term_id ) {
				wp_update_term( $term_id, ComparisonCategory::TAXONOMY, [ 'parent' => $parent_id ] );
			}
		}
	}

	/**
	 * Create or refresh the topics of one language.
	 *
	 * @param string                           $lang Language slug.
	 * @param array<int, array<string, mixed>> $rows Topic rows from the file.
	 * @return array<string, int>
	 */
	private function import_topics( string $lang, array $rows ): array {
		$counts = [
			'topics_created'  => 0,
			'topics_updated'  => 0,
			'topics_existing' => 0,
			'topics_edited'   => 0,
		];

		foreach ( $rows as $row ) {
			$key   = isset( $row['key'] ) ? (string) $row['key'] : '';
			$title = isset( $row['title'] ) ? (string) $row['title'] : '';

			if ( '' === $key || '' === $title ) {
				continue;
			}

			$slug     = isset( $row['slug'] ) ? (string) $row['slug'] : sanitize_title( $title );
			$existing = $this->find_post( $key, $slug, $lang );

			if ( $existing instanceof WP_Post ) {
				$this->post_map[ $key ][ $lang ] = $existing->ID;

				if ( ! $this->dry_run ) {
					$this->adopt_post( $existing, $key, $lang );
				}

				if ( ! $this->overwrite ) {
					++$counts['topics_existing'];
					continue;
				}

				if ( $this->hand_edited( $existing->ID ) ) {
					++$counts['topics_edited'];
					continue;
				}

				if ( ! $this->dry_run ) {
					$this->write_post( $existing->ID, $row, $lang );
				}

				++$counts['topics_updated'];
				continue;
			}

			++$counts['topics_created'];

			if ( $this->dry_run ) {
				continue;
			}

			$post_id = wp_insert_post(
				[
					'post_type'   => ComparisonTopic::POST_TYPE,
					'post_status' => 'publish',
					'post_title'  => $title,
					'post_name'   => $slug,
				],
				true
			);

			if ( is_wp_error( $post_id ) ) {
				--$counts['topics_created'];
				continue;
			}

			$post_id = (int) $post_id;

			update_post_meta( $post_id, self::KEY_META, $key );
			$this->set_post_language( $post_id, $lang, true );
			$this->write_post( $post_id, $row, $lang );

			$this->post_map[ $key ][ $lang ] = $post_id;
		}

		return $counts;
	}

	/**
	 * Write a topic's data and record the hash of what was written.
	 *
	 * @param int                  $post_id Topic id.
	 * @param array<string, mixed> $row     Row from the file.
	 * @param string               $lang    Language slug.
	 */
	private function write_post( int $post_id, array $row, string $lang ): void {
		wp_update_post(
			[
				'ID'         => $post_id,
				'post_title' => isset( $row['title'] ) ? (string) $row['title'] : '',
			]
		);

		update_post_meta( $post_id, 'sort_order', isset( $row['sort_order'] ) ? (int) $row['sort_order'] : 0 );
		update_post_meta( $post_id, 'churches', $this->churches( $row ) );

		$term_ids = [];

		if ( isset( $row['categories'] ) && is_array( $row['categories'] ) ) {
			foreach ( $row['categories'] as $category_key ) {
				$term_id = $this->term_map[ (string) $category_key ][ $lang ] ?? 0;

				if ( $term_id > 0 ) {
					$term_ids[] = $term_id;
				}
			}
		}

		wp_set_object_terms( $post_id, $term_ids, ComparisonCategory::TAXONOMY );

		update_post_meta( $post_id, self::HASH_META, $this->hash_of_row( $row ) );
	}

	/**
	 * The churches array of one row, sanitised the same way the meta box does.
	 *
	 * @param array<string, mixed> $row Row from the file.
	 * @return array<int, array{church_name:string,description:string}>
	 */
	private function churches( array $row ): array {
		$out = [];

		if ( ! isset( $row['churches'] ) || ! is_array( $row['churches'] ) ) {
			return $out;
		}

		foreach ( $row['churches'] as $church ) {
			if ( ! is_array( $church ) ) {
				continue;
			}

			$out[] = [
				'church_name' => sanitize_text_field( isset( $church['church_name'] ) ? (string) $church['church_name'] : '' ),
				'description' => wp_kses_post( isset( $church['description'] ) ? (string) $church['description'] : '' ),
			];
		}

		return $out;
	}

	/**
	 * Has a person changed this topic since the import wrote it?
	 *
	 * A record with no stored hash was not made by the import - it is somebody's
	 * own content that happens to share a slug, and it is treated as edited,
	 * which is the safe reading.
	 *
	 * @param int $post_id Topic id.
	 */
	private function hand_edited( int $post_id ): bool {
		$stored = (string) get_post_meta( $post_id, self::HASH_META, true );

		if ( '' === $stored ) {
			return true;
		}

		return $stored !== $this->hash_of_post( $post_id );
	}

	/**
	 * Hash of the fields the seed owns, taken from a file row.
	 *
	 * Category assignment is deliberately not part of it: moving a topic between
	 * panels is arranging, not rewriting, and should not lock the text.
	 *
	 * @param array<string, mixed> $row Row from the file.
	 */
	private function hash_of_row( array $row ): string {
		return $this->hash(
			isset( $row['title'] ) ? (string) $row['title'] : '',
			isset( $row['sort_order'] ) ? (int) $row['sort_order'] : 0,
			$this->churches( $row )
		);
	}

	/**
	 * The same hash, taken from what the database holds now.
	 *
	 * @param int $post_id Topic id.
	 */
	private function hash_of_post( int $post_id ): string {
		$stored = get_post_meta( $post_id, 'churches', true );

		return $this->hash(
			(string) get_the_title( $post_id ),
			(int) get_post_meta( $post_id, 'sort_order', true ),
			$this->churches( [ 'churches' => is_array( $stored ) ? $stored : [] ] )
		);
	}

	/**
	 * Build the hash from the three fields, in one place so both sides agree.
	 *
	 * @param string                                                   $title      Topic title.
	 * @param int                                                      $sort_order Display order.
	 * @param array<int, array{church_name:string,description:string}> $churches   Church rows.
	 */
	private function hash( string $title, int $sort_order, array $churches ): string {
		$json = wp_json_encode(
			[
				'title'      => $title,
				'sort_order' => $sort_order,
				'churches'   => $churches,
			]
		);

		return md5( false === $json ? $title : $json );
	}

	/**
	 * Find a topic of this key in this language.
	 *
	 * The language is verified on each candidate rather than pushed into the
	 * query, and that is not defensive programming - it is the fix for a bug this
	 * code had. Polylang's query filters do not run under WP-CLI, so `lang` in a
	 * `get_posts()` call is silently ignored there. The first version trusted it,
	 * found the POLISH post while looking for the English one, and wrote a
	 * translation group in which all four languages pointed at the same post.
	 * Polylang then rebuilt the groups from that, and 37 topics lost their links.
	 *
	 * The key is tried before the slug. The slug fallback is what lets the import
	 * adopt content that is already there - the site the seed came from, or an
	 * earlier import made by the old one-off script.
	 *
	 * @param string $key  Cross-language identity.
	 * @param string $slug Slug in this language.
	 * @param string $lang Language slug.
	 */
	private function find_post( string $key, string $slug, string $lang ): ?WP_Post {
		$attempts = [
			[
				'meta_key'   => self::KEY_META,
				'meta_value' => $key,
			],
			[ 'name' => $slug ],
		];

		foreach ( $attempts as $args ) {
			foreach ( $this->query_posts( $args ) as $post ) {
				if ( Languages::post_speaks( $post->ID, $lang ) ) {
					return $post;
				}
			}
		}

		return null;
	}

	/**
	 * Topics matching one set of arguments, every language included.
	 *
	 * @param array<string, mixed> $args Extra query arguments.
	 * @return array<int, WP_Post>
	 */
	private function query_posts( array $args ): array {
		$posts = get_posts(
			array_merge(
				[
					'post_type'        => ComparisonTopic::POST_TYPE,
					'post_status'      => 'any',
					'numberposts'      => 20,
					'suppress_filters' => false,
				],
				$args
			)
		);

		return array_values( $posts );
	}

	/**
	 * Find a category of this key in this language, verifying the language itself.
	 *
	 * @param string $key  Cross-language identity.
	 * @param string $slug Slug in this language.
	 * @param string $lang Language slug.
	 */
	private function find_term( string $key, string $slug, string $lang ): ?WP_Term {
		$attempts = [
			[
				'meta_key'   => self::KEY_META,
				'meta_value' => $key,
			],
			[ 'slug' => $slug ],
		];

		foreach ( $attempts as $args ) {
			$terms = get_terms(
				array_merge(
					[
						'taxonomy'   => ComparisonCategory::TAXONOMY,
						'hide_empty' => false,
					],
					$args
				)
			);

			if ( ! is_array( $terms ) ) {
				continue;
			}

			foreach ( $terms as $term ) {
				if ( Languages::term_speaks( $term->term_id, $lang ) ) {
					return $term;
				}
			}
		}

		return null;
	}

	/**
	 * Make an existing topic recognisable to later runs, without changing its content.
	 *
	 * Two non-destructive repairs, both needed for state 3: record the key if it
	 * is missing, and give the post a language if Polylang now exists and the post
	 * has none.
	 *
	 * @param WP_Post $post Existing topic.
	 * @param string  $key  Cross-language identity.
	 * @param string  $lang Language slug.
	 */
	private function adopt_post( WP_Post $post, string $key, string $lang ): void {
		if ( '' === (string) get_post_meta( $post->ID, self::KEY_META, true ) ) {
			update_post_meta( $post->ID, self::KEY_META, $key );
		}

		$this->set_post_language( $post->ID, $lang );
	}

	/**
	 * The same two repairs for a category.
	 *
	 * @param WP_Term $term Existing category.
	 * @param string  $key  Cross-language identity.
	 * @param string  $lang Language slug.
	 */
	private function adopt_term( WP_Term $term, string $key, string $lang ): void {
		if ( '' === (string) get_term_meta( $term->term_id, self::KEY_META, true ) ) {
			update_term_meta( $term->term_id, self::KEY_META, $key );
		}

		$this->set_term_language( $term->term_id, $lang );
	}

	/**
	 * Give a topic its language.
	 *
	 * Two different needs, hence the flag, and getting this wrong is not
	 * theoretical - it was caught by a test. When Polylang is active it assigns
	 * the DEFAULT language to every newly inserted post by itself, so a post just
	 * created for the English file already claims to be Polish. A freshly created
	 * record therefore has to be told its language outright.
	 *
	 * Adoption is the opposite case: a post that already existed keeps whatever
	 * language it has, because overriding it would silently move somebody's
	 * content from one language version to another.
	 *
	 * @param int    $post_id Topic id.
	 * @param string $lang    Language slug.
	 * @param bool   $force   True when the record was just created by this import.
	 */
	private function set_post_language( int $post_id, string $lang, bool $force = false ): void {
		if ( ! Languages::available() || ! function_exists( 'pll_get_post_language' ) || ! function_exists( 'pll_set_post_language' ) ) {
			return;
		}

		if ( ! $force ) {
			$current = pll_get_post_language( $post_id );

			if ( is_string( $current ) && '' !== $current ) {
				return;
			}
		}

		pll_set_post_language( $post_id, $lang );
	}

	/**
	 * The same for a category, for the same two reasons.
	 *
	 * @param int    $term_id Category id.
	 * @param string $lang    Language slug.
	 * @param bool   $force   True when the term was just created by this import.
	 */
	private function set_term_language( int $term_id, string $lang, bool $force = false ): void {
		if ( ! Languages::available() || ! function_exists( 'pll_get_term_language' ) || ! function_exists( 'pll_set_term_language' ) ) {
			return;
		}

		if ( ! $force ) {
			$current = pll_get_term_language( $term_id );

			if ( is_string( $current ) && '' !== $current ) {
				return;
			}
		}

		pll_set_term_language( $term_id, $lang );
	}

	/**
	 * Tell Polylang which topics are translations of each other.
	 *
	 * @return int Number of groups linked.
	 */
	private function link_posts(): int {
		if ( ! function_exists( 'pll_save_post_translations' ) ) {
			return 0;
		}

		$linked = 0;

		foreach ( $this->post_map as $key => $group ) {
			$group = $this->complete_post_group( (string) $key, $group );

			if ( count( $group ) < 2 ) {
				continue;
			}

			pll_save_post_translations( $group );
			++$linked;
		}

		return $linked;
	}

	/**
	 * Add the languages this run did not import to a translation group.
	 *
	 * Without this, `--lang=uk` on a site that already has the other three
	 * languages recreates the Ukrainian topics as orphans: the group would hold
	 * only what this run touched, and Polylang would be told that Ukrainian has
	 * no translations. Found by a test that deleted one language and imported it
	 * back on its own.
	 *
	 * @param string             $key   Cross-language identity.
	 * @param array<string, int> $group Language => post id, as far as this run knows.
	 * @return array<string, int>
	 */
	private function complete_post_group( string $key, array $group ): array {
		foreach ( Languages::site() as $lang ) {
			if ( isset( $group[ $lang ] ) ) {
				continue;
			}

			$found = $this->locate_by_key( $key, $lang );

			if ( null !== $found ) {
				$group[ $lang ] = $found;
			}
		}

		return $group;
	}

	/**
	 * A topic carrying this key, in this language, or null.
	 *
	 * @param string $key  Cross-language identity.
	 * @param string $lang Language slug.
	 */
	private function locate_by_key( string $key, string $lang ): ?int {
		foreach ( $this->query_posts(
			[
				'meta_key'   => self::KEY_META,
				'meta_value' => $key,
			]
		) as $post ) {
			if ( Languages::post_speaks( $post->ID, $lang ) ) {
				return $post->ID;
			}
		}

		return null;
	}

	/**
	 * The same for categories.
	 *
	 * @return int Number of groups linked.
	 */
	private function link_terms(): int {
		if ( ! function_exists( 'pll_save_term_translations' ) ) {
			return 0;
		}

		$linked = 0;

		foreach ( $this->term_map as $key => $group ) {
			$group = $this->complete_term_group( (string) $key, $group );

			if ( count( $group ) < 2 ) {
				continue;
			}

			pll_save_term_translations( $group );
			++$linked;
		}

		return $linked;
	}

	/**
	 * The same completion for a category group. See `complete_post_group()`.
	 *
	 * @param string             $key   Cross-language identity.
	 * @param array<string, int> $group Language => term id.
	 * @return array<string, int>
	 */
	private function complete_term_group( string $key, array $group ): array {
		foreach ( Languages::site() as $lang ) {
			if ( isset( $group[ $lang ] ) ) {
				continue;
			}

			$terms = get_terms(
				[
					'taxonomy'   => ComparisonCategory::TAXONOMY,
					'hide_empty' => false,
					'meta_key'   => self::KEY_META,
					'meta_value' => $key,
				]
			);

			if ( ! is_array( $terms ) ) {
				continue;
			}

			foreach ( $terms as $term ) {
				if ( Languages::term_speaks( $term->term_id, $lang ) ) {
					$group[ $lang ] = $term->term_id;
					break;
				}
			}
		}

		return $group;
	}
}
