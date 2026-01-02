import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import {
	GenerateContent as RenderGeneratedContent,
	UpgradeCPT,
} from '@GlobalComponents/fix-it-for-me';
import { STORE_NAME } from '@Store/constants';
import {
	PROCESS_STATUSES,
	CONTENT_GENERATION_MAPPING,
} from '@Global/constants';
import { generateContent as generateContentAPI } from '@Functions/api';
import ContentGenerationError from '@GlobalComponents/content-generation-error';
import useFixPageSeoCheck from './hooks/useFixPageSeoCheck';

const GenerateContent = ( props ) => {
	const { updateAppSettings } = useDispatch( STORE_NAME );
	const {
		status = PROCESS_STATUSES.IDLE,
		allGeneratedContents = {},
		selectedCheckId,
		selectedFieldKey,
		checkType,
		genError,
		postId,
		onSuccess,
		onError,
		onProgress,
		onUseThis,
		previousScreen,
	} = useSelect( ( select ) => {
		const seoChecks = select( STORE_NAME ).getPageSeoChecks();
		const appSettings = select( STORE_NAME ).getAppSettings();
		return {
			status: appSettings?.generateContentProcess,
			allGeneratedContents: appSettings?.generatedContents,
			selectedCheckId:
				appSettings?.selectedCheckId || seoChecks?.selectedItem,
			selectedFieldKey: appSettings?.selectedFieldKey,
			genError: appSettings?.error,
			postId: seoChecks?.postId,
			checkType: seoChecks?.checkType,
			previousScreen: appSettings?.previousScreen,
			onSuccess: appSettings?.onSuccess,
			onError: appSettings?.onError,
			onProgress: appSettings?.onProgress,
			onUseThis: appSettings?.onUseThis,
		};
	}, [] );

	// Get the content for the current check/field
	const currentKey = selectedFieldKey || selectedCheckId;
	const content = allGeneratedContents[ currentKey ] || [];

	const { handleFixContent, isFixing } = useFixPageSeoCheck( {
		onSuccess,
		onError,
		onProgress,
	} );

	// Generate content using the real API
	const generateContent = async () => {
		if ( status === PROCESS_STATUSES.IN_PROGRESS ) {
			return; // Prevent multiple simultaneous requests
		}

		try {
			updateAppSettings( {
				generateContentProcess: PROCESS_STATUSES.START,
			} );

			// Get the content type from the selected check or field key
			const contentType = selectedCheckId || selectedFieldKey;
			const mappedType = CONTENT_GENERATION_MAPPING[ contentType ]
				? CONTENT_GENERATION_MAPPING[ contentType ]
				: '';

			if ( ! mappedType ) {
				throw {
					message: __(
						'No content type selected. Please select an item to generate content for.',
						'surerank'
					),
					code: 'no_content_type',
				};
			}

			updateAppSettings( {
				generateContentProcess: PROCESS_STATUSES.IN_PROGRESS,
			} );

			// Get the post/term ID
			const id =
				postId ||
				window?.surerank_seo_popup?.post_id ||
				window?.surerank_seo_popup?.term_id;
			const isTermPage =
				checkType === 'taxonomy' ||
				window?.surerank_seo_popup?.is_taxonomy === '1';

			// Call the actual API
			const response = await generateContentAPI(
				mappedType,
				id,
				isTermPage
			);

			// Handle successful response
			if ( ! response?.success ) {
				throw response;
			}

			const transformedContent = response.content.map(
				( item, index ) => ( {
					id: Date.now() + index, // Use timestamp to ensure unique IDs
					text: item,
				} )
			);

			// Replace existing content with new content (don't append)
			const updatedContent = transformedContent;

			updateAppSettings( {
				generateContentProcess: PROCESS_STATUSES.COMPLETED,
				generatedContents: {
					...allGeneratedContents,
					[ currentKey ]: updatedContent,
				},
			} );
		} catch ( error ) {
			updateAppSettings( {
				generateContentProcess: PROCESS_STATUSES.FAILED,
				error: {
					message:
						error?.message ??
						__(
							'An unexpected error occurred while generating content.',
							'surerank'
						),
					code: error?.code || 'unknown_error',
				},
			} );
		}
	};

	const handleRegenerate = () => {
		generateContent();
	};

	// Handle "Use Me" action for generated content
	const handleUseContent = async ( selectedContent ) => {
		// Check if this is field-based generation (from magic button)
		if ( onUseThis && selectedFieldKey ) {
			// Call the field success callback with the selected content
			onUseThis( selectedFieldKey, selectedContent );
			updateAppSettings( {
				currentScreen: previousScreen,
				previousScreen: '',
				// Clear field generation state
				selectedCheckId: null,
				selectedFieldKey: null,
				onUseThis: null,
				generateContentProcess: PROCESS_STATUSES.IDLE,
				error: null,
			} );
			return;
		}
		await handleFixContent( selectedContent );
	};

	// Auto-start generation when component mounts and no content exists
	useEffect( () => {
		if (
			status === PROCESS_STATUSES.IDLE &&
			( selectedCheckId || selectedFieldKey ) &&
			! content.length
		) {
			generateContent();
		}
	}, [] );

	if ( status === PROCESS_STATUSES.FAILED ) {
		// If the error is due to not having a Pro plan and limit exceeded, show the upgrade component
		if ( genError?.code === 'require_pro' ) {
			return <UpgradeCPT />;
		}

		if ( genError?.code === 'limit_exceeded' ) {
			return (
				<UpgradeCPT
					title={ __( 'Daily Limit Reached', 'surerank' ) }
					description={ __(
						"You've used all your AI credits for today. Your credits will refresh automatically tomorrow, so you can continue creating content.",
						'surerank'
					) }
					showButton={ false }
				/>
			);
		}

		// For other errors, show the ContentGenerationError component
		return (
			<ContentGenerationError
				error={ genError }
				onRetry={ handleRegenerate }
				title={ __( 'Generation Failed', 'surerank' ) }
				supportText={ __(
					'Click here to contact support.',
					'surerank'
				) }
				retryText={ __( 'Retry', 'surerank' ) }
			/>
		);
	}

	// Show content or empty state for completed state
	if ( status === PROCESS_STATUSES.COMPLETED ) {
		return (
			<RenderGeneratedContent
				{ ...props }
				contents={ content }
				onRegenerate={ handleRegenerate }
				onUseThis={ handleUseContent }
				fixing={ isFixing }
			/>
		);
	}

	// Default fallback
	return (
		<RenderGeneratedContent
			{ ...props }
			contents={ content }
			onRegenerate={ handleRegenerate }
			onUseThis={ handleUseContent }
			generating={
				status === PROCESS_STATUSES.IN_PROGRESS ||
				status === PROCESS_STATUSES.START
			}
			error={ status === PROCESS_STATUSES.FAILED ? genError : null }
			fixing={ isFixing }
		/>
	);
};

export default GenerateContent;
