(function (blocks, element, editor) {
    const el = element.createElement;
    const { InspectorControls } = editor;
    const { PanelBody } = wp.components;

    blocks.registerBlockType('siteseo/html-sitemap', {
        title: 'Sitemap',
        icon: 'list-view',
        category: 'siteseo',
        edit: function (props) {
            return [
                el('div', { className: props.className },
                    el('div', { className: 'sitemap-html-preview' },
                        el('h3', null),
                        el('div', { dangerouslySetInnerHTML: { __html: siteseositemap.previewData } })
                    )
                ),
                el(InspectorControls, null,
                    el(PanelBody, { title: 'Settings' }, null)
                )
            ];
        },
        save: function () {
            return null; // Rendered via PHP
        }
    });
})(window.wp.blocks, window.wp.element, window.wp.blockEditor);