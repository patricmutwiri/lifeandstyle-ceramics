// External Dependencies.
import React, { useEffect, useState, useReducer, useRef, useMemo } from 'react';
import { sortBy } from 'underscore';
import {
	SiteType,
	SiteOrder,
	SiteBusinessType,
	NoResultFound,
} from '@brainstormforce/starter-templates-components';
import { useNavigate } from 'react-router-dom';
import { decodeEntities } from '@wordpress/html-entities';
import { sprintf, __ } from '@wordpress/i18n';

// Internal Dependencies.
import { DefaultStep, PreviousStepLink, Button } from '../../components/index';
import './style.scss';
import { useStateValue } from '../../store/store';
import {
	isPro,
	whiteLabelEnabled,
	storeCurrentState,
	getAllSites,
	trackOnboardingStep,
} from '../../utils/functions';
import { setURLParmsValue } from '../../utils/url-params';
import SiteListSkeleton from './site-list-skeleton';
import GridSkeleton from './grid-skeleton';
import SiteGrid from './sites-grid/index';
import SiteSearch from './search-filter';
import FavoriteSites from './favorite-sites';
import RelatedSites from './related-sites';
import { ChevronUpIcon } from '@heroicons/react/24/outline';
import {
	SyncAndGetAllCategories,
	SyncAndGetAllCategoriesAndTags,
	isSyncUptoDate,
	fetchSitesPageCount,
	fetchPagedSites,
	fetchAllSites,
} from './header/sync-library/utils';
import SpectraBlocksVersionSelector from './spectra-blocks-version-selector';

export const useFilteredSites = () => {
	const [
		{ builder, siteType, spectraBlocksVersion, siteOrder, allSitesData },
	] = useStateValue();

	const filteredSites = useMemo( () => {
		// Step 1: fallback for all sites
		let allSites =
			allSitesData && Object.keys( allSitesData ).length
				? allSitesData
				: getAllSites();

		// Step 2: ensure object format (fallback for Chrome)
		if ( Array.isArray( allSites ) ) {
			allSites = allSites.reduce( ( acc, site ) => {
				if ( site.id ) {
					acc[ `id-${ site.id }` ] = site;
				}
				return acc;
			}, {} );
		}

		// Step 3: filter by builder
		let sites =
			builder && builder !== 'custom-templates'
				? Object.fromEntries(
						Object.entries( allSites ).filter(
							( [ , site ] ) =>
								site[ 'astra-site-page-builder' ] === builder
						)
				  )
				: { ...allSites };

		// Step 4: filter by site type
		if ( siteType ) {
			sites = Object.fromEntries(
				Object.entries( sites ).filter( ( [ , site ] ) => {
					const currentSiteType = site?.[ 'astra-sites-type' ] || '';
					return siteType === currentSiteType;
				} )
			);
		}

		// Step 5: filter by Spectra Blocks version (only for Gutenberg)
		if ( builder === 'gutenberg' && spectraBlocksVersion ) {
			const version = astraSitesVars?.spectraBlocks?.selectorEnabled
				? spectraBlocksVersion
				: astraSitesVars?.spectraBlocks?.version || 'v2';

			sites = Object.fromEntries(
				Object.entries( sites ).filter( ( [ , site ] ) => {
					let siteVersion = site?.[ 'spectra-ver' ] || 'v2';
					if ( ! siteVersion?.length ) {
						siteVersion = 'v2';
					}
					return siteVersion === version;
				} )
			);
		}

		// Step 6: Filter custom templates builder sites to only include those with custom templates.
		if ( builder === 'custom-templates' ) {
			sites = Object.fromEntries(
				Object.entries( sites ).filter(
					( [ , site ] ) => site?.[ 'astra-sites-custom-template' ]
				)
			);
		}

		// Step 7: sort if latest
		if ( siteOrder === 'latest' && Object.keys( sites ).length ) {
			sites = sortBy( Object.values( sites ), 'publish-date' ).reverse();
		}

		return sites;
	}, [ allSitesData, builder, siteType, spectraBlocksVersion, siteOrder ] );

	return filteredSites;
};

