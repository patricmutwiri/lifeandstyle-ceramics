import { __ } from '@wordpress/i18n';
import { Label } from '@bsf/force-ui';
import { default as SocialPreviewComponent } from '@GlobalComponents/social-preview';
import replacement from '@Functions/replacement';
import { decodeHtmlEntities, truncateText } from '@Functions/utils';
import { useSuspenseSelect } from '@wordpress/data';
import { STORE_NAME } from '@AdminStore/constants';
import AnimateChangeInHeight from '@GlobalComponents/animate-change-in-height';

// type definition
/**
 * @typedef {Object} SocialPreviewProps
 * @property {'facebook' | 'twitter'} platform The platform to use for the preview
 */

/**
 * @param {SocialPreviewProps} props
 * @return {JSX.Element} The Social Preview component
 */
const SocialPreview = ( { platform = 'facebook' } ) => {
	const {
		fallback_image: fallbackImage,
		page_title: title,
		page_description: description,
		site,
		home_page_featured_image,
		home_page_facebook_image_url,
		home_page_twitter_image_url,
		twitter_card_type: twitterPreviewType,
	} = useSuspenseSelect( ( select ) => {
		const selectStore = select( STORE_NAME );
		return {
			...selectStore.getSiteSettings(),
			...selectStore.getMetaSettings(),
		};
	}, [] );
	const { site_url: siteURL } = site;
	const imageURL =
		platform === 'facebook'
			? home_page_facebook_image_url
			: home_page_twitter_image_url;

	const titleContent = truncateText( replacement( title, site ) || '', 60 );
	const descriptionContent = truncateText(
		replacement( description, site ) || '',
		117
	);

	const homePageImage = home_page_featured_image
		? home_page_featured_image
		: '';
	const socialImage =
		imageURL !== false && imageURL !== '' ? imageURL : fallbackImage;
	const finalFallbackImage =
		homePageImage && homePageImage !== '' ? homePageImage : socialImage;
	const decoded_title_content = decodeHtmlEntities( titleContent );
	const decoded_description_content =
		decodeHtmlEntities( descriptionContent );
	return (
		<AnimateChangeInHeight className="[&>div]:space-y-2 w-full">
			<Label tag="span" size="sm" className="space-x-0.5">
				{ platform === 'facebook'
					? __( 'Facebook Preview', 'surerank' )
					: __( 'X Preview', 'surerank' ) }
			</Label>
			<SocialPreviewComponent
				title={ decoded_title_content }
				description={ decoded_description_content }
				imageURL={ finalFallbackImage }
				siteURL={ siteURL.replace( /(^\w+:|^)\/\//, '' ) }
				hideRemoveButton={ true }
				type={ platform }
				twitterLargePreview={
					platform === 'twitter' &&
					twitterPreviewType === 'summary_large_image'
				}
			/>
		</AnimateChangeInHeight>
	);
};

export default SocialPreview;
