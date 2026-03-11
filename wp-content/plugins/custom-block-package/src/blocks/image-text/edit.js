import { __ } from '@wordpress/i18n';
import {
    RichText,
    useBlockProps,
    InspectorControls,
    MediaUpload,
    MediaUploadCheck,
    __experimentalLinkControl as LinkControl
} from '@wordpress/block-editor';
import {
    PanelBody,
    Button,
    Popover,
    ToggleControl
} from '@wordpress/components';
import { Fragment, useState } from '@wordpress/element';
import ServerSideRender from '@wordpress/server-side-render';
import block from './block.json';
import './index.scss';

const Edit = ({ attributes, setAttributes }) => {
    const { text, imgID, postURL, fullWidth } = attributes;
    const blockProps = useBlockProps({
        className: "about-one",
    });

    const [isLinkPickerVisible, setIsLinkPickerVisible] = useState(false);

    return (
        <Fragment>
            <InspectorControls>
                <PanelBody title={__("Ustawienia", "custom-block-package")}>

                    <ToggleControl
                        label={__("Pełna szerokość", "custom-block-package")}
                        checked={fullWidth}
                        onChange={(value) => setAttributes({ fullWidth: value })}
                        __nextHasNoMarginBottom={true}
                    />

                    <h2>{__("Zmień/dodaj obraz", "custom-block-package")}</h2>

                    <MediaUploadCheck>
                        <MediaUpload
                            onSelect={(media) => setAttributes({ imgID: media.id })}
                            allowedTypes={["image"]}
                            value={imgID}
                            render={({ open }) => (
                                <Button
                                    onClick={open}
                                    className="select-button"
                                >
                                    {!imgID ? __("Wybierz obraz", "custom-block-package") : __("Zmień obraz", "custom-block-package")}
                                </Button>
                            )}
                        />
                    </MediaUploadCheck>
                    <h2>{__("Opcje odnośnika", "custom-block-package")}</h2>
                    <Button
                        onClick={() => setIsLinkPickerVisible(true)}
                        className="select-button"
                    >
                        {__("Wybierz link", "custom-block-package")}
                    </Button>

                    {isLinkPickerVisible && (
                        <Popover
                            position="middle left"
                            onClose={() => setIsLinkPickerVisible(false)}
                        >
                            <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
                                <LinkControl
                                    searchInputPlaceholder={__("Szukaj...", "custom-block-package")}
                                    value={postURL || {}}
                                    settings={[
                                        {
                                            id: 'opensInNewTab',
                                            title: 'Otworzyć w nowym oknie',
                                        }
                                    ]}
                                    onChange={(newPostURL) => setAttributes({ postURL: newPostURL })}
                                    withCreateSuggestion={true}
                                    createSuggestion={(inputValue) => setAttributes({
                                        postURL: {
                                            ...postURL,
                                            title: inputValue,
                                            type: "page",
                                            id: Date.now(),
                                            url: inputValue
                                        }
                                    })}
                                    createSuggestionButtonText={(newValue) => `${__("Nowa: ")} ${newValue}`}
                                />

                                {postURL.url && (
                                    <Button
                                        isDestructive
                                        onClick={() => setAttributes({ postURL: { url: "" } })}
                                        className="remove-link-button"
                                    >
                                        {__("Usuń link", "custom-block-package")}
                                    </Button>
                                )}

                                <Button
                                    onClick={() => setIsLinkPickerVisible(false)}
                                    className="select-button"
                                >
                                    {__("Zamknij", "custom-block-package")}
                                </Button>
                            </div>
                        </Popover>

                    )}

                    <h2>{__("Tekst na obrazie", "custom-block-package")}</h2>

                    <RichText
                        tagName="h2"
                        placeholder={__("Wpisz tekst", "custom-block-package")}
                        value={text}
                        onChange={(newVal) => setAttributes({ text: newVal })}
                    />

                </PanelBody>
            </InspectorControls>

            <div {...blockProps}>
                <ServerSideRender
                    block={block.name}
                    attributes={attributes}
                    EmptyResponsePlaceholder={() => <p>{__('Ładowanie podglądu...', 'custom-block-package')}</p>}
                />
            </div>
        </Fragment>
    );
};

export default Edit;
