import { cleanContent } from '@Functions/utils';

/**
 * Returns true if the viewport matches the given query, or false otherwise.
 *
 * @param {Object} state Viewport state object.
 *
 *
 * @return {boolean} Whether viewport matches query.
 */
export function getPostSeoMeta( state ) {
	return state.postSeoMeta;
}

export function getVariables( state ) {
	return state.variables;
}

export function getMetaboxState( state ) {
	return state.metaboxInitialized;
}

export function getState( state ) {
	return state;
}

export function getModalState( state ) {
	return state.modalEnabled;
}

export function getPostDynamicData( state ) {
	const dynamicData = state.postDynamicData;

	// Verify if title is empty. then set the title from the variables object.
	if ( state?.variables?.post?.title?.value && ! dynamicData?.title ) {
		dynamicData.title = state.variables.post.title.value;
	}

	// Verify if content is empty. then set the content from the variables object.
	if ( state?.variables?.post?.content?.value && ! dynamicData?.content ) {
		dynamicData.content = cleanContent(
			state.variables.post.content.value
		);
	}

	// Verify if excerpt is empty. then set the excerpt from the variables object.
	if ( state?.variables?.post?.excerpt?.value && ! dynamicData?.excerpt ) {
		dynamicData.excerpt = state.variables.post.excerpt.value;
	}

	return dynamicData;
}

export const getGlobalDefaults = ( state ) => state.globalDefaults;

export const getAppSettings = ( state ) => state.appSettings;

export const getUnsavedMetaSettings = ( state ) => state.unsavedPostSeoMeta;

export const getRefreshCalled = ( state ) => state.pageSeoChecks.refreshCalled;

export const getCurrentPostIgnoredList = ( state ) => {
	const postId =
		state.pageSeoChecks?.postId ?? state?.variables?.post?.ID?.value;
	if ( ! postId ) {
		return [];
	}
	const ignoredList = state.pageSeoChecks.ignoredList || [];
	return ignoredList;
};

export const getPageSeoChecks = ( state ) => {
	return state.pageSeoChecks;
};

export const getSeoBarChecks = ( state, postId, checkType ) => {
	if ( ! postId || ! checkType ) {
		return {};
	}

	const checks = state.pageSeoChecks[ postId ]?.checks || {};
	const sequence = state.pageSeoChecks[ postId ]?.sequence || [];
	const error = state.pageSeoChecks[ postId ]?.error || null;

	return {
		checks,
		sequence,
		error,
	};
};
