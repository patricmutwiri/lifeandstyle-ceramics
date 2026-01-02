import PageContentWrapper from '@AdminComponents/page-content-wrapper';
import { __, sprintf } from '@wordpress/i18n';
import withSuspense from '@AdminComponents/hoc/with-suspense';
import GeneratePageContent from '@Functions/page-content-generator';
import { UpgradeNotice } from '@/global/components/nudges';
import { Text } from '@bsf/force-ui';
import { applyFilters } from '@wordpress/hooks';
import { renderToString } from '@wordpress/element';

const ImageSeoHelpText = () => {
	return (
		<div className="flex items-center justify-between p-3 gap-2 relative ring-1 rounded-lg ring-alert-border-warning bg-alert-background-warning shadow-none">
			<div className="w-full gap-2">
				<Text size={ 14 } weight={ 400 } color="primary" className="mb-2">
					{ __(
						"Without AI, meta tags will be added to images using the file name and post title. However, this data is often repetitive or too generic and doesn't describe the image accurately. Search engines may even treat such tags as spammy.",
						'surerank'
					) }
				</Text>
				<Text size={ 14 } weight={ 400 } color="primary">
					{ __(
						'For the best results, we strongly recommend using AI to generate authentic, context-aware image descriptions that truly optimize your image SEO.',
						'surerank'
					) }
				</Text>
			</div>
		</div>
	);
};

const OptimizeImageSeoWithAiHelpText = () => {
	return (
		<div
			dangerouslySetInnerHTML={ {
				__html: sprintf(
					/* translators: %s: robots.txt */
					__(
						'%1$s to use AI image SEO',
						'surerank'
					),
					renderToString(
						<Text
							as="a"
							color="link"
							href="https://surerank.com/pro/?utm_medium=surerank_image_seo"
							target="_blank"
							rel="noopener noreferrer"
							className="no-underline focus:ring-0 hover:no-underline"
						>
							{ __( 'Upgrade to pro', 'surerank' ) }
						</Text>
					)
				),
			} }
		/>
	);
};

const imageSeoContent = () => [
	{
		id: 'redirect_attachment_pages_to_post_parent',
		type: 'switch',
		storeKey: 'redirect_attachment_pages_to_post_parent',
		label: __( 'Redirect Attachment Pages to Parent Post', 'surerank' ),
		description: __(
			'Enable this to redirect all attachment pages to their parent post or to the homepage. This avoids low-content pages and improves SEO.',
			'surerank'
		),
		searchKeywords: [ 'Redirect Attachment Pages' ],
	},
	{
		id: 'image_seo_seperator',
		type: 'custom',
		storeKey: 'image_seo_helptext',
		dataType: 'string',
		component: <>
			<span className="w-full block">
				<hr className="border-border-subtle border-b border-t-0 border-x-0 my-0 w-full" />
			</span>
		</>,
	},
	{
		id: 'generate_alt_with_ai',
		type: 'switch',
		disabled: true,
		storeKey: 'generate_alt_with_ai',
		label: __(
			'Optimize Image SEO with AI',
			'surerank'
		),
		description: <OptimizeImageSeoWithAiHelpText />,
	},
	{
		id: 'auto_set_image_alt',
		type: 'switch',
		storeKey: 'auto_set_image_alt',
		label: __(
			'Optimize Image SEO without AI',
			'surerank'
		),
		description: __(
			'Auto-add basic alt text for images that don’t have one, using the file name or post title.',
			'surerank'
		),
		searchKeywords: [
			'Optimize Image',
			'Image Alt Text',
			'Add Missing Alt Text',
			'Image Alt Text Attribute',
		],
	},
];

const getImageSeoContent = () => {
	const content = imageSeoContent();
	const filteredContent = applyFilters(
		'surerank-pro.image-seo-settings',
		[
			{
				container: {
					direction: 'column',
					gap: 6,
				},
				content,
			},
		],
		content
	);

	// Add helptext after filter so it appears last
	if ( filteredContent[ 0 ] && filteredContent[ 0 ].content ) {
		filteredContent[ 0 ].content.push( {
			type: 'custom',
			id: 'image_seo_helptext',
			storeKey: 'image_seo_helptext',
			dataType: 'string',
			component: <ImageSeoHelpText />,
			wrapperClassName: ( formValues ) => {
				return formValues.auto_set_image_alt ? '' : 'hidden';
			},
		} );
	}

	return filteredContent;
};

export const PAGE_CONTENT = [
	{
		container: {
			direction: 'column',
			gap: 6,
		},
		content: imageSeoContent(),
	},
];

const ImageSeo = () => {
	return (
		<PageContentWrapper
			title={ __( 'Image SEO', 'surerank' ) }
			description={ __(
				'Generate SEO-friendly image alt text automatically with SureRank AI to improve accessibility and boost your image search visibility.',
				'surerank'
			) }
		>
			<GeneratePageContent json={ getImageSeoContent() } />
			<UpgradeNotice
				title={ __( 'Optimize Image SEO with AI', 'surerank' ) }
				description={ __(
					'Generate authentic, context-aware image descriptions that truly optimize your image SEO.',
					'surerank'
				) }
				utmMedium="surerank_image_seo"
			/>
		</PageContentWrapper>
	);
};

export default withSuspense( ImageSeo );
