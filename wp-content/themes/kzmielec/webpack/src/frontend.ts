import './sass/frontend.scss';
import { HamburgerMenu } from './js/menu/menu';

new HamburgerMenu();

// Lazy-load masonry only on pages with .news container (blog/archive/search).
if (document.querySelector('.news')) {
  import(/* webpackChunkName: "masonry" */ './js/masonry/masonry.js');
}

// Prevent browser native jump to anchor on cross-page navigation.
if (window.location.hash) {
  history.scrollRestoration = 'manual';
  window.scrollTo(0, 0);
}

// Smooth scroll to anchor after page fully loads.
window.addEventListener('load', () => {
  const { hash } = window.location;
  if (!hash) return;

  const target = document.querySelector(hash);
  if (!target) return;

  setTimeout(() => {
    target.scrollIntoView({ behavior: 'smooth' });
  }, 100);
});