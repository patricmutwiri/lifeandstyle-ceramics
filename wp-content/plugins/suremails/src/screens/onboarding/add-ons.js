import { Header } from './components';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import NavigationButtons from './navigation-buttons';
import { useOnboardingNavigation } from './hooks';
import {
	Badge,
	Button,
	Container,
	Text,
	Loader,
	toast,
	Skeleton,
} from '@bsf/force-ui';
import { pluginAddons } from '@utils/plugin-add-ons';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { fetchInstalledPluginsData } from '@api/plugins';
import { installAndActivatePlugin, activatePlugin } from '@utils/plugin-utils';
import { setOnboardingCompletionStatus } from '@api/onboarding';

const AddOns = () => {
	const { navigateToNextRoute, navigateToPreviousRoute } =
		useOnboardingNavigation();
	const [ installingPlugins, setInstallingPlugins ] = useState( [] );
	const [ activatingPlugins, setActivatingPlugins ] = useState( [] );
	const queryClient = useQueryClient();

	// Fetch installed plugins data
	const { data: pluginsData, isLoading: isPluginsDataLoading } = useQuery( {
		queryKey: [ 'installed-plugins' ],
		queryFn: fetchInstalledPluginsData,
		refetchInterval: 100000,
		refetchOnMount: false,
		refetchOnWindowFocus: false,
		refetchOnReconnect: true,
	} );

	/**
	 * Render the appropriate action button based on plugin state.
	 *
	 * @param {Object} plugin - The plugin object.
	 * @return {JSX.Element} - The action button.
	 */
	const renderActionButton = ( plugin ) => {
		const isInstalling = installingPlugins.includes( plugin.slug );
		const isActivating = activatingPlugins.includes( plugin.slug );
		const isInstalled = pluginsData?.installed?.includes( plugin.slug );
		const isActive = pluginsData?.active?.includes( plugin.slug );
		const isLoading = isInstalling || isActivating;
		const hasInstallPermission =
			window?.suremails?.pluginInstallationPermission === '1';

		// Common button props
		const baseButtonProps = {
			variant: 'outline',
			size: 'xs',
			className: 'w-fit mt-auto',
		};

		// Loading icon
		const loadingIcon = isLoading ? (
			<Loader variant="primary" size="sm" />
		) : null;

		// Button configurations based on plugin state
		const buttonConfigs = {
			notInstalled: {
				...baseButtonProps,
				onClick: () =>
					installAndActivatePlugin(
						plugin,
						pluginsData,
						installingPlugins,
						activatingPlugins,
						setInstallingPlugins,
						setActivatingPlugins,
						queryClient,
						toast
					),
				icon: loadingIcon,
				iconPosition: loadingIcon ? 'left' : undefined,
				disabled: isLoading || ! hasInstallPermission,
				children: __( 'Install & Activate', 'suremails' ),
			},
			installedNotActive: {
				...baseButtonProps,
				onClick: () =>
					activatePlugin(
						plugin,
						pluginsData,
						installingPlugins,
						activatingPlugins,
						setActivatingPlugins,
						queryClient,
						toast
					),
				icon: loadingIcon,
				iconPosition: loadingIcon ? 'left' : undefined,
				disabled: isLoading,
				children: __( 'Activate', 'suremails' ),
			},
			active: {
				...baseButtonProps,
				className: `${ baseButtonProps.className } bg-badge-background-green`,
				children: __( 'Activated', 'suremails' ),
			},
			default: {
				...baseButtonProps,
				disabled: true,
				children: __( 'Install', 'suremails' ),
			},
		};

		// Determine which configuration to use
		let buttonConfig;

		if ( ! isInstalled ) {
			buttonConfig = buttonConfigs.notInstalled;
		} else if ( ! isActive ) {
			buttonConfig = buttonConfigs.installedNotActive;
		} else if ( isActive ) {
			buttonConfig = buttonConfigs.active;
		} else {
			buttonConfig = buttonConfigs.default;
		}

		if ( isPluginsDataLoading ) {
			return <Skeleton className="w-28 h-6" variant="rectangular" />;
		}

		return <Button { ...buttonConfig } />;
	};

	const updateOnboardingCompletionStatus = async () => {
		if ( !! suremails.onboardingCompleted ) {
			return;
		}
		try {
			await setOnboardingCompletionStatus();
		} catch ( error ) {
			toast.error(
				error?.message ?? __( 'Something went wrong', 'suremails' ),
				! error?.message
					? {
							description: __(
								'An error occurred while setting the onboarding status.',
								'suremails'
							),
					  }
					: {}
			);
		}
	};

	const handleNext = async () => {
		await updateOnboardingCompletionStatus();
		navigateToNextRoute();
	};

	return (
		<div className="space-y-6">
			<Header
				title={ __( 'Add More Power to Your Website', 'suremails' ) }
				description={ __(
					'These tools can help you build your website faster and easier. Try them out and see how they can help your website grow.',
					'suremails'
				) }
			/>

			<div className="grid grid-cols-1 sm:grid-cols-2 gap-1 p-1 rounded-lg bg-background-secondary">
				{ pluginAddons.map( ( plugin ) => (
					<Container
						key={ plugin.id }
						gap="none"
						direction="column"
						className="p-3 bg-background-primary rounded-md shadow-sm w-full"
					>
						<Container align="start" className="p-1 w-full">
							<span className="[&>svg]:size-6">
								{ plugin.svg }
							</span>
							<Badge
								label={ plugin.badgeText }
								variant="green"
								className="ml-auto"
							/>
						</Container>
						<Container
							direction="column"
							className="gap-1.5 flex-1"
						>
							<div className="space-y-0.5">
								<Text
									size={ 14 }
									weight={ 500 }
									color="primary"
								>
									{ plugin.title }
								</Text>
								<Text
									size={ 14 }
									weight={ 400 }
									color="tertiary"
								>
									{ plugin.short_description }
								</Text>
							</div>
							{ renderActionButton( plugin ) }
						</Container>
					</Container>
				) ) }
			</div>

			<NavigationButtons
				backProps={ { onClick: navigateToPreviousRoute } }
				continueProps={ { onClick: handleNext } }
			/>
		</div>
	);
};

export default AddOns;
