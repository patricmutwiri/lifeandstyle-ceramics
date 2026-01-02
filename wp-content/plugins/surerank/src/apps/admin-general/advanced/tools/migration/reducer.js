import { ACTIONS, MIGRATION_PROGRESS_KEY } from './constants';

// Initialize default migration state
export const initialMigrationState = {
	plugin_slug: '',
	isMigrating: false,
	isDone: false,
	error: null,
	progress: 0,
	showResume: false,
	deactivatePlugin: true,
	currentStatus: null, // { type: 'reading_terms' | 'reading_posts' | 'global_settings' | 'terms' | 'posts', taxonomy?: string, postType?: string }
	migrationData: {
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
	},
};

// Calculate progress percentage
export const calculateProgress = ( state ) => {
	const { migrationData } = state;
	if ( ! migrationData || migrationData.total_items === 0 ) {
		return 0;
	}
	return Math.round(
		( migrationData.migrated_items / migrationData.total_items ) * 100
	);
};

// Save migration progress to local storage
export const saveMigrationProgress = (
	migrationData,
	storageKey = MIGRATION_PROGRESS_KEY
) => {
	window.localStorage.setItem( storageKey, JSON.stringify( migrationData ) );
};

// Load migration progress from local storage
export const loadMigrationProgress = (
	storageKey = MIGRATION_PROGRESS_KEY
) => {
	const savedProgress = window.localStorage.getItem( storageKey );
	return savedProgress ? JSON.parse( savedProgress ) : null;
};

// Clear migration progress from local storage
export const clearMigrationProgress = (
	storageKey = MIGRATION_PROGRESS_KEY
) => {
	window.localStorage.removeItem( storageKey );
};

// Migration state reducer (uses default localStorage key)
export const migrationReducer = ( state, action ) => {
	return createMigrationReducer()( state, action );
};

/**
 * Factory function to create migration reducer with custom storage key
 *
 * @param {string} [storageKey=MIGRATION_PROGRESS_KEY] - Custom localStorage key for saving migration progress
 * @return {Function} - Reducer function that uses the specified localStorage key
 *
 * @example
 * // Create reducer with default key
 * const defaultReducer = createMigrationReducer();
 *
 * @example
 * // Create reducer with custom key
 * const customReducer = createMigrationReducer('my_custom_migration_key');
 */
export const createMigrationReducer = (
	storageKey = MIGRATION_PROGRESS_KEY
) => {
	return ( state, action ) => {
		let newState;

		switch ( action.type ) {
			case ACTIONS.SET_PLUGIN:
				return {
					...state,
					plugin_slug: action.payload,
				};

			case ACTIONS.START_MIGRATION:
				newState = {
					...state,
					isMigrating: true,
					showResume: false,
					error: null,
				};
				saveMigrationProgress( newState, storageKey );
				return {
					...newState,
					progress: calculateProgress( newState ),
				};

			case ACTIONS.SET_MIGRATION_DATA:
				newState = {
					...state,
					migrationData: {
						...state.migrationData,
						...action.payload,
					},
				};
				saveMigrationProgress( newState, storageKey );
				return {
					...newState,
					progress: calculateProgress( newState ),
				};

			case ACTIONS.COMPLETE_GLOBAL_SETTINGS:
				newState = {
					...state,
					migrationData: {
						...state.migrationData,
						global_settings_migrated: true,
						migrated_items: state.migrationData.migrated_items + 1,
					},
				};
				saveMigrationProgress( newState, storageKey );
				return {
					...newState,
					progress: calculateProgress( newState ),
				};

			case ACTIONS.COMPLETE_TERM:
				const { taxonomy, termId } = action.payload;
				const updatedTerms = { ...state.migrationData.terms };

				updatedTerms[ taxonomy ] = {
					...updatedTerms[ taxonomy ],
					completed: [
						...updatedTerms[ taxonomy ].completed,
						termId,
					],
					remaining: updatedTerms[ taxonomy ].remaining.filter(
						( id ) => id !== termId
					),
				};

				newState = {
					...state,
					migrationData: {
						...state.migrationData,
						terms: updatedTerms,
						migrated_items: state.migrationData.migrated_items + 1,
					},
				};
				saveMigrationProgress( newState, storageKey );
				return {
					...newState,
					progress: calculateProgress( newState ),
				};

			case ACTIONS.COMPLETE_POST:
				const { postType, postId } = action.payload;
				const updatedPosts = { ...state.migrationData.posts };

				updatedPosts[ postType ] = {
					...updatedPosts[ postType ],
					completed: [
						...updatedPosts[ postType ].completed,
						postId,
					],
					remaining: updatedPosts[ postType ].remaining.filter(
						( id ) => id !== postId
					),
				};

				newState = {
					...state,
					migrationData: {
						...state.migrationData,
						posts: updatedPosts,
						migrated_items: state.migrationData.migrated_items + 1,
					},
				};
				saveMigrationProgress( newState, storageKey );
				return {
					...newState,
					progress: calculateProgress( newState ),
				};

			case ACTIONS.SET_ERROR:
				return {
					...state,
					error: action.payload,
					isMigrating: false,
				};

			case ACTIONS.COMPLETE_MIGRATION:
				clearMigrationProgress( storageKey );
				return {
					...state,
					isDone: true,
					isMigrating: false,
					progress: 100,
				};

			case ACTIONS.RESET_MIGRATION:
				clearMigrationProgress( storageKey );
				return {
					...initialMigrationState,
					plugin_slug: state.plugin_slug,
				};

			case ACTIONS.LOAD_SAVED_STATE:
				return {
					...state,
					...action.payload,
					showResume: true,
				};

			case ACTIONS.SET_MIGRATION_STATUS:
				return {
					...state,
					currentStatus: action.payload,
				};
			case ACTIONS.SET_DEACTIVATE_PLUGIN:
				return {
					...state,
					deactivatePlugin: action.payload,
				};

			default:
				return state;
		}
	};
};
