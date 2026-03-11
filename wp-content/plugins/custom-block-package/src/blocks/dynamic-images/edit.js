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

const Edit = ({ attributes, setAttributes }) => {
    const {
        imgDesktopID,
        imgDesktopURL,
        imgTabletID,
        imgTabletURL,
        imgMobileID,
        imgMobileURL,
        heading
    } = attributes;

    const blockProps = useBlockProps();

    return (
        <Fragment>
            <InspectorControls>
                <PanelBody
                    title={__("Ustawienia obrazów", "custom-block-package")}
                    initialOpen={true}
                >
                    <h4>{__("Obraz Desktop", "custom-block-package")}</h4>
                    <MediaUploadCheck>
                        <MediaUpload
                            onSelect={(media) => {
                                setAttributes({
                                    imgDesktopID: media.id,
                                    imgDesktopURL: media.url
                                });
                            }}
                            allowedTypes={["image"]}
                            value={imgDesktopID}
                            render={({ open }) => (
                                <>
                                    <Button onClick={open} isSecondary>
                                        {!imgDesktopID
                                            ? __("Wybierz obraz Desktop", "custom-block-package")
                                            : __("Zmień obraz Desktop", "custom-block-package")}
                                    </Button>

                                    {imgDesktopURL && (
                                        <div style={{ marginTop: "10px" }}>
                                            <img
                                                src={imgDesktopURL}
                                                style={{ maxWidth: "100%", display: "block" }}
                                                alt={__("Podgląd Desktop", "custom-block-package")}
                                            />
                                            <Button
                                                onClick={() => setAttributes({
                                                    imgDesktopID: 0,
                                                    imgDesktopURL: ""
                                                })}
                                                isDestructive
                                                style={{ marginTop: "5px" }}
                                            >
                                                {__("Usuń obraz Desktop", "custom-block-package")}
                                            </Button>
                                        </div>
                                    )}
                                </>
                            )}
                        />
                    </MediaUploadCheck>

                    <h4>{__("Obraz Tablet", "custom-block-package")}</h4>
                    <MediaUploadCheck>
                        <MediaUpload
                            onSelect={(media) => {
                                setAttributes({
                                    imgTabletID: media.id,
                                    imgTabletURL: media.url
                                });
                            }}
                            allowedTypes={["image"]}
                            value={imgTabletID}
                            render={({ open }) => (
                                <>
                                    <Button onClick={open} isSecondary>
                                        {!imgTabletID
                                            ? __("Wybierz obraz Tablet", "custom-block-package")
                                            : __("Zmień obraz Tablet", "custom-block-package")}
                                    </Button>

                                    {imgTabletURL && (
                                        <div style={{ marginTop: "10px" }}>
                                            <img
                                                src={imgTabletURL}
                                                style={{ maxWidth: "100%", display: "block" }}
                                                alt={__("Podgląd Tablet", "custom-block-package")}
                                            />
                                            <Button
                                                onClick={() => setAttributes({
                                                    imgTabletID: 0,
                                                    imgTabletURL: ""
                                                })}
                                                isDestructive
                                                style={{ marginTop: "5px" }}
                                            >
                                                {__("Usuń obraz Tablet", "custom-block-package")}
                                            </Button>
                                        </div>
                                    )}
                                </>
                            )}
                        />
                    </MediaUploadCheck>

                    <h4>{__("Obraz Mobile", "custom-block-package")}</h4>
                    <MediaUploadCheck>
                        <MediaUpload
                            onSelect={(media) => {
                                setAttributes({
                                    imgMobileID: media.id,
                                    imgMobileURL: media.url
                                });
                            }}
                            allowedTypes={["image"]}
                            value={imgMobileID}
                            render={({ open }) => (
                                <>
                                    <Button onClick={open} isSecondary>
                                        {!imgMobileID
                                            ? __("Wybierz obraz Mobile", "custom-block-package")
                                            : __("Zmień obraz Mobile", "custom-block-package")}
                                    </Button>

                                    {imgMobileURL && (
                                        <div style={{ marginTop: "10px" }}>
                                            <img
                                                src={imgMobileURL}
                                                style={{ maxWidth: "100%", display: "block" }}
                                                alt={__("Podgląd Mobile", "custom-block-package")}
                                            />
                                            <Button
                                                onClick={() => setAttributes({
                                                    imgMobileID: 0,
                                                    imgMobileURL: ""
                                                })}
                                                isDestructive
                                                style={{ marginTop: "5px" }}
                                            >
                                                {__("Usuń obraz Mobile", "custom-block-package")}
                                            </Button>
                                        </div>
                                    )}
                                </>
                            )}
                        />
                    </MediaUploadCheck>
                </PanelBody>
                <PanelBody
                    title={__("Nagłówek H1", "custom-block-package")}
                    initialOpen={true}
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
                    <picture>
                        {imgMobileURL && (
                            <source
                                srcSet={imgMobileURL}
                                media="(max-width: 480px)"
                            />
                        )}
                        {imgTabletURL && (
                            <source
                                srcSet={imgTabletURL}
                                media="(max-width: 768px)"
                            />
                        )}
                        <img
                            src={imgDesktopURL || imgTabletURL || imgMobileURL}
                            style={{ maxWidth: "100%", display: "block", height: "auto" }}
                            alt=""
                        />
                    </picture>
                )}
            </div>

        </Fragment>
    );
};

export default Edit;
