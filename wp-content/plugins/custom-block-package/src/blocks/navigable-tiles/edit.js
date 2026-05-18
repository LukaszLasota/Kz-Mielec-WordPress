import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	RangeControl,
	ToggleControl,
	TextControl,
	Disabled,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import './index.scss';

const Edit = ( { attributes, setAttributes } ) => {
	const { dataSource, columns, showDayHour, highlightCurrent, sectionTitle } = attributes;
	const blockProps = useBlockProps();

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Ustawienia bloku', 'custom-block-package' ) } initialOpen={ true }>
					<SelectControl
						label={ __( 'Źródło danych', 'custom-block-package' ) }
						value={ dataSource }
						options={ [
							{ label: __( 'Wiara (strony)', 'custom-block-package' ), value: 'beliefs' },
							{ label: __( 'Spotkania (CPT)', 'custom-block-package' ), value: 'meetings' },
						] }
						onChange={ ( value ) => setAttributes( { dataSource: value } ) }
					/>
					<RangeControl
						label={ __( 'Liczba kolumn', 'custom-block-package' ) }
						value={ columns }
						onChange={ ( value ) => setAttributes( { columns: value } ) }
						min={ 1 }
						max={ 6 }
					/>
					<TextControl
						label={ __( 'Tytuł sekcji (opcjonalny)', 'custom-block-package' ) }
						value={ sectionTitle }
						onChange={ ( value ) => setAttributes( { sectionTitle: value } ) }
					/>
					{ dataSource === 'meetings' && (
						<ToggleControl
							label={ __( 'Pokaż dzień i godzinę', 'custom-block-package' ) }
							checked={ showDayHour }
							onChange={ ( value ) => setAttributes( { showDayHour: value } ) }
						/>
					) }
					<ToggleControl
						label={ __( 'Wyróżnij aktualną stronę', 'custom-block-package' ) }
						checked={ highlightCurrent }
						onChange={ ( value ) => setAttributes( { highlightCurrent: value } ) }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<Disabled>
					<ServerSideRender
						block="custom-block-package/navigable-tiles"
						attributes={ attributes }
					/>
				</Disabled>
			</div>
		</>
	);
};

export default Edit;
