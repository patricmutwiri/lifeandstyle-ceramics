import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';
import { STORE_NAME } from '@Store/constants';
import { PROCESS_STATUSES } from '@Global/constants';
import { toast } from '@bsf/force-ui';

/**
 * Custom hook for handling fix content for page SEO checks
 *
 * @param {Object}   options            - Configuration options for the hook
 * @param {Function} options.onSuccess  - Callback function executed on successful content fix
 * @param {Function} options.onError    - Callback function executed on error
 * @param {Function} options.onProgress - Callback function executed when process starts
 */
const useFixPageSeoCheck = ( options = {} ) => {
	const { onSuccess, onError, onProgress } = options;
	const { updateAppSettings } = useDispatch( STORE_NAME );
	const {
		postId,
		checkType,
		selectedCheckId,
		status,
		currentScreen,
		previousScreen,
	} = useSelect( ( select ) => {
		const seoChecks = select( STORE_NAME ).getPageSeoChecks();
		const appSettings = select( STORE_NAME ).getAppSettings();
		return {
			postId: seoChecks?.postId,
			checkType: seoChecks?.checkType,
			selectedCheckId: appSettings?.selectedCheckId,
			status: appSettings?.fixProcess,
			currentScreen: appSettings?.currentScreen,
			previousScreen: appSettings?.previousScreen,
		};
	}, [] );

	const handleFixContent = async ( content ) => {
		try {
			if ( status === PROCESS_STATUSES.IN_PROGRESS ) {
				return; // Prevent multiple simultaneous requests
			}

			if ( content?.trim() === '' ) {
				throw new Error(
					__( 'Selected content is empty.', 'surerank' )
				);
			}

			// Get the post/term ID from the store or from global variables
			const id =
				postId ||
				window?.surerank_seo_popup?.post_id ||
				window?.surerank_seo_popup?.term_id;

			if ( ! id ) {
				throw new Error(
					__( 'Post or term ID not found.', 'surerank' )
				);
			}

			updateAppSettings( {
				fixProcess: PROCESS_STATUSES.IN_PROGRESS,
			} );

			// Call onProgress callback if provided
			if ( onProgress && typeof onProgress === 'function' ) {
				onProgress( {
					selectedCheckId,
					content,
					postId: id,
					checkType,
				} );
			}

			const isTermPage =
				checkType === 'taxonomy' ||
				window?.surerank_seo_popup?.is_taxonomy === '1';

			// Call the page SEO check fix API
			const response = await apiFetch( {
				path: '/surerank/v1/page-seo-checks/fix',
				method: 'POST',
				data: {
					type: 'content-generation',
					input_key: selectedCheckId,
					input_value: content,
					id: parseInt( id ),
					is_taxonomy: isTermPage,
				},
			} );

			if ( ! response?.success ) {
				throw new Error(
					response?.message ||
						__( 'Failed to apply content.', 'surerank' )
				);
			}

			// Call onSuccess callback if provided
			if ( onSuccess && typeof onSuccess === 'function' ) {
				onSuccess( {
					response,
					selectedCheckId,
					content,
					postId: id,
					checkType,
				} );
			}

			// Show success toast. If this is a taxonomy page, inform the user the page may require a reload.
			if ( surerank_seo_popup.editor_type !== 'block' ) {
				toast.success(
					response?.message || __( 'Fixed successfully', 'surerank' ),
					{
						description: __(
							'Fix applied successfully. Some fixes may require a page reload to see changes.',
							'surerank'
						),
					}
				);
			}

			// Reset the content generation state
			updateAppSettings( {
				currentScreen: previousScreen,
				previousScreen: currentScreen,
				fixProcess: PROCESS_STATUSES.IDLE,
				selectedCheckId: null,
				error: null,
			} );

			return response;
		} catch ( error ) {
			// Update the state with the error
			updateAppSettings( {
				fixProcess: PROCESS_STATUSES.ERROR,
			} );

			const errorMessage =
				error?.message ||
				__(
					'An unexpected error occurred while applying content.',
					'surerank'
				);

			// Call onError callback if provided
			if ( onError && typeof onError === 'function' ) {
				onError( {
					error,
					selectedCheckId,
					content,
					postId:
						postId ||
						window?.surerank_seo_popup?.post_id ||
						window?.surerank_seo_popup?.term_id,
					checkType,
				} );
			}

			toast.error( errorMessage );

			return {
				error: error?.message || __( 'Unknown error', 'surerank' ),
			};
		}
	};

	return {
		handleFixContent,
		isFixing: status === PROCESS_STATUSES.IN_PROGRESS,
	};
};

export default useFixPageSeoCheck;
