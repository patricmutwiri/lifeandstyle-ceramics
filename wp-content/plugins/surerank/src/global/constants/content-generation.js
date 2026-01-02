import { applyFilters } from '@wordpress/hooks';
import { SEO_FIX_TYPE_MAPPING } from '@Global/constants';
import { select } from '@wordpress/data';

// Mapping check types to SaaS content generation keys
export const SITE_SEO_CHECKS_CONTENT_GENERATION_MAPPING = {
	page_title: 'page_title',
	page_description: 'page_description',
	title: 'home_page_title',
	meta_description: 'home_page_description',
	site_tag_line: 'site_tag_line',
	url_length: 'page_url_slug',
};

const PAGE_CHECKS_CONTENT_GENERATION_MAPPING = applyFilters(
	'surerank-pro.page-seo-checks-content-generation-mapping',
	{
		search_engine_title: 'page_title',
		search_engine_description: 'page_description',
		url_length: 'page_url_slug',
	}
);

export const PRO_PAGE_CHECKS_CONTENT_GENERATION_MAPPING = applyFilters(
	'surerank-pro.pro-page-seo-checks-content-generation',
	[ 'url_length', 'search_engine_title', 'search_engine_description' ]
);

const FIELD_CONTENT_GENERATION_MAPPING = {
	facebook_title: 'social_title',
	facebook_description: 'social_description',
	twitter_title: 'social_title',
	twitter_description: 'social_description',
};

export const CONTENT_GENERATION_MAPPING = Object.freeze( {
	...SITE_SEO_CHECKS_CONTENT_GENERATION_MAPPING,
	...PAGE_CHECKS_CONTENT_GENERATION_MAPPING,
	...FIELD_CONTENT_GENERATION_MAPPING,
} );

export const PAGE_SEO_CHECKS_ID_TO_STATE_MAPPING = {
	search_engine_title: 'page_title',
	search_engine_description: 'page_description',
};

// List of item ids that require content generation to fix
export const REQUIRE_CONTENT_GENERATION = Object.freeze(
	Array.from(
		new Set( [
			...Object.keys( SITE_SEO_CHECKS_CONTENT_GENERATION_MAPPING ),
			...Object.keys( PAGE_CHECKS_CONTENT_GENERATION_MAPPING ),
		] )
	)
);

export const helpMeFixRedirect = ( checkId ) => {
	const redirectFn = applyFilters(
		'surerank-pro.help-me-fix-redirect',
		null
	);
	if ( typeof redirectFn === 'function' ) {
		redirectFn( checkId );
	}
};

export const isFixItForMeButton = ( id ) => {
	return (
		REQUIRE_CONTENT_GENERATION.includes( id ?? '' ) ||
		Object.keys( SEO_FIX_TYPE_MAPPING ).includes( id ?? '' )
	);
};

export const IS_HELP_ME_FIX_PRO_ACTIVE = applyFilters(
	'surerank-pro.help-me-fix-active',
	false
);

export const shouldHideFixHelpButtons = ( storeName ) => {
	const pageSeoChecks = select( storeName ).getPageSeoChecks();
	return pageSeoChecks?.hideFixHelpButtons || false;
};
