import { useLayoutEffect } from '@wordpress/element';
import { useNavigate } from 'react-router-dom';
import { toast } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';
import { ONBOARDING_SESSION_STORAGE_KEY } from '@screens/onboarding/onboarding-state';
import {
	isValidOAuthProvider,
	getProviderTypeByState,
} from '@oauth/oauth-providers';

/* global sessionStorage */

const AuthCodeDisplay = () => {
	const navigate = useNavigate();
	const storedConnectionState = localStorage.getItem( 'formStateValues' );
	const onboardingSavedState = sessionStorage.getItem(
		ONBOARDING_SESSION_STORAGE_KEY
	);

	const redirectToConnectionDrawer = ( providerType ) => {
		setTimeout( () => {
			navigate( '/connections', {
				state: {
					openDrawer: true,
					selectedProvider: providerType,
				},
			} );
		}, 300 );
	};

	const cleanUrlToDashboard = () => {
		window.history.replaceState(
			{},
			'',
			suremails.adminURL + '#/dashboard'
		);
	};

	useLayoutEffect( () => {
		if ( onboardingSavedState ) {
			return;
		}

		if ( ! storedConnectionState ) {
			return;
		}
		const storedFormStateTimeStamp = parseInt( storedConnectionState, 10 );

		const currentTime = Date.now();
		if ( currentTime > storedFormStateTimeStamp ) {
			localStorage.removeItem( 'formStateValues' );
			localStorage.removeItem( 'formStateValuesTimestamp' );
			return;
		}

		const urlParams = new URLSearchParams( window.location.search );
		const state = urlParams.get( 'state' );

		if ( ! state || ! isValidOAuthProvider( state ) ) {
			cleanUrlToDashboard();

			toast.error( __( 'Authorization Failed', 'suremails' ), {
				description: __(
					'Invalid state parameter. Please try again.',
					'suremails'
				),
				autoDismiss: false,
			} );

			redirectToConnectionDrawer( 'GMAIL' );
			return;
		}

		const code = urlParams.get( 'code' );

		if ( code ) {
			const storedFormState =
				JSON.parse( localStorage.getItem( 'formStateValues' ) ) || {};

			cleanUrlToDashboard();

			const providerType = getProviderTypeByState( state );

			const updatedFormState = {
				...storedFormState,
				auth_code: code,
				type: providerType,
				refresh_token: '',
				force_save: true,
			};

			localStorage.setItem(
				'formStateValues',
				JSON.stringify( updatedFormState )
			);

			// Redirect to appropriate connection drawer based on provider
			redirectToConnectionDrawer( providerType );
			return;
		}

		toast.error( __( 'Authorization Failed', 'suremails' ), {
			description: __(
				'We could not receive the auth code. Please try again.',
				'suremails'
			),
			autoDismiss: false,
		} );

		const storedFormState =
			JSON.parse( localStorage.getItem( 'formStateValues' ) ) || {};

		const providerType = getProviderTypeByState( state );

		const updatedFormState = {
			...storedFormState,
			type: providerType,
			refresh_token: '',
			force_save: true,
		};

		localStorage.setItem(
			'formStateValues',
			JSON.stringify( updatedFormState )
		);

		cleanUrlToDashboard();

		// Redirect to appropriate connection drawer based on provider
		redirectToConnectionDrawer( providerType );
	}, [ navigate ] );

	// For onboarding flow
	useLayoutEffect( () => {
		if ( ! onboardingSavedState || storedConnectionState ) {
			return;
		}

		// Get the code and state from the URL
		const urlParams = new URLSearchParams( window.location.search );
		const code = urlParams.get( 'code' );
		const state = urlParams.get( 'state' );

		if ( ! code || ! isValidOAuthProvider( state ) ) {
			return;
		}

		cleanUrlToDashboard();

		const connectionType = getProviderTypeByState( state );

		navigate( '/onboarding/connection', {
			state: {
				connection: connectionType,
				auth_code: code,
			},
		} );
	}, [ navigate ] );

	return null;
};

export default AuthCodeDisplay;
