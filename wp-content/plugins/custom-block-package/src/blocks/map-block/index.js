import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import Edit from './edit.js';
import block from './block.json';
import './style.scss';

registerBlockType(block.name, {
    ...block,
    edit: Edit,
    save: () => {
        return null;
    }
});
