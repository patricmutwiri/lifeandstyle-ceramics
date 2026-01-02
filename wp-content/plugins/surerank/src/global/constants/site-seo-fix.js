// Mapping of selected item id to available SEO fix types
export const SEO_FIX_TYPE_MAPPING = {
	// Content generation types (use mapped key from CONTENT_GENERATION_MAPPING)
	page_title: 'content_generation',
	page_description: 'content_generation',
	title: 'content_generation',
	meta_description: 'content_generation',
	url_length: 'content_generation',
	site_tag_line: 'content_generation',

	// Other SEO check types
	other_seo_plugins: 'other_seo_plugins',
	indexing: 'indexing',
	sitemaps: 'sitemaps',
	index_status: 'index_status',
};
