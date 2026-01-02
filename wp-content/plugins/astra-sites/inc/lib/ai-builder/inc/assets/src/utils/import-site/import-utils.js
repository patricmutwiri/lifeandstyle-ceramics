import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import toast from 'react-hot-toast';
import { toastBody } from '../../helpers';

const { themeStatus } = aiBuilderVars;

export const getDemo = async ( id, storedState ) => {
	const [ { currentIndex }, dispatch ] = storedState;

	const generateData = new FormData();
	generateData.append( 'action', 'astra-sites-api_request' );
	generateData.append( 'url', 'astra-sites/' + id );
	generateData.append( '_ajax_nonce', aiBuilderVars._ajax_nonce );

	await fetch( ajaxurl, {
		method: 'post',
		body: generateData,
	} )
		.then( ( response ) => response.json() )
		.then( ( response ) => {
			if ( response.success ) {
				aiBuilderVars.previewUrl =
					'https:' + response.data[ 'astra-site-url' ];
				dispatch( {
					templateId: id,
					templateResponse: response.data,
					importErrorMessages: {},
					importErrorResponse: [],
					importError: false,
				} );
			} else {
				let errorMessages = {};

				if ( undefined !== response.data.response_code ) {
					const code = response.data.code.toString();
					switch ( code ) {
						case '401':
						case '404':
							errorMessages = {
								primaryText:
									aiBuilderVars.server_import_primary_error,
								secondaryText: '',
								errorCode: code,
								errorText: response.data.message,
								solutionText: '',
								tryAgain: true,
							};
							break;
						case '500':
							errorMessages = {
								primaryText:
									aiBuilderVars.server_import_primary_error,
								secondaryText: '',
								errorCode: code,
								errorText: response.data.message,
								solutionText:
									aiBuilderVars.ajax_request_failed_secondary,
								tryAgain: true,
							};
							break;

						case 'WP_Error':
							errorMessages = {
								primaryText:
									aiBuilderVars.client_import_primary_error,
								secondaryText: '',
								errorCode: code,
								errorText: response.data.message,
								solutionText: '',
								tryAgain: true,
							};
							break;

						case 'Cloudflare':
							errorMessages = {
								primaryText:
									aiBuilderVars.cloudflare_import_primary_error,
								secondaryText: '',
								errorCode: code,
								errorText: response.data.message,
								solutionText: '',
								tryAgain: true,
							};
							break;

						default:
							errorMessages = {
								primaryText: __(
									'Fetching related demo failed.',
									'ai-builder'
								),
								secondaryText: '',
								errorCode: '',
								errorText: response.data,
								solutionText:
									aiBuilderVars.ajax_request_failed_secondary,
								tryAgain: false,
							};
							break;
					}
					dispatch( {
						importError: true,
						importErrorMessages: errorMessages,
						importErrorResponse: response.data,
						templateResponse: null,
						currentIndex: currentIndex + 3,
					} );
				}
			}
		} )
		.catch( ( error ) => {
			dispatch( {
				importError: true,
				importErrorMessages: {
					primaryText: __(
						'Fetching related demo failed.',
						'ai-builder'
					),
					secondaryText: aiBuilderVars?.ajax_request_failed_secondary,
					errorCode: '',
					errorText: error,
					solutionText: '',
					tryAgain: false,
				},
			} );
		} );
};

export const getAiDemo = async (
	{ businessName, selectedTemplate },
	dispatch,
	websiteInfo
) => {
	const { uuid } = websiteInfo;
	const aiResponse = await apiFetch( {
		path: 'zipwp/v1/ai-site',
		method: 'POST',
		data: {
			template: selectedTemplate,
			business_name: businessName,
			uuid,
		},
	} );

	if ( aiResponse.success ) {
		dispatch( {
			templateId: selectedTemplate,
			templateResponse: aiResponse.data?.data,
			importErrorMessages: {},
			importErrorResponse: [],
			importError: false,
		} );
		return { success: true, data: aiResponse.data?.data };
	}
	dispatch( {
		importError: true,
		importErrorMessages: {
			primaryText: __( 'Fetching related demo failed.', 'ai-builder' ),
			secondaryText: '',
			errorCode: '',
			errorText:
				typeof aiResponse.data === 'string'
					? aiResponse.data
					: aiResponse?.data?.data ?? '',
			solutionText: '',
			tryAgain: false,
		},
	} );
	return { success: false, data: aiResponse.data };
};

