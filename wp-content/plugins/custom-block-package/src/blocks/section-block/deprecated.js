import { useBlockProps, InnerBlocks } from "@wordpress/block-editor";

/**
 * Old save function (before render.php migration).
 * Kept for backward compatibility with existing blocks.
 */
const v1Save = ({ attributes }) => {
    const {
        columnsMobile,
        columnsSmallTablet,
        columnsLargeTablet,
        columnsDesktop,
        tagName,
        backgroundImage,
        backgroundColor,
        padding,
        margin,
        gridGap,
        justifyItems,
        justifyContent,
        alignContent,
        layoutType,
        flexWrap,
        flexDirection,
        alignItems,
        toggleClass
    } = attributes;

    const TagName = tagName;

    const blockProps = useBlockProps.save({
        className: `cbp-block section-block ${layoutType === "grid" ? "is-grid" : layoutType === "flex" ? "is-flex" : ""} ${toggleClass ? 'width-settings' : ''}`,
        style: {
            background: backgroundColor || undefined,
            backgroundImage: backgroundImage ? `url(${backgroundImage})` : undefined,
            padding: padding || undefined,
            margin: margin || undefined,
            ...(layoutType === "grid" && {
                "--columns-mobile": `${columnsMobile}`,
                "--columns-small-tablet": `${columnsSmallTablet}`,
                "--columns-large-tablet": `${columnsLargeTablet}`,
                "--columns-desktop": `${columnsDesktop}`,
                "--grid-gap": `${gridGap}px`,
                ...(justifyItems && { "--justify-items": justifyItems }),
                ...(alignContent && { "--align-content": alignContent }),
            }),
            ...(layoutType === "flex" && {
                "--display": "flex",
                ...(flexWrap && { "--flex-wrap": flexWrap }),
                ...(flexDirection && { "--flex-direction": flexDirection }),
                ...(alignItems && { "--align-items": alignItems }),
                ...(justifyContent && { "--justify-content": justifyContent }),
            }),
        },
    });

    return (
        <TagName {...blockProps}>
            <InnerBlocks.Content />
        </TagName>
    );
};

const deprecated = [
    {
        attributes: {
            tagName: { type: "string", default: "div" },
            backgroundImage: { type: "string", default: "" },
            backgroundColor: { type: "string", default: "" },
            padding: { type: "string", default: "" },
            margin: { type: "string", default: "" },
            columnsMobile: { type: "number", default: 1 },
            columnsSmallTablet: { type: "number", default: 2 },
            columnsLargeTablet: { type: "number", default: 3 },
            columnsDesktop: { type: "number", default: 4 },
            gridGap: { type: "number", default: 10 },
            justifyItems: { type: "string", default: "" },
            justifyContent: { type: "string", default: "" },
            alignContent: { type: "string", default: "" },
            layoutType: { type: "string", default: "none" },
            flexWrap: { type: "string", default: "" },
            flexDirection: { type: "string", default: "" },
            alignItems: { type: "string", default: "" },
            gradient: { type: "string", default: "" },
            toggleClass: { type: "boolean", default: false },
        },
        supports: {
            color: { text: true, background: true, gradients: true, link: true },
            align: true,
            typography: { fontSize: true, lineHeight: true, fontWeight: true, textTransform: true, letterSpacing: true },
            spacing: { margin: true, padding: true, blockGap: true },
            customClassName: true,
            anchor: true,
        },
        save: v1Save,
    },
];

export default deprecated;