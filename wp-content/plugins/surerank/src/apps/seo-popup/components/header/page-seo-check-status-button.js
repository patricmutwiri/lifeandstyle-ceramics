import { usePageChecks } from '../../hooks';
import { Button, Skeleton, Text, Badge } from '@bsf/force-ui';
import { ChartNoAxesColumnIncreasingIcon } from 'lucide-react';
import { cn, getSeoCheckLabel } from '@/functions/utils';
import { _n, sprintf, __ } from '@wordpress/i18n';
import { isPageBuilderActive } from '../page-seo-checks/analyzer/utils/page-builder';
import { SeoPopupTooltip } from '@/apps/admin-components/tooltip';
import { useDispatch, useSelect } from '@wordpress/data';
import { STORE_NAME } from '@/store/constants';
import { Suspense } from '@wordpress/element';

const MAP_BADGE_VARIANTS = {
	error: 'red',
	warning: 'yellow',
	success: 'green',
};

const PageSeoCheckButton = ( { className, showAsBadge = false } ) => {
	const { updateAppSettings } = useDispatch( STORE_NAME );
	const appSettings = useSelect( ( select ) =>
		select( STORE_NAME ).getAppSettings()
	);
	const { status, initializing, counts } = usePageChecks();
	const { setPageSeoCheck } = useDispatch( STORE_NAME );
	const isPageBuilderEditor = isPageBuilderActive();

	const handleNavigateToChecks = () => {
		if ( appSettings.currentScreen === 'checks' ) {
			return;
		}
		updateAppSettings( {
			currentScreen: 'checks',
			previousScreen: appSettings?.currentScreen,
		} );
	};

	const handleOpenChecks = () => {
		const isTaxonomy = window?.surerank_seo_popup?.is_taxonomy === '1';
		setPageSeoCheck( 'checkType', isTaxonomy ? 'taxonomy' : 'post' );
		if ( isTaxonomy && window?.surerank_seo_popup?.term_id ) {
			setPageSeoCheck( 'postId', window?.surerank_seo_popup?.term_id );
		}
		handleNavigateToChecks();
	};

	if ( ! isPageBuilderEditor && initializing ) {
		return <Skeleton className="size-10 shrink-0" />;
	}

	let content = (
		<Button
			variant="ghost"
			size="sm"
			onClick={ handleOpenChecks }
			icon={ <ChartNoAxesColumnIncreasingIcon className="shrink-0" /> }
			className={ cn(
				'p-2 border-0.5 border-solid focus:[box-shadow:none] focus:outline-none [&>svg]:size-6 size-10',
				status === 'error' &&
					'bg-badge-background-red hover:bg-badge-background-red border-badge-border-red text-badge-color-red',
				status === 'warning' &&
					'bg-badge-background-yellow hover:bg-badge-background-yellow border-badge-border-yellow text-badge-color-yellow',
				status === 'success' &&
					'bg-badge-background-green hover:bg-badge-background-green border-badge-border-green text-badge-color-green',
				className
			) }
		/>
	);

	if ( showAsBadge ) {
		content = (
			<button
				className="p-0 size-fit outline-none focus:outline-none bg-transparent hover:bg-transparent border-0 cursor-pointer"
				onClick={ handleOpenChecks }
			>
				<Badge
					icon={
						<ChartNoAxesColumnIncreasingIcon className="shrink-0" />
					}
					variant={ MAP_BADGE_VARIANTS[ status ] }
					label={ getSeoCheckLabel(
						status,
						counts.error || counts.warning || counts.success
					) }
				/>
			</button>
		);
	}

	return (
		<SeoPopupTooltip
			content={
				<Text size={ 12 } weight={ 600 } color="inverse">
					{ counts.errorAndWarnings > 0
						? sprintf(
								// translators: %1$s is the number of issues detected, %2$s is the word "Issue".
								__(
									'%1$s %2$s need attention. Click to see',
									'surerank'
								),
								counts.errorAndWarnings,
								_n(
									'issue',
									'issues',
									counts.errorAndWarnings,
									'surerank'
								)
						  )
						: __(
								'All SEO checks passed. Click to see',
								'surerank'
						  ) }
				</Text>
			}
			offset={ {
				crossAxis: showAsBadge ? 0 : -100,
				mainAxis: 8,
			} }
			arrow
		>
			{ content }
		</SeoPopupTooltip>
	);
};

const PageSeoCheckStatusButton = ( { showAsBadge = false } ) => {
	const renderSkeleton = showAsBadge ? (
		<Skeleton className="w-40 h-6 shrink-0" />
	) : (
		<Skeleton className="size-10 shrink-0" />
	);
	const classNames = cn( showAsBadge && '[&>svg]:size-6 size-8' );

	return (
		<Suspense fallback={ renderSkeleton }>
			<PageSeoCheckButton
				className={ classNames }
				showAsBadge={ showAsBadge }
			/>
		</Suspense>
	);
};

export default PageSeoCheckStatusButton;
