/**
 * Banner Hero Pattern Script
 *
 * Smooth scroll for the arrow button.
 */

document.addEventListener('DOMContentLoaded', () => {
	const arrow = document.querySelector('.pattern-banner-hero .black-circle a');
	if (!arrow) return;

	arrow.addEventListener('click', (e: Event) => {
		const target = document.querySelector((arrow as HTMLAnchorElement).getAttribute('href') || '');
		if (target) {
			e.preventDefault();
			target.scrollIntoView({ behavior: 'smooth', block: 'start' });
		}
	});
});
