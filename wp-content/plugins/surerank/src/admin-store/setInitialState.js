import apiFetch from '@wordpress/api-fetch';
import { dispatch } from '@wordpress/data';
import { addQueryArgs } from '@wordpress/url';
import { STORE_NAME } from './constants';
import { ADMIN_SETTINGS_URL } from '@Global/constants/api';
const prepareQueryParams = ( settingPage ) => {
	const queryParams = { setting_page: settingPage };
	return addQueryArgs( ADMIN_SETTINGS_URL, queryParams );
};

const callApi = ( queryParams ) => {
	return apiFetch( { path: queryParams } );
};

const settingResponseInState = ( response, settingPage ) => {
	if ( response?.success && response?.option_data ) {
		const chooseStoreFunction = {
			general_settings: 'updateDataGeneral',
			social_settings: 'updateDataSocial',
			advanced_settings: 'updateDataAdvanced',
		}[ settingPage ];

		// Update store data based on setting page.
		if ( chooseStoreFunction ) {
			dispatch( STORE_NAME )[ chooseStoreFunction ](
				response.option_data
			);
		}

		// Update general settings in store if setting page is social settings because we need some general settings in social settings.
		if ( 'social_settings' === settingPage ) {
			dispatch( STORE_NAME ).updateDataGeneral(
				response.general_settings
			);
		}

		// Update separator in store if response has separator.
		if ( '' !== response?.separator ) {
			dispatch( STORE_NAME ).updateSeparator( response.separator );
			delete response.separator;
		}

		// Remove success and option_data from keys from response and rest data update in store app settings.
		delete response.success;
		delete response.option_data;

		// Update app settings in store if response has data.
		if ( Object.keys( response ).length ) {
			dispatch( STORE_NAME ).updateAppSettings( response );
		}
	}
};

const setInitialState = ( settingPage ) => {
	const queryData = prepareQueryParams( settingPage );
	callApi( queryData ).then( ( response ) =>
		settingResponseInState( response, settingPage )
	);
};

export default setInitialState;
