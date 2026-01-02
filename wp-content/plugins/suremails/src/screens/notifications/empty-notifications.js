// @components/EmptyNotifications.js
import React, { useState, useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import { Plus } from 'lucide-react';
import { toast, Loader } from '@bsf/force-ui';
import { NoNotifications } from 'assets/icons';
import EmptyState from '@components/empty-state/empty-state';
import { useQueryClient } from '@tanstack/react-query';
import apiFetch from '@wordpress/api-fetch';
import { installAndActivatePlugin, activatePlugin } from 'utils/plugin-utils';
import { pluginAddons } from 'utils/plugin-add-ons';

const EmptyNotifications = ( {
	isSureTriggersInstalled,
	isSureTriggersActive,
} ) => {
	const isOttokitConnected = window.suremails?.ottokit_connected === '1';

	const queryClient = useQueryClient();
	const [ installingPlugins, setInstallingPlugins ] = useState( [] );
	const [ activatingPlugins, setActivatingPlugins ] = useState( [] );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ buttonText, setButtonText ] = useState(
		__( 'Install and Activate', 'suremails' )
	);

	const sureTriggersPlugin = pluginAddons.find(
		( plugin ) => plugin.slug === 'suretriggers'
	);

	const handleAction = async () => {
		setIsLoading( true );

		try {
			const pluginsData = queryClient.getQueryData( [
				'installed-plugins',
			] ) || {
				installed: [],
				active: [],
			};

			if ( ! isSureTriggersInstalled ) {
				await installAndActivatePlugin(
					sureTriggersPlugin,
					pluginsData,
					installingPlugins,
					activatingPlugins,
					setInstallingPlugins,
					setActivatingPlugins,
					queryClient,
					toast
				);
			} else if ( isSureTriggersInstalled && ! isSureTriggersActive ) {
				await activatePlugin(
					sureTriggersPlugin,
					pluginsData,
					installingPlugins,
					activatingPlugins,
					setActivatingPlugins,
					queryClient,
					toast
				);
			} else if (
				isSureTriggersInstalled &&
				isSureTriggersActive &&
				! isOttokitConnected
			) {
				await handleConnectOttokit();
			}
		} catch ( error ) {
		} finally {
			setIsLoading( false );
		}
	};

	const handleConnectOttokit = async () => {
		const connectionUrl = window?.suremails?.ottokit_admin_url;
		const authWindow = window.open( connectionUrl, '_blank' );

		let iterations = 0;
		const maxIterations = 240;
		let isConnected = false;

		setButtonText( __( 'Connecting…', 'suremails' ) );

		const pollInterval = setInterval( async () => {
			iterations++;

			try {
				const response = await apiFetch( {
					path: '/suremails/v1/ottokit-status',
					method: 'GET',
					headers: {
						'X-WP-Nonce': window.suremails?.nonce,
					},
				} );

				if ( response?.success && response.data?.ottokit_status ) {
					isConnected = true;
					clearInterval( pollInterval );
					try {
						if ( authWindow && ! authWindow.closed ) {
							authWindow.close();
						}
					} catch ( e ) {}

					setTimeout( () => {
						window.location.reload();
					}, 500 );
				}
			} catch ( err ) {}

			if (
				iterations >= maxIterations ||
				( authWindow && authWindow.closed )
			) {
				clearInterval( pollInterval );
			}
		}, 2000 );

		// Ensure fallback cleanup in 2 minutes
		setTimeout( () => {
			if ( ! isConnected ) {
				if ( authWindow && ! authWindow.closed ) {
					authWindow.close();
				}
				setButtonText( __( 'Connect OttoKit', 'suremails' ) );
			}
		}, maxIterations * 500 );
	};

	const getActionIcon = () => {
		if ( isLoading ) {
			return (
				<Loader
					variant="primary"
					size="sm"
					className="mr-2"
					aria-hidden="true"
					aria-label={ __( 'Loading', 'suremails' ) }
				/>
			);
		}

		// Hide icon if we’re in the process of connecting or asking to connect OttoKit
		if (
			buttonText === __( 'Connect OttoKit', 'suremails' ) ||
			buttonText === __( 'Connecting…', 'suremails' )
		) {
			return null;
		}

		return <Plus />;
	};

	useEffect( () => {
		if ( isSureTriggersInstalled && ! isSureTriggersActive ) {
			setButtonText( __( 'Activate OttoKit', 'suremails' ) );
		} else if (
			isSureTriggersInstalled &&
			isSureTriggersActive &&
			! isOttokitConnected
		) {
			setButtonText( __( 'Connect OttoKit', 'suremails' ) );
		} else if (
			isSureTriggersInstalled &&
			isSureTriggersActive &&
			isOttokitConnected
		) {
			setButtonText( __( 'Active', 'suremails' ) );
		} else {
			setButtonText( __( 'Install and Activate', 'suremails' ) );
		}
	}, [ isSureTriggersInstalled, isSureTriggersActive, isOttokitConnected ] );

	return (
		<EmptyState
			image={ NoNotifications }
			title={ __( 'Setup Notifications via OttoKit', 'suremails' ) }
			description={ __(
				'OttoKit integrates with SureMail, enabling real-time alerts and seamless app connections.',
				'suremails'
			) }
			bulletPoints={ [
				__(
					'Instantly receive notifications when an email fails.',
					'suremails'
				),
				__(
					'Connect with your favorite tools like Slack, Telegram, etc.',
					'suremails'
				),
				__(
					'Automatically resend failed emails or alert your team.',
					'suremails'
				),
			] }
			action={ {
				variant: 'primary',
				size: 'md',
				icon: getActionIcon(),
				iconPosition: 'left',
				onClick: handleAction,
				className: 'font-medium',
				children: buttonText,
				disabled: isLoading,
			} }
		/>
	);
};

export default EmptyNotifications;
