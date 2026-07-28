<?php
/**
 * Churches Meta Box — Admin repeater UI for editing church comparison data.
 *
 * This meta box appears on the comparison_topic edit screen and provides:
 *   - A sort order number input (controls display position in the accordion)
 *   - A repeater for adding/removing churches, each with:
 *     - Church name (text input)
 *     - Description/position (TinyMCE rich text editor)
 *
 * How the repeater works:
 *   - Existing churches are rendered server-side with wp_editor() (full TinyMCE).
 *   - New churches added via JS use a <template> with a plain <textarea>,
 *     then meta-box.js initializes TinyMCE on it dynamically.
 *   - On form submit, TinyMCE content is synced to textareas via triggerSave().
 *   - On remove, TinyMCE instance is destroyed before removing the DOM element.
 *
 * Data is saved as the 'churches' post meta (array of {church_name, description}).
 * See ChurchesMeta.php for the schema definition.
 *
 * @package ComparisonOfReligions
 */

declare(strict_types=1);

namespace ComparisonOfReligions\MetaBoxes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ComparisonOfReligions\PostTypes\ComparisonTopic;

/**
 * Renders and saves the Churches repeater meta box.
 *
 * Hooks:
 *   - add_meta_boxes: registers the meta box
 *   - save_post_{cpt}: saves data with nonce + capability checks
 *   - admin_enqueue_scripts: loads meta-box.js + meta-box.css on the edit screen
 */
class ChurchesMetaBox {

