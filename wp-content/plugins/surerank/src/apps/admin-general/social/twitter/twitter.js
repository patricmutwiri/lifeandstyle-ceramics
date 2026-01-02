import { __ } from '@wordpress/i18n';
import PageContentWrapper from '@AdminComponents/page-content-wrapper';
import SocialPreview from '@AdminComponents/social-preview';
import withSuspense from '@AdminComponents/hoc/with-suspense';
import GeneratePageContent from '@Functions/page-content-generator';
import { createLazyRoute } from '@tanstack/react-router';

const twitterCardType = [
	{
		value: 'summary_large_image',
		label: __( 'Summary Large Image', 'surerank' ),
	},
	{
		value: 'summary',
		label: __( 'Summary', 'surerank' ),
	},
];

export const PAGE_CONTENT = [
	{
		container: {
			direction: 'column',
			gap: 6,
		},
		content: [
			{
				id: 'twitter_preview',
				type: 'custom',
				component: <SocialPreview platform="twitter" />,
			},
			{
				id: 'twitter_card_type',
				type: 'radio',
				label: __( 'X Card Type', 'surerank' ),
				options: twitterCardType,
				storeKey: 'twitter_card_type',
				tooltip: __(
					'Choose how your content will appear when shared on X. This setting determines the card type—either a summary or a summary with a large image—used in the post preview.',
					'surerank'
				),
				showBorder: true,
				showBorderOnActive: true,
				optionWrapperClassName: 'pl-3.5 py-2.5 pr-10',
			},
			{
				id: 'twitter_profile_username',
				type: 'text',
				label: __( "Site's X Profile", 'surerank' ),
				storeKey: 'twitter_profile_username',
				placeholder: 'https://www.x.com/your-site-username',
				tooltip: __(
					'Provide the full URL of your official X (Twitter) profile. This URL will be used in your site’s structured data under the Schema → Organization → SameAs field to associate your brand with its verified social profile.',
					'surerank'
				),
			},
			{
				id: 'twitter_profile_fallback',
				type: 'text',
				label: __( 'Author’s X Profile', 'surerank' ),
				storeKey: 'twitter_profile_fallback',
				placeholder: 'https://www.x.com/your-personal-username',
				tooltip: __(
					'Enter the URL of your fallback Twitter author page. This will be used if an individual author URL is not provided.',
					'surerank'
				),
			},
		],
	},
];

const SocialTwitter = () => {
	return (
		<PageContentWrapper
			title={ __( 'X', 'surerank' ) }
			description={ __(
				'Control how your pages appear when shared on X. Set attribution and preview settings here.',
				'surerank'
			) }
		>
			<GeneratePageContent json={ PAGE_CONTENT } />
		</PageContentWrapper>
	);
};

export const LazyRoute = createLazyRoute( '/social/x' )( {
	component: withSuspense( SocialTwitter ),
} );

export default withSuspense( SocialTwitter );
