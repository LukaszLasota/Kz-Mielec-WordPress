import Glide from '@glidejs/glide';

export function initGlide(selector, options = {}) {
  return new Glide(selector, {
    type: 'carousel',
    gap: 0,
    autoplay: 3000,
    keyboard: true,
    ...options,
  }).mount();
}

// Export as a global so WordPress-enqueued scripts can call it.
window.initGlide = initGlide;