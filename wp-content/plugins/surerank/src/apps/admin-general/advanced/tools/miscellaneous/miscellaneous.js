import PageContentWrapper from '@AdminComponents/page-content-wrapper';
import { __ } from '@wordpress/i18n';
import withSuspense from '@AdminComponents/hoc/with-suspense';
import GeneratePageContent from '@Functions/page-content-generator';
import { createLazyRoute } from '@tanstack/react-router';

export const PAGE_CONTENT = [
	{
		container: {
			id: 'miscellaneous-container',
			direction: 'column',
			gap: 6,
		},
		content: [
			{
				container: null,
				content: [
					{
						type: 'switch',
						id: 'surerank_analytics_optin',
						storeKey: 'surerank_analytics_optin',
						dataType: 'boolean',
						label: __( 'Contribute to SureRank', 'surerank' ),
						description: (
							<span>
								<span>
									{ __(
										'Collect non-sensitive information from your website, such as the PHP version and features used, to help us fix bugs faster, make smarter decisions, and build features that actually matter to you. ',
										'surerank'
									) }
								</span>
								<a
									href="https://surerank.com/share-usage-data/"
									target="_blank"
									rel="noopener noreferrer"
									className="no-underline hover:no-underline ring-0"
								>
									{ __( 'Learn More', 'surerank' ) }
								</a>
							</span>
						),
					},
				],
			},
		],
	},
];

const Miscellaneous = () => {
	return (
		<PageContentWrapper
			title={ __( 'Miscellaneous', 'surerank' ) }
			description={ __(
				'Additional settings and preferences for SureRank.',
				'surerank'
			) }
		>
			<GeneratePageContent json={ PAGE_CONTENT } />
		</PageContentWrapper>
	);
};

export const LazyRoute = createLazyRoute( '/tools/miscellaneous' )( {
	component: withSuspense( Miscellaneous ),
} );

export default withSuspense( Miscellaneous );