	/**
	 * Constructor — registers all necessary WordPress hooks.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );
		add_action( 'save_post_' . ComparisonTopic::POST_TYPE, [ $this, 'save' ], 10, 1 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
	}

	/**
	 * Register the meta box.
	 */
	public function add_meta_box(): void {
		add_meta_box(
			'cor_churches_meta_box',
			__( 'Stanowiska kosciolow', 'comparison-of-religions' ),
			[ $this, 'render' ],
			ComparisonTopic::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Enqueue admin scripts only on the comparison_topic edit screen.
	 *
	 * @param string $hook_suffix Current admin page.
	 */
	public function enqueue_admin_scripts( string $hook_suffix ): void {
		if ( ! in_array( $hook_suffix, [ 'post.php', 'post-new.php' ], true ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || ComparisonTopic::POST_TYPE !== $screen->post_type ) {
			return;
		}

		// Ensure TinyMCE and quicktags are available for dynamic editors.
		wp_enqueue_editor();

		wp_enqueue_script(
			'cor-meta-box',
			COR_PLUGIN_URL . 'app/MetaBoxes/meta-box.js',
			[ 'editor', 'quicktags' ],
			COR_VERSION,
			true
		);

		wp_enqueue_style(
			'cor-meta-box',
			COR_PLUGIN_URL . 'app/MetaBoxes/meta-box.css',
			[],
			COR_VERSION
		);
	}

	/**
	 * Render the meta box HTML.
	 *
	 * @param \WP_Post $post Current post.
	 */
	public function render( \WP_Post $post ): void {
		$churches   = get_post_meta( $post->ID, 'churches', true );
		$sort_order = (int) get_post_meta( $post->ID, 'sort_order', true );

		if ( ! is_array( $churches ) || empty( $churches ) ) {
			$churches = [];
		}

		wp_nonce_field( 'cor_churches_save', 'cor_churches_nonce' );
		?>
		<div class="cor-meta-box">
			<div class="cor-meta-box__field">
				<label for="cor_sort_order">
					<strong><?php esc_html_e( 'Kolejnosc wyswietlania', 'comparison-of-religions' ); ?></strong>
				</label>
				<input
					type="number"
					id="cor_sort_order"
					name="cor_sort_order"
					value="<?php echo esc_attr( (string) $sort_order ); ?>"
					min="0"
					step="1"
					style="width: 80px;"
				/>
			</div>

			<hr />

			<div id="cor-churches-repeater">
				<?php
				if ( ! empty( $churches ) ) {
					foreach ( $churches as $index => $church ) {
						$this->render_church_row( $index, $church );
					}
				}
				?>
			</div>

			<button type="button" id="cor-add-church" class="button button-primary">
				<?php esc_html_e( '+ Dodaj kosciol', 'comparison-of-religions' ); ?>
			</button>

			<!-- Hidden template for JS cloning (uses textarea, JS inits TinyMCE) -->
			<template id="cor-church-template">
				<?php
				$this->render_church_row(
					'__INDEX__',
					[
						'church_name' => '',
						'description' => '',
					],
					true
				);
				?>
			</template>
		</div>
		<?php
	}

	/**
	 * Render a single church repeater row.
	 *
	 * When $is_template is true, renders a plain <textarea> instead of wp_editor().
	 * This is used inside the <template> tag — JS clones it and initializes TinyMCE
	 * dynamically (wp_editor() can't be initialized on cloned elements).
	 *
	 * @param int|string $index       Row index (or '__INDEX__' for the JS template).
	 * @param array      $church      Church data {church_name, description}.
	 * @param bool       $is_template Whether this is the JS template row.
	 */
	private function render_church_row( $index, array $church, bool $is_template = false ): void {
		$name       = $church['church_name'] ?? '';
		$desc       = $church['description'] ?? '';
		$editor_id  = 'cor_desc_' . $index;
		$field_name = 'cor_churches[' . $index . '][description]';
		?>
		<div class="cor-church-row" data-index="<?php echo esc_attr( (string) $index ); ?>">
			<div class="cor-church-row__header">
				<strong><?php esc_html_e( 'Kosciol', 'comparison-of-religions' ); ?></strong>
				<button type="button" class="button cor-remove-church">
					<?php esc_html_e( 'Usun', 'comparison-of-religions' ); ?>
				</button>
			</div>
			<div class="cor-church-row__fields">
				<label>
					<?php esc_html_e( 'Nazwa kosciola', 'comparison-of-religions' ); ?>
					<input
						type="text"
						name="cor_churches[<?php echo esc_attr( (string) $index ); ?>][church_name]"
						value="<?php echo esc_attr( $name ); ?>"
						class="widefat"
						placeholder="<?php esc_attr_e( 'np. Kosciol Rzymskokatolicki', 'comparison-of-religions' ); ?>"
					/>
				</label>
				<div class="cor-church-row__editor">
					<p><strong><?php esc_html_e( 'Opis / stanowisko', 'comparison-of-religions' ); ?></strong></p>
					<?php if ( $is_template ) : ?>
						<textarea
							id="<?php echo esc_attr( $editor_id ); ?>"
							name="<?php echo esc_attr( $field_name ); ?>"
							class="cor-editor-textarea widefat"
							rows="8"
						><?php echo esc_textarea( $desc ); ?></textarea>
						<?php
					else :
						wp_editor(
							$desc,
							$editor_id,
							[
								'textarea_name' => $field_name,
								'media_buttons' => false,
								'textarea_rows' => 8,
								'teeny'         => true,
								'quicktags'     => true,
								'tinymce'       => [
									'toolbar1' => 'bold,italic,underline,separator,bullist,numlist,separator,link,unlink,separator,undo,redo',
									'toolbar2' => '',
								],
							]
						);
					endif;
					?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Save meta box data on post save.
	 *
	 * Security checks: nonce verification, autosave skip, capability check.
	 * Saves both 'sort_order' (integer) and 'churches' (array) post meta.
	 * Empty church rows (no name and no description) are silently discarded.
	 *
	 * @param int $post_id Post ID.
	 */
	public function save( int $post_id ): void {
		// Verify nonce — prevents CSRF and ensures the save came from our meta box.
		if (
			! isset( $_POST['cor_churches_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cor_churches_nonce'] ) ), 'cor_churches_save' )
		) {
			return;
		}

		// Skip autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check capabilities.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Save sort_order.
		if ( isset( $_POST['cor_sort_order'] ) ) {
			update_post_meta( $post_id, 'sort_order', absint( $_POST['cor_sort_order'] ) );
		}

		// Save churches.
		$churches = [];
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below per field.
		$raw_churches = isset( $_POST['cor_churches'] ) ? wp_unslash( $_POST['cor_churches'] ) : [];

		if ( is_array( $raw_churches ) ) {
			foreach ( $raw_churches as $church ) {
				$church_name = sanitize_text_field( $church['church_name'] ?? '' );
				$description = wp_kses_post( $church['description'] ?? '' );

				if ( '' === $church_name && '' === $description ) {
					continue;
				}

				$churches[] = [
					'church_name' => $church_name,
					'description' => $description,
				];
			}
		}

		update_post_meta( $post_id, 'churches', $churches );
	}
}
