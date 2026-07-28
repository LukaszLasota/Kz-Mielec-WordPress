/**
 * Comparison Accordion — Block registration.
 *
 * This is a dynamic block (server-side rendered via render.php),
 * so save() returns null — no HTML is stored in the post content.
 * All rendering happens at request time using current CPT data.
 */
import { registerBlockType } from '@wordpress/blocks';
import block from './block.json';
import Edit from './edit';
import './style.scss';

registerBlockType(block.name, {
	...block,
	edit: Edit,
	save: () => null, // Dynamic block — output comes from render.php.
});
