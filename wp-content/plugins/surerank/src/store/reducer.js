import * as actionTypes from './action-types';
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

const DEFAULT_STATE = {
	// Metabox side bar modal state.
	modalEnabled: false,
	metaboxInitialized: false,
	postSeoMeta: {
		page_title: '',
		page_description: '',
		post_no_index: '',
		post_no_follow: '',
		post_no_archive: '',
		facebook_image_url: '',
		facebook_title: '',
		facebook_description: '',
		facebook_image_id: '',
		twitter_image_url: '',
		twitter_title: '',
		twitter_description: '',
		twitter_image_id: '',
		twitter_same_as_facebook: true,
		focus_keyword: '',
		canonical_url: '',
	},
	unsavedPostSeoMeta: {},
	// Editor dynamic variables start.
	postDynamicData: {
		title: '',
		excerpt: '',
		content: '',
	},
	// Editor dynamic variables end.

	research: {},

	// Default Values for the post meta start.
	globalDefaults: {},
	// Default Values for the post meta end.

	// App settings start.
	appSettings: {
		currentTab: 'optimize',
		previousTab: '',
		currentScreen: 'settings',
		previousScreen: '',
		// Content generation states
		generateContentProcess: 'idle',
		generatedContents: {}, // Changed from array to object to store per check/field
		selectedCheckId: null,
		selectedFieldKey: null,
		error: null,
		fixProcess: 'idle',
		onUseThis: null,
	},
	// App settings end.

	// Page SEO checks start.
	pageSeoChecks: {
		authenticated: window?.surerank_globals?.ai_authenticated || false,
		initializing: true,
		isCheckingLinks: false,
		linkCheckProgress: {
			current: 0,
			total: 0,
		},
		refreshCalled: false,
		postId: null,
		checkType: null, // 'post' or 'term'.
		isRefreshing: false,
		// Check type arrays - add new check types here as needed
		pageChecks: [], // Array to store page-specific checks
		keywordChecks: [], // Array to store keyword-specific checks
		// Add new check types above (e.g., technicalChecks: [], accessibilityChecks: [])
		brokenLinkState: {
			isChecking: false,
			checkedLinks: [], // Array instead of Set for Redux compatibility
			brokenLinks: [], // Array instead of Set for Redux compatibility
			allLinks: [],
		},
		hideFixHelpButtons: false,
	},
	// Page SEO checks end.
};

function reducer( state = DEFAULT_STATE, action ) {
	switch ( action.type ) {
		case 'UPDATE_MODAL_STATE':
			return {
				...state,
				modalEnabled: action.value,
				appSettings: {
					...state.appSettings,
					currentScreen: DEFAULT_STATE.appSettings.currentScreen,
					previousScreen: state.appSettings.currentScreen,
				},
			};
		case 'UPDATE_INITIAL_STATE':
			return {
				...state,
				...action.value,
			};
		case 'UPDATE_METABOX_STATE':
			return {
				...state,
				metaboxInitialized: action.value,
			};
		case 'UPDATE_POST_SEO_META':
			// Verify if the action value is an object. else throw an error that the action value should be an object.
			if ( typeof action.value !== 'object' ) {
				throw new Error( 'Value should be an object' );
			}

			return {
				...state,
				postSeoMeta: { ...state.postSeoMeta, ...action.value },
			};
		case 'UPDATE_META_DATA':
			// Verify if the action value is an object. else throw an error that the action value should be an object.
			if ( typeof action.value !== 'object' ) {
				throw new Error( 'Value should be an object' );
			}

			return {
				...state,
				postSeoMeta: {
					...state.postSeoMeta,
					...action.value,
				},
			};
		// Post dynamic variables.
		case 'UPDATE_DYNAMIC_DATA':
			return {
				...state,
				postDynamicData: { ...state.postDynamicData, ...action.value },
			};
		case actionTypes.UPDATE_GLOBAL_DEFAULTS:
			return {
				...state,
				globalDefaults: action.payload,
			};
		case actionTypes.UPDATE_APP_SETTINGS:
			return {
				...state,
				appSettings: { ...state.appSettings, ...action.value },
			};
		case actionTypes.SET_PAGE_SEO_CHECKS:
			return {
				...state,
				pageSeoChecks: {
					...state.pageSeoChecks,
					...action.payload,
				},
			};
		case actionTypes.SET_PAGE_SEO_CHECK:
			return {
				...state,
				pageSeoChecks: {
					...state.pageSeoChecks,
					...action.payload,
				},
			};
		case actionTypes.SET_UNSAVED_META_SETTINGS:
			return {
				...state,
				unsavedPostSeoMeta: {
					...state.unsavedPostSeoMeta,
					...action.payload,
				},
			};
		case actionTypes.RESET_UNSAVED_META_SETTINGS:
			return {
				...state,
				unsavedPostSeoMeta: {},
			};
		case actionTypes.SET_REFRESH_CALLED:
			return {
				...state,
				pageSeoChecks: {
					...state.pageSeoChecks,
					refreshCalled: action.value,
				},
			};
		case actionTypes.SET_CURRENT_POST_IGNORED_LIST:
			return {
				...state,
				pageSeoChecks: {
					...state.pageSeoChecks,
					ignoredList: action.payload,
				},
			};
		case actionTypes.SET_PAGE_SEO_CHECKS_BY_ID_AND_TYPE:
			return {
				...state,
				pageSeoChecks: {
					...state.pageSeoChecks,
					[ action.payload.postId ]: {
						checks: {
							...state.pageSeoChecks[ action.payload.postId ]
								?.checks,
							...action.payload.checks,
						},
						sequence: action.payload.sequence,
						error: action.payload.error,
					},
				},
			};
		case actionTypes.SET_BATCH_PAGE_SEO_CHECKS:
			return {
				...state,
				pageSeoChecks: {
					...state.pageSeoChecks,
					...action.payload,
				},
			};
		default:
			const proState = applyFilters(
				'surerank-pro.seo-metabox-store',
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
