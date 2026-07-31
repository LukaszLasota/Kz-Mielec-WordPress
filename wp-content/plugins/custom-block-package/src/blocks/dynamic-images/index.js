import { registerBlockType } from '@wordpress/blocks';
import block from './block.json';
import Edit from './edit.js';
import './index.scss';
import './style.scss';

registerBlockType(block.name, {
    ...block,
    edit: Edit,
    save: () => null
});

