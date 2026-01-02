import * as actionTypes from './actionTypes';
/**
 * Returns an action object used in signalling that viewport queries have been
 * updated. Values are specified as an object of breakpoint query keys where
 * value represents whether query matches.
 * Ignored from documentation as it is for internal use only.
 *
 * @param {string} value Value to update.
 */
export function updateAppSettings( value ) {
	return {
		type: 'UPDATE_APP_SETTINGS',
		value,
	};
}

export function updateDataSocial( value ) {
	return {
		type: 'UPDATE_DATA_SOCIAL',
		value,
	};
}

export function updateDataAdvanced( value ) {
	return {
		type: 'UPDATE_DATA_ADVANCED',
		value,
	};
}

export function fetchFromAPI( payload ) {
	return {
		type: actionTypes.FETCH_FROM_API,
		payload,
	};
}

export const setLoading = ( payload ) => {
	return {
		type: actionTypes.SET_LOADING,
		payload,
	};
};

export const setSaving = ( payload ) => {
	return {
		type: actionTypes.SET_SAVING,
		payload,
	};
};

export const setMessage = ( payload ) => {
	return {
		type: actionTypes.SET_MESSAGE,
		payload,
	};
};

export function* setMetaSettings( payload, skipUnsavedSettings = false ) {
	// Track the unsaved settings.
	if ( ! skipUnsavedSettings ) {
		yield setUnsavedSettings( payload );
	}

	return {
		type: actionTypes.SET_META_SETTINGS,
		payload,
	};
}

export function* setMetaSetting( key, value ) {
	if ( ! key ) {
		return;
	}

	// Track the unsaved settings.
	yield setUnsavedSettings( {
		[ key ]: value,
	} );

	return {
		type: actionTypes.SET_META_SETTING,
		payload: {
			[ key ]: value,
		},
	};
}

export function* initSettings( payload ) {
	yield setMetaSettings( payload, true );
}

export const setSiteSettings = ( payload ) => {
	return {
		type: actionTypes.SET_SITE_SETTINGS,
		payload,
	};
};

export const setSiteSetting = ( key, value ) => {
	if ( ! key ) {
		return;
	}
	return {
		type: actionTypes.SET_SITE_SETTING,
		payload: {
			[ key ]: value,
		},
	};
};

export const toggleSiteSelectorModal = () => {
	return {
		type: actionTypes.TOGGLE_SITE_SELECTOR_MODAL,
	};
};

export const setConfirmationModal = ( payload ) => {
	return {
		type: actionTypes.SET_CONFIRMATION_MODAL,
		payload,
	};
};

export const setSearchConsole = ( payload ) => {
	return {
		type: actionTypes.SET_SEARCH_CONSOLE,
		payload,
	};
};

export const setSiteSeoAnalysis = ( payload ) => {
	return {
		type: actionTypes.SET_SITE_SEO_ANALYSIS,
		payload,
	};
};

export const setUnsavedSettings = ( payload ) => {
	return {
		type: actionTypes.SET_UNSAVED_SETTINGS,
		payload,
	};
};

export const resetUnsavedSettings = () => {
	return {
		type: actionTypes.RESET_UNSAVED_SETTINGS,
	};
};

export const setEmailReportsSettings = ( payload ) => {
	return {
		type: actionTypes.SET_EMAIL_REPORTS_SETTINGS,
		payload,
	};
};
