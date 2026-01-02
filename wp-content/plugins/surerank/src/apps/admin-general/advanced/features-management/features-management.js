import PageContentWrapper from '@AdminComponents/page-content-wrapper';
import { __ } from '@wordpress/i18n';
import withSuspense from '@AdminComponents/hoc/with-suspense';
import GeneratePageContent from '@Functions/page-content-generator';
import { applyFilters } from '@wordpress/hooks';

// Base feature toggles
const isWpSchemaProActive = surerank_globals?.wp_schema_pro_active || false;
const wpSchemaProActiveLabel = __(
	'WP Schema Pro is active. SureRank Schema has been disabled to avoid conflicts.',
	'surerank'
);
const wpSchemaProInactiveLabel = __(
	'Add structured data to improve how your site appears in search.',
	'surerank'
);

const getBaseToggles = () => [
	{
		type: 'switch',
		id: 'enable_page_level_seo',
		storeKey: 'enable_page_level_seo',
		dataType: 'boolean',
		label: __( 'Page Level Checks', 'surerank' ),
		description: __(
			'Check individual pages to improve on-page SEO performance.',
			'surerank'
		),
	},
	{
		type: 'switch',
		id: 'enable_google_console',
		storeKey: 'enable_google_console',
		shouldReload: true,
		dataType: 'boolean',
		label: __( 'Google Search Console', 'surerank' ),
		description: __(
			'Connect with Google to track clicks and search rankings.',
			'surerank'
		),
		pendingAction: [ false, true ],
	},
	{
		type: 'switch',
		id: 'enable_schemas',
		storeKey: 'enable_schemas',
		shouldReload: true,
		dataType: 'boolean',
		label: __( 'Schema', 'surerank' ),
		disabled: isWpSchemaProActive,
		description: isWpSchemaProActive
			? wpSchemaProActiveLabel
			: wpSchemaProInactiveLabel,
	},
	{
		type: 'switch',
		id: 'enable_migration',
		storeKey: 'enable_migration',
		shouldReload: true,
		dataType: 'boolean',
		label: __( 'Migration', 'surerank' ),
		description: __(
			'Helps you migrate your SEO plugin data into SureRank smoothly.',
			'surerank'
		),
	},
];

export const PAGE_CONTENT = [
	// This is the very first depth of the form. And it represents the section container of the form.
	{
		container: {
			id: 'disable-features-container',
			direction: 'column',
			gap: 6,
		},
		content: [
			{
				container: null,
				content: applyFilters(
					'surerank.feature-management.toggles',
					getBaseToggles()
				),
			},
		],
	},
];

const DisableFeatures = () => {
	return (
		<PageContentWrapper
			title={ __( 'Manage Features', 'surerank' ) }
			description={ __(
				'Select which SureRank features you want to use. Turning a feature off will stop it from running in the background and hide its settings from your dashboard.',
				'surerank'
			) }
		>
			<GeneratePageContent json={ PAGE_CONTENT } />
		</PageContentWrapper>
	);
};

export default withSuspense( DisableFeatures );
