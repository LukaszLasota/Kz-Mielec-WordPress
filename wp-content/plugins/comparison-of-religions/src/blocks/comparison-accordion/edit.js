/**
 * Comparison Accordion — Editor component.
 *
 * Uses ServerSideRender to display a live preview of the accordion in the editor,
 * rendered by the same render.php used on the frontend. This gives WYSIWYG editing.
 *
 * The 'cor-editor-preview' class triggers editor.scss styles that force all
 * accordion panels open and disable the toggle chevron/interaction.
 *
 * Note: The block has no InspectorControls (sidebar panel) — it displays all
 * comparison data automatically. Category filtering was intentionally removed.
 */
import { useBlockProps } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';
import './editor.scss';

const Edit = ({ attributes }) => {
	const blockProps = useBlockProps({ className: 'cor-editor-preview' });

	return (
		<div {...blockProps}>
			<ServerSideRender
				block="comparison-of-religions/comparison-accordion"
				attributes={attributes}
			/>
		</div>
	);
};

export default Edit;
