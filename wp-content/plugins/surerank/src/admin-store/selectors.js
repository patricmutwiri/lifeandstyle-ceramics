export function getState( state ) {
	return state;
}

export const getMetaSettings = ( { metaSettings } ) => metaSettings;

export const getMetaSetting = ( { metaSettings }, key ) => metaSettings[ key ];

export const getSettingsPage = ( { appSettings: { settingsPage } } ) =>
	settingsPage;

export const getSiteSettings = ( { siteSettings } ) => siteSettings;
export const getSiteSetting = ( { siteSettings }, key ) => siteSettings[ key ];

export const getOpenSiteSelectorModal = ( { openSiteSelectorModal } ) =>
	openSiteSelectorModal;

export const getConfirmationModal = ( { confirmationModal } ) =>
	confirmationModal;

export const getSearchConsole = ( { searchConsole } ) => searchConsole;

export const getSiteSeoAnalysis = ( { siteSeoAnalysis } ) => siteSeoAnalysis;

export const getAppSettings = ( { appSettings } ) => appSettings;
export const getUnsavedSettings = ( { unsavedSettings } ) => unsavedSettings;
export const getEmailReportsSettings = ( { emailReportsSettings } ) =>
	emailReportsSettings;
