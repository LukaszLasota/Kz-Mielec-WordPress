/**
 * Comparison Accordion — Frontend toggle behavior with smooth CSS transitions.
 *
 * This script runs on the frontend (not in the editor — see editor.scss for that).
 * It converts the server-rendered accordion from a no-JS "hidden" attribute approach
 * to an animated max-height transition.
 *
 * WCAG 2.1 AA compliance:
 *   - aria-expanded on trigger buttons
 *   - aria-hidden on panels
 *   - Keyboard navigation: Enter/Space to toggle, Arrow Up/Down to move between
 *     accordion headers, Home/End to jump to first/last header
 *
 * Animation approach:
 *   1. On init: replace [hidden] attribute with .cor-accordion__panel--collapsed class
 *      (max-height: 0; opacity: 0) — this enables CSS transitions.
 *   2. Open: set inline max-height to scrollHeight, transition plays, then set to 'none'
 *      so content can grow if needed (e.g. window resize).
 *   3. Close: lock current height as inline style, force reflow, transition to 0,
 *      then re-apply collapsed class and clean up inline styles.
 */
document.addEventListener('DOMContentLoaded', () => {
	const items = document.querySelectorAll('.cor-accordion__item');
	const triggers = []; // Collected for keyboard navigation between headers.

	items.forEach((item) => {
		const trigger = item.querySelector('.cor-accordion__trigger');
		const panel = item.querySelector('.cor-accordion__panel');

		if (!trigger || !panel) {
			return;
		}

		triggers.push(trigger);

		// Replace [hidden] attribute with CSS class to enable animated transitions.
		// The [hidden] attr is the no-JS fallback (hides panel with display: none).
		panel.removeAttribute('hidden');
		panel.classList.add('cor-accordion__panel--collapsed');
		panel.setAttribute('aria-hidden', 'true');

		const open = () => {
			item.classList.add('cor-accordion__item--active');
			trigger.setAttribute('aria-expanded', 'true');
			panel.setAttribute('aria-hidden', 'false');

			// Set starting values inline before removing the collapsed class.
			panel.style.maxHeight = '0';
			panel.style.opacity = '0';
			panel.classList.remove('cor-accordion__panel--collapsed');

			// Force reflow so the browser registers 0 as the starting point.
			void panel.offsetHeight;

			// Animate to full content height.
			panel.style.maxHeight = panel.scrollHeight + 'px';
			panel.style.opacity = '1';

			// After animation completes, set max-height to 'none' so the panel
			// can grow freely (e.g. if window is resized or content changes).
			panel.addEventListener(
				'transitionend',
				(e) => {
					if (e.propertyName !== 'max-height') return;
					panel.style.maxHeight = 'none';
					panel.style.opacity = '';
				},
				{ once: true }
			);
		};

		const close = () => {
			item.classList.remove('cor-accordion__item--active');
			trigger.setAttribute('aria-expanded', 'false');

			// Lock current height as explicit inline value (starting point for transition).
			panel.style.maxHeight = panel.scrollHeight + 'px';
			panel.style.opacity = '1';

			// Force reflow so the browser registers scrollHeight as the start.
			void panel.offsetHeight;

			// Animate to collapsed state.
			panel.style.maxHeight = '0';
			panel.style.opacity = '0';

			// After animation, restore collapsed class and clean up inline styles.
			panel.addEventListener(
				'transitionend',
				(e) => {
					if (e.propertyName !== 'max-height') return;
					panel.classList.add('cor-accordion__panel--collapsed');
					panel.setAttribute('aria-hidden', 'true');
					panel.style.maxHeight = '';
					panel.style.opacity = '';
				},
				{ once: true }
			);
		};

		const toggle = () => {
			item.classList.contains('cor-accordion__item--active')
				? close()
				: open();
		};

		trigger.addEventListener('click', toggle);
	});

	// Keyboard navigation between accordion trigger buttons (WCAG pattern).
	// ArrowDown/ArrowUp: move focus to next/previous trigger (wraps around).
	// Home/End: jump to first/last trigger.
	// Enter/Space: toggle current panel.
	triggers.forEach((trigger, index) => {
		trigger.addEventListener('keydown', (event) => {
			let targetIndex = null;

			switch (event.key) {
				case 'ArrowDown':
					targetIndex =
						index < triggers.length - 1 ? index + 1 : 0;
					break;
				case 'ArrowUp':
					targetIndex =
						index > 0 ? index - 1 : triggers.length - 1;
					break;
				case 'Home':
					targetIndex = 0;
					break;
				case 'End':
					targetIndex = triggers.length - 1;
					break;
				case 'Enter':
				case ' ':
					event.preventDefault();
					trigger.click();
					return;
				default:
					return;
			}

			event.preventDefault();
			triggers[targetIndex].focus();
		});
	});
});