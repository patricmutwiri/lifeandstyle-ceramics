import { __ } from '@wordpress/i18n';
import { useEffect, useReducer, useCallback } from '@wordpress/element';
import { useBlocker } from '@tanstack/react-router';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import {
	createMigrationReducer,
	initialMigrationState,
	loadMigrationProgress,
	clearMigrationProgress,
} from '../reducer';
import { ACTIONS, MIGRATION_PROGRESS_KEY, PLUGIN_OPTIONS } from '../constants';

/**
 * Custom hook to handle migration logic
 *
 * @param {Object}   options                    - Hook options
 * @param {Object}   [options.externalState]    - External state to use instead of internal state
 * @param {Function} [options.externalDispatch] - External dispatch function to use
 * @param {string}   [options.localStorageKey]  - Custom localStorage key for saving migration progress
 * @param {Object}   options.initialState       - Initial state for the migration
 * @return {Object}                           - Migration state and handlers
 *
 * @example
 * // Using default localStorage key
 * const migration = useMigration();
 *
 * @example
 * // Using custom localStorage key
 * const migration = useMigration({
 *   localStorageKey: 'custom_migration_progress_key'
 * });
 *
 * @example
 * // Using with external state management and custom localStorage key
 * const migration = useMigration({
 *   externalState: myState,
 *   externalDispatch: myDispatch,
 *   localStorageKey: 'my_custom_migration_key'
 * });
 */
