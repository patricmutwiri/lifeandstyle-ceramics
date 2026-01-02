import { Container, Title, Button } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';
import { Suspense } from '@wordpress/element';
import SiteSeoChecksTable from './site-seo-checks-table';
import SiteSeoChecksTableSkeleton, {
	SiteSeoChecksInnerTableSkeleton,
} from './site-seo-checks-table-skeleton';
import SiteSeoChecksDrawer from './site-seo-checks-drawer';
import { RefreshCw } from 'lucide-react';
import { useRunSeoChecks } from './use-run-seo-checks';
import { cn } from '@Functions/utils';
import SaveAuthToken from '@/global/components/save-auth-token';

/**
 * Component for showing site SEO checks summary on dashboard
 *
 * @param {Object}  props             Component props
 * @param {number}  props.limit       Number of items to show (default: 5)
 * @param {boolean} props.showViewAll Whether to show view all button (default: true)
 * @return {JSX.Element} Site SEO checks summary component
 */
const SiteSeoChecksSummary = ( { limit = 5, showViewAll = true } ) => {
	const { isLoading, handleRunChecksAgain } = useRunSeoChecks();

	// Title section component
	const SiteSeoChecksTitle = () => {
		return (
			<Container align="center" justify="between" className="p-2">
				<SaveAuthToken />
				<Title
					tag="h4"
					title={ __( 'Site SEO Audit', 'surerank' ) }
					size="md"
				/>
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
					disabled={ isLoading } // Disable button while loading
				>
					{ __( 'Run Checks', 'surerank' ) }
				</Button>
			</Container>
		);
	};

	// Component that uses suspense data
	const SiteSeoChecksContent = () => {
		if ( isLoading ) {
			return (
				<>
					<SiteSeoChecksTitle />
					<SiteSeoChecksInnerTableSkeleton />
				</>
			);
		}

		return (
			<>
				<SiteSeoChecksTitle />
				<SiteSeoChecksTable
					limit={ limit }
					showViewAll={ showViewAll }
				/>
			</>
		);
	};

	return (
		<div className="w-full space-y-2 rounded-xl bg-background-primary border-0.5 border-solid border-border-subtle p-4 shadow-sm">
			<Suspense fallback={ <SiteSeoChecksTableSkeleton /> }>
				<SiteSeoChecksContent />
				<SiteSeoChecksDrawer />
			</Suspense>
		</div>
	);
};

export default SiteSeoChecksSummary;