export const fetchRequiredPlugins = async (
	$featured_plugins = [],
	permissionCheck = false
) => {
	const reqPlugins = new FormData();
	reqPlugins.append( 'action', 'astra-sites-required_plugins' );
	reqPlugins.append( '_ajax_nonce', aiBuilderVars._ajax_nonce );
	if ( $featured_plugins.length > 0 ) {
		reqPlugins.append(
			'feature_plugins',
			JSON.stringify( $featured_plugins )
		);
	}

	if ( permissionCheck ) {
		reqPlugins.append( 'ai_plugin_permission', '1' );
	}

	try {
		const response = await fetch( ajaxurl, {
			method: 'post',
			body: reqPlugins,
		} );

		return await response.json();
	} catch ( error ) {
		console.error( 'Error fetching required plugins:', error );
		throw error;
	}
};

export const checkRequiredPlugins = async ( dispatch ) => {
	try {
		const response = await fetchRequiredPlugins();

		if ( response.success ) {
			const rPlugins = response.data?.required_plugins;
			const notInstalledPlugin = rPlugins.notinstalled || '';
			const notActivePlugins = rPlugins.inactive || '';
			dispatch( {
				requiredPlugins: response.data,
				notInstalledList: notInstalledPlugin,
				notActivatedList: notActivePlugins,
			} );
			return response;
		}
		dispatch( {
			importError: true,
			importErrorMessages: {
				primaryText: __(
					'Required plugins could not be verified.',
					'ai-builder'
				),
				tryAgain: true,
				errorText: response?.data?.error,
			},
		} );
		return response;
	} catch ( error ) {
		dispatch( {
			importError: true,
			importErrorMessages: {
				primaryText: __(
					'Required plugins could not be verified.',
					'ai-builder'
				),
				tryAgain: true,
				errorText: error.message,
			},
		} );
		throw error;
	}
};

export const getFeaturePluginList = (
	features,
	selectedEcommercePlugin,
	siteFeatures
) => {
	const requiredPlugins = [];

	features?.forEach( ( feature ) => {
		switch ( feature ) {
			case 'ecommerce':
				if ( selectedEcommercePlugin === 'surecart' ) {
					requiredPlugins.push( {
						name: 'SureCart',
						slug: 'surecart',
						compulsory: siteFeatures?.find(
							( f ) => f.id === 'ecommerce'
						)?.compulsory,
						init: 'surecart/surecart.php',
					} );
				} else if ( selectedEcommercePlugin === 'woocommerce' ) {
					requiredPlugins.push( {
						name: 'WooCommerce',
						slug: 'woocommerce',
						compulsory: siteFeatures?.find(
							( f ) => f.id === 'ecommerce'
						)?.compulsory,
						init: 'woocommerce/woocommerce.php',
					} );
				}
				break;
			case 'donations':
				requiredPlugins.push( {
					name: 'SureCart',
					slug: 'surecart',
					compulsory: siteFeatures?.find(
						( f ) => f.id === 'ecommerce'
					)?.compulsory,
					init: 'surecart/surecart.php',
				} );
				break;
			case 'automation-integrations':
				requiredPlugins.push( {
					name: 'OttoKit',
					slug: 'suretriggers',
					compulsory: siteFeatures?.find(
						( f ) => f.id === 'automation-integrations'
					)?.compulsory,
					init: 'suretriggers/suretriggers.php',
				} );
				break;
			case 'smtp':
				requiredPlugins.push( {
					name: 'Suremail',
					slug: 'suremails',
					compulsory: siteFeatures?.find( ( f ) => f.id === 'smtp' )
						?.compulsory,
					init: 'suremails/suremails.php',
				} );
				break;
			case 'sure-rank':
				requiredPlugins.push( {
					name: 'SureRank',
					slug: 'surerank',
					compulsory: siteFeatures?.find(
						( f ) => f.id === 'sure-rank'
					)?.compulsory,
					init: 'surerank/surerank.php',
				} );
				break;
			case 'sales-funnels':
				requiredPlugins.push( {
					name: 'CartFlows',
					slug: 'cartflows',
					init: 'cartflows/cartflows.php',
				} );
				requiredPlugins.push( {
					name: 'Woocommerce Cart Abandonment Recovery',
					slug: 'woo-cart-abandonment-recovery',
					init: 'woo-cart-abandonment-recovery/woo-cart-abandonment-recovery.php',
				} );
				break;
			case 'video-player':
				requiredPlugins.push( {
					name: 'Preso Player',
					slug: 'presto-player',
					init: 'presto-player/presto-player.php',
				} );
				break;
			case 'appointment-bookings':
				requiredPlugins.push( {
					name: 'Latepoint',
					slug: 'latepoint',
					compulsory: siteFeatures?.find(
						( f ) => f.id === 'appointment-bookings'
					)?.compulsory,
					init: 'latepoint/latepoint.php',
				} );
				break;
			case 'live-chat':
				requiredPlugins.push( {
					name: '3CX',
					slug: 'wp-live-chat-support',
					compulsory: siteFeatures?.find(
						( f ) => f.id === 'live-chat'
					)?.compulsory,
					init: 'wp-live-chat-support/wp-live-chat-support.php',
				} );
				break;
			default:
				break;
		}
	} );

	return requiredPlugins;
};

