import { Accordion, Text, Alert } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';
import { useCallback, useEffect, useMemo } from '@wordpress/element';
import {
	useSelect,
	useDispatch,
	select as staticSelect,
} from '@wordpress/data';
import RefreshButtonPortal from '@SeoPopup/components/refresh-button-portal';
import { STORE_NAME } from '@/store/constants';
import PageChecksHoc from '@SeoPopup/components/page-seo-checks/page-checks-hoc';
import PageBuilderPageSeoChecksHoc from '@SeoPopup/components/page-seo-checks/page-builder-page-checks-hoc';
import KeywordInput from '@SeoPopup/components/keyword-input';
import { ENABLE_PAGE_LEVEL_SEO } from '@Global/constants';
import {
	isPageBuilderActive,
	isElementorBuilder,
	refreshPageChecks,
	isSeoAnalysisDisabled,
	isBricksBuilder,
	isAvadaBuilder,
} from '@SeoPopup/components/page-seo-checks/analyzer/utils/page-builder';
import { calculateCheckStatus } from '@SeoPopup/utils/calculate-check-status';

const ChecksComponent = ( { type } ) => {
	if ( isSeoAnalysisDisabled() ) {
		return null;
	}

	const isPageBuilder = isPageBuilderActive();

	if ( isPageBuilder ) {
		return <PageBuilderPageSeoChecksHoc type={ type } />;
	}

	return <PageChecksHoc type={ type } />;
};

