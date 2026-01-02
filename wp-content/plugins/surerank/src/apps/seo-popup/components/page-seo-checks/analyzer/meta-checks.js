import { __, sprintf } from '@wordpress/i18n';
import { createCheck } from './content-checks';
import { TITLE_LENGTH, DESCRIPTION_LENGTH } from '@Global/constants';

export const checkSeoTitle = ( resolvedTitle ) => {
	if ( ! resolvedTitle ) {
		return createCheck( {
			id: 'search_engine_title',
			title: __(
				'Search engine title is missing on the page.',
				'surerank'
			),
			status: 'error',
			type: 'page',
		} );
	}

	if ( resolvedTitle.length > 60 ) {
		return createCheck( {
			id: 'search_engine_title',
			title: sprintf(
				/* translators: %1$d: current length */
				__(
					'Search engine title exceeds %1$d characters.',
					'surerank'
				),
				TITLE_LENGTH
			),
			status: 'warning',
			type: 'page',
		} );
	}

	return createCheck( {
		id: 'search_engine_title',
		title: sprintf(
			/* translators: %1$d: current length */
			__(
				'Search engine title is present and under %1$d characters.',
				'surerank'
			),
			TITLE_LENGTH
		),
		status: 'success',
		type: 'page',
	} );
};

export const checkSeoDescription = ( resolvedDescription ) => {
	if ( ! resolvedDescription ) {
		return createCheck( {
			id: 'search_engine_description',
			title: __(
				'Search engine description is missing on the page.',
				'surerank'
			),
			status: 'warning',
			type: 'page',
		} );
	}

	if ( resolvedDescription.length > 160 ) {
		return createCheck( {
			id: 'search_engine_description',
			title: sprintf(
				/* translators: %1$d: current length */
				__(
					'Search engine description exceeds %1$d characters.',
					'surerank'
				),
				DESCRIPTION_LENGTH
			),
			status: 'warning',
			type: 'page',
		} );
	}

	return createCheck( {
		id: 'search_engine_description',
		title: sprintf(
			/* translators: %1$d: current length */
			__(
				'Search engine description is present and under %1$d characters.',
				'surerank'
			),
			DESCRIPTION_LENGTH
		),
		status: 'success',
		type: 'page',
	} );
};

export const checkOpenGraphTags = () => {
	const openGraphTags = window?.surerank_globals?.open_graph_tags ?? false;

	if ( ! openGraphTags ) {
		// if not disbaled.
		return createCheck( {
			id: 'open_graph_tags',
			title: __( 'Open Graph tags are present on the page.', 'surerank' ),
			status: 'success',
			type: 'page',
		} );
	}

	return createCheck( {
		id: 'open_graph_tags',
		title: __( 'Open Graph tags are not present on the page.', 'surerank' ),
		status: 'suggestion',
		type: 'page',
	} );
};
