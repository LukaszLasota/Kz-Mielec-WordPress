/**
 * Ambient declarations for non-TypeScript imports.
 *
 * The entries (`frontend.ts`, `editor.ts`, `print.ts`) import stylesheets for
 * their side effect — webpack turns those into CSS files. TypeScript needs to be
 * told these modules exist, otherwise editors report
 * "Cannot find module … for side-effect import of './scss/frontend.scss'".
 */
declare module '*.scss';
declare module '*.css';

/**
 * npm packages used by the lazy-loaded masonry bundle that ship no type
 * definitions of their own.
 */
declare module 'masonry-layout';
declare module 'imagesloaded';
