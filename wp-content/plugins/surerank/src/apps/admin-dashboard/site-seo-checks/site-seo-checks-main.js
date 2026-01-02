import { STORE_NAME } from '@/admin-store/constants';
import { Breadcrumb, Button, Container, Title } from '@bsf/force-ui';
import { useDispatch, useSuspenseSelect, useSelect } from '@wordpress/data';
import { Suspense } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Home, RefreshCw } from 'lucide-react';
import SiteSeoChecksTableSkeleton, {
	SiteSeoChecksInnerTableSkeleton,
} from './site-seo-checks-table-skeleton';
import SiteSeoChecksTable from './site-seo-checks-table';
import SiteSeoChecksDrawer from './site-seo-checks-drawer';
import { createLazyRoute } from '@tanstack/react-router';
import { useRunSeoChecks } from './use-run-seo-checks';
import { cn } from '@Functions/utils';
import SaveAuthToken from '@/global/components/save-auth-token';

// Non-suspense version of the hook for components outside Suspense boundary
export const useSiteSeoAnalysis = () => {
	const state = useSelect(
		( select ) =>
			select( STORE_NAME ).getSiteSeoAnalysis() || {
				searchKeyword: '',
				report: [],
			}
	);
	const dispatch = useDispatch( STORE_NAME )?.setSiteSeoAnalysis;

	return [ state, dispatch ];
};

// Suspense version of the hook for components inside Suspense boundary
export const useSuspenseSiteSeoAnalysis = () => {
	const state = useSuspenseSelect( ( select ) =>
		select( STORE_NAME ).getSiteSeoAnalysis()
	);
	const dispatch = useDispatch( STORE_NAME )?.setSiteSeoAnalysis;

	return [ state, dispatch ];
};

// Header component with breadcrumb and search
const SiteSeoChecksHeader = () => {
	return (
		<Container justify="between" align="center">
			<Breadcrumb size="md">
				<Breadcrumb.List>
					<Breadcrumb.Item>
						<Breadcrumb.Link
							href="#/dashboard"
							className="flex items-center gap-2 hover:no-underline"
						>
							<Home className="size-4 text-text-primary" />
							{ __( 'Dashboard', 'surerank' ) }
						</Breadcrumb.Link>
					</Breadcrumb.Item>
					<Breadcrumb.Separator type="slash" />
					<Breadcrumb.Item>
						<Breadcrumb.Page>
							{ __( 'Site SEO Audit', 'surerank' ) }
						</Breadcrumb.Page>
					</Breadcrumb.Item>
				</Breadcrumb.List>
			</Breadcrumb>
		</Container>
	);
};

// Title section component
const SiteSeoChecksTitle = ( { isLoading, handleRunChecksAgain } ) => {
	return (
		<Container align="center" justify="between" className="p-2">
			<Title
				tag="h4"
				title={ __( 'Site SEO Audit', 'surerank' ) }
				size="md"
			/>
			<div></div>
			<Button
				variant="outline"
				size="sm"
				icon={
					<RefreshCw
						className={ cn( 'size-4', {
							'animate-spin': isLoading,
						} ) }
					/>
				}
				onClick={ handleRunChecksAgain }
				disabled={ isLoading }
			>
				{ __( 'Run Checks', 'surerank' ) }
			</Button>
		</Container>
	);
};

// Component that uses suspense data
const SiteSeoChecksContent = () => {
	const { isLoading, handleRunChecksAgain } = useRunSeoChecks();

	// Show skeleton while API calls are loading
	if ( isLoading ) {
		return (
			<div className="w-full space-y-2 rounded-xl bg-background-primary border-0.5 border-solid border-border-subtle p-4 shadow-sm">
				<SiteSeoChecksTitle
					isLoading={ isLoading }
					handleRunChecksAgain={ handleRunChecksAgain }
				/>
				<SiteSeoChecksInnerTableSkeleton />
			</div>
		);
	}

	return (
		<div className="w-full space-y-2 rounded-xl bg-background-primary border-0.5 border-solid border-border-subtle p-4 shadow-sm">
			<SiteSeoChecksTitle
				isLoading={ isLoading }
				handleRunChecksAgain={ handleRunChecksAgain }
			/>
			<SiteSeoChecksTable />
		</div>
	);
};

// Combined component for Suspense-enabled content
const SuspendedContent = () => {
	return (
		<Suspense fallback={ <SiteSeoChecksTableSkeleton /> }>
			<SiteSeoChecksContent />
			<SiteSeoChecksDrawer />
		</Suspense>
	);
};

// Main component
const SiteSeoChecks = () => {
	return (
		<div className="w-full p-5 pb-8 xl:p-8 max-[1920px]:max-w-full mx-auto space-y-8">
			<SiteSeoChecksHeader />
			<SuspendedContent />
			<SaveAuthToken />
		</div>
	);
};

export const LazyRoute = createLazyRoute( '/site-seo-analysis' )( {
	component: SiteSeoChecks,
} );

export default SiteSeoChecks;
