import { __ } from '@wordpress/i18n';
import * as actionTypes from './actionTypes';
import { getSettingsPageName } from '../functions/utils';
import { applyFilters } from '@wordpress/hooks';

/**
 * Reducer returning the viewport state, as keys of breakpoint queries with
 * boolean value representing whether query is matched.
 *
 * @param {Object} state  Current state.
 * @param {Object} action Dispatched action.
 *
 * @return {Object} Updated state.
 */

// App settings will used to store the settings of the app.
const appSettings = {
	breadCrumbs: {
		// Ex of the breadcrumb
		// items: [
		// 	{
		// 		title: 'Home',
		// 	},
		// 	{
		// 		title: 'Dashboard',
		// 	},
		// ],
		items: [],
	},
	/**
	 * home_page_static will be used in general > home page to store the static data.
	 * this data will come from the get_option('show_on_front')
	 */
	home_page_static: '',
	// saveButtonStatus will be used to store the status of the save button.
	saveButtonStatus: 'active', // loading, active, disabled
	// generalSettings
	generalTabActive: 'title-and-descriptions', // "title-and-descriptions", "home-page"
	// Input Picker Options
	inputPickerOptions: [
		{
			label: __( 'Site Name', 'surerank' ),
			value: 'site_name',
			title: '%site_name%',
		},
		{
			label: __( 'Separator', 'surerank' ),
			value: 'separator',
			title: '-',
		},
		{
			label: __( 'Tagline', 'surerank' ),
			value: 'tagline',
			title: '%tagline%',
		},
		{
			label: __( 'Post Title', 'surerank' ),
			value: 'title',
			title: '%title%',
		},
		// {
		// 	label: __( 'Current Year', 'surerank' ),
		// 	value: 'current_year',
		// 	title: '%current_year%',
		// },
		// {
		// 	label: __( 'Current Month', 'surerank' ),
		// 	value: 'current_month',
		// 	title: '%current_month%',
		// },
	],
	/**
	 * From Social settings.
	 *
	 */
	socialTabActive: 'general', // "general", "facebook", "twitter", "social-accounts"
	advancedTabActive: 'image-seo', // "image-seo", "special-pages", "robot-instructions", "role-manager", "site-maps", "crawl-optimization", "feeds"

	// This value will come from the during advance settings page load.
	archives: {},
	post_types: {},
	taxonomies: {},
	roles: {},

	// Current settings page.
	settingsPage: getSettingsPageName(),
};

/**
 * Global default meta values.
 * This object will be used to set default values for the global meta setting and same as helper -> global_default_meta_values function values so make sure to update both if you are updating this if required.
 */
