import { registerBlockType } from "@wordpress/blocks";
import { useBlockProps, InspectorControls } from "@wordpress/block-editor";
import { PanelBody, TextControl } from "@wordpress/components";
import { __ } from '@wordpress/i18n';
import ServerSideRender from "@wordpress/server-side-render";
import block from "./block.json";
import "./index.scss";
import './style.scss';

registerBlockType(block.name, {
    ...block,
    edit({ attributes, setAttributes }) {
        const { numberposts, blockTitle } = attributes;
        const blockProps = useBlockProps({ className: 'meetings' });

        return (
            <div {...blockProps}>
                <InspectorControls>
                    <PanelBody title={__("Ustawienia bloku", "custom-block-package")}>
                        <TextControl
                            label={__("Tytuł bloku", "custom-block-package")}
                            value={blockTitle}
                            onChange={(value) => setAttributes({ blockTitle: value })}
                        />
                        <TextControl
                            label={__("Liczba postów", "custom-block-package")}
                            type="number"
                            value={numberposts}
                            onChange={(value) => setAttributes({ numberposts: parseInt(value, 10) })}
                        />
                    </PanelBody>
                </InspectorControls>

                <ServerSideRender
                    block={block.name}
                    attributes={attributes}
                />
            </div>
        );
    },
    save: () => null,
});
