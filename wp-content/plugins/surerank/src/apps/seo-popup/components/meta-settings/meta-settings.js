import {
	Fragment,
	memo,
	useState,
	useEffect,
	useMemo,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { motion } from 'framer-motion';
import {
	withSelect,
	withDispatch,
	useDispatch,
	useSelect,
} from '@wordpress/data';
import { compose } from '@wordpress/compose';
import { applyFilters } from '@wordpress/hooks';
import { STORE_NAME } from '@Store/constants';
import {
	GutenbergData,
	ClassicEditorData,
} from '@/apps/seo-popup/modal/dynamic-data-provider';
import { MetaTab } from '@SeoPopup/components/meta-settings/tab-content/index';
import Analyze from '@SeoPopup/components/analyze/index';
import LoadingSkeleton from '../loading-skeleton';
import { Tabs } from '@bsf/force-ui';
import { usePageCheckStatus } from '@SeoPopup/hooks';
import { cn, getStatusIndicatorClasses } from '@/functions/utils';
import { ENABLE_PAGE_LEVEL_SEO } from '@/global/constants';
import { isSeoAnalysisDisabled } from '@SeoPopup/components/page-seo-checks/analyzer/utils/page-builder';

const MetaSettings = ( props ) => {
	const { postMetaData, updatePostMetaData, initialized, globalDefaults } =
		props;

	const { status, initializing } = usePageCheckStatus();

	const statusIndicatorIcon = useMemo( () => {
		if ( initializing || ! status || ! ENABLE_PAGE_LEVEL_SEO ) {
			return null;
		}

		const statusClasses = getStatusIndicatorClasses( status );
		return (
			<div
				className={ cn(
					'rounded-full ml-1 w-[7px] h-[7px]',
					statusClasses
				) }
			/>
		);
	}, [ status, initializing ] );

	const baseTabs = [
		{
			label: __( 'Optimize', 'surerank' ),
			slug: 'optimize',
		},
		...( isSeoAnalysisDisabled()
			? []
			: [
					{
						label: __( 'Analyze', 'surerank' ),
						slug: 'analyze',
						icon: statusIndicatorIcon,
					},
			  ] ),
	];

	const tabs = applyFilters( 'surerank.meta-settings.tabs', baseTabs, {
		statusIndicatorIcon,
	} );

	const { updateAppSettings } = useDispatch( STORE_NAME );
	const { currentMetaTab } = useSelect( ( select ) =>
		select( STORE_NAME ).getAppSettings()
	);

	const [ activeTab, setActiveTab ] = useState(
		currentMetaTab || 'optimize'
	);

	useEffect( () => {
		if ( currentMetaTab && currentMetaTab !== activeTab ) {
			setActiveTab( currentMetaTab );
		}
	}, [ currentMetaTab ] );

	const handleChangeTab = ( { event, value: { slug } } ) => {
		event.preventDefault();
		event.stopPropagation();

		setActiveTab( slug );
		updateAppSettings( { currentMetaTab: slug } );
	};

	let tabContent = null;
	switch ( activeTab ) {
		case 'optimize':
			tabContent = (
				<MetaTab
					postMetaData={ postMetaData }
					updatePostMetaData={ updatePostMetaData }
					globalDefaults={ globalDefaults }
				/>
			);
			break;
		case 'analyze':
			tabContent = <Analyze />;
			break;
		default:
			tabContent = null;
	}

	tabContent = applyFilters(
		'surerank.meta-settings.tab-content',
		tabContent,
		{
			activeTab,
			postMetaData,
			updatePostMetaData,
			globalDefaults,
		}
	);

	if ( ! initialized ) {
		tabContent = <LoadingSkeleton tab={ activeTab } />;
	}

	return (
		<Fragment>
			<div>
				<Tabs.Group
					className="w-full"
					iconPosition="right"
					size="md"
					variant="rounded"
					activeItem={ activeTab }
					onChange={ handleChangeTab }
				>
					{ tabs.map( ( { label, slug, icon } ) => (
						<Tabs.Tab
							key={ label }
							slug={ slug }
							text={ label }
							icon={ icon ?? null }
							className="text-sm"
						/>
					) ) }
				</Tabs.Group>
			</div>

			{ /* Tab content */ }
			<motion.div
				key={ activeTab }
				className="flex flex-col -mt-1 flex-1 overflow-y-auto"
				initial={ { opacity: 0 } }
				animate={ { opacity: 1 } }
				exit={ { opacity: 0 } }
				transition={ { duration: 0.2 } }
			>
				{ tabContent }
			</motion.div>
		</Fragment>
	);
};

let hocComponent = ( Component ) => Component;
if ( 'block' === surerank_seo_popup?.editor_type ) {
	hocComponent = GutenbergData;
} else if ( 'classic' === surerank_seo_popup?.editor_type ) {
	hocComponent = ClassicEditorData;
}

export default compose(
	withSelect( ( select ) => {
		const selectStore = select( STORE_NAME );

		return {
			postMetaData: selectStore.getPostSeoMeta(),
			initialized: selectStore.getMetaboxState(),
			globalDefaults: selectStore.getGlobalDefaults(),
		};
	} ),
	withDispatch( ( dispatch ) => {
		const dispatchStore = dispatch( STORE_NAME );

		return {
			updatePostMetaData: ( value ) =>
				dispatchStore.updatePostMetaData( value ),
		};
	} ),
	hocComponent
)( memo( MetaSettings ) );
