import { __ } from '@wordpress/i18n';
import {
    useBlockProps,
    InspectorControls,
    MediaUpload,
    MediaUploadCheck
} from '@wordpress/block-editor';
import {
    PanelBody,
    Button,
    TextControl
} from '@wordpress/components';
import { Fragment } from '@wordpress/element';
import './index.scss';

/**
 * Helper component for a single image upload slot.
 */
const ImageSlot = ({ label, imgID, imgURL, onSelect, onRemove, previewAlt }) => (
    <MediaUploadCheck>
        <h4>{label}</h4>
        <MediaUpload
            onSelect={onSelect}
            allowedTypes={["image"]}
            value={imgID}
            render={({ open }) => (
                <>
                    <Button onClick={open} isSecondary>
                        {!imgID
                            ? __("Wybierz obraz", "custom-block-package")
                            : __("Zmień obraz", "custom-block-package")}
                    </Button>
                    {imgURL && (
                        <div style={{ marginTop: "10px" }}>
                            <img
                                src={imgURL}
                                style={{ maxWidth: "100%", display: "block" }}
                                alt={previewAlt}
                            />
                            <Button onClick={onRemove} isDestructive style={{ marginTop: "5px" }}>
                                {__("Usuń obraz", "custom-block-package")}
                            </Button>
                        </div>
                    )}
                </>
            )}
        />
    </MediaUploadCheck>
);

