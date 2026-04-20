/**
 * Facebook Feed — infinite scroll via IntersectionObserver.
 *
 * Each block wrapper carries data attributes with REST endpoint + initial state.
 * When sentinel element enters viewport, fetches next batch and appends to grid.
 */

function initFeed(wrapper) {
	const scrollContainer = wrapper.querySelector('.facebook-feed__scroll');
	const grid = wrapper.querySelector('.facebook-feed__grid');
	const sentinel = wrapper.querySelector('.facebook-feed__sentinel');

	if (!scrollContainer || !grid || !sentinel) {
		return;
	}

	const endpoint = wrapper.dataset.endpoint;
	const showImages = wrapper.dataset.showImages === 'true';
	const showDate = wrapper.dataset.showDate === 'true';
	const batchSize = parseInt(wrapper.dataset.batchSize || '5', 10);

	let offset = parseInt(wrapper.dataset.offset || '0', 10);
	let hasMore = wrapper.dataset.hasMore === 'true';
	let loading = false;

	if (!endpoint || !hasMore) {
		return;
	}

	const fetchMore = async () => {
		if (loading || !hasMore) return;
		loading = true;
		wrapper.classList.add('is-loading');

		try {
			const url = new URL(endpoint, window.location.origin);
			url.searchParams.set('offset', String(offset));
			url.searchParams.set('limit', String(batchSize));
			url.searchParams.set('showImages', String(showImages));
			url.searchParams.set('showDate', String(showDate));

			const response = await fetch(url.toString(), {
				headers: { Accept: 'application/json' },
			});

			if (!response.ok) {
				throw new Error(`HTTP ${response.status}`);
			}

			const data = await response.json();

			if (data.html) {
				grid.insertAdjacentHTML('beforeend', data.html);
			}

			offset = offset + data.count;
			hasMore = !!data.hasMore;
			wrapper.dataset.offset = String(offset);
			wrapper.dataset.hasMore = String(hasMore);

			if (!hasMore) {
				observer.disconnect();
				sentinel.remove();
			}
		} catch (err) {
			// eslint-disable-next-line no-console
			console.error('Facebook Feed load failed:', err);
			hasMore = false;
			observer.disconnect();
			sentinel.remove();
		} finally {
			loading = false;
			wrapper.classList.remove('is-loading');
		}
	};

	const observer = new IntersectionObserver(
		(entries) => {
			if (entries.some((entry) => entry.isIntersecting)) {
				fetchMore();
			}
		},
		{
			root: scrollContainer,
			rootMargin: '200px 0px',
		}
	);

	observer.observe(sentinel);
}

document.addEventListener('DOMContentLoaded', () => {
	document
		.querySelectorAll('.wp-block-custom-block-package-facebook-feed[data-endpoint]')
		.forEach(initFeed);
});
