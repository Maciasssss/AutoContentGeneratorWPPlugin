(function (wp) {
    const { registerBlockType } = wp.blocks;
    const { RichText, useBlockProps } = wp.blockEditor;
    const { __ } = wp.i18n;

    registerBlockType('acg/block', {
        title: __('AI Agent Block', 'acg'),
        icon: 'smiley',
        category: 'widgets',
        attributes: {
            content: {
                type: 'string',
                source: 'html',
                selector: 'div',
            },
        },
        edit({ attributes, setAttributes }) {
            const { content } = attributes;
            const blockProps = useBlockProps();

            // Create an element with the block properties
            return wp.element.createElement(
                'div',
                blockProps,
                wp.element.createElement(
                    RichText,
                    {
                        tagName: 'div',
                        className: 'acg-block-content',
                        value: content,
                        onChange: (newContent) => setAttributes({ content: newContent }),
                    }
                )
            );
        },
        save({ attributes }) {
            const { content } = attributes;

            // Create a static element for the saved content
            return wp.element.createElement(
                'div',
                { className: 'acg-block' },
                wp.element.createElement(
                    RichText.Content,
                    {
                        tagName: 'div',
                        className: 'acg-block-content',
                        value: content,
                    }
                )
            );
        },
    });
})(window.wp);
