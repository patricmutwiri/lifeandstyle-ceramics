/**
 * Plugin Utility Functions
 *
 * Contains reusable functions for installing and activating plugins.
 *
 */

import { __, sprintf } from '@wordpress/i18n';

/**
 * Performs the given plugin or theme operation (install or activate).
 *
 * @param {Object}   plugin          - The plugin or theme object.
 * @param {string}   operation       - The operation ('install' or 'activate').
 * @param {Function} setLoadingState - Function to update loading state (array of plugin slugs).
 * @return {Promise} Resolves when operation is complete.
 */
export const handlePluginOperation = ( plugin, operation, setLoadingState ) => {
	return new Promise( async ( resolve, reject ) => {
		const isInstall = operation === 'install';
		// Determine the item type. If plugin.type is 'theme', we treat it as a theme; otherwise, as a plugin.
		const itemType = plugin.type === 'theme' ? 'theme' : 'plugin';

		if ( ! wp.updates ) {
			reject(
				new Error(
					__( 'WordPress updates API not available.', 'suremails' )
				)
			);
			return;
		}

		// Add the plugin slug to the loading state.
		setLoadingState( ( prev ) => [ ...prev, plugin.slug ] );

		// For install, use the appropriate wp.updates function.
		// For activate, we use wp.ajax.send with an action based on the item type.
		const operationFunction = isInstall
			? wp.updates[
					`install${
						itemType.charAt( 0 ).toUpperCase() + itemType.slice( 1 )
					}`
			  ]
			: wp.ajax.send;

		// Data to send: for installation, we simply send the slug;
		// for activation: for plugins we use the plugin file path; for themes, just the slug.
		const data = isInstall
			? { slug: plugin.slug }
			: {
					slug: plugin.init,
					_ajax_nonce: window.suremails?._ajax_nonce,
			  };

		const commonOptions = {
			success: () => {
				resolve();
			},
			error: ( error ) => {
				reject(
					new Error(
						error.errorMessage ||
							__( 'Operation failed.', 'suremails' )
					)
				);
			},
		};

		if ( isInstall ) {
			operationFunction( {
				...data,
				...commonOptions,
			} );
		} else {
			// For activation, use a different ajax action if it's a theme.
			const actionName =
				itemType === 'plugin'
					? 'suremails-activate_plugin'
					: 'suremails-activate_theme';
			operationFunction( actionName, {
				data,
				...commonOptions,
			} );
		}
	} );
};

/**
 * Install and then automatically activate a plugin or theme.
 *
 * @param {Object}   plugin               - The plugin or theme object.
 * @param {Object}   pluginsData          - Installed/active plugins data.
 * @param {Array}    installingPlugins    - Array of slugs currently installing.
 * @param {Array}    activatingPlugins    - Array of slugs currently activating.
 * @param {Function} setInstallingPlugins - Setter for installing plugins state.
 * @param {Function} setActivatingPlugins - Setter for activating plugins state.
 * @param {Object}   queryClient          - React Query client for cache invalidation.
 * @param {Function} toast                - Toast function for notifications.
 * @return {Promise} Resolves when installation and activation complete.
 */
export const installAndActivatePlugin = async (
	plugin,
	pluginsData,
	installingPlugins,
	activatingPlugins,
	setInstallingPlugins,
	setActivatingPlugins,
	queryClient,
	toast
) => {
	// Determine the type string for toast messages.
	// translators: %s: Plugin or Theme.
	const typeStr =
		plugin.type === 'theme'
			? __( 'Theme', 'suremails' )
			: __( 'Plugin', 'suremails' );

	if ( pluginsData.installed.includes( plugin.slug ) ) {
		toast.info(
			/* translators: %s: Plugin or Theme. */
			sprintf( __( '%s is already installed.', 'suremails' ), typeStr )
		);
		return;
	}

	if ( installingPlugins.length > 0 || activatingPlugins.length > 0 ) {
		toast.info(
			sprintf(
				/* translators: %s: Plugin or Theme. */
				__(
					'Another %s operation is in progress. Please wait.',
					'suremails'
				),
				typeStr
			)
		);
		return;
	}

	try {
		await handlePluginOperation( plugin, 'install', setInstallingPlugins );
		toast.success(
			/* translators: %s: Plugin or Theme. */
			sprintf( __( '%s installed successfully.', 'suremails' ), typeStr )
		);
		// Refresh the plugins data.
		queryClient.invalidateQueries( [ 'installed-plugins' ] );
		// Automatically activate after installation.
		await activatePlugin(
			plugin,
			pluginsData,
			installingPlugins,
			activatingPlugins,
			setActivatingPlugins,
			queryClient,
			toast
		);
	} catch ( error ) {
		toast.error(
			/* translators: %s: Plugin or Theme. */
			sprintf( __( 'Failed to install %s.', 'suremails' ), typeStr )
		);
	} finally {
		setInstallingPlugins( ( prev ) =>
			prev.filter( ( slug ) => slug !== plugin.slug )
		);
	}
};

