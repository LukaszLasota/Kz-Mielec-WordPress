/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import './editor.css';
import metadata from './block.json';
import Edit from './edit.js';
import save from './save.js';

registerBlockType(metadata.name, {
    ...metadata,
    /**
     * @see ./edit.js
     */
    edit: Edit,
    
    /**
     * @see ./save.js
     */
    save
});