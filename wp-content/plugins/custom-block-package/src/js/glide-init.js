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

// Eksportuj jako globalną funkcję dla WordPress
window.initGlide = initGlide;