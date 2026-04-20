import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl, ToggleControl, Disabled } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import './index.scss';

const Edit = ({ attributes, setAttributes }) => {
	const { postsCount, showImages, showDate, columns, containerHeight } = attributes;
	const blockProps = useBlockProps();

	return (
		< >
			< InspectorControls >
				< PanelBody title = {__( "Ustawienia feedu", "custom-block-package" )} initialOpen = {true} >
					< RangeControl
						label     = {__( "Liczba postów", "custom-block-package" )}
						value     = {postsCount}
						onChange  = {(value) => setAttributes( { postsCount: value } )}
						min       = {1}
						max       = {20}
					/ >
					< RangeControl
						label     = {__( "Liczba kolumn", "custom-block-package" )}
						value     = {columns}
						onChange  = {(value) => setAttributes( { columns: value } )}
						min       = {1}
						max       = {3}
					/ >
					< RangeControl
						label     = {__( "Wysokość pudełka (px)", "custom-block-package" )}
						value     = {containerHeight}
						onChange  = {(value) => setAttributes( { containerHeight: value } )}
						min       = {300}
						max       = {1200}
						step      = {50}
						help      = {__( "Posty przewijają się wewnątrz tego pudełka.", "custom-block-package" )}
					/ >
					< ToggleControl
						label     = {__( "Pokaż zdjęcia", "custom-block-package" )}
						checked   = {showImages}
						onChange  = {(value) => setAttributes( { showImages: value } )}
					/ >
					< ToggleControl
						label     = {__( "Pokaż datę publikacji", "custom-block-package" )}
						checked   = {showDate}
						onChange  = {(value) => setAttributes( { showDate: value } )}
					/ >
				< / PanelBody >
			< / InspectorControls >

			< div {...blockProps} >
				< Disabled >
					< ServerSideRender
						block      = "custom-block-package/facebook-feed"
						attributes = {attributes}
					/ >
				< / Disabled >
			< / div >
		< / >
	);
};

export default Edit;