export const activateAstra = ( dispatch ) => {
	const data = new FormData();
	data.append( 'action', 'astra-sites-activate_theme' );
	data.append( '_ajax_nonce', aiBuilderVars._ajax_nonce );

	fetch( ajaxurl, {
		method: 'post',
		body: data,
	} )
		.then( ( response ) => response.json() )
		.then( ( response ) => {
			if ( response.success ) {
				dispatch( {
					themeStatus: response,
					importStatus: __( 'Astra Theme Installed.', 'ai-builder' ),
				} );
			} else {
				dispatch( {
					importError: true,
					importErrorMessages: {
						primaryText: __(
							'Astra theme activation failed.',
							'ai-builder'
						),
						secondaryText: '',
						errorCode: '',
						errorText: response.data,
						solutionText: '',
						tryAgain: true,
					},
				} );
			}
		} )
		.catch( ( error ) => {
			/* eslint-disable-next-line no-console -- We are displaying errors in the console. */
			console.error( error );
		} );
};

export const installAstra = ( importPercent, dispatch ) => {
	const themeSlug = 'astra';
	let percentage = importPercent;
	if ( 'not-installed' === themeStatus ) {
		percentage += 5;
		dispatch( {
			importPercent: percentage,
			importStatus: __( 'Installing Astra Theme…', 'ai-builder' ),
		} );
		if (
			wp.updates.shouldRequestFilesystemCredentials &&
			! wp.updates.ajaxLocked
		) {
			wp.updates.requestFilesystemCredentials();
		}

		wp.updates
			.installTheme( {
				slug: themeSlug,
				ajax_nonce: aiBuilderVars._ajax_nonce,
			} )
			.catch( ( error ) => {
				console.log( error );
				// Check if error is due to folder already existing
				const isFolderExistsError =
					error?.errorCode === 'folder_exists' ||
					( error?.errorMessage &&
						error.errorMessage.toLowerCase().includes( 'folder' ) &&
						error.errorMessage.toLowerCase().includes( 'exist' ) );

				if ( isFolderExistsError ) {
					// Theme is already installed, proceed to activate
					dispatch( {
						importStatus: __(
							'Astra Theme Already Installed.',
							'ai-builder'
						),
					} );
					activateAstra( dispatch );
				} else {
					dispatch( {
						importError: true,
						importErrorMessages: {
							primaryText:
								error?.errorMessage ??
								__(
									'Theme installation failed.',
									'ai-builder'
								),
							tryAgain: true,
						},
					} );
				}
			} );

		jQuery( document ).on( 'wp-theme-install-success', function () {
			dispatch( {
				importStatus: __( 'Astra Theme Installed.', 'ai-builder' ),
			} );
			activateAstra( dispatch );
		} );
	}

	if ( 'installed-but-inactive' === themeStatus ) {
		// WordPress adds "Activate" button after waiting for 1000ms. So we will run our activation after that.
		setTimeout( () => activateAstra( dispatch ), 3000 );
	}

	if ( 'installed-and-active' === themeStatus ) {
		dispatch( {
			themeStatus: true,
		} );
	}
};

export const showErrorToast = ( title = '', error = {} ) => {
	toast.error(
		toastBody( { title, message: JSON.stringify( error, null, 4 ) } )
	);
};

export const setSiteLogo = async ( logo ) => {
	if ( '' === logo.id ) {
		return;
	}
	const data = new FormData();
	data.append( 'action', 'astra-sites-set_site_data' );
	data.append( 'param', 'site-logo' );
	data.append( 'logo', logo.id );
	data.append( 'logo-width', logo.width );
	data.append( '_ajax_nonce', aiBuilderVars._ajax_nonce );

	await fetch( ajaxurl, {
		method: 'post',
		body: data,
	} ).catch( ( e ) =>
		showErrorToast( __( 'Failed to save Site Logo', 'ai-builder' ), e )
	);
};

export const setColorPalettes = async ( palette ) => {
	if ( ! palette ) {
		return;
	}

	const data = new FormData();
	data.append( 'action', 'astra-sites-set_site_data' );
	data.append( 'param', 'site-colors' );
	data.append( 'palette', palette );
	data.append( '_ajax_nonce', aiBuilderVars._ajax_nonce );

	await fetch( ajaxurl, {
		method: 'post',
		body: data,
	} ).catch( ( e ) =>
		showErrorToast( __( 'Failed to save Color Palletes', 'ai-builder' ), e )
	);
};