const SiteList = () => {
	const [ loadingSkeleton, setLoadingSkeleton ] = useState( true );
	const backToTopBtn = useRef( null );
	const allFilteredSites = useFilteredSites();
	const history = useNavigate();
	const [ siteData, setSiteData ] = useReducer(
		( state, newState ) => ( { ...state, ...newState } ),
		{
			gridSkeleton: false,
			sites: {},
		}
	);
	const [ storedState, dispatch ] = useStateValue();
	const {
		onMyFavorite,
		builder,
		siteSearchTerm,
		siteType,
		siteOrder,
		siteBusinessType,
		selectedMegaMenu,
		allSitesData,
		bgSyncInProgress,
		spectraBlocksVersion,
	} = storedState;

	useEffect( () => {
		const loadingTimeout = setTimeout( () => {
			setLoadingSkeleton( false );
		}, 800 );

		return () => clearTimeout( loadingTimeout );
	}, [] );

	useEffect( () => {
		dispatch( {
			type: 'set',
			templateResponse: null,
			selectedTemplateName: '',
			selectedTemplateType: '',
			shownRequirementOnce: false,
			templateId: 0,
		} );

		setSiteData( {
			sites: allFilteredSites,
		} );
	}, [ builder, siteType, spectraBlocksVersion, siteOrder, allSitesData ] );

	useEffect( () => {
		// Track template listing step when component mounts
		trackOnboardingStep( 'template-listing' );
	}, [] );

	storeCurrentState( storedState );

	const siteCount = Object.keys( siteData.sites ).length;

	const backStep = () => {
		dispatch( {
			type: 'set',
			siteSearchTerm: '', // Reset the search term on back
			siteBusinessType: '', // Reset the business type on back
			currentIndex: builder === 'fse' ? 0 : 1,
		} );
		const urlParam = setURLParmsValue( 's' );
		history( `?${ urlParam }` );
	};

	const handleClickBackToTop = () => {
		const contentWrapper = document.querySelector( '.step-content ' );
		if ( ! contentWrapper ) {
			return;
		}
		contentWrapper.scrollTo( {
			top: 0,
			behavior: 'smooth',
		} );
	};

	useEffect( () => {
		// use timeout function so the new content wrapper for the builder will be loaded
		const scrollTopTimeout = setTimeout( () => {
			handleClickBackToTop();
		}, 300 );

		// Cleanup function to clear the timeout if the component unmounts
		return () => clearTimeout( scrollTopTimeout );
	}, [ builder ] );

	const handleShowBackToTop = ( event ) => {
		const SCROLL_THRESHOLD = 250;
		const target = event.target;

		if ( ! backToTopBtn.current ) {
			return;
		}

		const btn = backToTopBtn.current;
		if (
			target.scrollTop > SCROLL_THRESHOLD &&
			btn.classList.contains( 'hidden' )
		) {
			btn.classList.remove( 'hidden' );
		} else if (
			target.scrollTop <= SCROLL_THRESHOLD &&
			! btn.classList.contains( 'hidden' )
		) {
			btn.classList.add( 'hidden' );
		}
	};

	// const fetchSitesAndCategories = async () => {
	// 	try {
	// 		const formData = new FormData();
	// 		formData.append( 'action', 'astra-sites-update-library' );
	// 		formData.append( '_ajax_nonce', astraSitesVars?._ajax_nonce );
	// 		const response = await fetch( ajaxurl, {
	// 			method: 'post',
	// 			body: formData,
	// 		} );
	// 		const jsonData = await response.json();
	// 		if (
	// 			jsonData.data === 'updated' &&
	// 			Object.keys( storedState.allSitesData ).length !== 0
	// 		) {
	// 			dispatch( {
	// 				type: 'set',
	// 				bgSyncInProgress: false,
	// 			} );
	// 			return;
	// 		}

	// 		const sites = await SyncImportAllSites();
	// 		const categories = await SyncAndGetAllCategories();
	// 		const categoriesAndTags = await SyncAndGetAllCategoriesAndTags();
	// 		console.log( typeof dispatch );
	// 		dispatch( {
	// 			type: 'set',
	// 			bgSyncInProgress: false,
	// 			allSitesData: sites,
	// 			categories,
	// 			categoriesAndTags,
	// 		} );

	// 		// await fetchSitesAndCategories();
	// 	} catch ( error ) {
	// 		console.error( error );
	// 	}
	// };

	const syncSites = async () => {
		// const newData = await SyncStart();
		const { totalPages, currentPage } = await fetchSitesPageCount();

		dispatch( {
			type: 'set',
			syncPageCount: totalPages,
			syncPageInProgress: currentPage,
		} );

		const sites = [];
		for ( let i = currentPage; i < totalPages; i++ ) {
			const sitesData = await fetchPagedSites( i + 1 );
			sitesData.forEach( ( siteItem ) => {
				sites.push( siteItem );
			} );
			dispatch( {
				type: 'set',
				syncPageInProgress: i + 1,
			} );
		}

		if ( currentPage <= 1 && sites.length > 0 ) {
			return sites;
		}

		// Fetch all sites if the current page is greater than 1 (means we were in the middle of fetching all the sites).
		return Object.values( await fetchAllSites() );
	};

	const fetchSitesAndCategories = async () => {
		try {
			dispatch( {
				type: 'set',
				syncPageInProgress: 0,
				syncPageCount: 0,
				bgSyncInProgress: !! astraSitesVars?.bgSyncInProgress,
			} );

			const syncUptoDate = await isSyncUptoDate();
			if ( syncUptoDate ) {
				dispatch( {
					type: 'set',
					bgSyncInProgress: false,
				} );
				return;
			}

			const sites = await syncSites();
			const categories = await SyncAndGetAllCategories();
			const categoriesAndTags = await SyncAndGetAllCategoriesAndTags();

			dispatch( {
				type: 'set',
				bgSyncInProgress: false,
				syncPageInProgress: 0,
				syncPageCount: 0,
				allSitesData: sites ?? null,
				categories: categories ?? null,
				categoriesAndTags: categoriesAndTags ?? null,
			} );

			astraSitesVars.bgSyncInProgress = false;
		} catch ( error ) {
			console.error( error );
		}
	};

	useEffect( () => {
		// if ( ! bgSyncInProgress ) {
		// 	return;
		// }

		fetchSitesAndCategories();
	}, [] );

	useEffect( () => {
		const contentWrapper = document.querySelector( '.step-content ' );
		if ( ! contentWrapper ) {
			return;
		}
		contentWrapper.addEventListener( 'scroll', handleShowBackToTop );
		return () => {
			contentWrapper.removeEventListener( 'scroll', handleShowBackToTop );
		};
	}, [] );

	const showSkeleton =
		siteData.gridSkeleton || bgSyncInProgress || loadingSkeleton;

	return (
		<DefaultStep
			content={
				<>
					<div
						className={ `site-list-screen-container ${
							loadingSkeleton ? 'site-loading' : 'site-loaded'
						}` }
					>
						<SiteListSkeleton />
						<div className="site-list-screen-wrap flex flex-col gap-6 mx-auto">
							<div>
								<h3 className="site-list-title">
									{ __(
										'What type of website are you building?',
										'astra-sites'
									) }
								</h3>
							</div>

							<div className="site-list-content">
								<SiteSearch setSiteData={ setSiteData } />

								<div className="st-templates-content">
									<div className="st-other-filters">
										<div className="st-category-filter">
											<SiteBusinessType
												parent={ siteBusinessType }
												menu={ selectedMegaMenu }
												onClick={ (
													event,
													option,
													childItem
												) => {
													dispatch( {
														type: 'set',
														siteBusinessType:
															option.ID,
														selectedMegaMenu:
															childItem.ID,
														siteSearchTerm:
															childItem.title,
														onMyFavorite: false,
														siteOrder: 'popular',
													} );
													const urlParam =
														setURLParmsValue(
															's',
															childItem.title
														);
													history( `?${ urlParam }` );
												} }
											/>
										</div>
										<div className="st-type-and-order-filters">
											<SpectraBlocksVersionSelector />

											<SiteType
												value={ siteType }
												onClick={ ( event, type ) => {
													dispatch( {
														type: 'set',
														siteType: type.id,
														onMyFavorite: false,
													} );
												} }
											/>

											<SiteOrder
												value={ siteOrder }
												onClick={ ( event, order ) => {
													dispatch( {
														type: 'set',
														siteOrder: order.id,
														onMyFavorite: false,
														siteBusinessType: '',
														selectedMegaMenu: '',
														siteSearchTerm: '',
													} );
													const urlParam =
														setURLParmsValue(
															's',
															''
														);
													history( `?${ urlParam }` );
												} }
											/>
										</div>
									</div>

									{ onMyFavorite ? (
										<FavoriteSites />
									) : (
										<>
											{ ( !! siteCount ||
												showSkeleton ) && (
												<>
													<div className="st-sites-grid">
														{ siteSearchTerm ? (
															<div className="st-sites-found-message">
																{ sprintf(
																	/* translators: %1$s: search term. */
																	__(
																		'Starter Templates for %1$s:',
																		'astra-sites'
																	),
																	decodeEntities(
																		siteSearchTerm
																	)
																) }
															</div>
														) : null }

														{ showSkeleton ? (
															<GridSkeleton />
														) : (
															<SiteGrid
																sites={
																	siteData.sites
																}
															/>
														) }
													</div>
												</>
											) }

											{ ! siteCount && ! showSkeleton && (
												<>
													<NoResultFound
														searchTerm={
															siteSearchTerm
														}
													/>
													<RelatedSites
														sites={
															allFilteredSites
														}
													/>
												</>
											) }
										</>
									) }
								</div>
							</div>
						</div>
						{ /* Back to the top */ }
						<div
							ref={ backToTopBtn }
							className="hidden absolute right-20 bottom-28 ml-auto"
						>
							<button
								type="button"
								className="absolute bottom-0 right-0 z-10 w-8 h-8 rounded-full bg-accent-st-secondary border-0 border-solid text-white flex items-center justify-center shadow-sm cursor-pointer"
								onClick={ handleClickBackToTop }
							>
								<ChevronUpIcon className="w-5 h-5" />
							</button>
						</div>
					</div>
				</>
			}
			actions={
				<div className="step-action-wrapper">
					<PreviousStepLink before onClick={ backStep }>
						{ __( 'Back', 'astra-sites' ) }
					</PreviousStepLink>

					{ ! isPro() && ! whiteLabelEnabled() && (
						<div className="cta-strip-right">
							<h5>
								{ __(
									'Get unlimited access to all Premium Starter Templates and more, at a single low cost!',
									'astra-sites'
								) }
							</h5>
							<Button
								className="st-access-btn"
								onClick={ () =>
									window.open(
										astraSitesVars?.cta_links[ builder ]
									)
								}
							>
								{ __( 'Get Premium Templates', 'astra-sites' ) }
							</Button>
						</div>
					) }
				</div>
			}
		/>
	);
};

export default SiteList;
