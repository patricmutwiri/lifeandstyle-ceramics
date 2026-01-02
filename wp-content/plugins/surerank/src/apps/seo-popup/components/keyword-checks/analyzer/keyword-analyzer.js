import { __, sprintf } from '@wordpress/i18n';
import { createCheck } from '@SeoPopup/components/page-seo-checks/analyzer/content-checks';

// Helper function to check if keyword exists in text (case-insensitive)
const keywordExistsInText = ( text, keyword ) => {
	if ( ! text || ! keyword ) {
		return false;
	}

	return text.toLowerCase().includes( keyword.toLowerCase() );
};

export const checkKeywordInTitle = ( title, keyword ) => {
	if ( ! keyword ) {
		return createCheck( {
			id: 'keyword_in_title',
			title: __( 'No focus keyword set to analyze title.', 'surerank' ),
			status: 'suggestion',
			type: 'keyword',
		} );
	}

	if ( ! title ) {
		return createCheck( {
			id: 'keyword_in_title',
			title: __( 'No SEO title found to analyze.', 'surerank' ),
			status: 'warning',
			type: 'keyword',
		} );
	}

	if ( keywordExistsInText( title, keyword ) ) {
		return createCheck( {
			id: 'keyword_in_title',
			title: sprintf(
				/* translators: %s: focus keyword */
				__( 'Focus keyword "%s" found in SEO title.', 'surerank' ),
				keyword
			),
			status: 'success',
			type: 'keyword',
		} );
	}

	return createCheck( {
		id: 'keyword_in_title',
		title: sprintf(
			/* translators: %s: focus keyword */
			__( 'Focus keyword "%s" not found in SEO title.', 'surerank' ),
			keyword
		),
		status: 'warning',
		type: 'keyword',
	} );
};

export const checkKeywordInDescription = ( description, keyword ) => {
	if ( ! keyword ) {
		return createCheck( {
			id: 'keyword_in_description',
			title: __(
				'No focus keyword set to analyze meta description.',
				'surerank'
			),
			status: 'suggestion',
			type: 'keyword',
		} );
	}

	if ( ! description ) {
		return createCheck( {
			id: 'keyword_in_description',
			title: __( 'No meta description found to analyze.', 'surerank' ),
			status: 'warning',
			type: 'keyword',
		} );
	}

	if ( keywordExistsInText( description, keyword ) ) {
		return createCheck( {
			id: 'keyword_in_description',
			title: sprintf(
				/* translators: %s: focus keyword */
				__(
					'Focus keyword "%s" found in meta description.',
					'surerank'
				),
				keyword
			),
			status: 'success',
			type: 'keyword',
		} );
	}

	return createCheck( {
		id: 'keyword_in_description',
		title: sprintf(
			/* translators: %s: focus keyword */
			__(
				'Focus keyword "%s" not found in meta description.',
				'surerank'
			),
			keyword
		),
		status: 'warning',
		type: 'keyword',
	} );
};

export const checkKeywordInUrl = ( url, keyword ) => {
	if ( ! keyword ) {
		return createCheck( {
			id: 'keyword_in_url',
			title: __( 'No focus keyword set to analyze URL.', 'surerank' ),
			status: 'suggestion',
			type: 'keyword',
		} );
	}

	if ( ! url ) {
		return createCheck( {
			id: 'keyword_in_url',
			title: __( 'No URL found to analyze.', 'surerank' ),
			status: 'warning',
			type: 'keyword',
		} );
	}

	// Convert keyword to URL-friendly format (lowercase, spaces to hyphens)
	const urlFriendlyKeyword = keyword.toLowerCase().replace( /\s+/g, '-' );
	const urlLower = url.toLowerCase();

	if (
		urlLower.includes( urlFriendlyKeyword ) ||
		keywordExistsInText( url, keyword )
	) {
		return createCheck( {
			id: 'keyword_in_url',
			title: sprintf(
				/* translators: %s: focus keyword */
				__( 'Focus keyword "%s" found in URL.', 'surerank' ),
				keyword
			),
			status: 'success',
			type: 'keyword',
		} );
	}

	return createCheck( {
		id: 'keyword_in_url',
		title: sprintf(
			/* translators: %s: focus keyword */
			__( 'Focus keyword "%s" not found in URL.', 'surerank' ),
			keyword
		),
		status: 'warning',
		type: 'keyword',
	} );
};

export const checkKeywordInContent = ( content, keyword ) => {
	if ( ! keyword ) {
		return createCheck( {
			id: 'keyword_in_content',
			title: __( 'No focus keyword set to analyze content.', 'surerank' ),
			status: 'suggestion',
			type: 'keyword',
		} );
	}

	if ( ! content ) {
		return createCheck( {
			id: 'keyword_in_content',
			title: __( 'No content found to analyze.', 'surerank' ),
			status: 'warning',
			type: 'keyword',
		} );
	}

	// Clean content of HTML tags for better analysis
	const cleanContent = content
		.replace( /<[^>]*>/g, ' ' )
		.replace( /\s+/g, ' ' )
		.trim();

	if ( keywordExistsInText( cleanContent, keyword ) ) {
		return createCheck( {
			id: 'keyword_in_content',
			title: sprintf(
				/* translators: %s: focus keyword */
				__( 'Focus keyword "%s" found in content.', 'surerank' ),
				keyword
			),
			status: 'success',
			type: 'keyword',
		} );
	}

	return createCheck( {
		id: 'keyword_in_content',
		title: sprintf(
			/* translators: %s: focus keyword */
			__( 'Focus keyword "%s" not found in content.', 'surerank' ),
			keyword
		),
		status: 'warning',
		type: 'keyword',
	} );
};
