import {
	useRef,
	useMemo,
	useCallback,
	useLayoutEffect,
	useTransition,
} from '@wordpress/element';
import { subscribe, useDispatch, useSuspenseSelect } from '@wordpress/data';
import { debounce, isEqual } from 'lodash';
import {
	checkImageAlt,
	checkMediaPresence,
	checkLinkPresence,
	checkUrlLength,
	checkSeoTitle,
	checkSeoDescription,
	checkOpenGraphTags,
	checkSubheadings,
	checkBrokenLinks,
	checkCanonicalUrl,
} from '../components/page-seo-checks/analyzer';
import { parseContent } from '../components/page-seo-checks/analyzer/utils';
import { STORE_NAME } from '@Store/constants';
import replacement from '@Functions/replacement';
import { flat } from '@Functions/variables';
import { getEditorData } from '@SeoPopup/modal';
import { isPageBuilderActive } from '../components/page-seo-checks/analyzer/utils/page-builder';
import { calculateCheckStatus } from '@SeoPopup/utils/calculate-check-status';
import { ENABLE_PAGE_LEVEL_SEO } from '@/global/constants';

const usePageChecks = () => {
	const { setPageSeoCheck } = useDispatch( STORE_NAME );
	const {
		metaData,
		variables,
		postDynamicData,
		globalDefaults,
		pageSeoChecks,
		settingsLoaded,
	} = useSuspenseSelect( ( sel ) => {
		const selectors = sel( STORE_NAME );

		// Calling this to trigger the resolver to fetch the ignored list.
		selectors?.getCurrentPostIgnoredList();

		return {
			metaData: selectors?.getPostSeoMeta() || {},
			variables: selectors?.getVariables() || {},
			postDynamicData: selectors?.getPostDynamicData() || {},
			globalDefaults: selectors?.getGlobalDefaults() || {},
			pageSeoChecks: selectors?.getPageSeoChecks() || {},
			settingsLoaded: selectors?.getMetaboxState(),
		};
	}, [] );

	const { categorizedChecks = {}, initializing } = pageSeoChecks;
	const [ , startTransition ] = useTransition();

	const lastSnapshot = useRef( { postContent: '', permalink: '' } );
	const lastMeta = useRef( metaData );

	// Check if Elementor editor is active
	const isPageBuilderEditor = isPageBuilderActive();
	// Resolve title and description with variable replacement
	const variablesArray = useMemo( () => flat( variables ), [ variables ] );

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

	const canonicalUrl = useMemo( () => {
		const canonicalUrlData =
			metaData.canonical_url || globalDefaults.canonical_url || '';
		return replacement( canonicalUrlData, variablesArray, postDynamicData );
	}, [
		metaData.canonical_url,
		globalDefaults.canonical_url,
		variablesArray,
		postDynamicData,
	] );

	const runChecks = useCallback(
		async ( snapshot, seoMeta, title, description, canonical ) => {
			if ( surerank_seo_popup?.is_taxonomy === '1' ) {
				return setPageSeoCheck( 'page', [
					checkUrlLength( snapshot.permalink ),
					checkSeoTitle( title ),
					checkSeoDescription( description ),
					checkCanonicalUrl( canonical ),
				] );
			}
			const doc = parseContent( snapshot.postContent );
			const immediateChecks = await Promise.all( [
				await checkImageAlt( doc ),
				await checkMediaPresence( doc ),
				checkLinkPresence( doc ),
				checkUrlLength( snapshot.permalink ),
				checkSeoTitle( title ),
				checkSeoDescription( description ),
				checkOpenGraphTags(),
				checkSubheadings( doc ),
				checkCanonicalUrl( canonical ),
			] );
			setPageSeoCheck( 'page', immediateChecks );

			startTransition( async () => {
				const brokenLinks = await checkBrokenLinks(
					doc,
					variables?.post?.ID?.value || 0,
					undefined,
					setPageSeoCheck
				);

				// Filter out falsy values and add broken links check
				const validChecks = [ ...immediateChecks ];
				if ( brokenLinks ) {
					validChecks.push( brokenLinks );
				}
				setPageSeoCheck( 'page', validChecks );
			} );
		},
		[]
	);
	const start = async () => {
		const snapshot = getEditorData();
		await runChecks(
			snapshot,
			metaData,
			resolvedTitle,
			resolvedDescription,
			canonicalUrl
		);
		lastSnapshot.current = snapshot;
		if ( ! initializing ) {
			return;
		}
		setPageSeoCheck( 'initializing', false );
	};

	useLayoutEffect( () => {
		if (
			isPageBuilderEditor ||
			! settingsLoaded ||
			! initializing ||
			! ENABLE_PAGE_LEVEL_SEO
		) {
			return;
		}

		// Initialize checks on page load
		start();
	}, [
		isPageBuilderEditor,
		initializing,
		metaData,
		resolvedTitle,
		resolvedDescription,
		canonicalUrl,
		settingsLoaded,
	] );

	useLayoutEffect( () => {
		if (
			isPageBuilderEditor ||
			! settingsLoaded ||
			initializing ||
			! ENABLE_PAGE_LEVEL_SEO
		) {
			return;
		}
		const updateChecks = debounce( async () => {
			const snapshot = getEditorData();
			if (
				! isEqual( lastSnapshot.current, snapshot ) ||
				! isEqual( lastMeta.current, metaData )
			) {
				lastSnapshot.current = snapshot;
				lastMeta.current = metaData;
				await runChecks(
					snapshot,
					metaData,
					resolvedTitle,
					resolvedDescription,
					canonicalUrl
				);
			}
		}, 300 );
		const unsubscribe = subscribe( updateChecks );
		return () => {
			unsubscribe();
			updateChecks.cancel();
		};
	}, [
		isPageBuilderEditor,
		metaData,
		resolvedTitle,
		resolvedDescription,
		runChecks,
		canonicalUrl,
		initializing,
		settingsLoaded,
	] );

	const { status, counts } = useMemo( () => {
		return calculateCheckStatus( categorizedChecks );
	}, [ categorizedChecks ] );

	return {
		status,
		initializing,
		counts,
	};
};

export default usePageChecks;