const dataSettings = {
	/* Title and description Start */
	metaSettings: {
		separator: '-',
		// Page/Post Title
		page_title: '%title% - %site_name%', // Title
		page_description: '%excerpt%', // Description
		// Page Description
		auto_generate_description: true, // true/false
		// Home Page General
		home_page_title: '%title% - %site_name%', // Title
		home_page_description: '%excerpt%', // Description
		// Home Page Social Profiles
		home_page_facebook_image_url: '', // Facebook Image URL
		home_page_facebook_title: '', // Facebook Title
		home_page_facebook_description: '', // Facebook Description
		home_page_twitter_image_url: '', // Twitter Image URL
		home_page_twitter_title: '', // Twitter Title
		home_page_twitter_description: '', // Twitter Description
		twitter_same_as_facebook: true, // true/false
		home_page_robots: {
			general: [], // Ex - [ 'noindex', 'nofollow', 'noarchive' ]
		},
		index_home_page_paginated_pages: true, // true/false
		// Social settings.
		// General -> general
		open_graph_tags: true, // true/false
		facebook_meta_tags: true, // true/false
		twitter_meta_tags: true, // true/false
		oembeded_scripts: true, // true/false
		// General -> social image
		fallback_image: '', // Image URL
		// General -> oembeded settings
		oembeded_og_title: false, // true/false
		oembeded_social_images: true, // true/false
		oembeded_remove_author_name: true, // true/false

		// facebook -> facebook
		facebook_page_url: '', // Facebook Page URL
		facebook_author_fallback: '', // Facebook Author URL

		// twitter -> twitter
		twitter_card_type: 'summary_large_image', // summary_large_image, summary
		twitter_profile_username: '', // Twitter Profile Username
		twitter_profile_fallback: '', // Twitter Profile Fallback

		// social -> social accounts
		pinterest_account: '', // Pinterest URL
		instagram_account: '', // Instagram URL
		youtube_account: '', // Youtube URL
		linkedin_account: '', // Linkedin URL
		tiktok_account: '', // Tiktok URL
		redirectAttachmentPagesToPostParent: true, // true/false
		autoSetImageTitle: true, // true/false
		autoSetImageAlt: true, // true/false

		// Special Pages( archives )
		author_archive: false, // true/false
		date_archive: false, // true/false
		noindex_paginated_pages: false, // true/false
		paginated_link_relationships: [],

		//Robot Instructions
		//Following
		no_follow: [],
		no_index: [],
		no_archive: [],
		//Feed Settings
		convert_feed_entries_into_excerpts: true, // true/false
		addlink_to_source_below_feed_entries: true, // true/false
		allow_indexing_of_feed_entries: false, // true/false

		/// Feed formats optimization.
		remove_global_comments_feed: false, // true/false
		remove_post_authors_feed: false, // true/false
		remove_post_types_feed: false, // true/false
		remove_category_feed: false, // true/false
		remove_tag_feeds: false, // true/false
		remove_custom_taxonomy_feeds: false, // true/false
		remove_search_results_feed: false, // true/false
		remove_atom_rdf_feeds: false, // true/false

		// Disable features
		enable_page_level_seo: true, // true/false
		enable_google_console: true, // true/false
		enable_schemas: true, // true/false
		enable_migration: true, // true/false

		// Miscellaneous
		surerank_analytics_optin: false, // true/false
	},
	/* Title and description End */
	// Site Settings (Previously known as site variables)
	siteSettings: {},
	// advanced_settings: {
	// 	// Robot instructions
	// 	/// index
	// 	noindex: [
	// 		// No index will be according to the post types. ex -
	// 		// post,
	// 		// page,
	// 		// another_post_type,
	// 		// Same for the taxonomies and archives. both will be true/false. ex -
	// 	],
	// 	/// follow
	// 	nofollow: [
	// 		// No follow will be according to the post types. ex -
	// 		// post,
	// 		// page,
	// 		// another_post_type,
	// 		// Same for the taxonomies and archives. both will be true/false. ex -
	// 	],
	// 	/// noarchive
	// 	noarchive: [
	// 		// No archive will be according to the post types. ex -
	// 		// post: true,
	// 		// page: false,
	// 		// another_post_type: true,
	// 		// Same for the taxonomies and archives. both will be true/false. ex -
	// 	],
	// 	/// Directive settings
	// 	enable_directive: false, // true/false
	// 	max_text_length: 'unlimited',
	// 	max_image_preview_size: 'large',
	// 	max_video_preview_size: 'full',

	// 	// Site maps
	enable_xml_sitemap: false, // true/false
	enable_xml_image_sitemap: false, // true/false
	enable_author_sitemap: false, // true/false
	// 	/// html sitemap
	// 	enableHtmlSitemap: true, // true/false
	sitemap_display_format: 'shortcode', // shortcode, page
	sitemap_display_shortcode: [], // Shortcode
	// 	/// Other sitemap
	enable_xml_video_sitemap: true, // true/false
	enable_xml_news_sitemap: true, // true/false

	// 	// Role Manager.
	// 	/// Seo metabox.
	// 	blockSeoMetaboxToUser: [ 'administrator', 'editor' ], // Ex - [ 'administrator', 'editor' ]
	// 	/// Seo settings pages.
	// 	generalRoleSetting: [ 'administrator', 'editor' ], // Ex - [ 'administrator', 'editor' ]
	// 	socialRoleSetting: [ 'administrator', 'editor' ], // Ex - [ 'administrator', 'editor' ]
	// 	schemaRoleSetting: [ 'administrator', 'editor' ], // Ex - [ 'administrator', 'editor' ]
	// 	advancedRoleSetting: [ 'administrator', 'editor' ], // Ex - [ 'administrator', 'editor' ]
	// 	toolsRoleSetting: [ 'administrator', 'editor' ], // Ex - [ 'administrator', 'editor' ]
	// 	integrationRoleSetting: [ 'administrator', 'editor' ], // Ex - [ 'administrator', 'editor' ]

	// 	// Crawl Optimization
	// 	crawlRemoveCategory: false, // true/false
	// 	crawlRemoveProductCategory: false, // true/false
	// 	crawlReplyToComLink: false, // true/false
	// 	crawlNoReferrerLinkInPost: false, // true/false
	// 	crawlRemoveWpMetaTagGenerator: false, // true/false
	// 	crawlRemoveHentryPostClass: false, // true/false
	// 	crawlRemoveWebsiteFieldInComment: false, // true/false
	// 	crawlRemoveShortLinkMetaTag: false, // true/false
	// 	crawlRemoveWindowsLiveWriter: false, // true/false
	// 	crawlRemoveRsdLink: false, // true/false
	// 	crawlRemoveOEmbededLinks: false, // true/false
	// 	crawlRemoveWpXpingback: false, // true/false
	// 	crawlRemoveWpXpoweredByHeader: false, // true/false
	// 	crawlRemoveEmoji: false, // true/false
	// 	crawlRemoveRestApiLinks: false, // true/false
	// 	crawlRemoveWpJsonApi: false, // true/false
	// 	/// unwanted bots scripts.
	// 	preventGoogleAdsBotFormCrawling: false, // true/false
	// 	preventGoogleBardVertexBotFormCrawling: false, // true/false
	// 	preventOpenAiBotFormCrawling: false, // true/false
	// 	preventCommonCrawlBotFormCrawling: false, // true/false
	// 	removeHentryPostClass: false, // true/false
	// 	/// internal site search
	// 	filterSearchTerms: false, // true/false
	// 	maxNumberOfCharacterToAllowInSearch: 50,
	// 	filterSearchesWithEmojisAndSpecialCharacters: false, // true/false
	// 	filterSearchesWithCommonSpamPatterns: false, // true/false
	// 	preventCrawlingOfInternalSiteSearchUrls: false, // true/false
	// 	redirectPrettyUrlToRawFormats: false, // true/false
};

