import { __ } from '@wordpress/i18n';
import { useEffect, useRef } from '@wordpress/element';
import { getAuth } from '@Functions/api';
import { toast } from '@bsf/force-ui';

/**
 * Custom hook for handling authentication polling across tabs
 *
 * @param {Function} onAuthSuccess           - Callback when authentication succeeds
 * @param {Object}   options                 - Configuration options
 * @param {number}   options.pollingInterval - Polling interval in ms (default: 2000)
 * @param {number}   options.maxPollingTime  - Max polling time in ms (default: 300000)
 *
 * @return {Object} - { startPolling, stopPolling, isPolling }
 */
const useAuthPolling = ( onAuthSuccess, options = {} ) => {
	const { pollingInterval = 2000, maxPollingTime = 300000 } = options;

	const pollingIntervalRef = useRef( null );
	const popupWindowRef = useRef( null );
	const pollingStartTimeRef = useRef( null );
	const requestCountRef = useRef( 0 );
	const popupClosedRef = useRef( false );

	// Clean up polling interval on unmount
	useEffect( () => {
		return () => {
			if ( pollingIntervalRef.current ) {
				clearInterval( pollingIntervalRef.current );
			}
		};
	}, [] );

	const checkAuthStatus = async () => {
		try {
			if ( popupClosedRef.current ) {
				requestCountRef.current += 1;

				if ( requestCountRef.current > 10 ) {
					stopPolling();
					toast.error(
						__(
							'Authentication timeout. Please try again.',
							'surerank'
						)
					);
					return;
				}
			}

			// Check if polling has exceeded max time
			const currentTime = Date.now();
			const elapsedTime = currentTime - pollingStartTimeRef.current;

			if ( elapsedTime > maxPollingTime ) {
				stopPolling();
				toast.error(
					__(
						'Authentication timeout. Please try again.',
						'surerank'
					)
				);
				return;
			}

			const response = await getAuth();

			if ( ! response?.success ) {
				return;
			}

			// Check if user is authenticated (no auth_url means already authenticated)
			if ( ! response?.auth_url ) {
				stopPolling();

				// Close popup if still open
				if (
					popupWindowRef.current &&
					! popupWindowRef.current.closed
				) {
					popupWindowRef.current.close();
				}

				// Call success callback
				if ( onAuthSuccess ) {
					onAuthSuccess();
				}

				toast.success( __( 'Authentication successful!', 'surerank' ) );
			}
		} catch ( error ) {
			toast.error(
				__( 'An error occurred during authentication', 'surerank' ),
				{
					description: error?.message || '',
					autoDismiss: false,
				}
			);
		}
	};

	const startPolling = ( popupWindow = null ) => {
		stopPolling();

		requestCountRef.current = 0;
		popupClosedRef.current = false;

		if ( popupWindow ) {
			popupWindowRef.current = popupWindow;
		}

		pollingStartTimeRef.current = Date.now();

		pollingIntervalRef.current = setInterval(
			checkAuthStatus,
			pollingInterval
		);

		if ( popupWindowRef.current ) {
			const checkClosed = setInterval( () => {
				if ( popupWindowRef.current?.closed ) {
					popupClosedRef.current = true;
					clearInterval( checkClosed );
				}
			}, 1000 );
		}
	};

	const openAuthPopup = ( authUrl ) => {
		const popupWindow = window.open(
			authUrl,
			'surerank_auth',
			'fullscreen=yes,scrollbars=yes,resizable=yes'
		);

		if ( ! popupWindow || popupWindow.closed ) {
			toast.error(
				__(
					'Popup blocked. Redirecting to authentication…',
					'surerank'
				)
			);
			window.location.href = authUrl;
			return null;
		}

		startPolling( popupWindow );
		return popupWindow;
	};

	const stopPolling = () => {
		if ( pollingIntervalRef.current ) {
			clearInterval( pollingIntervalRef.current );
			pollingIntervalRef.current = null;
		}
		pollingStartTimeRef.current = null;
		requestCountRef.current = 0;
		popupClosedRef.current = false;
	};

	const isPolling = pollingIntervalRef.current !== null;

	return {
		startPolling,
		stopPolling,
		openAuthPopup,
		isPolling,
	};
};

export default useAuthPolling;
