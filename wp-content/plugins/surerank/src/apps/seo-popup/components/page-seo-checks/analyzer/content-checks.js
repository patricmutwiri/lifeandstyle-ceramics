import { __, sprintf } from '@wordpress/i18n';
import { URL_LENGTH } from '@Global/constants';

export const createCheck = ( {
	id,
	title,
	status,
	description,
	data,
	showImages,
	type = 'page',
} ) => ( {
	id,
	title,
	status,
	description,
	data,
	showImages,
	type,
} );

const getFeaturedImage = async () => {
	if ( window?.tinymce?.editors?.length ) {
		return document.querySelector( '#set-post-thumbnail>img' );
	}

	// Check if block editor
	const editor = window?.wp?.data?.select( 'core/editor' );
	if ( editor && typeof editor.getEditedPostContent === 'function' ) {
		const featuredImageId =
			editor.getEditedPostAttribute( 'featured_media' );
		if ( !! featuredImageId ) {
			const image = await new Promise( ( resolve ) => {
				const imageFunc = wp.media.attachment( featuredImageId );
				if ( typeof imageFunc.fetch === 'function' ) {
					imageFunc
						.fetch()
						.then( ( media ) => resolve( media ) )
						.catch( () => {
							resolve( {} );
						} );
				}
			} );
			return {
				alt: image?.alt ?? '',
				src: image?.url ?? '',
			};
		}
	}
};

export const checkImageAlt = async ( doc ) => {
	let imgs = [];

	const featuredImage = await getFeaturedImage();
	if ( !! featuredImage ) {
		imgs.push( featuredImage );
	}

	imgs = [
		...imgs,
		...Array.from( doc.querySelectorAll( 'img' ) )?.filter(
			( img ) => img?.src
		),
	];

	if ( imgs.length === 0 ) {
		return;
	}

	const missingAltImages = imgs
		.filter( Boolean )
		.filter( ( img ) => ! img?.alt?.trim() );
	const missingCount = missingAltImages.length;
	if ( missingCount === 0 ) {
		return createCheck( {
			id: 'image_alt_text',
			title: __(
				'All images on this page have alt text attributes.',
				'surerank'
			),
			status: 'success',
			type: 'page',
		} );
	}

	const image_seo = window?.surerank_seo_popup?.image_seo;
	const baseMessage = __(
		'One or more images are missing alt text attributes.',
		'surerank'
	);

	let title = `${ baseMessage } ${ __(
		'You can add them manually or turn on auto-set image title and alt in the settings.',
		'surerank'
	) }`;
	if ( image_seo ) {
		title = `${ baseMessage } ${ __(
			"But don't worry, we will add them automatically for you.",
			'surerank'
		) }`;
	}

	return createCheck( {
		id: 'image_alt_text',
		title,
		status: 'suggestion',
		data: Array.from(
			new Set( missingAltImages.map( ( img ) => img.src ) )
		),
		showImages: true,
		type: 'page',
	} );
};

export const checkMediaPresence = async ( doc ) => {
	const hasFeaturedImage = !! ( await getFeaturedImage() );

	const hasImage =
		doc.querySelectorAll(
			'img, figure img, figure picture, figure.is-type-photo'
		).length > 0 || hasFeaturedImage;
	const hasVideo =
		doc.querySelectorAll( 'video, figure.is-type-video' ).length > 0;

	if ( ! hasImage && ! hasVideo ) {
		return createCheck( {
			id: 'media_present',
			title: __( 'No images or videos found on this page.', 'surerank' ),
			status: 'warning',
			type: 'page',
		} );
	}

	if ( ! hasImage && hasVideo ) {
		return createCheck( {
			id: 'media_present',
			title: __(
				'This page includes video(s) but no images.',
				'surerank'
			),
			status: 'warning',
			type: 'page',
		} );
	}

	return createCheck( {
		id: 'media_present',
		title: __(
			'This page includes images or videos to enhance content.',
			'surerank'
		),
		status: 'success',
		type: 'page',
	} );
};

export const checkLinkPresence = ( doc ) => {
	const links = Array.from( doc.querySelectorAll( 'a[href]' ) );

	if ( links.length === 0 ) {
		return createCheck( {
			id: 'links_present',
			title: __( 'No links found on this page.', 'surerank' ),
			status: 'warning',
			type: 'page',
		} );
	}

	if ( links.length > 0 ) {
		return createCheck( {
			id: 'links_present',
			title: __( 'Links are present on this page.', 'surerank' ),
			status: 'success',
			type: 'page',
		} );
	}
};

export const checkUrlLength = ( permalink ) => {
	const len = permalink.length;
	const title = sprintf(
		/* translators: %s is the URL length. */
		__(
			'Page URL is longer than %s characters and may affect SEO and readability.',
			'surerank'
		),
		URL_LENGTH
	);

	if ( len > URL_LENGTH ) {
		return createCheck( {
			id: 'url_length',
			title,
			status: 'warning',
			type: 'page',
		} );
	}

	return createCheck( {
		id: 'url_length',
		title: __( 'Page URL is short and SEO-friendly.', 'surerank' ),
		status: 'success',
		type: 'page',
	} );
};

export const checkSubheadings = ( doc ) => {
	const subheadings = Array.from(
		doc.querySelectorAll( 'h2, h3, h4, h5, h6' )
	);

	if ( subheadings.length ) {
		return createCheck( {
			id: 'h2_subheadings',
			title: __( 'Page contains at least one subheading.', 'surerank' ),
			status: 'success',
			type: 'page',
		} );
	}

	return createCheck( {
		id: 'h2_subheadings',
		title: __(
			'Page does not contain at least one subheading.',
			'surerank'
		),
		status: 'warning',
		type: 'page',
	} );
};
