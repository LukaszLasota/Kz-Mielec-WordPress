# Navigable Tiles Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement unified `navigable-tiles` block + supporting infrastructure for two parallel sections (Meetings + Beliefs) with single source of truth, WCAG 2.1 AA compliance, and full project standards conformance.

**Architecture:** One block with `dataSource` attribute handles both meetings (CPT) and beliefs (Pages + Options). Theme templates (`page-belief.php`, `archive-meetings.php`) auto-render belief subpages and CPT archive. Admin UI: meta boxes for each data source + BeliefSettings admin page with Sortable.js.

**Tech Stack:** WordPress 6.x, PHP 8.1+, PSR-4 autoloading, wp-scripts (webpack), SCSS, Gutenberg blocks API v3, native WP `register_meta()` (no ACF), Sortable.js for drag-and-drop reorder.

**Spec reference:** `docs/superpowers/specs/2026-05-18-navigable-tiles-design.md`

---

## File Structure

### Plugin custom-block-package (new files)

- `app/Admin/MeetingMeta.php` — Meta box for CPT meetings (4 fields: hover_image, day_hour, place, anchor)
- `app/Services/NavigableTilesService.php` — Data source abstraction (get_meetings, get_beliefs)
- `src/blocks/navigable-tiles/block.json` — Block metadata
- `src/blocks/navigable-tiles/index.js` — Block registration
- `src/blocks/navigable-tiles/edit.js` — Editor UI with ServerSideRender
- `src/blocks/navigable-tiles/render.php` — Server-side render with router
- `src/blocks/navigable-tiles/style.scss` — Frontend + editor styles
- `src/blocks/navigable-tiles/index.scss` — Editor-only styles (imports style.scss)

### Plugin custom-block-package (modifications)

- `app/Cache/BlockCache.php` — Add `NAVIGABLE_TILES_PREFIX` constant
- `index.php` — Register MeetingMeta, add cache invalidation hooks

### Theme kzmielec (new files)

- `App/Admin/BeliefSettings.php` — Admin subpage with multi-select + Sortable.js
- `App/Admin/BeliefPageMeta.php` — Meta box for belief pages (1 field: hover_image)
- `page-belief.php` — Template "Strona wiary" for belief subpages
- `archive-meetings.php` — Auto-render meetings archive at `/zaplanuj-wizyte/`
- `webpack/src/patterns/page-belief/style.scss` — Belief page template styles
- `webpack/src/patterns/archive-meetings/style.scss` — Meetings archive styles
- `webpack/src/admin/belief-settings.ts` — Sortable.js init for admin page

### Theme kzmielec (modifications)

- `App/Theme.php` — Register BeliefSettings, BeliefPageMeta
- `webpack/webpack.common.js` — Add admin entry point for belief-settings
- `webpack/package.json` — Add sortablejs dependency

### Content tasks (no code, WP-CLI / Admin)

- Activate `custom-posts` plugin
- Set old "Zaplanuj wizytę" page (ID 92) to draft
- Assign "Strona wiary" template to 8 belief pages
- Configure BeliefSettings (page order)
- Create 3 meeting CPT posts (Nabożeństwo Główne, Mała Kawka, Studium Słowa)

---

## Task 1: Add NAVIGABLE_TILES_PREFIX to BlockCache

**Files:**
- Modify: `wp-content/plugins/custom-block-package/app/Cache/BlockCache.php`

- [ ] **Step 1: Read current BlockCache file**

Run: `cat /home/lukasz/projects/kzmielec/wp-content/plugins/custom-block-package/app/Cache/BlockCache.php | head -50`
Expected: See existing `NEWS_SLIDER_PREFIX`, `MEETING_LIST_PREFIX`, `FACEBOOK_FEED_PREFIX` constants.

- [ ] **Step 2: Add new constant after FACEBOOK_FEED_PREFIX**

Edit `app/Cache/BlockCache.php`, insert after `public const FACEBOOK_FEED_PREFIX` line:

```php
	/**
	 * Cache key prefix for the navigable tiles block.
	 *
	 * @var string
	 */
	public const NAVIGABLE_TILES_PREFIX = 'cbp_navigable_tiles_v1_';
```

- [ ] **Step 3: Verify PHPStan + PHPCS pass**

Run: `ddev exec "cd wp-content/plugins/custom-block-package && ./vendor/bin/phpstan analyse app/Cache/BlockCache.php && ./vendor/bin/phpcs --standard=phpcs.xml --extensions=php app/Cache/BlockCache.php"`
Expected: PHPStan OK, PHPCS 0 errors.

- [ ] **Step 4: Commit**

```bash
git add wp-content/plugins/custom-block-package/app/Cache/BlockCache.php
git commit -m "Add NAVIGABLE_TILES_PREFIX to BlockCache"
```

---

## Task 2: Activate custom-posts plugin and verify CPT

**Files:** (no code changes, plugin activation only)

- [ ] **Step 1: Check plugin file exists**

Run: `ls /home/lukasz/projects/kzmielec/wp-content/plugins/custom-posts/`
Expected: directory exists with PHP files.

- [ ] **Step 2: Verify CptBuilder constructor in RegisterPosts.php**

Run: `grep -n "CptBuilder('meetings'" /home/lukasz/projects/kzmielec/wp-content/plugins/custom-posts/src/Posts/RegisterPosts.php`
Expected: Match found with args including archive slug.

- [ ] **Step 3: Check archive slug — should be 'zaplanuj-wizyte'**

If not, edit `wp-content/plugins/custom-posts/src/Posts/RegisterPosts.php`:

```php
new CptBuilder('meetings', $labels, 5, 'zaplanuj-wizyte');
```

The 4th argument is the archive slug. If existing value differs, change it.

- [ ] **Step 4: Activate plugin**

Run: `ddev wp plugin activate custom-posts`
Expected: `Plugin 'custom-posts' activated.`

- [ ] **Step 5: Flush rewrite rules**

Run: `ddev wp rewrite flush`
Expected: `Success: Rewrite rules flushed.`

- [ ] **Step 6: Verify CPT registered**

Run: `ddev wp post-type list --format=table | grep meetings`
Expected: Row showing `meetings` post type.

- [ ] **Step 7: Verify archive URL**