const Edit = ({ attributes, setAttributes }) => {
    const {
        imgDesktopID, imgDesktopURL,
        imgTabletID, imgTabletURL,
        imgMobileID, imgMobileURL,
        overlayDesktopID, overlayDesktopURL,
        overlayTabletID, overlayTabletURL,
        overlayMobileID, overlayMobileURL,
        overlayAlt,
        heading
    } = attributes;

    const blockProps = useBlockProps();

    return (
        <Fragment>
            <InspectorControls>
                <PanelBody
                    title={__("Obrazy tła", "custom-block-package")}
                    initialOpen={true}
                >
                    <ImageSlot
                        label={__("Obraz Desktop", "custom-block-package")}
                        imgID={imgDesktopID}
                        imgURL={imgDesktopURL}
                        previewAlt={__("Podgląd Desktop", "custom-block-package")}
                        onSelect={(media) => setAttributes({ imgDesktopID: media.id, imgDesktopURL: media.url })}
                        onRemove={() => setAttributes({ imgDesktopID: 0, imgDesktopURL: "" })}
                    />
                    <ImageSlot
                        label={__("Obraz Tablet", "custom-block-package")}
                        imgID={imgTabletID}
                        imgURL={imgTabletURL}
                        previewAlt={__("Podgląd Tablet", "custom-block-package")}
                        onSelect={(media) => setAttributes({ imgTabletID: media.id, imgTabletURL: media.url })}
                        onRemove={() => setAttributes({ imgTabletID: 0, imgTabletURL: "" })}
                    />
                    <ImageSlot
                        label={__("Obraz Mobile", "custom-block-package")}
                        imgID={imgMobileID}
                        imgURL={imgMobileURL}
                        previewAlt={__("Podgląd Mobile", "custom-block-package")}
                        onSelect={(media) => setAttributes({ imgMobileID: media.id, imgMobileURL: media.url })}
                        onRemove={() => setAttributes({ imgMobileID: 0, imgMobileURL: "" })}
                    />
                </PanelBody>

                <PanelBody
                    title={__("Overlay (nakładka)", "custom-block-package")}
                    initialOpen={false}
                >
                    <p className="components-base-control__help">
                        {__("Obraz nakładany na tło. Jeśli nie ustawisz dla danego rozmiaru — nakładka nie pojawi się na tym breakpoincie.", "custom-block-package")}
                    </p>
                    <ImageSlot
                        label={__("Overlay Desktop", "custom-block-package")}
                        imgID={overlayDesktopID}
                        imgURL={overlayDesktopURL}
                        previewAlt={__("Podgląd overlay Desktop", "custom-block-package")}
                        onSelect={(media) => setAttributes({ overlayDesktopID: media.id, overlayDesktopURL: media.url })}
                        onRemove={() => setAttributes({ overlayDesktopID: 0, overlayDesktopURL: "" })}
                    />
                    <ImageSlot
                        label={__("Overlay Tablet", "custom-block-package")}
                        imgID={overlayTabletID}
                        imgURL={overlayTabletURL}
                        previewAlt={__("Podgląd overlay Tablet", "custom-block-package")}
                        onSelect={(media) => setAttributes({ overlayTabletID: media.id, overlayTabletURL: media.url })}
                        onRemove={() => setAttributes({ overlayTabletID: 0, overlayTabletURL: "" })}
                    />
                    <ImageSlot
                        label={__("Overlay Mobile", "custom-block-package")}
                        imgID={overlayMobileID}
                        imgURL={overlayMobileURL}
                        previewAlt={__("Podgląd overlay Mobile", "custom-block-package")}
                        onSelect={(media) => setAttributes({ overlayMobileID: media.id, overlayMobileURL: media.url })}
                        onRemove={() => setAttributes({ overlayMobileID: 0, overlayMobileURL: "" })}
                    />

                    <TextControl
                        label={__("Tekst alternatywny overlay", "custom-block-package")}
                        value={overlayAlt || ""}
                        onChange={(value) => setAttributes({ overlayAlt: value })}
                        help={__("Opis nakładki dla czytników ekranu. Zostaw puste jeśli nakładka jest czysto dekoracyjna.", "custom-block-package")}
                    />
                </PanelBody>

                <PanelBody
                    title={__("Nagłówek H1", "custom-block-package")}
                    initialOpen={false}
                >
                    <TextControl
                        label={__("Tekst nagłówka (ukryty wizualnie)", "custom-block-package")}
                        value={heading || ""}
                        onChange={(value) => setAttributes({ heading: value })}
                        help={__("Nagłówek H1 widoczny tylko dla wyszukiwarek i czytników ekranu.", "custom-block-package")}
                    />
                </PanelBody>
            </InspectorControls>

            <div {...blockProps}>
                {heading && (
                    <h1 className="visually-hidden">{heading}</h1>
                )}

                {!imgDesktopURL && !imgTabletURL && !imgMobileURL && (
                    <p>{__("Dodaj zdjęcia dla różnych rozdzielczości.", "custom-block-package")}</p>
                )}

                {(imgDesktopURL || imgTabletURL || imgMobileURL) && (
                    <div className="dynamic-images__preview">
                        <picture>
                            {imgMobileURL && (
                                <source srcSet={imgMobileURL} media="(max-width: 480px)" />
                            )}
                            {imgTabletURL && (
                                <source srcSet={imgTabletURL} media="(max-width: 768px)" />
                            )}
                            <img
                                src={imgDesktopURL || imgTabletURL || imgMobileURL}
                                style={{ maxWidth: "100%", display: "block", height: "auto" }}
                                alt=""
                            />
                        </picture>

                        {(overlayDesktopURL || overlayTabletURL || overlayMobileURL) && (
                            <div className="dynamic-images__overlay-preview">
                                <picture>
                                    {overlayMobileURL && (
                                        <source srcSet={overlayMobileURL} media="(max-width: 480px)" />
                                    )}
                                    {overlayTabletURL && (
                                        <source srcSet={overlayTabletURL} media="(max-width: 768px)" />
                                    )}
                                    <img
                                        src={overlayDesktopURL || overlayTabletURL || overlayMobileURL}
                                        style={{ maxWidth: "100%", display: "block", height: "auto" }}
                                        alt=""
                                    />
                                </picture>
                            </div>
                        )}
                    </div>
                )}
            </div>
        </Fragment>
    );
};

export default Edit;