export const setSiteTitle = async ( businessName, showSiteTitle ) => {
	if ( ! businessName ) {
		return;
	}

	const data = new FormData();
	data.append( 'action', 'astra-sites-set_site_data' );
	data.append( 'param', 'site-title' );
	data.append( 'business-name', businessName );
	data.append( '_ajax_nonce', aiBuilderVars._ajax_nonce );
	data.append( 'show-site-title', showSiteTitle );

	await fetch( ajaxurl, {
		method: 'post',
		body: data,
	} ).catch( ( e ) =>
		showErrorToast( __( 'Failed to save Color Palletes', 'ai-builder' ), e )
	);
};

export const saveTypography = async ( selectedValue ) => {
	const data = new FormData();
	data.append( 'action', 'astra-sites-set_site_data' );
	data.append( 'param', 'site-typography' );
	data.append( 'typography', JSON.stringify( selectedValue ) );
	data.append( '_ajax_nonce', aiBuilderVars._ajax_nonce );

	await fetch( ajaxurl, {
		method: 'post',
		body: data,
	} ).catch( ( e ) =>
		showErrorToast( __( 'Failed to save Typography', 'ai-builder' ), e )
	);
};

export const divideIntoChunks = ( chunkSize, inputArray ) => {
	const values = Object.keys( inputArray );
	const final = [];
	let counter = 0;
	let portion = {};

	for ( const key in inputArray ) {
		if ( counter !== 0 && counter % chunkSize === 0 ) {
			final.push( portion );
			portion = {};
		}
		portion[ key ] = inputArray[ values[ counter ] ];
		counter++;
	}
	final.push( portion );
	return final;
};

export const checkFileSystemPermissions = async ( dispatch ) => {
	const formData = new FormData();
	formData.append( 'action', 'astra-sites-filesystem_permission' );
	formData.append( '_ajax_nonce', aiBuilderVars._ajax_nonce );
	const data = await fetch( aiBuilderVars.ajax_url, {
		method: 'POST',
		body: formData,
	} )
		.then( ( res ) => res.json )
		.catch( ( e ) =>
			showErrorToast(
				__( 'Failed to check File Permissions', 'ai-builder' ),
				e
			)
		);
	dispatch( {
		fileSystemPermissions: data.data,
	} );
};

export const setSiteLanguage = async ( siteLanguage = 'en_US' ) => {
	if ( ! siteLanguage ) {
		return;
	}

	const data = new FormData();
	data.append( 'action', 'astra-sites-site_language' );
	data.append( 'language', siteLanguage );
	data.append( '_ajax_nonce', aiBuilderVars._ajax_nonce );

	await fetch( ajaxurl, {
		method: 'post',
		body: data,
	} ).catch( ( e ) =>
		showErrorToast( __( 'Failed to save Site Language', 'ai-builder' ), e )
	);
};

export const generateAnalyticsLead = async ( tryAgainCount, status, data ) => {
	const importContent = new FormData();
	importContent.append( 'action', 'astra-sites-generate-analytics-lead' );
	importContent.append( 'status', status );
	importContent.append( 'try-again-count', tryAgainCount );
	importContent.append( 'type', 'ai_builder' );
	importContent.append( '_ajax_nonce', aiBuilderVars._ajax_nonce );

	// Append extra data.
	Object.entries( data ).forEach( ( [ key, value ] ) =>
		importContent.append( key, value )
	);

	await fetch( ajaxurl, {
		method: 'post',
		body: importContent,
	} );
};

/**
 * Check if user has permission to import in multisite environment
 *
 * @param {Object} requiredPluginsData - Plugin requirements data from server
 * @param {Object} aiBuilderVars       - Localized variables
 * @return {Object} Permission check result
 */
export const checkMultisiteImportPermissions = (
	requiredPluginsData,
	aiBuilderVars
) => {
	// Only apply in multisite environments
	if ( ! aiBuilderVars.isMultisite ) {
		return { allowed: true };
	}

	// Server-side logic in helper.php already handles all the multisite permission checks
	// and returns an 'error' key in the response data
	const hasPluginError = requiredPluginsData?.error || false;
	const hasThemeError = aiBuilderVars.canActivatePlugins
		? themeStatus === 'not-installed'
		: themeStatus !== 'installed-and-active';

	if ( hasPluginError || hasThemeError ) {
		// Extract missing plugins and themes from the required plugins data
		const requiredPlugins = requiredPluginsData?.required_plugins || {};
		const notInstalledPlugins = requiredPlugins.notinstalled || [];
		const inactivePlugins = requiredPlugins.inactive || [];

		let allMissingPlugins = [ ...notInstalledPlugins, ...inactivePlugins ];
		if ( aiBuilderVars.canActivatePlugins ) {
			allMissingPlugins = [ ...notInstalledPlugins ];
		}

		// Check theme requirements from localized variables
		const missingThemes = hasThemeError ? [ { name: 'Astra' } ] : [];

		return {
			allowed: false,
			missingThemes,
			missingPlugins: allMissingPlugins,
		};
	}

	// Import allowed
	return { allowed: true };
};
