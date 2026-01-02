import { dispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { saveAuthAccessToken } from '@Functions/api';
import { memo, useLayoutEffect, useState } from '@wordpress/element';
import { getURLParams, removeQueryParams } from '@Functions/utils';
import { STORE_NAME } from '@AdminStore/constants';

/* global toast */

const SaveAccessToken = () => {
	const [ saving, setSaving ] = useState( false );

	const handleSaveToken = async ( token ) => {
		if ( saving || ! token ) {
			return;
		}
		setSaving( true );
		try {
			const response = await saveAuthAccessToken( token );
			if ( ! response?.success ) {
				throw new Error( response?.message );
			}
			toast.success( __( 'Authentication successful', 'surerank' ), {
				description: __(
					'Authentication completed successfully. Closing tab in 3 seconds…',
					'surerank'
				),
			} );
			// Set the store authentication status to true.
			dispatch( STORE_NAME )?.setSiteSeoAnalysis( {
				authenticated: true,
			} );

			setTimeout( () => {
				window.close();
			}, 3000 );
		} catch ( error ) {
			toast.error(
				error?.message || __( 'Authentication failed', 'surerank' )
			);
		} finally {
			setSaving( false );
		}
	};

	// This effect extracts the access token from URL parameters on component mount,
	// removes it from the URL for security, and saves it via the handleSaveToken function.
	useLayoutEffect( () => {
		const url = window?.location?.href;
		const accessToken = getURLParams( url, 'access_key' );
		if ( ! accessToken ) {
			return;
		}
		const finalURL = removeQueryParams( url, 'access_key' );
		window.history.replaceState( {}, document.title, finalURL );
		handleSaveToken( accessToken );
	}, [] );

	return null;
};

export default memo( SaveAccessToken );