const useMigration = ( {
	externalState = null,
	externalDispatch = null,
	localStorageKey = MIGRATION_PROGRESS_KEY,
	initialState = {},
} = {} ) => {
	// Create a custom reducer with the provided localStorage key
	const customMigrationReducer = createMigrationReducer( localStorageKey );

	// Use internal reducer if no external state management is provided
	const [ localState, localDispatch ] = useReducer( customMigrationReducer, {
		...initialMigrationState,
		...initialState,
	} );

	// Use either external or local state management
	const state = externalState || localState;
	const dispatch = externalDispatch || localDispatch;

	const {
		plugin_slug,
		isMigrating,
		isDone,
		error,
		showResume,
		deactivatePlugin,
	} = state;

	// Handle beforeunload event when migration is in progress
	const handleBeforeUnload = useCallback( () => {
		if ( isMigrating ) {
			const shouldLeave = confirm(
				__(
					'Migration is in progress. Are you sure you want to leave? Unsaved changes may be lost.',
					'surerank'
				)
			);
			return ! shouldLeave;
		}

		return false;
	}, [ isMigrating ] );

	// Block navigation when migration is in progress
	useBlocker( {
		shouldBlockFn: handleBeforeUnload,
		enableBeforeUnload: isMigrating,
	} );

	// Check if there's a migration in progress on component mount
	useEffect( () => {
		const savedProgress = loadMigrationProgress( localStorageKey );
		if ( savedProgress ) {
			dispatch( {
				type: ACTIONS.LOAD_SAVED_STATE,
				payload: savedProgress,
			} );
		}
	}, [ localStorageKey ] );

	// API helper functions
	const apiRequest = async (
		endpoint,
		method,
		data = null,
		skipError = false
	) => {
		try {
			const response = await apiFetch( {
				path: endpoint,
				method,
				data,
			} );

			if ( ! response.success && ! skipError ) {
				throw new Error(
					response.message || __( 'API request failed', 'surerank' )
				);
			}

			return response;
		} catch ( err ) {
			throw new Error(
				err.message || __( 'API request failed', 'surerank' )
			);
		}
	};

	// Mark migration as completed in the backend
	const markMigrationCompleted = async ( pluginSlug ) => {
		try {
			await apiRequest(
				`/surerank/v1/migration/completed`,
				'POST',
				{ plugin_slug: pluginSlug },
				true // Skip error handling since this is optional
			);
		} catch ( err ) {
			// Silently fail
		}
	};

	// Fetch terms data with pagination
	const fetchTermsData = async ( page = 1 ) => {
		dispatch( {
			type: ACTIONS.SET_MIGRATION_STATUS,
			payload: {
				type: 'reading_terms',
				page,
			},
		} );

		return apiRequest(
			addQueryArgs( '/surerank/v1/migration/terms', {
				page,
				plugin_slug,
			} ),
			'GET'
		);
	};

	// Fetch posts data with pagination
	const fetchPostsData = async ( page = 1 ) => {
		dispatch( {
			type: ACTIONS.SET_MIGRATION_STATUS,
			payload: {
				type: 'reading_posts',
				page,
			},
		} );

		return apiRequest(
			addQueryArgs( '/surerank/v1/migration/posts', {
				page,
				plugin_slug,
			} ),
			'GET'
		);
	};

	// Initialize migration with data fetching
	const initializeMigration = async () => {
		let totalItems = 0;
		const newState = {
			global_settings_migrated: false,
			terms: {},
			posts: {},
			pagination: {
				terms: {
					current_page: 1,
					total_pages: 1,
				},
				posts: {
					current_page: 1,
					total_pages: 1,
				},
			},
			total_items: 0,
			migrated_items: 0,
		};

		// Fetch terms data for all pages
		let termsCurrentPage = 1;
		let termsTotalPages = 1;

		do {
			const termsResponse = await fetchTermsData( termsCurrentPage );
			termsTotalPages = termsResponse.pagination.total_pages;

			// Process each taxonomy
			Object.entries( termsResponse.data ).forEach(
				( [ taxonomy, taxData ] ) => {
					if ( ! newState.terms[ taxonomy ] ) {
						newState.terms[ taxonomy ] = {
							total: taxData.count,
							title: taxData.title,
							completed: [],
							remaining: [ ...taxData.term_ids ],
						};
					} else {
						newState.terms[ taxonomy ].total += taxData.count;
						newState.terms[ taxonomy ].remaining.push(
							...taxData.term_ids
						);
					}
					totalItems += taxData.count;
				}
			);

			termsCurrentPage++;
		} while ( termsCurrentPage <= termsTotalPages );

		// Update pagination info
		newState.pagination.terms.current_page = 1;
		newState.pagination.terms.total_pages = termsTotalPages;

		// Fetch posts data for all pages
		let postsCurrentPage = 1;
		let postsTotalPages = 1;

		do {
			const postsResponse = await fetchPostsData( postsCurrentPage );
			postsTotalPages = postsResponse.pagination.total_pages;

			// Process each post type
			Object.entries( postsResponse.data ).forEach(
				( [ postType, postData ] ) => {
					if ( ! newState.posts[ postType ] ) {
						newState.posts[ postType ] = {
							total: postData.count,
							title: postData.title,
							completed: [],
							remaining: [ ...postData.post_ids ],
						};
					} else {
						newState.posts[ postType ].total += postData.count;
						newState.posts[ postType ].remaining.push(
							...postData.post_ids
						);
					}
					totalItems += postData.count;
				}
			);

			postsCurrentPage++;
		} while ( postsCurrentPage <= postsTotalPages );

		// Update pagination info
		newState.pagination.posts.current_page = 1;
		newState.pagination.posts.total_pages = postsTotalPages;

		// Add global settings to the total count
		totalItems += 1; // +1 for global settings

		// Update total items count
		newState.total_items = totalItems;

		return newState;
	};

	// Migration process functions
	const migrateGlobalSettings = async () => {
		try {
			dispatch( {
				type: ACTIONS.SET_MIGRATION_STATUS,
				payload: {
					type: 'global_settings',
				},
			} );

			await apiRequest(
				'/surerank/v1/migration/global-settings',
				'POST',
				{
					plugin_slug,
					cleanup: false,
				}
			);

			dispatch( { type: ACTIONS.COMPLETE_GLOBAL_SETTINGS } );
		} catch ( err ) {
			dispatch( { type: ACTIONS.SET_ERROR, payload: err.message } );
			throw err;
		}
	};

	const migrateTerm = async ( taxonomy, termId ) => {
		try {
			// Set the current migration status with taxonomy information
			dispatch( {
				type: ACTIONS.SET_MIGRATION_STATUS,
				payload: {
					type: 'terms',
					taxonomy,
				},
			} );

			await apiRequest(
				'/surerank/v1/migration/terms',
				'POST',
				{
					plugin_slug,
					term_ids: [ termId ],
					cleanup: false,
				},
				true
			);

			dispatch( {
				type: ACTIONS.COMPLETE_TERM,
				payload: { taxonomy, termId },
			} );
		} catch ( err ) {
			dispatch( { type: ACTIONS.SET_ERROR, payload: err.message } );
			throw err;
		}
	};

	const migratePost = async ( postType, postId ) => {
		try {
			// Set the current migration status with postType information
			dispatch( {
				type: ACTIONS.SET_MIGRATION_STATUS,
				payload: {
					type: 'posts',
					postType,
				},
			} );

			await apiRequest(
				'/surerank/v1/migration/posts',
				'POST',
				{
					plugin_slug,
					post_ids: [ postId ],
					cleanup: false,
				},
				true
			);

			dispatch( {
				type: ACTIONS.COMPLETE_POST,
				payload: { postType, postId },
			} );
		} catch ( err ) {
			dispatch( { type: ACTIONS.SET_ERROR, payload: err.message } );
			throw err;
		}
	};

	const deactivateSourcePlugin = async ( pluginSlug = plugin_slug ) => {
		try {
			await apiRequest( '/surerank/v1/plugins/deactivate', 'POST', {
				plugin_slug: pluginSlug,
			} );
		} catch ( err ) {}
	};

	// Start or continue migration process
	const processMigration = async ( initialData = {}, deactivate = false ) => {
		try {
			const { global_settings_migrated, terms, posts } = initialData;

			// Migrate global settings if not already migrated
			if ( ! global_settings_migrated ) {
				await migrateGlobalSettings();
			}

			// Migrate remaining terms one by one
			for ( const taxonomy in terms ) {
				const termsData = terms[ taxonomy ];
				// Set the current taxonomy being processed
				dispatch( {
					type: ACTIONS.SET_MIGRATION_STATUS,
					payload: {
						type: 'terms',
						taxonomy,
					},
				} );

				for ( const termId of [ ...termsData.remaining ] ) {
					await migrateTerm( taxonomy, termId );
				}
			}

			// Migrate remaining posts one by one
			for ( const postType in posts ) {
				const postsData = posts[ postType ];
				// Set the current post type being processed
				dispatch( {
					type: ACTIONS.SET_MIGRATION_STATUS,
					payload: {
						type: 'posts',
						postType,
					},
				} );

				for ( const postId of [ ...postsData.remaining ] ) {
					await migratePost( postType, postId );
				}
			}

			if ( deactivate ) {
				await deactivateSourcePlugin( plugin_slug );
			}

			// Mark migration as completed in the backend
			await markMigrationCompleted( state.plugin_slug );

			// Migration complete
			dispatch( { type: ACTIONS.COMPLETE_MIGRATION } );
		} catch ( err ) {
			// Error handling is done in individual migration functions
			dispatch( {
				type: ACTIONS.SET_ERROR,
				payload: err.message || __( 'Migration failed', 'surerank' ),
			} );
		}
	};

	// Handler functions
	const handleSelectPlugin = ( value ) => {
		if ( ! value ) {
			return;
		}
		if ( typeof value === 'string' ) {
			value = PLUGIN_OPTIONS.find( ( plugin ) => plugin.slug === value );
		}
		dispatch( { type: ACTIONS.SET_PLUGIN, payload: value.slug } );
	};

	const handleMigrate = async () => {
		try {
			// Start migration with initial data
			dispatch( { type: ACTIONS.START_MIGRATION } );
			// Initialize migration data
			const initialData = await initializeMigration();
			// Set the initial data
			dispatch( {
				type: ACTIONS.SET_MIGRATION_DATA,
				payload: initialData,
			} );

			// Process the migration
			await processMigration( initialData, deactivatePlugin );
		} catch ( err ) {
			dispatch( {
				type: ACTIONS.SET_ERROR,
				payload:
					err.message ||
					__( 'Migration failed. Please try again.', 'surerank' ),
			} );
		}
	};

	const handleResumeMigration = async () => {
		const savedProgress = loadMigrationProgress( localStorageKey );
		if ( savedProgress ) {
			dispatch( { type: ACTIONS.START_MIGRATION } );
			await processMigration(
				savedProgress?.migrationData,
				savedProgress?.deactivatePlugin
			);
		}
	};

	const handleStartOver = () => {
		dispatch( { type: ACTIONS.RESET_MIGRATION } );
		handleMigrate();
	};

	const handleCancelMigration = () => {
		clearMigrationProgress( localStorageKey );
		dispatch( { type: ACTIONS.RESET_MIGRATION } );
	};

	const handleChangeDeactivate = ( checked ) => {
		dispatch( {
			type: ACTIONS.SET_DEACTIVATE_PLUGIN,
			payload: checked,
		} );
	};

	// Migration history related data.
	const {
		migration_ever_completed: alreadyMigrated = false,
		migration_completed_plugins: completedPlugins = [],
	} = window?.surerank_admin_common || {};

	return {
		state,
		alreadyMigrated,
		completedPlugins,
		plugin_slug,
		isMigrating,
		isDone,
		error,
		showResume,
		localStorageKey,
		dispatch,
		handleSelectPlugin,
		handleMigrate,
		handleResumeMigration,
		handleStartOver,
		handleCancelMigration,
		handleChangeDeactivate,
		deactivatePlugin,
		deactivatePluginAPI: deactivateSourcePlugin,
	};
};

export default useMigration;
