import { __ } from '@wordpress/i18n';
import PageContentWrapper from '@AdminComponents/page-content-wrapper';
import withSuspense from '@AdminComponents/hoc/with-suspense';
import GeneratePageContent from '@Functions/page-content-generator';
import SocialPreview from '@AdminComponents/social-preview';
import { createLazyRoute } from '@tanstack/react-router';

export const PAGE_CONTENT = [
	{
		container: {
			direction: 'column',
			gap: 6,
		},
		content: [
			{
				id: 'facebook_preview',
				type: 'custom',
				component: <SocialPreview platform="facebook" />,
			},
			{
				id: 'facebook_page_url',
				type: 'text',
				label: __( 'Site Facebook Page', 'surerank' ),
				placeholder: 'https://www.facebook.com/YourPage',
				storeKey: 'facebook_page_url',
				tooltip: __(
					'Provide the full URL of your official Facebook Page. This URL will be used in your site’s structured data under the Schema → Organization → SameAs field to associate your brand with its verified social profile.',
					'surerank'
				),
			},
			{
				id: 'facebook_author_fallback',
				type: 'text',
				label: __( 'Author Facebook Page', 'surerank' ),
				placeholder: 'https://www.facebook.com/YourPersonalProfile',
				storeKey: 'facebook_author_fallback',
				tooltip: __(
					'Enter the URL of your default Facebook author page. It will be used in structured data when a post doesn’t have a specific author profile set - useful for multi-author sites.',
					'surerank'
				),
			},
			// {
			// 	id: 'facebook_admin_id',
			// 	type: 'text',
			// 	label: __( 'Facebook Admin ID', 'surerank' ),
			// 	storeKey: 'facebook_admin_id',
			// 	description: __(
			// 		'The ID (or comma-separated list for properties that can accept multiple IDs) of an app, person using the app, or Page Graph API object.',
			// 		'surerank'
			// 	),
			// },
			// {
			// 	id: 'facebook_app_id',
			// 	type: 'text',
			// 	label: __( 'Facebook App ID', 'surerank' ),
			// 	storeKey: 'facebook_app_id',
			// 	description: __(
			// 		"The Facebook app ID of the site's app. In order to use Facebook Insights you must add the app ID to your page. Insights lets you view analytics for traffic to your site from Facebook. Find the app ID in your App Dashboard.",
			// 		'surerank'
			// 	),
			// },
		],
	},
];

const SocialFacebook = () => {
	return (
		<PageContentWrapper
			title={ __( 'Facebook', 'surerank' ) }
			description={ __(
				'Control how your pages appear when shared on Facebook. Set attribution and preview settings here.',
				'surerank'
			) }
		>
			<GeneratePageContent json={ PAGE_CONTENT } />
		</PageContentWrapper>
	);
};

export const LazyRoute = createLazyRoute( '/social/facebook' )( {
	component: withSuspense( SocialFacebook ),
} );

export default withSuspense( SocialFacebook );
