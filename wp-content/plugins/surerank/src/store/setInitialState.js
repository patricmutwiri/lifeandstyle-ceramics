import apiFetch from '@wordpress/api-fetch';
import { dispatch } from '@wordpress/data';
import { addQueryArgs } from '@wordpress/url';
import { store } from './store';
import { EDITOR_URL } from '@Global/constants/api';

const setInitialState = () => {
	const queryParams = {};
	// If post page.
	if ( window?.surerank_globals?.post_id ) {
		queryParams.post_id = window?.surerank_globals.post_id;
	}
	// If term page.
	if ( window?.surerank_globals?.term_id ) {
		queryParams.term_id = window?.surerank_globals.term_id;
	}

	if ( window?.surerank_seo_popup?.post_id ) {
		queryParams.post_id = window?.surerank_seo_popup.post_id;
	}
	// If term page.
	if ( window?.surerank_seo_popup?.term_id ) {
		queryParams.term_id = window?.surerank_seo_popup.term_id;
	}

	// If no post or term id, return.
	if ( ! queryParams?.post_id && ! queryParams?.term_id ) {
		return;
	}

	apiFetch( {
		path: addQueryArgs( EDITOR_URL, queryParams ),
	} ).then( ( response ) => {
		if ( response?.success ) {
			let initialState = {
				variables: response.variables,
			};

			if ( response.other ) {
				initialState = { ...initialState, ...response.other };
			}

			dispatch( store ).updateInitialState( initialState );
		}
	} );
};

export default setInitialState;