const Analyze = () => {
	const isPageBuilder = isPageBuilderActive();

	// Get entire pageSeoChecks state and extract what we need
	const pageSeoChecks = useSelect(
		( select ) => select( STORE_NAME ).getPageSeoChecks(),
		[]
	);

	const hasAnyPageCheckIssues = useMemo( () => {
		const categorizedChecks = pageSeoChecks?.categorizedPageChecks || {};
		return calculateCheckStatus( categorizedChecks )?.status !== 'success';
	}, [ pageSeoChecks?.categorizedPageChecks ] );

	const isRefreshing = pageSeoChecks?.isRefreshing || false;
	const brokenLinkStateFromStore = pageSeoChecks?.brokenLinkState || {
		isChecking: false,
		checkedLinks: [],
		brokenLinks: [],
		allLinks: [],
	};

	// Convert arrays back to Sets for backward compatibility
	const brokenLinkState = {
		...brokenLinkStateFromStore,
		checkedLinks: new Set( brokenLinkStateFromStore.checkedLinks ),
		brokenLinks: new Set( brokenLinkStateFromStore.brokenLinks ),
	};

	const modalState = useSelect(
		( select ) => select( STORE_NAME ).getModalState(),
		[]
	);

	const refreshCalled = useSelect(
		( select ) => select( STORE_NAME ).getRefreshCalled(),
		[]
	);

	const { setPageSeoCheck, setRefreshCalled } = useDispatch( STORE_NAME );

	// Create Redux action wrapper functions to maintain compatibility with refreshPageChecks
	const setIsRefreshing = useCallback(
		( value ) => {
			setPageSeoCheck( 'isRefreshing', value );
		},
		[ setPageSeoCheck ]
	);

	const setBrokenLinkState = useCallback(
		( value ) => {
			// Convert Sets to arrays before storing in Redux
			let storeValue = value;
			if ( typeof value === 'function' ) {
				// For functional updates, we need to get current state, apply function, then convert
				const currentState = {
					...brokenLinkStateFromStore,
					checkedLinks: new Set(
						brokenLinkStateFromStore.checkedLinks
					),
					brokenLinks: new Set(
						brokenLinkStateFromStore.brokenLinks
					),
				};
				const updatedState = value( currentState );
				storeValue = {
					...updatedState,
					checkedLinks: Array.from( updatedState.checkedLinks || [] ),
					brokenLinks: Array.from( updatedState.brokenLinks || [] ),
				};
			} else if ( value && typeof value === 'object' ) {
				storeValue = {
					...value,
					checkedLinks:
						value.checkedLinks instanceof Set
							? Array.from( value.checkedLinks )
							: value.checkedLinks || [],
					brokenLinks:
						value.brokenLinks instanceof Set
							? Array.from( value.brokenLinks )
							: value.brokenLinks || [],
				};
			}
			setPageSeoCheck( 'brokenLinkState', storeValue );
		},
		[ setPageSeoCheck, brokenLinkStateFromStore ]
	);

	const handleRefreshWithBrokenLinks = useCallback( async () => {
		setRefreshCalled( true );
		await refreshPageChecks(
			setIsRefreshing,
			setBrokenLinkState,
			setPageSeoCheck,
			staticSelect,
			pageSeoChecks,
			brokenLinkState
		);
	}, [
		setIsRefreshing,
		setBrokenLinkState,
		setPageSeoCheck,
		pageSeoChecks,
		brokenLinkState,
		setRefreshCalled,
	] );

	// Auto-refresh functionality for page builders
	useEffect( () => {
		if ( isPageBuilder && modalState && ! refreshCalled ) {
			refreshPageChecks(
				setIsRefreshing,
				setBrokenLinkState,
				setPageSeoCheck,
				staticSelect,
				pageSeoChecks,
				brokenLinkState
			);
			setRefreshCalled( true );
		}
	}, [
		isPageBuilder,
		modalState,
		refreshCalled,
		setPageSeoCheck,
		pageSeoChecks,
		brokenLinkState,
		setRefreshCalled,
	] );

	// Determine default accordion value based on page check status
	const defaultAccordionValue = hasAnyPageCheckIssues
		? 'page-checks'
		: 'keyword-checks';

	// Early return if no valid component is found.
	if ( isSeoAnalysisDisabled() ) {
		return (
			<div>
				<Text
					color="help"
					size={ 14 }
					className="text-center py-5 border-0.5 border-solid border-border-secondary rounded-md"
				>
					{ __(
						'SEO analysis is not available for this page.',
						'surerank'
					) }
				</Text>
			</div>
		);
	}

	return (
		<div className="space-y-2">
			{ /* Show save message only for Elementor */ }
			{ isElementorBuilder() && (
				<div className="[&_p.mr-10]:mr-0 m-1">
					<Alert
						variant="info"
						content={
							<span className="flex items-start gap-2">
								<p>
									{ __(
										'Please save changes in the editor before refreshing the checks.',
										'surerank'
									) }
								</p>
								<span className="-mr-3 refresh-button-container shrink-0" />
							</span>
						}
						className="shadow-none"
					/>
				</div>
			) }
			{ /* Render RefreshButtonPortal at top level so it's always available for page builders */ }
			{ isPageBuilder && (
				<RefreshButtonPortal
					isRefreshing={ isRefreshing }
					isChecking={ pageSeoChecks.isCheckingLinks }
					onClick={ handleRefreshWithBrokenLinks }
				/>
			) }
			<Accordion
				autoClose={ true }
				defaultValue={ defaultAccordionValue }
				type="boxed"
			>
				<Accordion.Item
					value="page-checks"
					className="bg-background-primary overflow-hidden"
				>
					<Accordion.Trigger className="text-base [&>svg]:size-5 pr-2 pl-3 py-3">
						{ __( 'Page Checks', 'surerank' ) }
					</Accordion.Trigger>
					<Accordion.Content>
						<div className="pt-3">
							<ChecksComponent type="page" />
						</div>
					</Accordion.Content>
				</Accordion.Item>
				<Accordion.Item
					value="keyword-checks"
					className="bg-background-primary overflow-hidden"
				>
					<Accordion.Trigger className="text-base [&>svg]:size-5 pr-2 pl-3 py-3">
						{ __( 'Keyword Checks', 'surerank' ) }
					</Accordion.Trigger>
					<Accordion.Content>
						<div className="pt-3">
							{ ENABLE_PAGE_LEVEL_SEO &&
								! isBricksBuilder() &&
								! isAvadaBuilder() && (
									<div className="flex items-center gap-2 mb-3">
										<KeywordInput />
									</div>
								) }
							<ChecksComponent type="keyword" />
						</div>
					</Accordion.Content>
				</Accordion.Item>
			</Accordion>
		</div>
	);
};

export default Analyze;
