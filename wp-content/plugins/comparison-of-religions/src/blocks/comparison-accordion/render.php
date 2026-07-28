<?php
/**
 * Comparison Accordion Block — Server-side render template.
 *
 * This file is called by WordPress for each instance of the comparison-accordion
 * block on the frontend. It queries comparison_topic posts grouped by
 * comparison_category terms and renders a responsive accordion.
 *
 * HOW IT WORKS:
 * 1. Check transient cache — return cached HTML if available (30 min TTL).
 * 2. Query taxonomy terms (categories) sorted by term meta 'sort_order'.
 * 3. Query all published comparison_topic posts sorted by post meta 'sort_order'.
 * 4. Discover unique church names dynamically (supports 2+ churches).
 * 5. Render accordion: each category = collapsible panel with a CSS Grid table.
 *    - Desktop: multi-column grid with aligned paragraphs across churches.
 *    - Mobile (≤768px): stacked layout with church labels shown between groups.
 * 6. Append FAQ Schema (JSON-LD) for SEO rich snippets.
 * 7. Save rendered HTML to transient cache.
 *
 * Cache is invalidated by hooks in index.php when posts or terms are modified.
 *
 * @var array  $attributes Block attributes (e.g. selectedCategories).
 * @var string $content    Inner blocks content (unused — this is a dynamic block).
 *
 * @package ComparisonOfReligions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Block render templates are included in file scope by WordPress core.
// Variables here are NOT truly global — they are local to this template inclusion.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

use ComparisonOfReligions\PostTypes\ComparisonTopic;
use ComparisonOfReligions\Taxonomies\ComparisonCategory;

$selected_categories = $attributes['selectedCategories'] ?? [];

// --- Transient cache layer ---
// Skip cache during REST requests (editor preview via ServerSideRender)
// so the editor always shows fresh data when topics are edited.
$is_rest      = defined( 'REST_REQUEST' ) && REST_REQUEST;
$cache_key    = \ComparisonOfReligions\Cache\AccordionCache::key( $attributes );
$cache_expire = \ComparisonOfReligions\Cache\AccordionCache::TTL;

if ( ! $is_rest ) {
	$cached = get_transient( $cache_key );
	if ( false !== $cached ) {
		echo $cached; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		return;
	}
}

// Start output buffering — everything rendered below will be captured and cached.
ob_start();

// --- Step 1: Fetch taxonomy terms (accordion panel headings) ---
// Sorted by term meta 'sort_order' (set in admin when editing the term).
$tax_args = [
	'taxonomy'   => ComparisonCategory::TAXONOMY,
	'hide_empty' => true,
	'meta_key'   => 'sort_order',
	'orderby'    => 'meta_value_num',
	'order'      => 'ASC',
];
if ( ! empty( $selected_categories ) ) {
	$tax_args['include'] = array_map( 'absint', $selected_categories );
}
$categories = get_terms( $tax_args );

if ( empty( $categories ) || is_wp_error( $categories ) ) {
	return;
}

// --- Step 2: Fetch all published comparison_topic posts ---
// Sorted by post meta 'sort_order' to control display order within each category.
$query_args = [
	'post_type'      => ComparisonTopic::POST_TYPE,
	'posts_per_page' => -1,
	'post_status'    => 'publish',
	'meta_key'       => 'sort_order',
	'orderby'        => 'meta_value_num',
	'order'          => 'ASC',
	'no_found_rows'  => true,
	'cache_results'  => false,
];
if ( ! empty( $selected_categories ) ) {
	$query_args['tax_query'] = [
		[
			'taxonomy' => ComparisonCategory::TAXONOMY,
			'terms'    => array_map( 'absint', $selected_categories ),
		],
	];
}
$all_topics = get_posts( $query_args );

if ( empty( $all_topics ) ) {
	return;
}

// --- Step 3: Discover unique church names ---
// Scans all topics to find distinct church names in order of first appearance.
// This makes the plugin flexible — works with 2, 3, or more churches without
// any hardcoded column configuration.
$church_names = [];
foreach ( $all_topics as $topic ) {
	$churches = get_post_meta( $topic->ID, 'churches', true );
	if ( is_array( $churches ) ) {
		foreach ( $churches as $church ) {
			$name = $church['church_name'] ?? '';
			if ( '' !== $name && ! in_array( $name, $church_names, true ) ) {
				$church_names[] = $name;
			}
		}
	}
}

$church_count = count( $church_names );
if ( 0 === $church_count ) {
	return;
}

// --- Step 4: Render the accordion HTML ---
// The CSS custom property --cor-church-count drives the grid column count
// in style.scss (e.g. repeat(var(--cor-church-count), 1fr)).
$wrapper_attrs = get_block_wrapper_attributes(
	[
		'class' => 'cor-accordion',
		'style' => '--cor-church-count: ' . $church_count,
	]
);
?>

<div <?php echo $wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by get_block_wrapper_attributes. ?>>
	<?php
	foreach ( $categories as $category ) :
		$content_id = wp_unique_id( 'cor-panel-' );
		$heading_id = wp_unique_id( 'cor-heading-' );

		// Filter the master topics list to only those assigned to this category.
		$category_topics = array_filter(
			$all_topics,
			function ( $topic ) use ( $category ) {
				return has_term( $category->term_id, ComparisonCategory::TAXONOMY, $topic );
			}
		);

		if ( empty( $category_topics ) ) {
			continue;
		}
		?>
	<div class="cor-accordion__item">
		<h2 class="cor-accordion__heading" id="<?php echo esc_attr( $heading_id ); ?>">
			<button
				class="cor-accordion__trigger"
				aria-expanded="false"
				aria-controls="<?php echo esc_attr( $content_id ); ?>"
			>
				<?php echo esc_html( $category->name ); ?>
			</button>
		</h2>

		<div
			class="cor-accordion__panel"
			id="<?php echo esc_attr( $content_id ); ?>"
			role="region"
			aria-labelledby="<?php echo esc_attr( $heading_id ); ?>"
			hidden
		>
			<div class="cor-accordion__panel-inner">
				<!-- Column header — a visual aid for sighted users only (aria-hidden).
					Screen-reader users get per-description church labels below instead,
					so this flat CSS-grid layout carries no (invalid) ARIA table roles. -->
				<div class="cor-table__header" aria-hidden="true">
					<div class="cor-table__cell cor-table__cell--topic">
						<?php esc_html_e( 'Temat', 'comparison-of-religions' ); ?>
					</div>
					<?php foreach ( $church_names as $name ) : ?>
					<div class="cor-table__cell cor-table__cell--church">
						<?php echo esc_html( $name ); ?>
					</div>
					<?php endforeach; ?>
				</div>

				<!-- Topic rows — rendered as a single CSS Grid per category.
					Each topic's cells use inline grid-column/grid-row for precise placement.
					On mobile (display: block), inline grid styles are ignored and elements
					flow naturally: topic → church label → paragraphs → next church label → paragraphs. -->
				<div class="cor-table__body">
				<?php $current_row = 1; ?>
				<?php
				foreach ( $category_topics as $topic ) :
					$churches            = get_post_meta( $topic->ID, 'churches', true );
					$churches_paragraphs = [];
					$max_paragraphs      = 0;

					// Parse each church's HTML description into individual paragraphs.
					// This allows paragraph-level alignment across churches in the grid
					// (e.g. church A paragraph 1 aligns with church B paragraph 1).
					if ( is_array( $churches ) ) {
						foreach ( $churches as $c ) {
							$name = $c['church_name'] ?? '';
							$desc = $c['description'] ?? '';
							// Extract content between <p> tags; fall back to raw text if no <p> tags.
							preg_match_all( '/<p[^>]*>(.*?)<\/p>/si', $desc, $matches );
							$paras                        = ! empty( $matches[1] ) ? $matches[1] : ( '' !== trim( wp_strip_all_tags( $desc ) ) ? [ $desc ] : [] );
							$churches_paragraphs[ $name ] = $paras;
							$max_paragraphs               = max( $max_paragraphs, count( $paras ) );
						}
					}

					if ( 0 === $max_paragraphs ) {
						$max_paragraphs = 1;
					}
					?>
					<div
						class="cor-table__cell cor-table__cell--topic"
						style="grid-column: 1; grid-row: <?php echo (int) $current_row; ?> / span <?php echo (int) $max_paragraphs; ?>"
					>
						<?php echo esc_html( $topic->post_title ); ?>
					</div>
					<?php
					foreach ( $church_names as $church_idx => $name ) :
						$paras = $churches_paragraphs[ $name ] ?? [];
						$col   = $church_idx + 2;
						?>
					<div class="cor-table__church-label"><?php echo esc_html( $name ); ?></div>
						<?php
						for ( $p = 0; $p < $max_paragraphs; $p++ ) :
							$para = $paras[ $p ] ?? '';
							$row  = $current_row + $p;
							?>
					<div
						class="cor-table__cell cor-table__cell--church"
						style="grid-column: <?php echo (int) $col; ?>; grid-row: <?php echo (int) $row; ?>"
					>
							<?php if ( '' !== $para ) : ?>
							<p><?php echo wp_kses_post( $para ); ?></p>
						<?php endif; ?>
					</div>
						<?php endfor; ?>
					<?php endforeach; ?>
					<?php
					$current_row += $max_paragraphs;
				endforeach;
				?>
				</div>
			</div>
		</div>
	</div>
	<?php endforeach; ?>
</div>
<?php
// --- Step 5: FAQ Schema (JSON-LD) for Google rich snippets ---
// Each topic becomes a Question, with all church descriptions combined as the Answer.
// This helps search engines display the content as an FAQ in search results.
$faq_items = [];
foreach ( $all_topics as $topic ) {
	$churches = get_post_meta( $topic->ID, 'churches', true );
	if ( ! is_array( $churches ) || empty( $churches ) ) {
		continue;
	}

	// Combine all church descriptions into one plain-text answer.
	$answer_parts = [];
	foreach ( $churches as $c ) {
		$c_name = $c['church_name'] ?? '';
		$c_desc = $c['description'] ?? '';
		$text   = wp_strip_all_tags( $c_desc );
		if ( '' !== $c_name && '' !== trim( $text ) ) {
			$answer_parts[] = $c_name . ': ' . trim( $text );
		}
	}

	if ( ! empty( $answer_parts ) ) {
		$faq_items[] = [
			'@type'          => 'Question',
			'name'           => $topic->post_title,
			'acceptedAnswer' => [
				'@type' => 'Answer',
				'text'  => implode( ' ', $answer_parts ),
			],
		];
	}
}

if ( ! empty( $faq_items ) ) :
	$schema = [
		'@context'   => 'https://schema.org',
		'@type'      => 'FAQPage',
		'mainEntity' => $faq_items,
	];
	?>
<script type="application/ld+json"><?php echo wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP ); ?></script>
<?php endif; ?>
<?php
// --- Step 6: Save rendered HTML to transient cache and output ---
$output = ob_get_clean();
if ( ! $is_rest ) {
	set_transient( $cache_key, $output, $cache_expire );
}
echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?>
