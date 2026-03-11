import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls, RichText } from '@wordpress/block-editor';
import { PanelBody, PanelRow, Button, ToggleControl, RangeControl, } from '@wordpress/components';
import { Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import block from './block.json';
import { MediaUpload } from '@wordpress/block-editor';
import './index.scss';
import './style.scss';

registerBlockType(block.name, {


  edit: ({ attributes, setAttributes }) => {
    const { pdfUrl, showDownload, viewerHeight, viewerWidth, btnTitle, pdfTitle } = attributes;
    const blockProps = useBlockProps({
      className: 'cbp-block',
    });

    const onSelectPDF = (media) => {
      setAttributes({
        pdfUrl: media.url,
        pdfTitle: media.title
      });
    };

    const onHeightChange = (value) => {
      setAttributes({
        viewerHeight: value,
      });
    };

    const onWidthChange = (value) => {
      setAttributes({
        viewerWidth: value,
      });
    };

    const onDownloadButtonChange = (checked) => {
      setAttributes({
        showDownload: checked,
      });
    };

    return (
      <Fragment>
        <InspectorControls>
          <PanelBody title={__('PDF Ustawienia', 'custom-block-package')} initialOpen={true}>
            <PanelRow>
              <MediaUpload
                onSelect={onSelectPDF}
                allowedTypes={['application/pdf']}
                value={pdfUrl}
                render={({ open }) => (
                  <Button isPrimary onClick={open}>
                    {pdfUrl ? __('Zmień PDF', 'custom-block-package') : __('Dodaj PDF', 'custom-block-package')}

                  </Button>
                )}
              />
            </PanelRow>
            <PanelRow>
              <RichText
                value={pdfTitle}
                withoutInteractiveFormatting
                placeholder={__("Tytuł pdf", "custom-block-package")}
              />
            </PanelRow>
          </PanelBody>
          <PanelBody title={__('Wysokość pdf', 'custom-block-package')}>
            <RangeControl
              label={__(
                'Wysokość pdf (piksele)',
                'custom-block-package'
              )}
              value={viewerHeight}
              onChange={onHeightChange}
              min={0}
              max={1500}
              allowReset={true}
            />
          </PanelBody>
          <PanelBody title={__('Szerokość pdf', 'custom-block-package')}>
            <RangeControl
              label={__(
                'Szerokość pdf (procenty)',
                'custom-block-package'
              )}
              help="By default 0 will be 100%."
              value={viewerWidth}
              onChange={onWidthChange}
              min={20}
              max={100}
              allowReset={true}
            />
          </PanelBody>
          <PanelBody title={__('Pokaż przycisk pobierz', 'custom-block-package')}>
            <ToggleControl
              label={__('Pokaż/Ukryj przycisk', 'custom-block-package')}
              help={
                showDownload ?
                  __('Pokaż przycisk pobierz', 'custom-block-package') :
                  __('Ukryj przycisk pobierz', 'custom-block-package')
              }
              checked={showDownload}
              onChange={onDownloadButtonChange}

            // onChange={showDownload => setAttributes({ showDownload })}
            />
          </PanelBody>
          <PanelBody title={__('Wpisz tekst przycisku', 'custom-block-package')}>
            <RichText
              value={btnTitle}
              withoutInteractiveFormatting
              onChange={(btnTitle) => setAttributes({ btnTitle })}
              placeholder={__("Tytuł", "custom-block-package")}
            />
          </PanelBody>
        </InspectorControls>
        <section class="statut-page" {...blockProps}>
          <div class="statut-page-head">
            <div class="center-pdf">
              {pdfUrl && (
                <embed src={pdfUrl}
                  type="application/pdf"
                  width={viewerWidth + "%"}
                  height={viewerHeight + "px"}
                />
              )}
              {
                showDownload && (
                  <div class="center-pdf__btn">
                    <a class="more" href={pdfUrl}>{btnTitle}</a>
                  </div>
                )
              }
            </div>
          </div>
        </section>
      </Fragment>
    );
  },

  save: ({ attributes }) => {
    const { pdfUrl, viewerHeight, viewerWidth, showDownload, btnTitle } = attributes;
    const blockProps = useBlockProps.save({
      className: 'cbp-block',
    });

    return (
      <section class="statut-page" {...blockProps}>
        <div class="statut-page-head">
          <div class="center-pdf">
            {pdfUrl && (
              <embed src={pdfUrl}
                type="application/pdf"
                width={viewerWidth + "%"}
                height={viewerHeight + "px"}
              />
            )}
            {
              showDownload && (
                <div class="center-pdf__btn">
                  <a class="more" href={pdfUrl}>{btnTitle}</a>
                </div>
              )
            }
          </div>
        </div>
      </section>
    );
  },
});

