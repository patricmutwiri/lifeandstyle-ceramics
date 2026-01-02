// @components/Notifications.js
import React, { memo, useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import { Container, toast, Title } from '@bsf/force-ui';
import { useQuery } from '@tanstack/react-query';
import { fetchInstalledPluginsData } from '@api/plugins';
import EmptyNotifications from './empty-notifications';
import NotificationsSkeleton from './notifications-skeleton';
import Ottokit from '@screens/notifications/ottokit';

const Notifications = () => {
	// Fetch the installed/active plugins data using the centralized API call.
	const {
		data: pluginsData,
		isLoading,
		error,
	} = useQuery( {
		queryKey: [ 'installed-plugins' ],
		queryFn: fetchInstalledPluginsData,
		refetchInterval: 100000,
		refetchOnMount: false,
		refetchOnWindowFocus: false,
		refetchOnReconnect: true,
	} );

	// Determine installation and activation status.
	const isSureTriggersInstalled =
		pluginsData?.installed.includes( 'suretriggers' );
	const isSureTriggersActive = pluginsData?.active.includes( 'suretriggers' );
	// Check OttoKit connection status from localized data.
	const isOttokitConnected = window.suremails?.ottokit_connected === '1';

	// Handle error state.
	useEffect( () => {
		if ( error ) {
			toast.error( __( 'Error loading notifications.', 'suremails' ), {
				description:
					error.message ||
					__(
						'There was an issue fetching notifications.',
						'suremails'
					),
			} );
		}
	}, [ error ] );

	const renderContent = () => {
		if ( isLoading ) {
			return <NotificationsSkeleton />;
		}

		// Only display the OttoKit UI if the plugin is installed, active, and connected.
		if (
			isSureTriggersInstalled &&
			isSureTriggersActive &&
			isOttokitConnected
		) {
			return <Ottokit />;
		}

		// Otherwise, show an EmptyNotifications component with a Connect OttoKit button.
		return (
			<EmptyNotifications
				isSureTriggersInstalled={ isSureTriggersInstalled }
				isSureTriggersActive={ isSureTriggersActive }
			/>
		);
	};

	// Check if Ottokit is being rendered
	const isOttokitRendering =
		isSureTriggersInstalled && isSureTriggersActive && isOttokitConnected;

	return (
		<div className="flex items-start justify-center h-full px-8 py-8 overflow-hidden bg-background-secondary">
			<div className="w-full h-auto px-4 py-4 space-y-2 border-0.5 border-solid shadow-sm opacity-100 rounded-xl border-border-subtle bg-background-primary">
				{ /* Header */ }
				{ isSureTriggersInstalled && isSureTriggersActive && (
					<div className="flex items-center justify-between w-full gap-2 px-2 py-2.25">
						<Title
							title={ __( 'Notifications', 'suremails' ) }
							tag="h1"
						/>
					</div>
				) }
				{ /* Content Area */ }
				<div
					className={ `p-2 rounded-lg ${
						isOttokitRendering ? 'bg-background-secondary' : ''
					}` }
				>
					<Container className="rounded-lg">
						{ renderContent() }
					</Container>
				</div>
			</div>
		</div>
	);
};

export default memo( Notifications );
