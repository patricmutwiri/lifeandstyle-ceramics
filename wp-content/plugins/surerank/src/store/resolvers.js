import { select } from '@wordpress/data';
import { STORE_NAME } from './constants';
import { addQueryArgs } from '@wordpress/url';
import {
	fetchFromAPI,
	setPageSeoChecksByIdAndType,
	setCurrentPostIgnoredList,
	setBatchPageSeoChecks,
} from './actions';
import { __ } from '@wordpress/i18n';
import { toast } from '@bsf/force-ui';

export function* getCurrentPostIgnoredList() {
	// Do NOT yield on select here; it's a synchronous read from the store.
	const state = yield select( STORE_NAME ).getState();

	// Try multiple sources for postId (state first, then window globals)
	const postId =
		state.pageSeoChecks?.postId ||
		state.variables?.post?.ID?.value ||
		state.variables?.term?.ID?.value ||
		window?.surerank_seo_popup?.post_id ||
		window?.surerank_seo_popup?.term_id ||
		window?.surerank_globals?.post_id ||
		window?.surerank_globals?.term_id;

	// Determine check type from multiple sources
	const checkType =
		state.pageSeoChecks?.checkType ||
		( window?.surerank_seo_popup?.is_taxonomy === '1'
			? 'taxonomy'
			: 'post' );

	// If we don't yet have a postId or checkType, defer resolution.
	if ( ! postId || ! checkType ) {
		return [];
	}

	// Short-circuit if we already have data for this post in state
	const existingData = state.pageSeoChecks?.ignoredList;
	if ( existingData?.length > 0 ) {
		return existingData;
	}

	try {
		const ignoredChecks = yield fetchFromAPI( {
			path: addQueryArgs( 'surerank/v1/checks/ignore-page-check', {
				post_id: postId,
				check_type: checkType,
			} ),
			method: 'GET',
		} );
		yield setCurrentPostIgnoredList( ignoredChecks?.checks || [] );
	} catch ( error ) {
		// Silently handle errors by setting an empty list
		yield setCurrentPostIgnoredList( [] );
	}
}

export function* getSeoBarChecks( postIds, postType, forceRefresh = null ) {
	if ( ! postIds || ! postType ) {
		return {};
	}

	const ids = Array.isArray( postIds ) ? postIds : [ postIds ];
	const state = select( STORE_NAME ).getState();

	let idsToFetch = ids;

	if ( ! forceRefresh ) {
		idsToFetch = ids.filter( ( id ) => {
			const existingData = state.pageSeoChecks?.[ id ]?.checks;
			return ! existingData;
		} );
	}

	if ( idsToFetch.length === 0 ) {
		return;
	}

	try {
		yield fetchSeoBarChecks( idsToFetch, postType, forceRefresh );
	} catch ( error ) {
		const errorMessage =
			error?.message || __( 'Error loading SEO checks', 'surerank' );
		// For single ID calls, set error. For batch, we might want to set error for all?
		// Keeping it simple for now as per previous logic.
		if ( ! Array.isArray( postIds ) ) {
			yield setPageSeoChecksByIdAndType(
				postIds,
				postType,
				[],
				errorMessage
			);
		}
	}
}

export function* fetchSeoBarChecks( ids, type, forceRefresh = false ) {
	if ( ! ids || ! ids.length ) {
		return;
	}

	const isTaxonomy = type === 'taxonomy';
	const endpoint = isTaxonomy
		? '/surerank/v1/checks/taxonomy'
		: '/surerank/v1/checks/page';
	const queryArg = isTaxonomy ? 'term_ids' : 'post_ids';
	const cacheBuster = forceRefresh ? `&_t=${ forceRefresh }` : '';

	try {
		const response = yield fetchFromAPI( {
			path:
				addQueryArgs( endpoint, {
					[ queryArg ]: ids,
				} ) + cacheBuster,
			method: 'GET',
		} );

		if ( response?.status !== 'success' || ! response?.data ) {
			throw response;
		}
		yield setBatchPageSeoChecks( response.data, type );
	} catch ( error ) {
		toast( {
			message:
				error?.message || __( 'Error loading SEO checks', 'surerank' ),
			type: 'error',
		} );
	}
}
