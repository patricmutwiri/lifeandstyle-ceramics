import PageContentWrapper from '@AdminComponents/page-content-wrapper';
import { __ } from '@wordpress/i18n';
import ImageSection from './image-section';
import withSuspense from '@AdminComponents/hoc/with-suspense';
import GeneratePageContent from '@Functions/page-content-generator';
import { createLazyRoute } from '@tanstack/react-router';

export const PAGE_CONTENT = [
	// {
	// 	container: {
	// 		direction: 'column',
	// 		gap: 6,
	// 	},
	// 	content: [
	// 		{
	// 			id: 'meta-tags-label',
	// 			type: 'title',
	// 			label: __( 'Meta Tags', 'surerank' ),
	// 		},
	// 		{
	// 			id: 'open_graph_tags',
	// 			type: 'switch',
	// 			label: __( 'Open Graph Meta Tags', 'surerank' ),
	// 			storeKey: 'open_graph_tags',
	// 			description: __(
	// 				'Platforms like Facebook, X, Pinterest, and other social networks utilize these meta tags.',
	// 				'surerank'
	// 			),
	// 		},
	// 		{
	// 			id: 'facebook_meta_tags',
	// 			type: 'switch',
	// 			label: __( 'Facebook Meta Tags', 'surerank' ),
	// 			storeKey: 'facebook_meta_tags',
	// 			description: __(
	// 				'Generate a range of meta tags specifically aimed at Facebook.',
	// 				'surerank'
	// 			),
	// 		},
	// 		{
	// 			id: 'twitter_meta_tags',
	// 			type: 'switch',
	// 			label: __( 'X (Twitter) Meta Tags', 'surerank' ),
	// 			storeKey: 'twitter_meta_tags',
	// 			description: __(
	// 				'X , Discord, LinkedIn, and several other networks utilize these meta tags.',
	// 				'surerank'
	// 			),
	// 		},
	// 		{
	// 			id: 'oembeded_scripts',
	// 			type: 'switch',
	// 			label: __( 'oEmbed Scripts', 'surerank' ),
	// 			storeKey: 'oembeded_scripts',
	// 			description: __(
	// 				'Platforms like WordPress, Discord, Drupal, Squarespace, and many others can utilize these scripts.',
	// 				'surerank'
	// 			),
	// 		},
	// 	],
	// },
	{
		container: {
			direction: 'column',
			gap: 6,
		},
		content: [
			{
				id: 'fallback_image',
				type: 'custom',
				component: <ImageSection />,
			},
		],
	},
	// {
	// 	container: {
	// 		direction: 'column',
	// 		gap: 6,
	// 	},
	// 	content: [
	// 		{
	// 			id: 'oembed-label',
	// 			type: 'title',
	// 			label: __( 'oEmbed', 'surerank' ),
	// 		},
	// 		{
	// 			id: 'oembeded_og_title',
	// 			type: 'switch',
	// 			label: __( 'Open Graph Title', 'surerank' ),
	// 			storeKey: 'oembeded_og_title',
	// 			description: __(
	// 				'Select this option to replace page titles with Open Graph titles in embeds.',
	// 				'surerank'
	// 			),
	// 		},
	// 		{
	// 			id: 'oembeded_social_images',
	// 			type: 'switch',
	// 			label: __( 'Social Images', 'surerank' ),
	// 			storeKey: 'oembeded_social_images',
	// 			description: __(
	// 				'If you prefer to replace the post\'s featured image with the social image in LinkedIn embeds, please check this option.',
	// 				'surerank'
	// 			),
	// 		},
	// 		{
	// 			id: 'oembeded_remove_author_name',
	// 			type: 'switch',
	// 			label: __( 'Remove Author Name', 'surerank' ),
	// 			storeKey: 'oembeded_remove_author_name',
	// 			description: __(
	// 				'If you prefer not to display the page author\'s name above the sharing embed in Discord, please check this option.',
	// 				'surerank'
	// 			),
	// 		},
	// 	],
	// },
];

const General = () => {
	return (
		<PageContentWrapper
			title={ __( 'Default Social Image', 'surerank' ) }
			description={ __(
				"This is your site's default social sharing image (Open Graph). It shows up when your pages are shared on social platforms - unless a custom image is set on that page.",
				'surerank'
			) }
		>
			<GeneratePageContent json={ PAGE_CONTENT } />
		</PageContentWrapper>
	);
};

export const LazyRoute = createLazyRoute( '/social' )( {
	component: withSuspense( General ),
} );

export default withSuspense( General );
