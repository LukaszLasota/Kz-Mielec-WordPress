import { __ } from '@wordpress/i18n';
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import './editor.scss';

const Edit = () => {
    const blockProps = useBlockProps({
        className: 'cbp-block',
    });

    return (
        <div {...blockProps}>
            <InnerBlocks
                allowedBlocks={['custom-block-package/accordion-item']}
                template={[['custom-block-package/accordion-item']]}
                templateLock={false}
            />
        </div>
    );
};

export default Edit;
