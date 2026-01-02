import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import { TrendingUp, BarChart2 } from 'lucide-react';
import { Analyze, MetaSettings } from '@SeoPopup/components';
import { isSeoAnalysisDisabled } from '@SeoPopup/components/page-seo-checks/analyzer/utils/page-builder';

export const TABS = applyFilters( 'surerank-pro.seo-popup-tabs', {
	optimize: {
		title: __( 'Optimize', 'surerank' ),
		component: MetaSettings,
		label: __( 'Optimize', 'surerank' ),
		icon: <TrendingUp />,
		slug: 'optimize',
	},
	// Conditionally add the Analyze tab
	...( isSeoAnalysisDisabled()
		? {}
		: {
				analyze: {
					title: __( 'Analyze', 'surerank' ),
					component: Analyze,
					label: __( 'Analyze', 'surerank' ),
					slug: 'analyze',
					icon: <BarChart2 />,
					className: 'relative surerank-page-checks-indicator',
				},
		  } ),
} );
