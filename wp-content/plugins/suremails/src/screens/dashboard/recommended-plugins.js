// File: src/components/RecommendedPlugins.js
import { useState, memo } from '@wordpress/element';
import { Container, Label, Button, toast, Loader } from '@bsf/force-ui';
import Title from '@components/title/title';
import { __ } from '@wordpress/i18n';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { fetchInstalledPluginsData } from '@api/plugins';
import { recommendedPluginsData, pluginAddons } from 'utils/plugin-add-ons';
import { installAndActivatePlugin, activatePlugin } from 'utils/plugin-utils';

const RecommendedPlugins = () => {
	const [ installingPlugins, setInstallingPlugins ] = useState( [] );
	const [ activatingPlugins, setActivatingPlugins ] = useState( [] );
	const queryClient = useQueryClient();

	// 	// Ussing the fetchInstalledPluginsData from plugins api
	const { data: pluginsData } = useQuery( {
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
	 * @return {JSX.Element|null} - The action button or null.
	 */
	const renderActionButton = ( plugin ) => {
		const isInstalling = installingPlugins.includes( plugin.slug );
		const isActivating = activatingPlugins.includes( plugin.slug );
		const isInstalled = pluginsData?.installed.includes( plugin.slug );
		const isActive = pluginsData?.active.includes( plugin.slug );

		if ( ! isInstalled ) {
			return (
				<Button
					variant="outline"
					className="no-underline border-border-subtle text-text-primary hover:no-underline [&_svg]:size-4"
					size="xs"
					onClick={ () =>
						installAndActivatePlugin(
							plugin,
							pluginsData,
							installingPlugins,
							activatingPlugins,
							setInstallingPlugins,
							setActivatingPlugins,
							queryClient,
							toast
						)
					}
					icon={
						( isInstalling || isActivating ) && (
							<Loader variant="primary" />
						)
					}
					iconPosition="left"
					disabled={
						isInstalling ||
						isActivating ||
						window?.suremails?.pluginInstallationPermission !== '1'
					}
				>
					{ __( 'Install & Activate', 'suremails' ) }
				</Button>
			);
		}

		if ( isInstalled && ! isActive ) {
			return (
				<Button
					variant="outline"
					className="no-underline bg-button-tertiary text-text-primary hover:no-underline border-border-subtle [&_svg]:size-4"
					size="xs"
					onClick={ () =>
						activatePlugin(
							plugin,
							pluginsData,
							installingPlugins,
							activatingPlugins,
							setActivatingPlugins,
							queryClient,
							toast
						)
					}
					disabled={ isInstalling || isActivating }
					icon={
						( isInstalling || isActivating ) && (
							<Loader variant="primary" />
						)
					}
					iconPosition="left"
				>
					{ __( 'Activate', 'suremails' ) }
				</Button>
			);
		}

		if ( isActive ) {
			return (
				<Button
					variant="outline"
					className="shadow-sm bg-badge-background-green text-text-primary border-border-subtle hover:no-underline"
					size="xs"
				>
					{ __( 'Activated', 'suremails' ) }
				</Button>
			);
		}

		return null;
	};

	return (
		<Container
			containerType="flex"
			gap="xs"
			direction="column"
			className="p-3 border-solid rounded-xl border-border-subtle border-0.5 gap-1"
		>
			<Container.Item className="md:w-full lg:w-full">
				<Container
					className="p-1"
					justify="between"
					gap="xs"
					align="center"
				>
					<Container.Item>
						<Title
							title={ __( 'Extend Your Website', 'suremails' ) }
							tag="h4"
						/>
					</Container.Item>
				</Container>
			</Container.Item>
			<Container.Item className="rounded-lg md:w-full lg:w-full bg-field-primary-background">
				<Container
					containerType="grid"
					className="gap-1 p-1 grid-cols-2 md:grid-cols-4 min-[1020px]:grid-cols-1 xl:grid-cols-2"
				>
					{ recommendedPluginsData.sequence
						.slice( 0, 4 )
						.map( ( slug ) => {
							const card = pluginAddons.find(
								( p ) => p.slug === slug
							);
							if ( ! card ) {
								return null;
							}

							return (
								<Container.Item
									key={ card.slug }
									className="flex"
								>
									<Container
										containerType="flex"
										direction="column"
										className="w-[190px] min-w-[144px] min-h-[135px] flex-1 gap-1 p-2 rounded-md shadow-soft-shadow-inner bg-background-primary"
									>
										<Container.Item>
											<Container className="items-center gap-1.5 p-1">
												<Container.Item
													className="[&>svg]:size-5 flex"
													grow={ 0 }
													shrink={ 0 }
												>
													{ card.svg }
												</Container.Item>
												<Container.Item className="flex">
													<Label className="text-sm font-medium">
														{ card.title }
													</Label>
												</Container.Item>
											</Container>
										</Container.Item>
										<Container.Item className="gap-0.5 p-1">
											<Label
												variant="help"
												className="text-sm font-normal text-text-tertiary"
											>
												{ recommendedPluginsData?.description ===
												'long_description'
													? card?.long_description
													: card?.short_description }
											</Label>
										</Container.Item>
										<Container.Item className="gap-0.5 px-1 pt-2 pb-1 mt-auto">
											{ renderActionButton( card ) }
										</Container.Item>
									</Container>
								</Container.Item>
							);
						} ) }
				</Container>
			</Container.Item>
		</Container>
	);
};

export default memo( RecommendedPlugins );
