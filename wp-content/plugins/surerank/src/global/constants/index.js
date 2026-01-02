import { __ } from '@wordpress/i18n';

export const INPUT_VARIABLE_SUGGESTIONS =
	window?.surerank_globals?.input_variable_suggestions ?? null;

export const ROBOTS_OPTIONS = [
	{
		id: 'post_no_index',
		value: 'no',
		label: __( 'No index', 'surerank' ),
		description: __(
			'Prevents search engines from listing your page in search results.',
			'surerank'
		),
	},
	{
		id: 'post_no_follow',
		value: 'no',
		label: __( 'No follow', 'surerank' ),
		description: __(
			'Tells search engines not to follow any links on your page.',
			'surerank'
		),
	},
	{
		id: 'post_no_archive',
		value: 'no',
		label: __( 'No archive', 'surerank' ),
		description: __(
			'Blocks search engines from storing a cached version of your page.',
			'surerank'
		),
	},
];

export const getDefaultPageDescription = () =>
	__(
		'This content will be set as the meta description tag and may appear in search results. Keep it short and clearly explain what the page is about.',
		'surerank'
	);

export const ADMIN_DASHBOARD_URL =
	window?.surerank_globals?.wp_dashboard_url ?? '';

export const DESCRIPTION_LENGTH =
	window?.surerank_globals?.description_length ?? 160;

export const TITLE_LENGTH = window?.surerank_globals?.title_length ?? 60;

export const URL_LENGTH = window?.surerank_globals?.url_length ?? 90;

export const getSearchEnginePreviewInfoText = () => (
	<>
		{ __(
			'View a preview of how your page may appear in search engine results. This preview is for guidance only and might not exactly match how search engines display your content.',
			'surerank'
		) }
		<div className="mt-2">
			<strong>{ __( 'Site Icon: ', 'surerank' ) }</strong>
			<span>
				{ __(
					'The site icon (favicon) appears in browser tabs, bookmarks, and mobile devices. To update it, go to General Settings → Site Icon and upload a new image under Site Identity.',
					'surerank'
				) }{ ' ' }
				<a
					href={
						window?.surerank_globals?.wp_general_settings_url ?? ''
					}
					target="_blank"
					rel="noopener noreferrer"
					className="block mt-2 no-underline hover:no-underline focus:[box-shadow:none] text-link-inverse hover:text-link-inverse-hover"
				>
					{ __( 'Change Here', 'surerank' ) }
				</a>
			</span>
		</div>
	</>
);

export const MAX_EDITOR_INPUT_LENGTH = 500;

export const ENABLE_PAGE_LEVEL_SEO =
	window?.surerank_globals?.enable_page_level_seo ?? false;
export const ENABLE_GOOGLE_CONSOLE =
	window?.surerank_globals?.enable_google_console ?? false;
export const ENABLE_SCHEMAS = window?.surerank_globals?.enable_schemas ?? false;
export const ENABLE_MIGRATION =
	window?.surerank_globals?.enable_migration ?? false;

// Page & Keyword SEO Checks
export const CHECK_TYPES = [ 'page', 'keyword' ];

// Content generation
export * from './content-generation';

// Site SEO Fix Types
export * from './site-seo-fix';

// Process status
export * from './process-status';

// Page SEO Checks
export const LEARN_MORE_AI_AUTH =
	'https://surerank.com/surerank-ai/?utm_medium=surerank_plugin';
