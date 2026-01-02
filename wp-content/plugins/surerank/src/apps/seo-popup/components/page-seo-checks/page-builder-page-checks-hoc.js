import { useSelect, useDispatch } from '@wordpress/data';
import { Suspense, useMemo } from '@wordpress/element';
import { PageChecks } from '..';
import { PAGE_SEO_CHECKS_ID_TO_STATE_MAPPING } from '@Global/constants/content-generation';
import { isBricksBuilder } from './analyzer/utils/page-builder';
import { STORE_NAME } from '@/store/constants';
import PageChecksListSkeleton from './page-checks-list-skeleton';
import { PROCESS_STATUSES } from '@/global/constants';
import { getCheckTypeKey } from '@/functions/utils';

const PageBuilderPageSeoChecksHoc = ( { type = 'page' } ) => {
	const pageSeoChecks = useSelect(
		( select ) => select( STORE_NAME ).getPageSeoChecks(),
		[]
	);
	const {
		ignorePageSeoCheck,
		restorePageSeoCheck,
		updateAppSettings,
		updatePostSeoMeta,
	} = useDispatch( STORE_NAME );

	// For keyword checks, we need focus keyword and ignored list
	const { focusKeyword, currentScreen, currentTab } = useSelect(
		( select ) => {
			const selectors = select( STORE_NAME );
			const appSettings = selectors.getAppSettings();
			return {
				focusKeyword: selectors?.getPostSeoMeta?.()?.focus_keyword,
				currentScreen: appSettings?.currentScreen,
				currentTab: appSettings?.currentTab,
			};
		},
		[]
	);

	// Get the appropriate checks based on type
	const checksData = useMemo( () => {
		const categorizedChecks = pageSeoChecks?.categorizedChecks || {
			badChecks: [],
			fairChecks: [],
			passedChecks: [],
			suggestionChecks: [],
			ignoredChecks: [],
		};

		const storeKeyByType = getCheckTypeKey( type )?.categorizedType;
		// For page checks, filter by type
		return pageSeoChecks[ storeKeyByType ] ?? { ...categorizedChecks };
	}, [ type, pageSeoChecks?.checks ] );

	const handleIgnoreCheck = ( checkId ) => {
		ignorePageSeoCheck( checkId );
	};
	const handleRestoreCheck = ( checkId ) => {
		restorePageSeoCheck( checkId );
	};

	const handleOnSuccess = ( { selectedCheckId, content } ) => {
		// Get the proper state key from the mapping
		const stateKey = PAGE_SEO_CHECKS_ID_TO_STATE_MAPPING[ selectedCheckId ];

		if ( ! stateKey ) {
			return;
		}
		// Update the state with the content used to fix the issue
		updatePostSeoMeta( {
			[ stateKey ]: content,
		} );
	};

	const handleClickFix = ( checkId ) => {
		updateAppSettings( {
			selectedCheckId: checkId,
			onSuccess: handleOnSuccess,
			generateContentProcess: PROCESS_STATUSES.IDLE,
			error: null,
			fixProcess: PROCESS_STATUSES.IDLE,
			currentScreen: 'fixItForMe',
			previousScreen: currentScreen,
			previousTab: currentTab,
		} );
	};

	// Bricks builder doesn't support page level SEO checks
	if ( isBricksBuilder() ) {
		return null;
	}

	// Handle the case where no focus keyword is provided for keyword checks
	if ( type === 'keyword' && ! focusKeyword ) {
		return null;
	}

	return (
		<div className="p-1 space-y-2 flex-1 flex flex-col">
			<div className="flex-1">
				<Suspense fallback={ <PageChecksListSkeleton /> }>
					<PageChecks
						type={ type }
						pageSeoChecks={ {
							...pageSeoChecks,
							...checksData,
							isCheckingLinks: pageSeoChecks.isCheckingLinks,
						} }
						onIgnore={ handleIgnoreCheck }
						onRestore={ handleRestoreCheck }
						onFix={ handleClickFix }
					/>
				</Suspense>
			</div>
		</div>
	);
};

export default PageBuilderPageSeoChecksHoc;