const DEFAULT_STATE = {
	appSettings,
	// This variable is used to store the data settings and interact with the server.
	...dataSettings,

	// Global loading state.
	loading: 'idle',
	// Global message state.
	message: '',
	// Global confirmation modal state.
	confirmationModal: {
		open: false,
		title: '',
		description: '',
		body: null,
		onConfirm: null,
		onCancel: null,
		confirmButtonText: __( 'Proceed', 'surerank' ),
		cancelButtonText: __( 'Cancel', 'surerank' ),
		type: 'destructive',
	},
	// Global site selector modal state.
	openSiteSelectorModal: false,

	// User data.
	searchConsole: {
		profile: surerank_admin_common?.google_console_user,
		authenticated: surerank_admin_common.is_gsc_connected,
		sites: [],
		selectedSite: '',
		hasSiteSelected: surerank_admin_common?.has_gsc_site_selected,
	},

	siteSeoAnalysis: {
		open: false,
		selectedItem: null,
		report: [],
		searchKeyword: '',
		runningChecks: false,
	},

	emailReportsSettings: {
		enabled: false,
		scheduledOn: 'sunday',
		recipientEmail: '',
	},

	// Store unsaved settings
	unsavedSettings: {},
};

function reducer(
	state = applyFilters(
		'surerank-pro.admin-store-reducer-default-state',
		DEFAULT_STATE
	),
	action
) {
	switch ( action.type ) {
		case 'UPDATE_APP_SETTINGS':
			return {
				...state,
				appSettings: {
					...state.appSettings,
					...action.value,
				},
			};
		case 'UPDATE_DATA_SOCIAL':
			const updatedSocialSettings = {
				...state.dataSettings.social_settings,
				...action.value,
			};
			return {
				...state,
				dataSettings: {
					...state.dataSettings,
					social_settings: updatedSocialSettings,
				},
			};
		case 'UPDATE_DATA_ADVANCED':
			const updatedAdvancedSettings = {
				...state.dataSettings.advanced_settings,
				...action.value,
			};
			return {
				...state,
				dataSettings: {
					...state.dataSettings,
					advanced_settings: updatedAdvancedSettings,
				},
			};
		case actionTypes.SET_META_SETTINGS:
			return {
				...state,
				metaSettings: {
					...state.metaSettings,
					...action.payload,
				},
			};
		case actionTypes.SET_META_SETTING:
			return {
				...state,
				metaSettings: {
					...state.metaSettings,
					...action.payload,
				},
			};
		case actionTypes.SET_LOADING: //state of setting data from API
			return {
				...state,
				loading: action.payload,
			};
		case actionTypes.SET_SAVING: //state of save button
			return {
				...state,
				saving: action.payload,
			};
		case actionTypes.SET_MESSAGE:
			return {
				...state,
				message: action.payload,
			};
		case actionTypes.SET_SITE_SETTINGS:
			return {
				...state,
				siteSettings: action.payload,
			};
		case actionTypes.SET_SITE_SETTING:
			return {
				...state,
				siteSettings: {
					...state.siteSettings,
					...action.payload,
				},
			};
		case actionTypes.TOGGLE_SITE_SELECTOR_MODAL:
			return {
				...state,
				openSiteSelectorModal: ! state.openSiteSelectorModal,
			};
		case actionTypes.SET_CONFIRMATION_MODAL:
			return {
				...state,
				confirmationModal: {
					...state.confirmationModal,
					...action.payload,
				},
			};
		case actionTypes.SET_SEARCH_CONSOLE:
			return {
				...state,
				searchConsole: {
					...state.searchConsole,
					...action.payload,
				},
			};
		case actionTypes.SET_SITE_SEO_ANALYSIS:
			return {
				...state,
				siteSeoAnalysis: {
					...state.siteSeoAnalysis,
					...action.payload,
				},
			};
		case actionTypes.SET_UNSAVED_SETTINGS:
			return {
				...state,
				unsavedSettings: {
					...state.unsavedSettings,
					...action.payload,
				},
			};
		case actionTypes.RESET_UNSAVED_SETTINGS:
			return {
				...state,
				unsavedSettings: {},
			};
		case actionTypes.SET_EMAIL_REPORTS_SETTINGS:
			return {
				...state,
				emailReportsSettings: {
					...state.emailReportsSettings,
					...action.payload,
				},
			};
		default:
			const proState = applyFilters(
				'surerank-pro.admin-store-reducer',
				state,
				action
			);
			if ( ! proState ) {
				return state;
			}
			return proState;
	}
}

export default reducer;
