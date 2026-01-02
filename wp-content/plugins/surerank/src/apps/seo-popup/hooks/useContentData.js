import {
	useMemo,
	useCallback,
	useLayoutEffect,
	useRef,
} from '@wordpress/element';
import { subscribe, useSelect } from '@wordpress/data';
import { debounce, isEqual } from 'lodash';
import { STORE_NAME } from '@Store/constants';
import replacement from '@Functions/replacement';
import { flat } from '@Functions/variables';
import { getEditorData } from '@SeoPopup/modal';

/**
 * Shared hook for getting resolved content data (title, description, content, URL)
 * Used by both page checks and keyword checks to avoid duplication
 * @param {Function} onContentChange - Callback function to handle content changes
 */
export const useContentData = ( onContentChange ) => {
	const {
		metaData,
		variables,
		postDynamicData,
		globalDefaults,
		settingsLoaded,
	} = useSelect( ( sel ) => {
		const selectors = sel( STORE_NAME );
		return {
			metaData: selectors?.getPostSeoMeta() || {},
			variables: selectors?.getVariables() || {},
			postDynamicData: selectors?.getPostDynamicData() || {},
			globalDefaults: selectors?.getGlobalDefaults() || {},
			settingsLoaded: selectors?.getMetaboxState(),
		};
	}, [] );

	const lastSnapshot = useRef( { postContent: '', permalink: '' } );
	const lastMeta = useRef( metaData );

	// Get variables array for replacement function
	const variablesArray = useMemo( () => flat( variables ), [ variables ] );

	// Resolve title and description with variable replacement
	const resolvedTitle = useMemo( () => {
		const title = metaData.page_title || globalDefaults.page_title || '';
		return replacement( title, variablesArray, postDynamicData );
	}, [
		metaData.page_title,
		globalDefaults.page_title,
		variablesArray,
		postDynamicData,
	] );

	const resolvedDescription = useMemo( () => {
		const description =
			metaData.page_description || globalDefaults.page_description || '';
		return replacement( description, variablesArray, postDynamicData );
	}, [
		metaData.page_description,
		globalDefaults.page_description,
		variablesArray,
		postDynamicData,
	] );

	const resolvedUrl = useMemo( () => {
		return (
			variables?.post?.permalink?.value ||
			variables?.term?.permalink?.value ||
			window.location.href ||
			''
		);
	}, [ variables ] );

	const getResolvedData = useCallback(
		( snapshot = null ) => {
			const currentSnapshot = snapshot || getEditorData();
			// Use snapshot permalink for real-time URL updates, fallback to variables
			const currentUrl = currentSnapshot?.permalink || resolvedUrl;
			return {
				title: resolvedTitle,
				description: resolvedDescription,
				url: currentUrl,
				content:
					currentSnapshot?.postContent ||
					postDynamicData?.content ||
					'',
				snapshot: currentSnapshot,
			};
		},
		[ resolvedTitle, resolvedDescription, resolvedUrl, postDynamicData ]
	);

	// Subscribe to content changes
	useLayoutEffect( () => {
		if ( ! settingsLoaded || ! onContentChange ) {
			return;
		}

		const updateContent = debounce( () => {
			const snapshot = getEditorData();
			if (
				! isEqual( lastSnapshot.current, snapshot ) ||
				! isEqual( lastMeta.current, metaData )
			) {
				lastSnapshot.current = snapshot;
				lastMeta.current = metaData;
				const resolvedData = getResolvedData( snapshot );
				onContentChange( resolvedData, metaData );
			}
		}, 300 );

		// Initial call
		const initialData = getResolvedData();
		onContentChange( initialData, metaData );
		lastSnapshot.current = initialData.snapshot;
		lastMeta.current = metaData;

		const unsubscribe = subscribe( updateContent );
		return () => {
			unsubscribe();
			updateContent.cancel();
		};
	}, [ metaData, getResolvedData, onContentChange, settingsLoaded ] );

	return {
		...getResolvedData(),
		settingsLoaded,
		metaData,
		variables,
		postDynamicData,
		globalDefaults,
	};
};