Run: `ddev exec "curl -s -o /dev/null -w '%{http_code}\n' https://kzmielec.ddev.site/zaplanuj-wizyte/"`
Expected: `200` (page renders) or `404` (no posts yet — that's also OK, archive exists).

- [ ] **Step 8: No commit needed** (plugin activation is database state)

---

## Task 3: Set old "Zaplanuj wizytę" page (ID 92) to draft

**Files:** (database state change)

- [ ] **Step 1: Find page**

Run: `ddev wp post list --post_type=page --name=zaplanuj-wizyte --format=table`
Expected: Row with ID and status. Note the ID (might not be 92 in this DB).

- [ ] **Step 2: Set to draft (replace ID with actual)**

Run: `ddev wp post update <ID> --post_status=draft`
Expected: `Success: Updated post <ID>.`

- [ ] **Step 3: Re-test archive URL**

Run: `ddev exec "curl -s -o /dev/null -w '%{http_code}\n' https://kzmielec.ddev.site/zaplanuj-wizyte/"`
Expected: `200`.

- [ ] **Step 4: No commit needed**

---

## Task 4: Create MeetingMeta class

**Files:**
- Create: `wp-content/plugins/custom-block-package/app/Admin/MeetingMeta.php`

- [ ] **Step 1: Create file**

Write `wp-content/plugins/custom-block-package/app/Admin/MeetingMeta.php`:

```php
<?php
/**
 * Meta box and meta field registration for CPT meetings.
 *
 * @package CustomBlockPackage
 */

declare(strict_types=1);

namespace CustomBlockPackage\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MeetingMeta
 *
 * Registers post meta and admin meta box for the meetings CPT.
 */
class MeetingMeta {

	/**
	 * Meta key: hover image attachment ID.
	 */
	public const META_HOVER_IMAGE = '_meeting_hover_image';

	/**
	 * Meta key: day and hour text.
	 */
	public const META_DAY_HOUR = '_meeting_day_hour';

	/**
	 * Meta key: place text.
	 */
	public const META_PLACE = '_meeting_place';

	/**
	 * Meta key: anchor ID for cross-page links.
	 */
	public const META_ANCHOR = '_meeting_anchor';

	/**
	 * Nonce action name.
	 */
	private const NONCE_ACTION = 'cbp_meeting_meta_save';

	/**
	 * Nonce field name.
	 */
	private const NONCE_FIELD = 'cbp_meeting_meta_nonce';

	/**
	 * Constructor — register hooks.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_meta_fields' ) );
		add_action( 'add_meta_boxes_meetings', array( $this, 'add_meta_box' ) );
		add_action( 'save_post_meetings', array( $this, 'save_meta' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register meta fields with REST support.
	 *
	 * @return void
	 */
	public function register_meta_fields(): void {
		$common = array(
			'object_subtype' => 'meetings',
			'single'         => true,
			'show_in_rest'   => true,
			'auth_callback'  => static function (): bool {
				return current_user_can( 'edit_posts' );
			},
		);

		register_post_meta( 'meetings', self::META_HOVER_IMAGE, array_merge( $common, array( 'type' => 'integer', 'default' => 0 ) ) );
		register_post_meta( 'meetings', self::META_DAY_HOUR, array_merge( $common, array( 'type' => 'string', 'default' => '' ) ) );
		register_post_meta( 'meetings', self::META_PLACE, array_merge( $common, array( 'type' => 'string', 'default' => '' ) ) );
		register_post_meta( 'meetings', self::META_ANCHOR, array_merge( $common, array( 'type' => 'string', 'default' => '' ) ) );
	}

	/**
	 * Register meta box.
	 *
	 * @return void
	 */
	public function add_meta_box(): void {
		add_meta_box(
			'meeting_details',
			__( 'Szczegóły spotkania', 'custom-block-package' ),
			array( $this, 'render_meta_box' ),
			'meetings',
			'side',
			'high'
		);
	}

	/**
	 * Enqueue media library scripts on meetings edit screen.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'meetings' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_media();
	}

	/**
	 * Render meta box content.
	 *
	 * @param \WP_Post $post Current post.
	 * @return void
	 */
	public function render_meta_box( \WP_Post $post ): void {
		$hover_image_id = (int) get_post_meta( $post->ID, self::META_HOVER_IMAGE, true );
		$day_hour       = (string) get_post_meta( $post->ID, self::META_DAY_HOUR, true );
		$place          = (string) get_post_meta( $post->ID, self::META_PLACE, true );
		$anchor         = (string) get_post_meta( $post->ID, self::META_ANCHOR, true );

		$hover_image_url = $hover_image_id ? (string) wp_get_attachment_image_url( $hover_image_id, 'thumbnail' ) : '';

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );
		?>
		<p>
			<label for="cbp_meeting_day_hour"><strong><?php esc_html_e( 'Dzień i godzina', 'custom-block-package' ); ?></strong></label>
			<input type="text" id="cbp_meeting_day_hour" name="cbp_meeting_day_hour" value="<?php echo esc_attr( $day_hour ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'np. Niedziela 10:30', 'custom-block-package' ); ?>">
		</p>

		<p>
			<label for="cbp_meeting_place"><strong><?php esc_html_e( 'Miejsce', 'custom-block-package' ); ?></strong></label>
			<input type="text" id="cbp_meeting_place" name="cbp_meeting_place" value="<?php echo esc_attr( $place ); ?>" class="widefat" placeholder="ul. Dąbrowskiego 1a">
		</p>

		<p>
			<label for="cbp_meeting_anchor"><strong><?php esc_html_e( 'Anchor ID', 'custom-block-package' ); ?></strong></label>
			<input type="text" id="cbp_meeting_anchor" name="cbp_meeting_anchor" value="<?php echo esc_attr( $anchor ); ?>" class="widefat" placeholder="10">
			<span class="description"><?php esc_html_e( 'Liczba lub identyfikator do linku /zaplanuj-wizyte/#anchor', 'custom-block-package' ); ?></span>
		</p>

		<p>
			<label><strong><?php esc_html_e( 'Hover image', 'custom-block-package' ); ?></strong></label>
			<input type="hidden" id="cbp_meeting_hover_image" name="cbp_meeting_hover_image" value="<?php echo esc_attr( (string) $hover_image_id ); ?>">
			<span class="cbp-meeting-hover-preview" style="display:block;margin:6px 0;">
				<?php if ( $hover_image_url ) : ?>
					<img src="<?php echo esc_url( $hover_image_url ); ?>" style="max-width:120px;height:auto;" alt="">
				<?php endif; ?>
			</span>
			<button type="button" class="button cbp-meeting-hover-select"><?php esc_html_e( 'Wybierz obraz', 'custom-block-package' ); ?></button>
			<button type="button" class="button cbp-meeting-hover-remove"<?php echo $hover_image_id ? '' : ' style="display:none"'; ?>><?php esc_html_e( 'Usuń', 'custom-block-package' ); ?></button>
		</p>

		<script>
		(function ($) {
			$(function () {
				var frame;
				$('.cbp-meeting-hover-select').on('click', function (e) {
					e.preventDefault();
					if (frame) { frame.open(); return; }
					frame = wp.media({
						title: <?php echo wp_json_encode( __( 'Wybierz hover image', 'custom-block-package' ) ); ?>,
						button: { text: <?php echo wp_json_encode( __( 'Użyj', 'custom-block-package' ) ); ?> },
						multiple: false,
						library: { type: 'image' }
					});
					frame.on('select', function () {
						var attachment = frame.state().get('selection').first().toJSON();
						$('#cbp_meeting_hover_image').val(attachment.id);
						$('.cbp-meeting-hover-preview').html('<img src="' + attachment.url + '" style="max-width:120px;height:auto;" alt="">');
						$('.cbp-meeting-hover-remove').show();
					});
					frame.open();
				});
				$('.cbp-meeting-hover-remove').on('click', function (e) {
					e.preventDefault();
					$('#cbp_meeting_hover_image').val('0');
					$('.cbp-meeting-hover-preview').empty();
					$(this).hide();
				});
			});
		})(jQuery);
		</script>
		<?php
	}

	/**
	 * Save meta box data.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function save_meta( int $post_id ): void {
		if ( ! isset( $_POST[ self::NONCE_FIELD ] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) );
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$day_hour = isset( $_POST['cbp_meeting_day_hour'] ) ? sanitize_text_field( wp_unslash( $_POST['cbp_meeting_day_hour'] ) ) : '';
		$place    = isset( $_POST['cbp_meeting_place'] ) ? sanitize_text_field( wp_unslash( $_POST['cbp_meeting_place'] ) ) : '';
		$anchor   = isset( $_POST['cbp_meeting_anchor'] ) ? sanitize_html_class( wp_unslash( $_POST['cbp_meeting_anchor'] ) ) : '';
		$hover    = isset( $_POST['cbp_meeting_hover_image'] ) ? absint( $_POST['cbp_meeting_hover_image'] ) : 0;

		update_post_meta( $post_id, self::META_DAY_HOUR, $day_hour );
		update_post_meta( $post_id, self::META_PLACE, $place );
		update_post_meta( $post_id, self::META_ANCHOR, $anchor );
		update_post_meta( $post_id, self::META_HOVER_IMAGE, $hover );
	}
}
```

- [ ] **Step 2: Verify PHPStan + PHPCS pass**

Run: `ddev exec "cd wp-content/plugins/custom-block-package && ./vendor/bin/phpstan analyse app/Admin/MeetingMeta.php && ./vendor/bin/phpcs --standard=phpcs.xml --extensions=php app/Admin/MeetingMeta.php"`
Expected: 0 errors.

- [ ] **Step 3: Commit**

```bash
git add wp-content/plugins/custom-block-package/app/Admin/MeetingMeta.php
git commit -m "Add MeetingMeta admin class for CPT meetings"
```

---

## Task 5: Register MeetingMeta in plugin bootstrap

**Files:**
- Modify: `wp-content/plugins/custom-block-package/index.php`

- [ ] **Step 1: Add use statement**

In `wp-content/plugins/custom-block-package/index.php`, find the existing `use CustomBlockPackage\...` block and add:

```php
use CustomBlockPackage\Admin\MeetingMeta;
```

(Keep alphabetical order: Admin, Assets, Blocks, Cache, Cron, Rest, Services)

- [ ] **Step 2: Instantiate MeetingMeta after existing classes**

Find the section instantiating classes (`new RegisterBlocks();` etc.) and add:

```php
if ( is_admin() ) {
	new MeetingMeta();
}
```

- [ ] **Step 3: Verify file syntax**

Run: `ddev exec "php -l wp-content/plugins/custom-block-package/index.php"`
Expected: `No syntax errors detected`.

- [ ] **Step 4: Verify in WP Admin**

Open in browser: `https://kzmielec.ddev.site/wp-admin/edit.php?post_type=meetings`

Click "Add New" → verify "Szczegóły spotkania" meta box appears on the right side.

- [ ] **Step 5: Commit**

```bash
git add wp-content/plugins/custom-block-package/index.php
git commit -m "Register MeetingMeta in plugin bootstrap"
```

---

## Task 6: Create BeliefPageMeta class in theme

**Files:**
- Create: `wp-content/themes/kzmielec/App/Admin/BeliefPageMeta.php`

- [ ] **Step 1: Create file**

Write `wp-content/themes/kzmielec/App/Admin/BeliefPageMeta.php`:

```php
<?php
/**
 * Meta box and meta field for belief subpages.
 *
 * @package Kzmielec
 */

declare(strict_types=1);

namespace Kzmielec\Admin;

use Kzmielec\Interfaces\ActionHookInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BeliefPageMeta
 *
 * Adds hover image meta field to pages using "page-belief.php" template.
 */
class BeliefPageMeta implements ActionHookInterface {

	/**
	 * Meta key for hover image attachment ID.
	 */
	public const META_HOVER_IMAGE = '_belief_hover_image';

	/**
	 * Template filename for belief pages.
	 */
	private const TEMPLATE_FILE = 'page-belief.php';

	/**
	 * Nonce action name.
	 */
	private const NONCE_ACTION = 'kzmielec_belief_meta_save';

	/**
	 * Nonce field name.
	 */
	private const NONCE_FIELD = 'kzmielec_belief_meta_nonce';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register_add_action();
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register_add_action(): void {
		add_action( 'init', array( $this, 'register_meta_field' ) );
		add_action( 'add_meta_boxes_page', array( $this, 'add_meta_box' ) );
		add_action( 'save_post_page', array( $this, 'save_meta' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register hover image meta with REST support.
	 *
	 * @return void
	 */
	public function register_meta_field(): void {
		register_post_meta(
			'page',
			self::META_HOVER_IMAGE,
			array(
				'object_subtype' => 'page',
				'single'         => true,
				'type'           => 'integer',
				'default'        => 0,
				'show_in_rest'   => true,
				'auth_callback'  => static function (): bool {
					return current_user_can( 'edit_pages' );
				},
			)
		);
	}

	/**
	 * Register meta box only for pages using the belief template.
	 *
	 * @param \WP_Post $post Current post.
	 * @return void
	 */
	public function add_meta_box( \WP_Post $post ): void {
		$template = get_page_template_slug( $post->ID );
		if ( self::TEMPLATE_FILE !== $template ) {
			return;
		}

		add_meta_box(
			'belief_page_meta',
			__( 'Strona wiary', 'kzmielec' ),
			array( $this, 'render_meta_box' ),
			'page',
			'side',
			'default'
		);
	}

	/**
	 * Enqueue media library on page edit screens.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}
		wp_enqueue_media();
	}

	/**
	 * Render meta box.
	 *
	 * @param \WP_Post $post Current post.
	 * @return void
	 */
	public function render_meta_box( \WP_Post $post ): void {
		$hover_image_id  = (int) get_post_meta( $post->ID, self::META_HOVER_IMAGE, true );
		$hover_image_url = $hover_image_id ? (string) wp_get_attachment_image_url( $hover_image_id, 'thumbnail' ) : '';

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );
		?>
		<p>
			<label><strong><?php esc_html_e( 'Hover image', 'kzmielec' ); ?></strong></label>
			<input type="hidden" id="kzmielec_belief_hover_image" name="kzmielec_belief_hover_image" value="<?php echo esc_attr( (string) $hover_image_id ); ?>">
			<span class="kzmielec-belief-hover-preview" style="display:block;margin:6px 0;">
				<?php if ( $hover_image_url ) : ?>
					<img src="<?php echo esc_url( $hover_image_url ); ?>" style="max-width:120px;height:auto;" alt="">
				<?php endif; ?>
			</span>
			<button type="button" class="button kzmielec-belief-hover-select"><?php esc_html_e( 'Wybierz obraz', 'kzmielec' ); ?></button>
			<button type="button" class="button kzmielec-belief-hover-remove"<?php echo $hover_image_id ? '' : ' style="display:none"'; ?>><?php esc_html_e( 'Usuń', 'kzmielec' ); ?></button>
			<span class="description" style="display:block;margin-top:6px;">
				<?php esc_html_e( 'Drugi obraz wyświetlany przy najechaniu myszką na kafelek w nawigacji wiary.', 'kzmielec' ); ?>
			</span>
		</p>

		<script>
		(function ($) {
			$(function () {
				var frame;
				$('.kzmielec-belief-hover-select').on('click', function (e) {
					e.preventDefault();
					if (frame) { frame.open(); return; }
					frame = wp.media({
						title: <?php echo wp_json_encode( __( 'Wybierz hover image', 'kzmielec' ) ); ?>,
						button: { text: <?php echo wp_json_encode( __( 'Użyj', 'kzmielec' ) ); ?> },
						multiple: false,
						library: { type: 'image' }
					});
					frame.on('select', function () {
						var a = frame.state().get('selection').first().toJSON();
						$('#kzmielec_belief_hover_image').val(a.id);
						$('.kzmielec-belief-hover-preview').html('<img src="' + a.url + '" style="max-width:120px;height:auto;" alt="">');
						$('.kzmielec-belief-hover-remove').show();
					});
					frame.open();
				});
				$('.kzmielec-belief-hover-remove').on('click', function (e) {
					e.preventDefault();
					$('#kzmielec_belief_hover_image').val('0');
					$('.kzmielec-belief-hover-preview').empty();
					$(this).hide();
				});
			});
		})(jQuery);
		</script>
		<?php
	}

	/**
	 * Save meta on page save.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function save_meta( int $post_id ): void {
		if ( ! isset( $_POST[ self::NONCE_FIELD ] ) ) {
			return;
		}
		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) );
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$hover = isset( $_POST['kzmielec_belief_hover_image'] ) ? absint( $_POST['kzmielec_belief_hover_image'] ) : 0;
		update_post_meta( $post_id, self::META_HOVER_IMAGE, $hover );
	}
}
```

- [ ] **Step 2: Commit**

```bash
git add wp-content/themes/kzmielec/App/Admin/BeliefPageMeta.php
git commit -m "Add BeliefPageMeta theme class for belief page hover image"
```

---

## Task 7: Create BeliefSettings admin page

**Files:**
- Create: `wp-content/themes/kzmielec/App/Admin/BeliefSettings.php`

- [ ] **Step 1: Create file**

Write `wp-content/themes/kzmielec/App/Admin/BeliefSettings.php`:

```php
<?php
/**
 * Belief Settings admin page.
 *
 * Stores ordered list of belief subpage IDs in wp_options.
 *
 * @package Kzmielec
 */

declare(strict_types=1);

namespace Kzmielec\Admin;

use Kzmielec\Interfaces\ActionHookInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BeliefSettings
 *
 * Subpage of ThemeSettingsPage with drag-and-drop multi-select for belief pages.
 */
class BeliefSettings implements ActionHookInterface {

	/**
	 * Option key for belief page IDs.
	 */
	public const OPTION_BELIEF_PAGES = 'kzmielec_belief_pages';

	/**
	 * Menu slug.
	 */
	public const MENU_SLUG = 'kzmielec-belief-settings';

	/**
	 * Parent menu slug (ThemeSettingsPage).
	 */
	private const PARENT_SLUG = 'kzmielec-theme-settings';

	/**
	 * Nonce action.
	 */
	private const NONCE_ACTION = 'kzmielec_belief_settings_save';

	/**
	 * Nonce field.
	 */
	private const NONCE_FIELD = 'kzmielec_belief_settings_nonce';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register_add_action();
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register_add_action(): void {
		add_action( 'admin_menu', array( $this, 'add_submenu' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Add submenu under ThemeSettingsPage.
	 *
	 * @return void
	 */
	public function add_submenu(): void {
		add_submenu_page(
			self::PARENT_SLUG,
			__( 'Wiara', 'kzmielec' ),
			__( 'Wiara', 'kzmielec' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue Sortable.js on this admin page.
	 *
	 * @param string $hook Current admin hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		if ( false === strpos( $hook, self::MENU_SLUG ) ) {
			return;
		}

		$asset_path = get_template_directory() . '/assets/js/admin/belief-settings.js';
		$asset_uri  = get_template_directory_uri() . '/assets/js/admin/belief-settings.js';

		if ( file_exists( $asset_path ) ) {
			wp_enqueue_script(
				'kzmielec-belief-settings',
				$asset_uri,
				array( 'jquery' ),
				(string) filemtime( $asset_path ),
				true
			);
		}
	}

	/**
	 * Render admin page.
	 *
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'kzmielec' ) );
		}

		$this->handle_form_submission();

		$selected_ids = (array) get_option( self::OPTION_BELIEF_PAGES, array() );
		$selected_ids = array_filter( array_map( 'intval', $selected_ids ) );

		$all_pages = get_pages(
			array(
				'sort_column' => 'post_title',
				'sort_order'  => 'ASC',
			)
		);

		$selected_pages    = array();
		$unselected_pages  = array();
		foreach ( $all_pages as $page ) {
			if ( in_array( $page->ID, $selected_ids, true ) ) {
				$selected_pages[ $page->ID ] = $page;
			} else {
				$unselected_pages[ $page->ID ] = $page;
			}
		}

		// Order selected pages by saved order.
		$ordered_selected = array();
		foreach ( $selected_ids as $id ) {
			if ( isset( $selected_pages[ $id ] ) ) {
				$ordered_selected[] = $selected_pages[ $id ];
			}
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Wiara — Ustawienia', 'kzmielec' ); ?></h1>

			<?php settings_errors( 'kzmielec_belief' ); ?>

			<p><?php esc_html_e( 'Wybierz strony do wyświetlenia w sekcji "W co i jak wierzymy" (na stronie głównej i jako nawigacja na podstronach wiary).', 'kzmielec' ); ?></p>

			<form method="post" action="">
				<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD ); ?>

				<h2><?php esc_html_e( 'Wybrane strony (przeciągnij, aby zmienić kolejność)', 'kzmielec' ); ?></h2>

				<ul id="kzmielec-belief-selected" class="kzmielec-belief-list">
					<?php foreach ( $ordered_selected as $page ) : ?>
						<li class="kzmielec-belief-item" data-page-id="<?php echo esc_attr( (string) $page->ID ); ?>">
							<span class="kzmielec-belief-handle" aria-hidden="true">☰</span>
							<span class="kzmielec-belief-title"><?php echo esc_html( $page->post_title ); ?></span>
							<button type="button" class="button button-small kzmielec-belief-remove" aria-label="<?php esc_attr_e( 'Usuń', 'kzmielec' ); ?>">✕</button>
							<input type="hidden" name="kzmielec_belief_pages[]" value="<?php echo esc_attr( (string) $page->ID ); ?>">
						</li>
					<?php endforeach; ?>
				</ul>

				<h2><?php esc_html_e( 'Dodaj stronę', 'kzmielec' ); ?></h2>
				<select id="kzmielec-belief-add" class="regular-text">
					<option value=""><?php esc_html_e( '— wybierz —', 'kzmielec' ); ?></option>
					<?php foreach ( $unselected_pages as $page ) : ?>
						<option value="<?php echo esc_attr( (string) $page->ID ); ?>" data-title="<?php echo esc_attr( $page->post_title ); ?>">
							<?php echo esc_html( $page->post_title ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<button type="button" class="button" id="kzmielec-belief-add-button"><?php esc_html_e( 'Dodaj', 'kzmielec' ); ?></button>

				<p class="submit">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Zapisz zmiany', 'kzmielec' ); ?></button>
				</p>
			</form>

			<style>
				.kzmielec-belief-list { list-style: none; padding: 0; margin: 0 0 24px; max-width: 600px; }
				.kzmielec-belief-item { display: flex; align-items: center; gap: 12px; padding: 10px 16px; background: #fff; border: 1px solid #ddd; margin-bottom: 4px; border-radius: 4px; }
				.kzmielec-belief-handle { cursor: grab; color: #888; }
				.kzmielec-belief-title { flex: 1; }
				.kzmielec-belief-item.sortable-ghost { opacity: 0.4; }
			</style>
		</div>
		<?php
	}

	/**
	 * Handle form submission.
	 *
	 * @return void
	 */
	private function handle_form_submission(): void {
		if ( ! isset( $_POST[ self::NONCE_FIELD ] ) ) {
			return;
		}
		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) );
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			wp_die( esc_html__( 'Security check failed.', 'kzmielec' ) );
		}

		$ids = isset( $_POST['kzmielec_belief_pages'] ) && is_array( $_POST['kzmielec_belief_pages'] )
			? array_map( 'absint', wp_unslash( $_POST['kzmielec_belief_pages'] ) )
			: array();

		$ids = array_values( array_filter( $ids ) );

		update_option( self::OPTION_BELIEF_PAGES, $ids );

		add_settings_error(
			'kzmielec_belief',
			'saved',
			__( 'Ustawienia zapisane.', 'kzmielec' ),
			'updated'
		);
	}
}
```

- [ ] **Step 2: Commit**

```bash
git add wp-content/themes/kzmielec/App/Admin/BeliefSettings.php
git commit -m "Add BeliefSettings admin page with multi-select + Sortable.js"
```

---

## Task 8: Create Sortable.js admin script

**Files:**
- Create: `wp-content/themes/kzmielec/assets/js/admin/belief-settings.js`

- [ ] **Step 1: Create directory and file**

Run: `mkdir -p /home/lukasz/projects/kzmielec/wp-content/themes/kzmielec/assets/js/admin`

Write `wp-content/themes/kzmielec/assets/js/admin/belief-settings.js`:

```js
/**
 * Belief Settings admin page - drag-and-drop reorder + add/remove pages.
 *
 * Uses Sortable.js loaded from CDN (small file, no build step needed).
 */
(function () {
	'use strict';

	function loadSortable(callback) {
		if (typeof window.Sortable !== 'undefined') {
			callback();
			return;
		}
		var script = document.createElement('script');
		script.src = 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js';
		script.onload = callback;
		document.head.appendChild(script);
	}

	function initSortable() {
		var list = document.getElementById('kzmielec-belief-selected');
		if (!list) return;

		// eslint-disable-next-line no-new, no-undef
		new Sortable(list, {
			handle: '.kzmielec-belief-handle',
			animation: 150,
			ghostClass: 'sortable-ghost',
		});
	}

	function initActions() {
		var list = document.getElementById('kzmielec-belief-selected');
		var addSelect = document.getElementById('kzmielec-belief-add');
		var addButton = document.getElementById('kzmielec-belief-add-button');

		if (addButton && addSelect && list) {
			addButton.addEventListener('click', function () {
				var pageId = addSelect.value;
				if (!pageId) return;
				var option = addSelect.options[addSelect.selectedIndex];
				var title = option.getAttribute('data-title') || option.textContent.trim();

				var li = document.createElement('li');
				li.className = 'kzmielec-belief-item';
				li.setAttribute('data-page-id', pageId);
				li.innerHTML =
					'<span class="kzmielec-belief-handle" aria-hidden="true">☰</span>' +
					'<span class="kzmielec-belief-title"></span>' +
					'<button type="button" class="button button-small kzmielec-belief-remove" aria-label="Usuń">✕</button>' +
					'<input type="hidden" name="kzmielec_belief_pages[]">';
				li.querySelector('.kzmielec-belief-title').textContent = title;
				li.querySelector('input').value = pageId;
				list.appendChild(li);

				addSelect.removeChild(option);
				addSelect.value = '';
			});
		}

		if (list) {
			list.addEventListener('click', function (e) {
				if (!e.target.classList.contains('kzmielec-belief-remove')) return;
				var item = e.target.closest('.kzmielec-belief-item');
				if (!item) return;
				var pageId = item.getAttribute('data-page-id');
				var title = item.querySelector('.kzmielec-belief-title').textContent;

				if (addSelect && pageId) {
					var option = document.createElement('option');
					option.value = pageId;
					option.textContent = title;
					option.setAttribute('data-title', title);
					addSelect.appendChild(option);
				}
				item.remove();
			});
		}
	}

	document.addEventListener('DOMContentLoaded', function () {
		loadSortable(initSortable);
		initActions();
	});
})();
```

- [ ] **Step 2: Commit**

```bash
git add wp-content/themes/kzmielec/assets/js/admin/belief-settings.js
git commit -m "Add Sortable.js admin script for belief settings page"
```

---

## Task 9: Register BeliefSettings + BeliefPageMeta in Theme.php

**Files:**
- Modify: `wp-content/themes/kzmielec/App/Theme.php`

- [ ] **Step 1: Read current file**

Run: `cat /home/lukasz/projects/kzmielec/wp-content/themes/kzmielec/App/Theme.php`
Expected: See `$admin_components` array.

- [ ] **Step 2: Add classes to admin components**

In `$admin_components` array, add:

```php
Admin\BeliefSettings::class,
Admin\BeliefPageMeta::class,
```

- [ ] **Step 3: Verify in WP Admin**

Open: `https://kzmielec.ddev.site/wp-admin/admin.php?page=kzmielec-theme-settings`
Expected: New submenu "Wiara" visible.

Click "Wiara" → page loads with empty list, "Dodaj stronę" select shows all pages.

- [ ] **Step 4: Commit**

```bash
git add wp-content/themes/kzmielec/App/Theme.php
git commit -m "Register BeliefSettings and BeliefPageMeta in Theme bootstrap"
```

---

## Task 10: Create NavigableTilesService

**Files:**
- Create: `wp-content/plugins/custom-block-package/app/Services/NavigableTilesService.php`

- [ ] **Step 1: Create file**

Write `wp-content/plugins/custom-block-package/app/Services/NavigableTilesService.php`:

```php
<?php
/**
 * Navigable Tiles Service.
 *
 * Unified data fetcher for navigable-tiles block (meetings CPT or beliefs pages).
 *
 * @package CustomBlockPackage
 */

declare(strict_types=1);

namespace CustomBlockPackage\Services;

use CustomBlockPackage\Admin\MeetingMeta;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class NavigableTilesService
 */
class NavigableTilesService {

	/**
	 * Option key for belief page IDs (defined in theme).
	 */
	private const OPTION_BELIEF_PAGES = 'kzmielec_belief_pages';

	/**
	 * Meta key for belief page hover image (defined in theme).
	 */
	private const META_BELIEF_HOVER_IMAGE = '_belief_hover_image';

	/**
	 * Get all meetings as normalized items.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_meetings(): array {
		$query = new \WP_Query(
			array(
				'post_type'      => 'meetings',
				'posts_per_page' => -1,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			)
		);

		$items = array();

		if ( ! $query->have_posts() ) {
			return $items;
		}

		while ( $query->have_posts() ) {
			$query->the_post();
			$post_id = get_the_ID();

			if ( false === $post_id ) {
				continue;
			}

			$anchor   = (string) get_post_meta( $post_id, MeetingMeta::META_ANCHOR, true );
			$day_hour = (string) get_post_meta( $post_id, MeetingMeta::META_DAY_HOUR, true );
			$hover_id = (int) get_post_meta( $post_id, MeetingMeta::META_HOVER_IMAGE, true );
			$base_id  = (int) get_post_thumbnail_id( $post_id );

			$link = $anchor
				? home_url( '/zaplanuj-wizyte/#' . rawurlencode( $anchor ) )
				: (string) get_permalink( $post_id );

			$items[] = array(
				'id'          => $post_id,
				'page_id'     => $post_id,
				'title'       => (string) get_the_title( $post_id ),
				'link'        => $link,
				'image_base'  => $base_id ? (string) wp_get_attachment_image_url( $base_id, 'medium' ) : '',
				'image_hover' => $hover_id ? (string) wp_get_attachment_image_url( $hover_id, 'medium' ) : '',
				'day_hour'    => $day_hour,
				'anchor'      => $anchor,
				'is_current'  => false, // Filled in render.
			);
		}

		wp_reset_postdata();

		return $items;
	}

	/**
	 * Get all beliefs as normalized items.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_beliefs(): array {
		$page_ids = (array) get_option( self::OPTION_BELIEF_PAGES, array() );
		$page_ids = array_filter( array_map( 'intval', $page_ids ) );

		if ( empty( $page_ids ) ) {
			return array();
		}

		$items = array();

		foreach ( $page_ids as $original_id ) {
			$page_id = self::resolve_translated_id( $original_id );
			$page    = get_post( $page_id );

			if ( ! $page instanceof \WP_Post || 'publish' !== $page->post_status ) {
				continue;
			}

			$base_id  = (int) get_post_thumbnail_id( $page_id );
			$hover_id = (int) get_post_meta( $page_id, self::META_BELIEF_HOVER_IMAGE, true );

			$items[] = array(
				'id'          => $page_id,
				'page_id'     => $page_id,
				'title'       => (string) get_the_title( $page_id ),
				'link'        => (string) get_permalink( $page_id ),
				'image_base'  => $base_id ? (string) wp_get_attachment_image_url( $base_id, 'medium' ) : '',
				'image_hover' => $hover_id ? (string) wp_get_attachment_image_url( $hover_id, 'medium' ) : '',
				'day_hour'    => '',
				'anchor'      => '',
				'is_current'  => false,
			);
		}

		return $items;
	}

	/**
	 * Resolve a post ID to the Polylang-translated version when available.
	 *
	 * @param int $post_id Original post ID.
	 * @return int
	 */
	private static function resolve_translated_id( int $post_id ): int {
		if ( ! function_exists( 'pll_get_post' ) ) {
			return $post_id;
		}

		$translated = pll_get_post( $post_id );
		return $translated ? (int) $translated : $post_id;
	}
}
```

- [ ] **Step 2: Verify PHPStan + PHPCS**

Run: `ddev exec "cd wp-content/plugins/custom-block-package && ./vendor/bin/phpstan analyse app/Services/NavigableTilesService.php && ./vendor/bin/phpcs --standard=phpcs.xml --extensions=php app/Services/NavigableTilesService.php"`
Expected: 0 errors.

- [ ] **Step 3: Commit**

```bash
git add wp-content/plugins/custom-block-package/app/Services/NavigableTilesService.php
git commit -m "Add NavigableTilesService — meetings and beliefs data abstraction"
```

---

## Task 11: Create navigable-tiles block — block.json + index.js

**Files:**
- Create: `wp-content/plugins/custom-block-package/src/blocks/navigable-tiles/block.json`
- Create: `wp-content/plugins/custom-block-package/src/blocks/navigable-tiles/index.js`

- [ ] **Step 1: Create block directory**

Run: `mkdir -p /home/lukasz/projects/kzmielec/wp-content/plugins/custom-block-package/src/blocks/navigable-tiles`

- [ ] **Step 2: Create block.json**

Write `wp-content/plugins/custom-block-package/src/blocks/navigable-tiles/block.json`:

```json
{
  "$schema": "https://schemas.wp.org/trunk/block.json",
  "apiVersion": 3,
  "name": "custom-block-package/navigable-tiles",
  "title": "Kafelki nawigacyjne",
  "category": "custom-blocks-from-scratch",
  "icon": "grid-view",
  "description": "Kafelki nawigacyjne z CPT meetings lub stron wiary.",
  "textdomain": "custom-block-package",
  "supports": {
    "anchor": true,
    "align": ["wide", "full"],
    "spacing": { "margin": true, "padding": true }
  },
  "attributes": {
    "anchor": { "type": "string" },
    "dataSource": {
      "type": "string",
      "default": "beliefs",
      "enum": ["meetings", "beliefs"]
    },
    "columns": { "type": "number", "default": 4 },
    "showDayHour": { "type": "boolean", "default": false },
    "highlightCurrent": { "type": "boolean", "default": true },
    "sectionTitle": { "type": "string", "default": "" }
  },
  "editorScript": "file:./index.js",
  "editorStyle": "file:./index.css",
  "viewStyle": "file:./style-index.css",
  "render": "file:./render.php"
}
```

- [ ] **Step 3: Create index.js**

Write `wp-content/plugins/custom-block-package/src/blocks/navigable-tiles/index.js`:

```js
import { registerBlockType } from '@wordpress/blocks';
import metadata from './block.json';
import Edit from './edit';
import './style.scss';

registerBlockType( metadata.name, {
	...metadata,
	edit: Edit,
	save: () => null,
} );
```

- [ ] **Step 4: Commit**

```bash
git add wp-content/plugins/custom-block-package/src/blocks/navigable-tiles/block.json wp-content/plugins/custom-block-package/src/blocks/navigable-tiles/index.js
git commit -m "Add navigable-tiles block.json and index.js"
```

---

## Task 12: Create navigable-tiles edit.js

**Files:**
- Create: `wp-content/plugins/custom-block-package/src/blocks/navigable-tiles/edit.js`
- Create: `wp-content/plugins/custom-block-package/src/blocks/navigable-tiles/index.scss`

- [ ] **Step 1: Create edit.js**

Write `wp-content/plugins/custom-block-package/src/blocks/navigable-tiles/edit.js`:

```js
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	RangeControl,
	ToggleControl,
	TextControl,
	Disabled,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import './index.scss';

const Edit = ( { attributes, setAttributes } ) => {
	const { dataSource, columns, showDayHour, highlightCurrent, sectionTitle } = attributes;
	const blockProps = useBlockProps();

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Ustawienia bloku', 'custom-block-package' ) } initialOpen={ true }>
					<SelectControl
						label={ __( 'Źródło danych', 'custom-block-package' ) }
						value={ dataSource }
						options={ [
							{ label: __( 'Wiara (strony)', 'custom-block-package' ), value: 'beliefs' },
							{ label: __( 'Spotkania (CPT)', 'custom-block-package' ), value: 'meetings' },
						] }
						onChange={ ( value ) => setAttributes( { dataSource: value } ) }
					/>
					<RangeControl
						label={ __( 'Liczba kolumn', 'custom-block-package' ) }
						value={ columns }
						onChange={ ( value ) => setAttributes( { columns: value } ) }
						min={ 1 }
						max={ 6 }
					/>
					<TextControl
						label={ __( 'Tytuł sekcji (opcjonalny)', 'custom-block-package' ) }
						value={ sectionTitle }
						onChange={ ( value ) => setAttributes( { sectionTitle: value } ) }
					/>
					{ dataSource === 'meetings' && (
						<ToggleControl
							label={ __( 'Pokaż dzień i godzinę', 'custom-block-package' ) }
							checked={ showDayHour }
							onChange={ ( value ) => setAttributes( { showDayHour: value } ) }
						/>
					) }
					<ToggleControl
						label={ __( 'Wyróżnij aktualną stronę', 'custom-block-package' ) }
						checked={ highlightCurrent }
						onChange={ ( value ) => setAttributes( { highlightCurrent: value } ) }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<Disabled>
					<ServerSideRender
						block="custom-block-package/navigable-tiles"
						attributes={ attributes }
					/>
				</Disabled>
			</div>
		</>
	);
};

export default Edit;
```

- [ ] **Step 2: Create index.scss (imports style.scss for editor preview)**

Write `wp-content/plugins/custom-block-package/src/blocks/navigable-tiles/index.scss`:

```scss
// Editor styles for navigable-tiles block.
// Imports frontend styles so editor preview matches frontend.
@import './style.scss';
```

- [ ] **Step 3: Commit**

```bash
git add wp-content/plugins/custom-block-package/src/blocks/navigable-tiles/edit.js wp-content/plugins/custom-block-package/src/blocks/navigable-tiles/index.scss
git commit -m "Add navigable-tiles edit.js and editor styles"
```

---

## Task 13: Create navigable-tiles render.php

**Files:**
- Create: `wp-content/plugins/custom-block-package/src/blocks/navigable-tiles/render.php`

- [ ] **Step 1: Create file**

Write `wp-content/plugins/custom-block-package/src/blocks/navigable-tiles/render.php`:

```php
<?php
/**
 * Navigable Tiles Block Render.
 *
 * Renders a grid of tiles from meetings CPT or beliefs pages.
 * Supports "you are here" indicator on the current page tile.
 *
 * @package CustomBlockPackage
 *
 * @var array $attributes Block attributes.
 */

use CustomBlockPackage\Cache\BlockCache;
use CustomBlockPackage\Services\NavigableTilesService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$data_source       = isset( $attributes['dataSource'] ) && 'meetings' === $attributes['dataSource'] ? 'meetings' : 'beliefs';
$columns           = isset( $attributes['columns'] ) ? max( 1, min( 6, (int) $attributes['columns'] ) ) : 4;
$show_day_hour     = ! empty( $attributes['showDayHour'] );
$highlight_current = ! isset( $attributes['highlightCurrent'] ) || (bool) $attributes['highlightCurrent'];
$section_title     = isset( $attributes['sectionTitle'] ) ? (string) $attributes['sectionTitle'] : '';

$cache_key = BlockCache::key( BlockCache::NAVIGABLE_TILES_PREFIX, $attributes );
$items     = get_transient( $cache_key );

if ( false === $items ) {
	$items = 'meetings' === $data_source
		? NavigableTilesService::get_meetings()
		: NavigableTilesService::get_beliefs();
	set_transient( $cache_key, $items, BlockCache::TTL );
}

if ( ! is_array( $items ) || empty( $items ) ) {
	return;
}

$current_page_id = (int) get_queried_object_id();

$wrapper_attrs = array(
	'class'      => 'has-source-' . $data_source . ' has-columns-' . $columns,
	'aria-label' => '' !== $section_title ? $section_title : __( 'Nawigacja', 'custom-block-package' ),
);
if ( ! empty( $attributes['anchor'] ) ) {
	$wrapper_attrs['id'] = $attributes['anchor'];
}
?>
<nav <?php echo get_block_wrapper_attributes( $wrapper_attrs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Returns pre-escaped HTML. ?>>

	<?php if ( '' !== $section_title ) : ?>
		<h2 class="navigable-tiles__heading"><?php echo esc_html( $section_title ); ?></h2>
	<?php endif; ?>

	<ul class="navigable-tiles__grid" role="list">
		<?php foreach ( $items as $item ) :
			$is_current = $highlight_current && ( (int) $item['page_id'] === $current_page_id );
			?>
			<li class="navigable-tiles__item<?php echo $is_current ? ' is-current' : ''; ?>">
				<a
					href="<?php echo esc_url( (string) $item['link'] ); ?>"
					class="navigable-tiles__link"
					<?php if ( $is_current ) : ?>aria-current="page"<?php endif; ?>
				>
					<span class="navigable-tiles__image" aria-hidden="true">
						<?php if ( '' !== (string) $item['image_base'] ) : ?>
							<img class="navigable-tiles__image--one" src="<?php echo esc_url( (string) $item['image_base'] ); ?>" alt="" loading="lazy">
						<?php endif; ?>
						<?php if ( 'beliefs' === $data_source ) : ?>
							<span class="navigable-tiles__image--black"></span>
						<?php endif; ?>
						<?php if ( ! $is_current && '' !== (string) $item['image_hover'] ) : ?>
							<img class="navigable-tiles__image--two" src="<?php echo esc_url( (string) $item['image_hover'] ); ?>" alt="" loading="lazy">
						<?php endif; ?>
					</span>
					<span class="navigable-tiles__title">
						<?php echo esc_html( (string) $item['title'] ); ?>
						<?php if ( $is_current ) : ?>
							<span class="screen-reader-text"> <?php esc_html_e( '(aktualna strona)', 'custom-block-package' ); ?></span>
						<?php endif; ?>
					</span>
					<?php if ( $show_day_hour && 'meetings' === $data_source && '' !== (string) $item['day_hour'] ) : ?>
						<span class="navigable-tiles__meta"><?php echo esc_html( (string) $item['day_hour'] ); ?></span>
					<?php endif; ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>
```

- [ ] **Step 2: Verify PHPStan + PHPCS**

Run: `ddev exec "cd wp-content/plugins/custom-block-package && ./vendor/bin/phpstan analyse src/blocks/navigable-tiles/render.php && ./vendor/bin/phpcs --standard=phpcs.xml --extensions=php src/blocks/navigable-tiles/render.php"`
Expected: 0 errors.

- [ ] **Step 3: Commit**

```bash
git add wp-content/plugins/custom-block-package/src/blocks/navigable-tiles/render.php
git commit -m "Add navigable-tiles render.php with you-are-here support"
```

---

## Task 14: Create navigable-tiles style.scss

**Files:**
- Create: `wp-content/plugins/custom-block-package/src/blocks/navigable-tiles/style.scss`

- [ ] **Step 1: Create file**

Write `wp-content/plugins/custom-block-package/src/blocks/navigable-tiles/style.scss`:

```scss
// Navigable Tiles — meetings + beliefs.
//
// Matches old production design (.vb__item pattern with hover swap).
// WCAG 2.1 AA compliant: focus-visible, aria-current, prefers-reduced-motion.

.wp-block-custom-block-package-navigable-tiles {
	width: 100%;

	.navigable-tiles__heading {
		text-align: center;
		margin: 0 0 2rem;
	}

	.navigable-tiles__grid {
		display: grid;
		grid-template-columns: repeat(var(--cbp-tiles-columns, 4), minmax(0, 1fr));
		gap: 2rem;
		list-style: none;
		padding: 0;
		margin: 0;
	}

	&.has-columns-1 .navigable-tiles__grid { --cbp-tiles-columns: 1; }
	&.has-columns-2 .navigable-tiles__grid { --cbp-tiles-columns: 2; }
	&.has-columns-3 .navigable-tiles__grid { --cbp-tiles-columns: 3; }
	&.has-columns-4 .navigable-tiles__grid { --cbp-tiles-columns: 4; }
	&.has-columns-5 .navigable-tiles__grid { --cbp-tiles-columns: 5; }
	&.has-columns-6 .navigable-tiles__grid { --cbp-tiles-columns: 6; }

	@media (max-width: 1024px) {
		.navigable-tiles__grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
	}

	@media (max-width: 768px) {
		.navigable-tiles__grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
	}

	@media (max-width: 480px) {
		.navigable-tiles__grid { grid-template-columns: 1fr; }
	}

	.navigable-tiles__item {
		display: flex;
		flex-direction: column;
		align-items: center;
		text-align: center;
		min-width: 0;
	}

	.navigable-tiles__link {
		display: flex;
		flex-direction: column;
		align-items: center;
		text-decoration: none;
		color: inherit;
		min-height: 88px;
		min-width: 88px;
		padding: 0.5rem;
		border-radius: 4px;
		transition: opacity 0.2s ease;

		&:hover {
			opacity: 0.9;
		}

		&:focus-visible {
			outline: 3px solid currentColor;
			outline-offset: 4px;
		}
	}

	.navigable-tiles__image {
		position: relative;
		display: block;
		width: 100%;
		max-width: 200px;
		aspect-ratio: 1 / 1;
		margin-bottom: 1rem;
	}

	.navigable-tiles__image--one,
	.navigable-tiles__image--two {
		position: absolute;
		inset: 0;
		width: 100%;
		height: 100%;
		object-fit: contain;
	}

	.navigable-tiles__image--two {
		opacity: 0;
		transition: opacity 0.3s ease;
	}

	// Hover and focus swap (keyboard accessibility).
	.navigable-tiles__link:hover .navigable-tiles__image--two,
	.navigable-tiles__link:focus-visible .navigable-tiles__image--two {
		opacity: 1;
	}

	// Black circle BG only for beliefs (matches old production).
	&.has-source-beliefs .navigable-tiles__image--black {
		position: absolute;
		inset: 5%;
		background: #000;
		border-radius: 50%;
		z-index: -1;
	}

	&.has-source-beliefs .navigable-tiles__image {
		position: relative;
		z-index: 0;
	}

	.navigable-tiles__title {
		font-size: 1.25rem;
		text-transform: uppercase;
		letter-spacing: 0.05em;
		font-weight: 400;
	}

	.navigable-tiles__meta {
		display: block;
		margin-top: 0.5rem;
		font-size: 0.9rem;
		color: #555;
	}

	// "You are here" state.
	.navigable-tiles__item.is-current {
		.navigable-tiles__link {
			cursor: default;

			&:hover {
				opacity: 1;
			}
		}

		.navigable-tiles__title {
			font-weight: 600;
		}
	}

	// Reduced motion respect.
	@media (prefers-reduced-motion: reduce) {
		.navigable-tiles__image--two,
		.navigable-tiles__link {
			transition: none;
		}
	}
}
```

- [ ] **Step 2: Commit**

```bash
git add wp-content/plugins/custom-block-package/src/blocks/navigable-tiles/style.scss
git commit -m "Add navigable-tiles styles (WCAG 2.1 AA compliant)"
```

---

## Task 15: Add cache invalidation hooks in plugin index.php

**Files:**
- Modify: `wp-content/plugins/custom-block-package/index.php`

- [ ] **Step 1: Add invalidation hooks**

In `wp-content/plugins/custom-block-package/index.php`, find the existing `add_action('save_post_meetings', ...)` and add after the BlockCache flush blocks:

```php
// Invalidate navigable-tiles cache on relevant changes.
add_action(
	'save_post_meetings',
	static function (): void {
		BlockCache::flush( BlockCache::NAVIGABLE_TILES_PREFIX );
	}
);
add_action(
	'save_post_page',
	static function (): void {
		BlockCache::flush( BlockCache::NAVIGABLE_TILES_PREFIX );
	}
);
add_action(
	'update_option_kzmielec_belief_pages',
	static function (): void {
		BlockCache::flush( BlockCache::NAVIGABLE_TILES_PREFIX );
	}
);
add_action(
	'add_option_kzmielec_belief_pages',
	static function (): void {
		BlockCache::flush( BlockCache::NAVIGABLE_TILES_PREFIX );
	}
);
```

- [ ] **Step 2: Verify PHP syntax**

Run: `ddev exec "php -l wp-content/plugins/custom-block-package/index.php"`
Expected: `No syntax errors detected`.

- [ ] **Step 3: Commit**

```bash
git add wp-content/plugins/custom-block-package/index.php
git commit -m "Add cache invalidation hooks for navigable-tiles"
```

---

## Task 16: Build plugin and verify block registered

**Files:** (no code changes)

- [ ] **Step 1: Build plugin assets**

Run: `ddev plugin:build 2>&1 | tail -5`
Expected: `webpack compiled successfully`. No errors.

- [ ] **Step 2: Verify build output**

Run: `ls /home/lukasz/projects/kzmielec/wp-content/plugins/custom-block-package/build/blocks/navigable-tiles/`
Expected: `block.json`, `index.asset.php`, `index.css`, `index.js`, `render.php`, `style-index.css`.

- [ ] **Step 3: Verify block registered with WP**

Run: `ddev wp eval "var_dump(\\WP_Block_Type_Registry::get_instance()->is_registered('custom-block-package/navigable-tiles'));"`
Expected: `bool(true)`.

- [ ] **Step 4: Insert block in editor (manual verification)**

Open: `https://kzmielec.ddev.site/wp-admin/post-new.php?post_type=page`
- Click + → search "Kafelki nawigacyjne" → insert
- Should show ServerSideRender preview (empty if no data)
- Inspector panel should show: Źródło danych, Liczba kolumn, Tytuł sekcji, Pokaż dzień (only meetings), Wyróżnij aktualną

- [ ] **Step 5: No commit needed** (build artifacts committed in next steps)

---

## Task 17: Create page-belief.php template

**Files:**
- Create: `wp-content/themes/kzmielec/page-belief.php`

- [ ] **Step 1: Create file**

Write `wp-content/themes/kzmielec/page-belief.php`:

```php
<?php
/**
 * Template Name: Strona wiary
 *
 * Renders a belief subpage with auto-generated heading, hero tile,
 * content, scroll arrow, and bottom navigation tiles.
 *
 * @package Kzmielec
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Kzmielec\Admin\BeliefPageMeta;

get_header();
?>

<main id="primary" class="site-main is-layout-constrained page-belief">

	<?php
	while ( have_posts() ) :
		the_post();
		$post_id        = (int) get_the_ID();
		$base_image     = get_the_post_thumbnail( $post_id, 'medium', array( 'alt' => '', 'class' => 'page-belief__hero-image--one' ) );
		$hover_image_id = (int) get_post_meta( $post_id, BeliefPageMeta::META_HOVER_IMAGE, true );
		$hover_image    = $hover_image_id
			? wp_get_attachment_image( $hover_image_id, 'medium', false, array( 'alt' => '', 'class' => 'page-belief__hero-image--two' ) )
			: '';
		?>

		<h2 class="wp-block-heading is-style-section-line">
			<?php esc_html_e( 'W co i jak wierzymy', 'kzmielec' ); ?>
		</h2>

		<div class="page-belief__hero">
			<span class="page-belief__hero-image" aria-hidden="true">
				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- safe WP-generated markup.
				echo $base_image;
				?>
				<span class="page-belief__hero-bg"></span>
				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- safe WP-generated markup.
				echo $hover_image;
				?>
			</span>
			<h1 class="page-belief__title"><?php the_title(); ?></h1>
		</div>

		<article class="page-belief__content">
			<?php the_content(); ?>
		</article>

		<div class="page-belief__separator">
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block output.
			echo do_blocks( '<!-- wp:custom-block-package/scroll-arrow {"targetId":"belief-nav","direction":"down","ariaLabel":"Przewiń do nawigacji wiary"} /-->' );
			?>
		</div>

		<div id="belief-nav">
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block output.
			echo do_blocks( '<!-- wp:custom-block-package/navigable-tiles {"dataSource":"beliefs","columns":4,"highlightCurrent":true,"className":"pattern-page-belief"} /-->' );
			?>
		</div>

	<?php endwhile; ?>

</main>

<?php
get_footer();
```

- [ ] **Step 2: Verify PHPCS**

Run: `ddev exec "cd wp-content/plugins/custom-block-package && ./vendor/bin/phpcs --standard=phpcs.xml --extensions=php /var/www/html/wp-content/themes/kzmielec/page-belief.php"`
Expected: 0 errors (if theme has its own phpcs config, run with that instead).

- [ ] **Step 3: Commit**

```bash
git add wp-content/themes/kzmielec/page-belief.php
git commit -m "Add page-belief.php template for belief subpages"
```

---

## Task 18: Create archive-meetings.php template

**Files:**
- Create: `wp-content/themes/kzmielec/archive-meetings.php`

- [ ] **Step 1: Create file**

Write `wp-content/themes/kzmielec/archive-meetings.php`:

```php
<?php
/**
 * Archive template for CPT meetings.
 *
 * Renders full descriptions of all meetings with anchor IDs
 * for cross-linking from homepage tiles.
 *
 * @package Kzmielec
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CustomBlockPackage\Admin\MeetingMeta;

get_header();
?>

<main id="primary" class="site-main is-layout-constrained archive-meetings pattern-archive-meetings">

	<h2 class="wp-block-heading is-style-section-line">
		<?php esc_html_e( 'Zaplanuj wizytę', 'kzmielec' ); ?>
	</h2>

	<?php
	$archive_query = new \WP_Query(
		array(
			'post_type'      => 'meetings',
			'posts_per_page' => -1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		)
	);

	if ( $archive_query->have_posts() ) :
		$collected_meetings = array();

		while ( $archive_query->have_posts() ) {
			$archive_query->the_post();
			$collected_meetings[] = (int) get_the_ID();
		}

		$total = count( $collected_meetings );

		foreach ( $collected_meetings as $index => $meeting_id ) :
			$post_obj = get_post( $meeting_id );
			if ( ! $post_obj instanceof \WP_Post ) {
				continue;
			}

			setup_postdata( $post_obj );

			$anchor         = (string) get_post_meta( $meeting_id, MeetingMeta::META_ANCHOR, true );
			$day_hour       = (string) get_post_meta( $meeting_id, MeetingMeta::META_DAY_HOUR, true );
			$hover_image_id = (int) get_post_meta( $meeting_id, MeetingMeta::META_HOVER_IMAGE, true );
			$base_image     = get_the_post_thumbnail( $meeting_id, 'medium', array( 'alt' => '', 'class' => 'archive-meetings__image--one' ) );
			$hover_image    = $hover_image_id
				? wp_get_attachment_image( $hover_image_id, 'medium', false, array( 'alt' => '', 'class' => 'archive-meetings__image--two' ) )
				: '';

			$next_anchor = '';
			if ( isset( $collected_meetings[ $index + 1 ] ) ) {
				$next_anchor = (string) get_post_meta( $collected_meetings[ $index + 1 ], MeetingMeta::META_ANCHOR, true );
			}
			?>

			<article id="<?php echo esc_attr( $anchor ); ?>" class="archive-meetings__item">

				<div class="archive-meetings__hero">
					<span class="archive-meetings__hero-image" aria-hidden="true">
						<?php
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- safe WP-generated markup.
						echo $base_image;
						?>
						<?php
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- safe WP-generated markup.
						echo $hover_image;
						?>
					</span>
					<h2 class="archive-meetings__title"><?php the_title(); ?></h2>
					<?php if ( '' !== $day_hour ) : ?>
						<p class="archive-meetings__meta"><?php echo esc_html( $day_hour ); ?></p>
					<?php endif; ?>
				</div>

				<div class="archive-meetings__content">
					<?php the_content(); ?>
				</div>

				<?php if ( $index < $total - 1 && '' !== $next_anchor ) : ?>
					<div class="archive-meetings__separator">
						<?php
						$block_html = sprintf(
							'<!-- wp:custom-block-package/scroll-arrow {"targetId":"%s","direction":"down"} /-->',
							esc_js( $next_anchor )
						);
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block output.
						echo do_blocks( $block_html );
						?>
					</div>
				<?php endif; ?>

			</article>

		<?php endforeach;

		wp_reset_postdata();
	endif;
	?>

	<div class="archive-meetings__back-top">
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block output.
		echo do_blocks( '<!-- wp:custom-block-package/scroll-arrow {"targetId":"zero","direction":"up","ariaLabel":"Wróć na górę"} /-->' );
		?>
	</div>

</main>

<?php
get_footer();
```

- [ ] **Step 2: Commit**

```bash
git add wp-content/themes/kzmielec/archive-meetings.php
git commit -m "Add archive-meetings.php template for CPT archive"
```

---

## Task 19: Create pattern styles for page-belief

**Files:**
- Create: `wp-content/themes/kzmielec/webpack/src/patterns/page-belief/style.scss`

- [ ] **Step 1: Create directory and file**

Run: `mkdir -p /home/lukasz/projects/kzmielec/wp-content/themes/kzmielec/webpack/src/patterns/page-belief`

Write `wp-content/themes/kzmielec/webpack/src/patterns/page-belief/style.scss`:

```scss
// Styles for page-belief.php template.
// Loaded via PatternAssets when class "pattern-page-belief" detected.

.page-belief {
	.page-belief__hero {
		display: flex;
		flex-direction: column;
		align-items: center;
		margin: 2rem 0;
	}

	.page-belief__hero-image {
		position: relative;
		display: block;
		width: 200px;
		height: 200px;
		margin-bottom: 1rem;

		.page-belief__hero-bg {
			position: absolute;
			inset: 5%;
			background: #000;
			border-radius: 50%;
			z-index: 0;
		}

		.page-belief__hero-image--one,
		.page-belief__hero-image--two {
			position: absolute;
			inset: 0;
			width: 100%;
			height: 100%;
			object-fit: contain;
			z-index: 1;
		}

		.page-belief__hero-image--two {
			opacity: 0;
			transition: opacity 0.3s ease;
		}

		&:hover .page-belief__hero-image--two {
			opacity: 1;
		}
	}

	.page-belief__title {
		font-size: 2.5rem;
		text-transform: uppercase;
		letter-spacing: 0.05em;
		text-align: center;
		margin: 0;
	}

	.page-belief__content {
		max-width: 800px;
		margin: 2rem auto;
		padding: 0 1rem;
	}

	.page-belief__separator {
		display: flex;
		justify-content: center;
		margin: 3rem 0;
	}

	@media (prefers-reduced-motion: reduce) {
		.page-belief__hero-image--two {
			transition: none;
		}
	}
}
```

- [ ] **Step 2: Commit**

```bash
git add wp-content/themes/kzmielec/webpack/src/patterns/page-belief/style.scss
git commit -m "Add page-belief pattern styles"
```

---

## Task 20: Create pattern styles for archive-meetings

**Files:**
- Create: `wp-content/themes/kzmielec/webpack/src/patterns/archive-meetings/style.scss`

- [ ] **Step 1: Create directory and file**

Run: `mkdir -p /home/lukasz/projects/kzmielec/wp-content/themes/kzmielec/webpack/src/patterns/archive-meetings`

Write `wp-content/themes/kzmielec/webpack/src/patterns/archive-meetings/style.scss`:

```scss
// Styles for archive-meetings.php template.
// Loaded via PatternAssets when class "pattern-archive-meetings" detected.

.archive-meetings {
	.archive-meetings__item {
		max-width: 800px;
		margin: 3rem auto;
		padding: 0 1rem;
		scroll-margin-top: 80px;
	}

	.archive-meetings__hero {
		display: flex;
		flex-direction: column;
		align-items: center;
		margin-bottom: 2rem;
	}

	.archive-meetings__hero-image {
		position: relative;
		display: block;
		width: 160px;
		height: 160px;
		margin-bottom: 1rem;

		.archive-meetings__image--one,
		.archive-meetings__image--two {
			position: absolute;
			inset: 0;
			width: 100%;
			height: 100%;
			object-fit: contain;
		}

		.archive-meetings__image--two {
			opacity: 0;
			transition: opacity 0.3s ease;
		}

		&:hover .archive-meetings__image--two {
			opacity: 1;
		}
	}

	.archive-meetings__title {
		font-size: 2rem;
		text-transform: uppercase;
		letter-spacing: 0.05em;
		text-align: center;
		margin: 0;
	}

	.archive-meetings__meta {
		text-align: center;
		font-size: 1rem;
		color: #555;
		margin-top: 0.5rem;
	}

	.archive-meetings__content {
		font-size: 1rem;
		line-height: 1.6;

		p {
			margin-bottom: 1rem;
		}
	}

	.archive-meetings__separator {
		display: flex;
		justify-content: center;
		margin: 2rem 0;
	}

	.archive-meetings__back-top {
		display: flex;
		justify-content: center;
		margin: 3rem 0;
	}

	@media (prefers-reduced-motion: reduce) {
		.archive-meetings__image--two {
			transition: none;
		}
	}
}
```

- [ ] **Step 2: Commit**

```bash
git add wp-content/themes/kzmielec/webpack/src/patterns/archive-meetings/style.scss
git commit -m "Add archive-meetings pattern styles"
```

---

## Task 21: Build theme assets

**Files:** (no code changes)

- [ ] **Step 1: Build theme**

Run: `ddev theme:dev 2>&1 | tail -5`
Expected: `webpack compiled successfully`.

- [ ] **Step 2: Verify pattern CSS generated**

Run: `ls /home/lukasz/projects/kzmielec/wp-content/themes/kzmielec/assets/css/patterns/ | grep -E "(page-belief|archive-meetings)"`
Expected: `page-belief-style.css`, `archive-meetings-style.css`.

- [ ] **Step 3: Run production build too**

Run: `ddev theme:prod 2>&1 | tail -5`
Expected: `compiled successfully`. Min files generated.

- [ ] **Step 4: No commit needed** (build assets committed after testing)

---

## Task 22: Assign "Strona wiary" template to belief pages

**Files:** (database state via WP-CLI)

- [ ] **Step 1: List existing belief pages**

Run: `ddev wp post list --post_type=page --format=table --fields=ID,post_title,post_name`
Expected: List of all pages. Identify: w-co-wierzymy, misja, wizja, wartosci, historia, roznica-wyznan, prawo, rodo.

- [ ] **Step 2: Assign template to each belief page**

For each belief page slug, get its ID and assign template:

```bash
# Replace <ID> with actual page IDs from previous step.
ddev wp post meta update <ID_misja> _wp_page_template page-belief.php
ddev wp post meta update <ID_wizja> _wp_page_template page-belief.php
ddev wp post meta update <ID_wartosci> _wp_page_template page-belief.php
ddev wp post meta update <ID_historia> _wp_page_template page-belief.php
ddev wp post meta update <ID_roznica_wyznan> _wp_page_template page-belief.php
ddev wp post meta update <ID_prawo> _wp_page_template page-belief.php
ddev wp post meta update <ID_rodo> _wp_page_template page-belief.php
ddev wp post meta update <ID_w_co_wierzymy> _wp_page_template page-belief.php
```

Expected for each: `Success: Updated custom field '_wp_page_template'.`

- [ ] **Step 3: Verify one page in WP Admin**

Open: `https://kzmielec.ddev.site/wp-admin/post.php?post=<ID_misja>&action=edit`

Verify in right panel:
- Page Attributes → Template: "Strona wiary"
- "Strona wiary" meta box visible (Hover image field)

- [ ] **Step 4: No commit needed** (database state)

---

## Task 23: Configure BeliefSettings

**Files:** (admin UI configuration)

- [ ] **Step 1: Open BeliefSettings admin**

Open: `https://kzmielec.ddev.site/wp-admin/admin.php?page=kzmielec-belief-settings`

- [ ] **Step 2: Add 8 belief pages in order**

Using "Dodaj stronę" select, add pages in this order:
1. W co wierzymy
2. Misja
3. Wizja
4. Wartości
5. Historia
6. Różnice wyznań
7. Prawo
8. RODO

Drag to reorder if needed.

- [ ] **Step 3: Save**

Click "Zapisz zmiany". Expected: green success notice "Ustawienia zapisane."

- [ ] **Step 4: Verify via WP-CLI**

Run: `ddev wp option get kzmielec_belief_pages --format=json`
Expected: JSON array of 8 page IDs in selected order.

- [ ] **Step 5: No commit needed** (option in database)

---

## Task 24: Create 3 meeting CPT posts

**Files:** (WP Admin content)

- [ ] **Step 1: Add Meeting 1 — Nabożeństwo Główne**

Open: `https://kzmielec.ddev.site/wp-admin/post-new.php?post_type=meetings`

Fill:
- Title: `Nabożeństwo Główne`
- Content: copy from `wp-content/themes/html5blank-stable/page-zaplanuj-wizyte.php` (paragraphs about Sunday service)
- Featured image: upload from old theme `img/wizyty/1.png`
- Meta box "Szczegóły spotkania":
  - Dzień i godzina: `Niedziela 10:30`
  - Miejsce: `ul. Dąbrowskiego 1a`
  - Anchor ID: `10`
  - Hover image: (optional)
- Order (Page Attributes): `1`
- Publish

- [ ] **Step 2: Add Meeting 2 — Mała Kawka**

Fill:
- Title: `Mała Kawka`
- Content: copy from old page-zaplanuj-wizyte.php (paragraphs about coffee fellowship)
- Featured image: from old theme `img/wizyty/2.png`
- Day/hour: (leave empty per old theme)
- Anchor: `11`
- Order: `2`
- Hover image: from `img/wizyty/2s.png`
- Publish

- [ ] **Step 3: Add Meeting 3 — Studium Słowa i modlitwa**

Fill:
- Title: `Studium Słowa i modlitwa`
- Content: copy from old page-zaplanuj-wizyte.php
- Featured image: from `img/wizyty/4.png`
- Day/hour: `Piątek 18:00`
- Anchor: `12`
- Order: `3`
- Hover image: from `img/wizyty/4s.png`
- Publish

- [ ] **Step 4: Verify on archive**

Open: `https://kzmielec.ddev.site/zaplanuj-wizyte/`
Expected: 3 meetings displayed in order with full content, anchor IDs (#10, #11, #12), scroll arrows between them.

- [ ] **Step 5: Test anchor links**

Open: `https://kzmielec.ddev.site/zaplanuj-wizyte/#11`
Expected: Page scrolls to "Mała Kawka" section.

---

## Task 25: Add navigable-tiles blocks to homepage

**Files:** (Gutenberg content editing)

- [ ] **Step 1: Open homepage editor**

Open: `https://kzmielec.ddev.site/wp-admin/` → Pages → find "Strona główna" → Edit.

- [ ] **Step 2: Add "Zaplanuj wizytę" section**

Below scroll arrow that links to `#one` (Aktualności section), add a new section with anchor `id="two"`:

1. Add Group block with anchor `two`
2. Inside, add Heading H2 with text "Zaplanuj wizytę" and style "Z linią"
3. Add Kafelki nawigacyjne block
4. Block settings:
   - Źródło danych: Spotkania
   - Liczba kolumn: 3
   - Pokaż dzień i godzinę: ON
   - Wyróżnij aktualną stronę: OFF (no current page concept on homepage)

- [ ] **Step 3: Add scroll arrow to "#three"**

After tiles, add Scroll Arrow block:
- Target ID: `three`
- Direction: Down

- [ ] **Step 4: Add "W co i jak wierzymy" section**

Add Group block with anchor `three`:
1. Heading H2 "W co i jak wierzymy" style "Z linią"
2. Kafelki nawigacyjne block
3. Block settings:
   - Źródło danych: Wiara
   - Liczba kolumn: 4
   - Wyróżnij aktualną stronę: OFF (homepage doesn't match any belief page)

- [ ] **Step 5: Add scroll arrow to "#four"**

After tiles, add Scroll Arrow block:
- Target ID: `four`
- Direction: Down

- [ ] **Step 6: Save page**

Click "Save". Open frontend `https://kzmielec.ddev.site/` and verify both sections render.

- [ ] **Step 7: Test "you are here" on belief page**

Open: `https://kzmielec.ddev.site/misja/`
Expected:
- Heading "W co i jak wierzymy" at top
- Hero tile with Misja image + title
- Content
- Scroll arrow
- 8 navigation tiles at bottom
- "Misja" tile: NO hover image swap on hover, has `aria-current="page"` and screen reader text "(aktualna strona)"
- Other tiles: hover swap works

---

## Task 26: Run PHPStan + PHPCS on all new code

**Files:** (verification only)

- [ ] **Step 1: PHPStan on plugin**

Run:
```bash
ddev exec "cd wp-content/plugins/custom-block-package && ./vendor/bin/phpstan analyse \
  app/Admin/MeetingMeta.php \
  app/Services/NavigableTilesService.php \
  src/blocks/navigable-tiles/render.php \
  app/Cache/BlockCache.php \
  index.php"
```
Expected: `[OK] No errors`.

- [ ] **Step 2: PHPCS on plugin**

Run:
```bash
ddev exec "cd wp-content/plugins/custom-block-package && ./vendor/bin/phpcs --standard=phpcs.xml --extensions=php \
  app/Admin/MeetingMeta.php \
  app/Services/NavigableTilesService.php \
  src/blocks/navigable-tiles/render.php \
  app/Cache/BlockCache.php \
  index.php"
```
Expected: 0 errors. Auto-fix warnings with `phpcbf` if needed.

- [ ] **Step 3: PHPCS on theme**

Run:
```bash
ddev exec "cd wp-content/themes/kzmielec && \
  if [ -f ./vendor/bin/phpcs ]; then \
    ./vendor/bin/phpcs App/Admin/BeliefSettings.php App/Admin/BeliefPageMeta.php page-belief.php archive-meetings.php; \
  else \
    echo 'No theme phpcs; using plugin phpcs config'; \
    cd ../../plugins/custom-block-package && \
    ./vendor/bin/phpcs --standard=phpcs.xml --extensions=php \
      ../../themes/kzmielec/App/Admin/BeliefSettings.php \
      ../../themes/kzmielec/App/Admin/BeliefPageMeta.php \
      ../../themes/kzmielec/page-belief.php \
      ../../themes/kzmielec/archive-meetings.php; \
  fi"
```
Expected: 0 errors.

- [ ] **Step 4: Fix any reported issues**

If errors, fix inline and re-run. If only warnings (e.g., alignment), run `phpcbf` to auto-fix.

---

## Task 27: WCAG audit

**Files:** (browser-based verification)

- [ ] **Step 1: Lighthouse audit on homepage**

Open Chrome DevTools → Lighthouse → Accessibility audit on `https://kzmielec.ddev.site/`
Expected: Score ≥ 95. Check for warnings about contrast, alt text, ARIA.

- [ ] **Step 2: Lighthouse audit on /misja/**

Same on belief subpage.

- [ ] **Step 3: Lighthouse on /zaplanuj-wizyte/**

Same on meetings archive.

- [ ] **Step 4: Keyboard navigation test**

On `/misja/`:
- Tab through page from top
- Each tile in navigation should be reachable via Tab
- Focus ring (3px outline) should be visible
- Enter on tile should navigate to that page
- "Misja" tile (current) — focus works but cursor is default

- [ ] **Step 5: Screen reader test (manual)**

Use NVDA (Windows) or VoiceOver (Mac):
- Navigate to nav tiles
- "Misja" tile should announce: "Misja, aktualna strona, link"
- Other tiles should announce: "[Title], link"

- [ ] **Step 6: Reduced motion test**

Chrome DevTools → Rendering tab → Emulate `prefers-reduced-motion: reduce`
Reload `/misja/`. Hover over tile.
Expected: No animation/transition on hover swap.

- [ ] **Step 7: Document findings**

If any WCAG issue found, fix in style.scss or render.php and re-run.

---

## Task 28: Final commit, rebuild, push

**Files:** (final batch)

- [ ] **Step 1: Final plugin build**

Run: `ddev plugin:build 2>&1 | tail -3`
Expected: success.

- [ ] **Step 2: Final theme build (dev + prod)**

Run: `ddev theme:dev && ddev theme:prod 2>&1 | tail -3`
Expected: success.

- [ ] **Step 3: Stage build artifacts**

```bash
git add wp-content/plugins/custom-block-package/build/blocks/navigable-tiles/
git add wp-content/themes/kzmielec/assets/css/patterns/page-belief-style*.css
git add wp-content/themes/kzmielec/assets/css/patterns/archive-meetings-style*.css
git add wp-content/themes/kzmielec/assets/js/admin/belief-settings.js
```

- [ ] **Step 4: Verify git status clean of unrelated files**

Run: `git status --short`
Expected: only navigable-tiles related files + maybe some build artifacts.

- [ ] **Step 5: Commit build artifacts**

```bash
git commit -m "Build navigable-tiles assets and theme patterns"
```

- [ ] **Step 6: Push main, merge develop**

```bash
git push origin main
git checkout develop
git merge main
git push origin develop
git checkout main
```

Expected: all green, branches synced.

- [ ] **Step 7: Verify final state**

Run: `git status --short`
Expected: working tree clean (or only unrelated stale files).

Run: `git log --oneline -10`
Expected: ~26-28 new commits for this feature.

---

## Verification Summary

After completing all tasks, verify these end-to-end scenarios pass:

### Scenario 1: New meeting flows through
1. Add new CPT meetings post in WP Admin
2. Set anchor `13`, day/hour `Niedziela 17:00`
3. Set featured image
4. Save
5. Hard refresh homepage → new tile appears in "Zaplanuj wizytę" section
6. Visit `/zaplanuj-wizyte/#13` → full description renders

### Scenario 2: New belief flows through
1. Create new Page in WP Admin
2. Title: "Test wiary"
3. Template: "Strona wiary"
4. Featured image + hover image (meta box)
5. Save
6. Go to Belief Settings → add "Test wiary" → save order
7. Hard refresh homepage → new tile in "W co i jak wierzymy"
8. Hard refresh `/misja/` → "Test wiary" tile in bottom nav
9. Visit `/test-wiary/` → page-belief template renders, "Test wiary" tile in nav has aria-current

### Scenario 3: "You are here" works
1. Visit `/misja/`
2. Scroll to nav at bottom
3. Inspect "Misja" tile → `aria-current="page"`, missing image-two
4. Screen reader announces "(aktualna strona)"

### Scenario 4: Cache invalidation works
1. Visit homepage, note title of first meeting tile
2. Edit that meeting in admin, change title
3. Save
4. Hard refresh homepage → new title visible (no manual cache flush needed)

### Scenario 5: Standards compliance
- PHPStan: `[OK] No errors`
- PHPCS: 0 errors
- Lighthouse Accessibility: ≥ 95
- Keyboard navigation works
- Reduced motion respected

---

**Plan complete and saved to `docs/superpowers/plans/2026-05-18-navigable-tiles-implementation.md`.**