/**
 * Activate a plugin.
 *
 * @param {Object}   plugin               - The plugin or theme object.
 * @param {Object}   pluginsData          - Installed/active plugins data.
 * @param {Array}    installingPlugins    - Array of slugs currently installing.
 * @param {Array}    activatingPlugins    - Array of slugs currently activating.
 * @param {Function} setActivatingPlugins - Setter for activating plugins state.
 * @param {Object}   queryClient          - React Query client for cache invalidation.
 * @param {Function} toast                - Toast function for notifications.
 * @return {Promise} Resolves when activation is complete.
 */
export const activatePlugin = async (
	plugin,
	pluginsData,
	installingPlugins,
	activatingPlugins,
	setActivatingPlugins,
	queryClient,
	toast
) => {
	// Determine the type string (should be "Plugin").
	// translators: %s: Plugin.
	const typeStr =
		plugin.type === 'theme'
			? __( 'Theme', 'suremails' )
			: __( 'Plugin', 'suremails' );

	if ( pluginsData.active.includes( plugin.slug ) ) {
		toast.info(
			/* translators: %s: Plugin or Theme. */
			sprintf( __( '%s is already activated.', 'suremails' ), typeStr )
		);
		return;
	}

	if ( installingPlugins.length > 0 || activatingPlugins.length > 0 ) {
		toast.info(
			sprintf(
				/* translators: %s: Plugin or Theme. */
				__(
					'Another %s operation is in progress. Please wait.',
					'suremails'
				),
				typeStr
			)
		);
		return;
	}

	try {
		await handlePluginOperation( plugin, 'activate', setActivatingPlugins );
		toast.success(
			/* translators: %s: Plugin or Theme. */
			sprintf( __( '%s activated successfully.', 'suremails' ), typeStr )
		);
		// Refresh the plugins data.
		queryClient.invalidateQueries( [ 'installed-plugins' ] );
	} catch ( error ) {
		toast.error(
			/* translators: %s: Plugin or Theme. */
			sprintf( __( 'Failed to activate %s.', 'suremails' ), typeStr )
		);
	} finally {
		setActivatingPlugins( ( prev ) =>
			prev.filter( ( slug ) => slug !== plugin.slug )
		);
	}
};

/**
 * Activate a theme.
 *
 * @param {Object}   item                 - The theme object.
 * @param {Object}   pluginsData          - Installed/active plugins data.
 * @param {Array}    installingPlugins    - Array of slugs currently installing.
 * @param {Array}    activatingPlugins    - Array of slugs currently activating.
 * @param {Function} setActivatingPlugins - Setter for activating plugins state.
 * @param {Object}   queryClient          - React Query client for cache invalidation.
 * @param {Function} toast                - Toast function for notifications.
 * @return {Promise} Resolves when activation is complete.
 */
export const activateTheme = async (
	item,
	pluginsData,
	installingPlugins,
	activatingPlugins,
	setActivatingPlugins,
	queryClient,
	toast
) => {
	// translators: %s: Theme.
	const typeStr = __( 'Theme', 'suremails' );

	if ( pluginsData.active.includes( item.slug ) ) {
		toast.info(
			/* translators: %s: Plugin or Theme. */
			sprintf( __( '%s is already activated.', 'suremails' ), typeStr )
		);
		return;
	}

	if ( installingPlugins.length > 0 || activatingPlugins.length > 0 ) {
		toast.info(
			sprintf(
				// translators: %s: Theme.
				__(
					'Another %s operation is in progress. Please wait.',
					'suremails'
				),
				typeStr
			)
		);
		return;
	}

	// Add theme slug to activating state.
	setActivatingPlugins( ( prev ) => [ ...prev, item.slug ] );

	const data = {
		slug: item.slug,
		_ajax_nonce: window.suremails?._ajax_nonce,
	};

	wp.ajax.send( 'suremails-activate_theme', {
		data,
		success: () => {
			toast.success(
				sprintf(
					/* translators: %s: Theme. */
					__( '%s activated successfully.', 'suremails' ),
					typeStr
				)
			);
			queryClient.invalidateQueries( [ 'installed-plugins' ] );
		},
		error: () => {
			toast.error(
				/* translators: %s: Theme. */
				sprintf( __( 'Failed to activate %s.', 'suremails' ), typeStr )
			);
		},
		complete: () => {
			setActivatingPlugins( ( prev ) =>
				prev.filter( ( slug ) => slug !== item.slug )
			);
		},
	} );
};
