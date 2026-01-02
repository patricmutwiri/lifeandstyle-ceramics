import { useLayoutEffect, useRef, useCallback } from '@wordpress/element';
import { useDispatch, useSuspenseSelect, subscribe } from '@wordpress/data';
import { debounce, isEqual } from 'lodash';
import { STORE_NAME } from '@Store/constants';
import replacement from '@Functions/replacement';
import { flat } from '@Functions/variables';
import { getEditorData } from '@SeoPopup/modal';
import {
	checkKeywordInTitle,
	checkKeywordInDescription,
	checkKeywordInUrl,
	checkKeywordInContent,
} from '../analyzer/keyword-analyzer';

export const useKeywordChecks = () => {
	const { setPageSeoCheck } = useDispatch( STORE_NAME );
	const {
		metaData,
		variables,
		postDynamicData,
		globalDefaults,
		settingsLoaded,
		pageSeoChecks,
		focusKeyword,
		ignoredList,
		initializing,
	} = useSuspenseSelect( ( select ) => {
		const selectors = select( STORE_NAME );
		return {
			metaData: selectors?.getPostSeoMeta(),
			variables: selectors?.getVariables(),
			postDynamicData: selectors?.getPostDynamicData(),
			globalDefaults: selectors?.getGlobalDefaults(),
			settingsLoaded: selectors?.getMetaboxState(),
			pageSeoChecks: selectors?.getPageSeoChecks() || {},
			focusKeyword: selectors?.getPostSeoMeta()?.focus_keyword,
			ignoredList: selectors.getCurrentPostIgnoredList(),
			initializing: selectors.getPageSeoChecks().initializing,
		};
	}, [] );

	const lastSnapshot = useRef( { postContent: '', permalink: '' } );
	const lastMeta = useRef( metaData );
	const lastKeyword = useRef( focusKeyword );
	const lastIgnoredList = useRef( ignoredList );

	const runKeywordChecks = useCallback(
		( snapshot, seoMeta, keyword ) => {
			if ( ! keyword ) {
				// If no keyword, clear keyword checks but keep page checks
				setPageSeoCheck( 'keyword', [] );
				return;
			}

			// variables array.
			const variablesArray = flat( variables );

			// title.
			const resolvedTitle = replacement(
				seoMeta.page_title || globalDefaults.page_title || '',
				variablesArray,
				postDynamicData
			);

			// description.
			const resolvedDescription = replacement(
				seoMeta.page_description ||
					globalDefaults.page_description ||
					'',
				variablesArray,
				postDynamicData
			);

			// permalink.
			const resolvedUrl =
				snapshot?.permalink ||
				variables?.post?.permalink?.value ||
				variables?.term?.permalink?.value ||
				window.location.href ||
				'';

			// content.
			const resolvedContent =
				snapshot?.postContent || postDynamicData?.content || '';

			const keywordChecks = [];
			keywordChecks.push( checkKeywordInTitle( resolvedTitle, keyword ) );
			keywordChecks.push(
				checkKeywordInDescription( resolvedDescription, keyword )
			);
			keywordChecks.push( checkKeywordInUrl( resolvedUrl, keyword ) );
			keywordChecks.push(
				checkKeywordInContent( resolvedContent, keyword )
			);

			// Filter out falsy values and dispatch keyword checks
			const validKeywordChecks = keywordChecks.filter( Boolean );
			setPageSeoCheck( 'keyword', validKeywordChecks );
		},
		[ variables, postDynamicData, globalDefaults, setPageSeoCheck ]
	);

	// initial check.
	useLayoutEffect( () => {
		if ( ! settingsLoaded ) {
			return;
		}
		const snapshot = getEditorData();

		// // Check if any dependencies have actually changed
		const hasDataChanged =
			! isEqual( lastMeta.current, metaData ) ||
			! isEqual( lastSnapshot.current, snapshot ) ||
			lastKeyword.current !== focusKeyword ||
			! isEqual( lastIgnoredList.current, ignoredList );

		if ( hasDataChanged ) {
			runKeywordChecks( snapshot, metaData, focusKeyword );
			lastSnapshot.current = snapshot;
			lastMeta.current = metaData;
			lastKeyword.current = focusKeyword;
			lastIgnoredList.current = ignoredList;
		}
	}, [
		settingsLoaded,
		focusKeyword,
		metaData,
		variables,
		globalDefaults,
		postDynamicData,
		ignoredList,
		initializing,
	] );

	// subscribe to content changes.
	useLayoutEffect( () => {
		if ( ! settingsLoaded ) {
			return;
		}

		const updateChecks = debounce( () => {
			const snapshot = getEditorData();
			if (
				! isEqual( lastSnapshot.current, snapshot ) ||
				! isEqual( lastMeta.current, metaData ) ||
				lastKeyword.current !== focusKeyword ||
				! isEqual( lastIgnoredList.current, ignoredList )
			) {
				lastSnapshot.current = snapshot;
				lastMeta.current = metaData;
				lastKeyword.current = focusKeyword;
				lastIgnoredList.current = ignoredList;
				runKeywordChecks( snapshot, metaData, focusKeyword );
			}
		}, 300 );

		const unsubscribe = subscribe( updateChecks );
		return () => {
			unsubscribe();
			updateChecks.cancel();
		};
	}, [
		settingsLoaded,
		metaData,
		focusKeyword,
		variables,
		globalDefaults,
		postDynamicData,
		ignoredList,
		runKeywordChecks,
		initializing,
	] );

	// Get filtered keyword checks from Redux state
	// Filter checks by type directly from categorizedChecks
	const categorizedChecks = pageSeoChecks?.keywordChecks ?? {
		badChecks: [],
		fairChecks: [],
		passedChecks: [],
		suggestionChecks: [],
		ignoredChecks: [],
	};

	// Calculate hasBadOrFairChecks flag for backward compatibility
	const hasBadOrFairChecks =
		categorizedChecks?.badChecks?.length > 0 ||
		categorizedChecks?.fairChecks?.length > 0 ||
		categorizedChecks?.suggestionChecks?.length > 0;

	return {
		...categorizedChecks,
		hasBadOrFairChecks,
	};
};
