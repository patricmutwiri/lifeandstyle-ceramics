// @components/AddOns.js
import React, { useState } from 'react';
import { __ } from '@wordpress/i18n';
import { Label, Button, Loader, toast, Badge } from '@bsf/force-ui';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { fetchInstalledPluginsData } from '@api/plugins';
import Title from '@components/title/title';

import { pluginAddons, AddOnsPlugin, AddOnsTheme } from 'utils/plugin-add-ons';
import {
	installAndActivatePlugin,
	activatePlugin,
	activateTheme,
} from 'utils/plugin-utils';

const PAGE_TITLE = __( 'Add-ons', 'suremails' );
const RECOMMENDED_PLUGINS_TITLE = __( 'Recommended Plugins', 'suremails' );
const RECOMMENDED_THEMES_TITLE = __( 'Recommended Themes', 'suremails' );
const INSTALL_ACTIVATE_LABEL = __( 'Install & Activate', 'suremails' );
const ACTIVATE_LABEL = __( 'Activate', 'suremails' );
const ACTIVATED_LABEL = __( 'Activated', 'suremails' );

const AddOns = () => {
	const [ installingPlugins, setInstallingPlugins ] = useState( [] );
	const [ activatingPlugins, setActivatingPlugins ] = useState( [] );
	const queryClient = useQueryClient();

	// Ussing the fetchInstalledPluginsData from plugins api
	const { data: pluginsData } = useQuery( {
		queryKey: [ 'installed-plugins' ],
		queryFn: fetchInstalledPluginsData,
		refetchInterval: 100000,
		refetchOnMount: false,
		refetchOnWindowFocus: false,
		refetchOnReconnect: true,
	} );

	const renderActionButton = ( item ) => {
		const isInstalling = installingPlugins.includes( item.slug );
		const isActivating = activatingPlugins.includes( item.slug );
		const isInstalled = pluginsData?.installed.includes( item.slug );
		const isActive = pluginsData?.active.includes( item.slug );

		if ( ! isInstalled ) {
			return (
				<Button
					variant="outline"
					className="no-underline border-border-subtle text-text-primary hover:no-underline"
					size="sm"
					onClick={ () =>
						installAndActivatePlugin(
							item,
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
							<Loader variant="primary" size="sm" />
						)
					}
					iconPosition="left"
					disabled={
						isInstalling ||
						isActivating ||
						window?.suremails?.pluginInstallationPermission !== '1'
					}
				>
					{ INSTALL_ACTIVATE_LABEL }
				</Button>
			);
		}

		if ( isInstalled && ! isActive ) {
			// Use plugin or theme activation based on item type.
			const onClickAction =
				item.type === 'plugin'
					? () =>
							activatePlugin(
								item,
								pluginsData,
								installingPlugins,
								activatingPlugins,
								setActivatingPlugins,
								queryClient,
								toast
							)
					: () =>
							activateTheme(
								item,
								pluginsData,
								installingPlugins,
								activatingPlugins,
								setActivatingPlugins,
								queryClient,
								toast
							);

			return (
				<Button
					variant="outline"
					className="no-underline bg-button-tertiary text-text-primary hover:no-underline border-border-subtle"
					size="sm"
					onClick={ onClickAction }
					disabled={ isInstalling || isActivating }
					icon={
						( isInstalling || isActivating ) && (
							<Loader variant="primary" size="sm" />
						)
					}
					iconPosition="left"
				>
					{ ACTIVATE_LABEL }
				</Button>
			);
		}

		if ( isActive ) {
			return (
				<Button
					variant="outline"
					className="shadow-sm bg-badge-background-green text-text-primary border-border-subtle hover:no-underline"
					size="sm"
				>
					{ ACTIVATED_LABEL }
				</Button>
			);
		}

		return null;
	};

	const getFilteredAddOns = (
		source,
		descriptionType = 'long_description'
	) => {
		return source.sequence
			.map( ( slug ) =>
				pluginAddons.find( ( addon ) => addon.slug === slug )
			)
			.filter( Boolean )
			.map( ( addon ) => ( {
				...addon,
				description: addon[ descriptionType ] || '',
			} ) );
	};

	const filteredPlugins = getFilteredAddOns(
		AddOnsPlugin,
		AddOnsPlugin.description
	);
	const filteredThemes = getFilteredAddOns(
		AddOnsTheme,
		AddOnsTheme.description
	);

	const PluginThemeCard = ( { item } ) => (
		<div className="min-w-[256px] rounded-md py-4 px-4 gap-2 border-solid border-0.5 border-border-subtle shadow-sm bg-background-primary flex flex-col box-border min-h-[135px]">
			{ /* Logo and Badge Row */ }
			<div className="flex items-center justify-between py-1 px-1">
				{ /* Logo */ }
				<div className="w-8 h-8 flex items-center justify-center [&_svg]:size-8">
					{ item.svg }
				</div>

				{ /* Free Badge */ }
				<Badge
					label={ __( 'Free', 'suremails' ) }
					size="xs"
					type="pill"
					variant="green"
				/>
			</div>

			{ /* Name and Description */ }
			<div className="flex flex-col gap-1 px-1">
				{ /* Name */ }
				<Label className="text-base font-medium leading-6 text-text-primary">
					{ item.title }
				</Label>

				{ /* Description */ }
				<Label
					variant="help"
					className="text-base font-normal leading-6 text-text-tertiary"
				>
					{ item.description }
				</Label>
			</div>

			{ /* Install / Activate Button */ }
			<div className="px-1 pt-2 pb-1 mt-auto">
				{ renderActionButton( item ) }
			</div>
		</div>
	);

	return (
		<div className="w-full max-w-full overflow-x-hidden box-border">
			<div className="w-full mx-auto rounded-lg shadow-sm px-8 py-6">
				{ /* Page Title */ }
				<div className="flex justify-between items-center mb-6 mt-2">
					<Title title={ PAGE_TITLE } tag="h1" />
				</div>

				{ /* Main Card Container with updated styles */ }
				<div className="bg-background-primary shadow-sm rounded-xl px-6 py-6 gap-4">
					{ /* Recommended Plugins */ }
					<div className="mb-10">
						<div className="flex items-center gap-2 px-0.5 mb-4">
							<Title
								title={ RECOMMENDED_PLUGINS_TITLE }
								tag="h2"
							/>
						</div>
						<div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
							{ filteredPlugins.map( ( card ) => (
								<PluginThemeCard
									key={ card.slug }
									item={ card }
								/>
							) ) }
						</div>
					</div>

					{ /* Recommended Themes */ }
					<div className="mt-10">
						<div className="flex items-center gap-2 px-0.5 mb-4">
							<Title
								title={ RECOMMENDED_THEMES_TITLE }
								tag="h2"
							/>
						</div>
						<div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
							{ filteredThemes.map( ( theme ) => (
								<PluginThemeCard
									key={ theme.slug }
									item={ theme }
								/>
							) ) }
						</div>
					</div>
				</div>
			</div>
		</div>
	);
};

export default AddOns;
