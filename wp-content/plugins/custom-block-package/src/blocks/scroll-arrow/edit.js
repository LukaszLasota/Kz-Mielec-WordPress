import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl } from '@wordpress/components';
import './index.scss';

import arrowDown from './images/arrow-down.png';
import arrowUp from './images/arrow-up.png';

const Edit = ({ attributes, setAttributes }) => {
    const { targetId, direction, ariaLabel } = attributes;
    const blockProps = useBlockProps();

    const arrowSrc = direction === 'up' ? arrowUp : arrowDown;

    return (
        <>
            <InspectorControls>
                <PanelBody
                    title={__("Ustawienia strzałki", "custom-block-package")}
                    initialOpen={true}
                >
                    <SelectControl
                        label={__("Kierunek", "custom-block-package")}
                        value={direction}
                        options={[
                            { label: __("W dół", "custom-block-package"), value: "down" },
                            { label: __("W górę", "custom-block-package"), value: "up" },
                        ]}
                        onChange={(value) => setAttributes({ direction: value })}
                    />
                    <TextControl
                        label={__("ID sekcji docelowej", "custom-block-package")}
                        value={targetId}
                        onChange={(value) => setAttributes({ targetId: value })}
                        help={__("Wpisz ID sekcji bez # (np. 'one', 'two', 'zero').", "custom-block-package")}
                    />
                    <TextControl
                        label={__("Etykieta dostępności", "custom-block-package")}
                        value={ariaLabel}
                        onChange={(value) => setAttributes({ ariaLabel: value })}
                        help={__("Opis dla czytników ekranu, np. 'Przewiń do sekcji Aktualności'.", "custom-block-package")}
                    />
                </PanelBody>
            </InspectorControls>

            <div {...blockProps}>
                {/*
                 * Editor preview only — a non-interactive stand-in. The front
                 * end renders a real <a href="#target"> in render.php, so this
                 * deliberately is not an anchor: an <a> without href is not a
                 * link, and role="button" on it would promise behaviour the
                 * preview does not have.
                 */}
                <div className="scroll-arrow">
                    <figure>
                        <img src={arrowSrc} alt="" />
                    </figure>
                </div>
            </div>
        </>
    );
};

export default Edit;
