=== Comparison of Religions ===
Contributors: lukaszlasota
Tags: accordion, comparison, religions, churches
Requires at least: 6.4
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Dynamic accordion block for comparing religious denominations side by side.

== Description ==

Provides a custom post type (comparison_topic) with a churches repeater meta box
and a dynamic Gutenberg block that renders an accessible, responsive accordion
table comparing church teachings across multiple topics.

Features:

* Custom Post Type with repeater meta box for church data entry
* Taxonomy-based grouping (comparison_category) with sort order
* WCAG 2.1 AA accessible accordion (keyboard navigation, ARIA attributes)
* Responsive CSS Grid table (desktop) / stacked layout (mobile)
* 30-minute transient cache with automatic invalidation
* FAQ Schema (JSON-LD) for SEO
* Full English developer documentation

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Add comparison categories and topics via the admin menu.
4. Insert the "Comparison Accordion" block on any page.

== Changelog ==

= 1.0.0 =
* Initial release with dynamic accordion block.
* CPT, taxonomy, meta box, cache layer, and WCAG compliance.
