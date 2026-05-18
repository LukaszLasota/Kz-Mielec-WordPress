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
