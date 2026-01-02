import { useSelect, useDispatch } from '@wordpress/data';
import { STORE_NAME } from '@/store/constants';
import apiFetch from '@wordpress/api-fetch';
import GlobalSaveButton from '@/apps/admin-components/global-save-button';
import { isCurrentPage } from '@/functions/utils';
import { POST_SEO_DATA_URL, TERM_SEO_DATA_URL } from '@/global/constants/api';
import { useCallback, useEffect } from '@wordpress/element';
import { fetchMetaSettings } from '@/functions/api';
import { DotIcon } from '@/global/components/icons';

const SaveButton = () => {
	const unsavedMetaSettings = useSelect(
		( select ) => select( STORE_NAME ).getUnsavedMetaSettings(),
		[]
	);
	const { resetUnsavedMetaSettings, initMetaDataAndDefaults } =
		useDispatch( STORE_NAME );

	const updateMetaSettings = async () => {
		try {
			const response = await fetchMetaSettings();
			initMetaDataAndDefaults( {
				postSeoMeta: response.data,
				globalDefaults: response.global_default,
			} );
		} catch ( error ) {
			// Do nothing
		}
	};

	const save = async () => {
		const queryParams = {
			metaData: unsavedMetaSettings,
			...( isCurrentPage( 'term.php' )
				? { term_id: surerank_seo_popup?.term_id }
				: { post_id: surerank_seo_popup?.post_id } ),
		};

		try {
			const response = await apiFetch( {
				path: isCurrentPage( 'term.php' )
					? TERM_SEO_DATA_URL
					: POST_SEO_DATA_URL,
				method: 'POST',
				data: queryParams,
			} );
			if ( ! response.success ) {
				throw response;
			}
			// Update the store with the latest data
			updateMetaSettings();
			setTimeout( () => {
				resetUnsavedMetaSettings();
			}, 1000 );
			return response;
		} catch ( error ) {
			throw error;
		}
	};

	const handleBeforeUnload = useCallback(
		( e ) => {
			e.preventDefault();
			e.returnValue = null;
		},
		[ unsavedMetaSettings ]
	);

	// Alert user when they try to leave the page without saving
	useEffect( () => {
		if (
			! unsavedMetaSettings ||
			! Object.keys( unsavedMetaSettings ?? {} ).length
		) {
			return;
		}
		window.addEventListener( 'beforeunload', handleBeforeUnload );
		return () => {
			window.removeEventListener( 'beforeunload', handleBeforeUnload );
		};
	}, [ handleBeforeUnload ] );

	const hasUnsavedSettings =
		Object.keys( unsavedMetaSettings ?? {} ).length > 0;

	return (
		<GlobalSaveButton
			onClick={ save }
			className={
				! hasUnsavedSettings
					? 'opacity-60 bg-background-brand cursor-not-allowed pointer-events-none'
					: ''
			}
			icon={ hasUnsavedSettings ? <DotIcon /> : null }
			disabled={ ! hasUnsavedSettings }
		/>
	);
};

export default SaveButton;
