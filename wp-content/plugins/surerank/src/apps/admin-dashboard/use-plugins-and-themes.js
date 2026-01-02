import { useState, useCallback } from '@wordpress/element';
import { sprintf, __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { toast } from '@bsf/force-ui';
import { FETCH_STATUS, themesAndPlugins } from './dashboard-constants';
import { GET_INSTALLED_PLUGINS_AND_THEMES_URL } from '@Global/constants/api';

/**
 * Utility function to handle plugin/theme operations (install/activate)
 *
 * @param {Object} plugin    - The plugin object
 * @param {string} operation - The operation to perform ('install' or 'activate')
 * @return {Promise} - Resolves when operation is complete
 */
const handlePluginAndThemeOperation = ( plugin, operation ) => {
	return new Promise( async ( resolve, reject ) => {
		const isInstall = operation === 'install';

		if ( ! wp.updates ) {
			reject(
				new Error(
					__( 'WordPress updates API not available.', 'surerank' )
				)
			);
			return;
		}

		// Set the operation function based on the theme or plugin type.
		const itemType = plugin.type === 'theme' ? 'theme' : 'plugin';

		const operationFunction = isInstall
			? wp.updates[
					`install${
						itemType.charAt( 0 ).toUpperCase() + itemType.slice( 1 )
					}`
			  ]
			: wp.ajax.send;

		const data = {
			slug:
				itemType === 'theme' || isInstall
					? plugin.slug
					: `${ plugin.slug }/${ plugin.slug }.php`,
			_ajax_nonce: window.surerank_globals?._ajax_nonce,
		};

		const commonOptions = {
			success: () => resolve( true ),
			error: ( error ) => {
				const errorMessage =
					error?.errorMessage ||
					sprintf(
						// translators: %1$s is the plugin name, %2$s is the operation (installation or activation), %3$s is the error message
						__( '%1$s %2$s failed: %3$s', 'surerank' ),
						plugin.name,
						isInstall
							? __( 'installation', 'surerank' )
							: __( 'activation', 'surerank' ),
						error?.statusText ||
							__( 'Unknown error occurred', 'surerank' )
					);
				reject( new Error( errorMessage ) );
			},
		};

		if ( isInstall ) {
			operationFunction( {
				...data,
				...commonOptions,
			} );
		} else {
			operationFunction( `surerank_activate_${ itemType }`, {
				data,
				...commonOptions,
			} );
		}
	} );
};

export const usePluginsAndThemes = () => {
	const [ installedThemesAndPlugins, setInstalledThemesAndPlugins ] =
		useState( {
			plugins: { installed: [], active: [] },
			themes: { installed: [], active: [] },
		} );
	const [ fetchStatus, setFetchStatus ] = useState( {
		slug: null,
		status: FETCH_STATUS.IDLE,
	} );

	const getProgressStatus = useCallback(
		( item ) => {
			return (
				fetchStatus.slug === item.slug &&
				[ FETCH_STATUS.INSTALLING, FETCH_STATUS.ACTIVATING ].includes(
					fetchStatus.status
				)
			);
		},
		[ fetchStatus ]
	);

	const getPluginStatus = useCallback(
		( item ) => {
			const itemType = item.type === 'theme' ? 'themes' : 'plugins';
			if (
				installedThemesAndPlugins[ itemType ].active.includes(
					item.slug
				)
			) {
				return 'active';
			}
			if (
				installedThemesAndPlugins[ itemType ].installed.includes(
					item.slug
				) &&
				! installedThemesAndPlugins[ itemType ].active.includes(
					item.slug
				)
			) {
				return 'activate';
			}
			return 'install';
		},
		[ installedThemesAndPlugins ]
	);

	const fetchInstalledPluginsAndThemes = async () => {
		try {
			setFetchStatus( ( prev ) => ( {
				...prev,
				status: FETCH_STATUS.LOADING,
			} ) );

			const response = await apiFetch( {
				path: GET_INSTALLED_PLUGINS_AND_THEMES_URL,
			} );

			if ( ! response.success ) {
				throw new Error( response.message );
			}

			const getInstalledAndActiveItems = ( items, type ) => {
				const installed = items
					.map( ( item ) =>
						response.data[ type ].installed.includes( item.slug )
							? item.slug
							: null
					)
					.filter( Boolean );
				const active = items
					.map( ( item ) =>
						response.data[ type ].active.includes( item.slug )
							? item.slug
							: null
					)
					.filter( Boolean );
				return { installed, active };
			};

			const { installed: installedPlugins, active: activePlugins } =
				getInstalledAndActiveItems( themesAndPlugins, 'plugins' );
			const { installed: installedThemes, active: activeThemes } =
				getInstalledAndActiveItems( themesAndPlugins, 'themes' );

			setInstalledThemesAndPlugins( {
				plugins: {
					installed: installedPlugins,
					active: activePlugins,
				},
				themes: {
					installed: installedThemes,
					active: activeThemes,
				},
			} );
		} catch ( error ) {
			toast.error(
				__( 'Failed to fetch plugins and themes', 'surerank' ),
				{
					description:
						error?.message ||
						__( 'Something went wrong', 'surerank' ),
				}
			);
		} finally {
			setFetchStatus( ( prev ) => ( {
				...prev,
				status: FETCH_STATUS.IDLE,
			} ) );
		}
	};

	const handleInstallThemeOrPlugin = async ( plugin ) => {
		if (
			!! fetchStatus.slug &&
			[ FETCH_STATUS.INSTALLING, FETCH_STATUS.ACTIVATING ].includes(
				fetchStatus.status
			)
		) {
			toast.warning(
				__(
					'Another operation is in progress. Please wait.',
					'surerank'
				)
			);
			return;
		}

		const type = plugin.type === 'theme' ? 'themes' : 'plugins';
		const isActive = installedThemesAndPlugins[ type ].active.includes(
			plugin.slug
		);

		if ( isActive ) {
			toast.info(
				sprintf(
					// translators: %1$s is the plugin name
					__(
						'%1$s is already installed and activated.',
						'surerank'
					),
					plugin.name
				)
			);
			return;
		}

		const isInstalled = installedThemesAndPlugins[
			type
		].installed.includes( plugin.slug );
		try {
			if ( ! isInstalled ) {
				setFetchStatus( {
					slug: plugin.slug,
					status: FETCH_STATUS.INSTALLING,
				} );
				await handlePluginAndThemeOperation( plugin, 'install' );
			}

			setFetchStatus( {
				slug: plugin.slug,
				status: FETCH_STATUS.ACTIVATING,
			} );
			await handlePluginAndThemeOperation( plugin, 'activate' );

			setInstalledThemesAndPlugins( ( prev ) => ( {
				...prev,
				[ type ]: {
					installed: isInstalled
						? prev[ type ].installed
						: [ ...prev[ type ].installed, plugin.slug ],
					active: [ ...prev[ type ].active, plugin.slug ],
				},
			} ) );

			toast.success(
				isInstalled
					? sprintf(
							// translators: %1$s is the plugin name
							__( '%1$s activated successfully', 'surerank' ),
							plugin.name
					  )
					: sprintf(
							// translators: %1$s is the plugin name
							__(
								'%1$s installed and activated successfully',
								'surerank'
							),
							plugin.name
					  )
			);
		} catch ( error ) {
			toast.error(
				error.message || __( 'Operation failed', 'surerank' )
			);
		} finally {
			setFetchStatus( {
				slug: null,
				status: FETCH_STATUS.IDLE,
			} );
		}
	};

	const getRecommendedPlugin = useCallback(
		( sequencedPlugins ) => {
			// Remove duplicates
			const uniquePlugins = sequencedPlugins.filter(
				( item, index, self ) =>
					index ===
					self.findIndex( ( plugin ) => plugin.slug === item.slug )
			);

			// Priority 1: Not installed plugins
			const notInstalledPlugin = uniquePlugins.find(
				( item ) => getPluginStatus( item ) === 'install'
			);
			if ( notInstalledPlugin ) {
				return notInstalledPlugin;
			}

			// Priority 2: Installed but inactive plugins
			const inactivePlugin = uniquePlugins.find(
				( item ) => getPluginStatus( item ) === 'activate'
			);
			if ( inactivePlugin ) {
				return inactivePlugin;
			}

			// Priority 3: If all are installed and active, show random one
			const randomPlugin =
				uniquePlugins[
					Math.floor( Math.random() * uniquePlugins.length )
				];
			return randomPlugin || null;
		},
		[ getPluginStatus ]
	);

	return {
		installedThemesAndPlugins,
		fetchStatus,
		fetchInstalledPluginsAndThemes,
		handleInstallThemeOrPlugin,
		getProgressStatus,
		getPluginStatus,
		getRecommendedPlugin,
	};
};
