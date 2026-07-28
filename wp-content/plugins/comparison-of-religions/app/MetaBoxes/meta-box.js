/**
 * Churches Repeater Meta Box — Admin-side JavaScript.
 *
 * Handles the dynamic "add/remove church" repeater in the comparison_topic editor.
 *
 * How it works:
 *   - A hidden <template id="cor-church-template"> contains the HTML for one church row
 *     with a plain <textarea> (placeholder index "__INDEX__").
 *   - Clicking "Add Church" clones the template, replaces __INDEX__ with the real index,
 *     appends the row to the repeater, and initializes TinyMCE on the new textarea.
 *   - Clicking "Remove" destroys the TinyMCE instance (to prevent memory leaks) and
 *     removes the DOM element.
 *   - On form submit, all TinyMCE editors sync their content back to the underlying
 *     textareas via tinymce.triggerSave(), so $_POST receives the HTML content.
 *
 * Dependencies: TinyMCE (wp_enqueue_editor), quicktags — loaded by ChurchesMetaBox.php.
 */
document.addEventListener('DOMContentLoaded', () => {
	const repeater = document.getElementById('cor-churches-repeater');
	const addBtn = document.getElementById('cor-add-church');
	const template = document.getElementById('cor-church-template');

	if (!repeater || !addBtn || !template) {
		return;
	}

	// Track the next available index for new rows (avoids duplicate IDs).
	let nextIndex = repeater.querySelectorAll('.cor-church-row').length;

	/**
	 * Initialize a TinyMCE rich text editor on a plain textarea element.
	 * Also sets up quicktags (Text/HTML tab) if available.
	 */
	function initEditor(textarea) {
		const id = textarea.id;
		if (!id || !window.tinymce) {
			return;
		}

		// Initialize TinyMCE.
		tinymce.init({
			selector: '#' + id,
			menubar: false,
			toolbar: 'bold italic underline | bullist numlist | link unlink | undo redo',
			plugins: 'lists link',
			branding: false,
			height: 200,
			relative_urls: false,
			remove_script_host: false,
			setup: function (editor) {
				// Sync content back to textarea on change.
				editor.on('change keyup', function () {
					editor.save();
				});
			},
		});

		// Initialize quicktags if available.
		if (window.quicktags) {
			quicktags({ id: id });
			// Re-init QT buttons.
			if (window.QTags && QTags._buttonsInit) {
				QTags._buttonsInit();
			}
		}
	}

	/**
	 * Destroy a TinyMCE instance by editor ID.
	 * Must be called before removing the DOM element to prevent memory leaks.
	 */
	function destroyEditor(id) {
		if (window.tinymce) {
			const editor = tinymce.get(id);
			if (editor) {
				editor.save();
				editor.remove();
			}
		}
	}

	// Sync all TinyMCE editors before form submit.
	const form = repeater.closest('form');
	if (form) {
		form.addEventListener('submit', () => {
			if (window.tinymce) {
				tinymce.triggerSave();
			}
		});
	}

	// Add new church row.
	addBtn.addEventListener('click', () => {
		const html = template.innerHTML.replace(/__INDEX__/g, String(nextIndex));
		const wrapper = document.createElement('div');
		wrapper.innerHTML = html.trim();
		const newRow = wrapper.firstElementChild;
		repeater.appendChild(newRow);

		// Init TinyMCE on the new textarea.
		const textarea = newRow.querySelector('.cor-editor-textarea');
		if (textarea) {
			initEditor(textarea);
		}

		nextIndex++;

		// Focus the name input.
		const nameInput = newRow.querySelector('input[type="text"]');
		if (nameInput) {
			nameInput.focus();
		}
	});

	// Remove church row (event delegation).
	repeater.addEventListener('click', (e) => {
		if (e.target.classList.contains('cor-remove-church')) {
			const row = e.target.closest('.cor-church-row');
			if (row) {
				// Destroy TinyMCE instance before removing DOM.
				const textarea = row.querySelector('textarea');
				if (textarea && textarea.id) {
					destroyEditor(textarea.id);
				}
				row.remove();
			}
		}
	});
});
