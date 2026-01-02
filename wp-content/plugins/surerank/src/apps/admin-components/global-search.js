import { __ } from '@wordpress/i18n';
import { SearchBox } from '@bsf/force-ui';
import {
	useNavigate,
	useLocation,
	useRouterState,
} from '@tanstack/react-router';
import { useState, useMemo, useEffect } from '@wordpress/element';
import React from 'react';
import { File } from 'lucide-react';
import { scrollToElement as scrollToElementFn } from '@Functions/utils';

const flattenNavLinks = ( navLinks ) => {
	const flattened = [];

	const extractPageContent = ( content ) => {
		if ( ! content ) {
			return [];
		}

		const processContentItem = ( item, parentLabel = '' ) => {
			// Skip non-searchable items explicitly marked as not searchable
			if ( item?.searchable === false ) {
				return null;
			}

			// For custom components: collect searchKeywords but don't display as separate items
			// If it's a custom component without label and no keywords, skip it
			if (
				item.type === 'custom' &&
				! item.label &&
				! item.searchKeywords?.length
			) {
				return null;
			}

			// Create a content item with all relevant information
			const contentItem = {
				label: item.label || parentLabel || '',
				description: item.description || '',
				type: 'content',
				id: item.id || '',
				storeKey: item.storeKey || '',
				contentType: item.type,
				dataType: item.dataType || '',
				searchKeywords: item.searchKeywords || [],
				useParentLabel: ! item.label && !! parentLabel,
			};

			// Add options if they exist (for radio, select, etc.)
			if ( item.options ) {
				contentItem.options = item.options
					.map( ( opt ) => opt.label )
					.join( ', ' );
			}

			// Add tooltip if it exists
			if ( item.tooltip ) {
				contentItem.tooltip = item.tooltip;
			}

			return contentItem;
		};

		const processContentArray = ( contentArray, parentLabel = '' ) => {
			return contentArray.reduce( ( acc, item ) => {
				// If item has its own content array, process it recursively
				// Pass down the label if available for nested items without labels
				if ( item.content ) {
					acc.push(
						...processContentArray(
							item.content,
							item.label || parentLabel
						)
					);
				}

				// Process the current item
				const processedItem = processContentItem( item, parentLabel );
				if ( processedItem ) {
					acc.push( processedItem );
				}

				return acc;
			}, [] );
		};

		return content.reduce( ( acc, section ) => {
			if ( section.content ) {
				acc.push( ...processContentArray( section.content ) );
			}
			return acc;
		}, [] );
	};

	navLinks.forEach( ( section ) => {
		section.links.forEach( ( link ) => {
			// Add main link
			const linkItem = {
				path: link.path,
				label: link.label,
				icon: link.icon,
				section: section.section,
				type: 'link',
			};

			// Add page content if exists
			if ( link.pageContent ) {
				linkItem.content = extractPageContent( link.pageContent );
			}

			flattened.push( linkItem );

			// Add submenu links if they exist
			if ( link.submenu ) {
				link.submenu.forEach( ( subLink ) => {
					const subLinkItem = {
						path: subLink.path,
						label: `${ link.label } > ${ subLink.label }`,
						icon: link.icon,
						section: section.section,
						type: 'link',
					};

					// Add page content for submenu items if exists
					if ( subLink.pageContent ) {
						subLinkItem.content = extractPageContent(
							subLink.pageContent
						);
					}

					flattened.push( subLinkItem );
				} );
			}
		} );
	} );

	return flattened;
};

const GlobalSearch = ( { navLinks = [] } ) => {
	const navigate = useNavigate();
	const location = useLocation();
	const {
		location: {
			state: { scrollToElement = undefined },
		},
	} = useRouterState();
	const [ open, setOpen ] = useState( false );
	const [ searchResults, setSearchResults ] = useState( [] );

	const flattenedLinks = useMemo(
		() => flattenNavLinks( navLinks ),
		[ navLinks ]
	);

	const handleChange = ( value ) => {
		const searchTerm = value.toLowerCase();

		if ( ! searchTerm ) {
			setSearchResults( [] );
			return;
		}

		// Helper function to extract text from ReactNode or string
		const extractTextFromReactNode = ( node ) => {
			if ( typeof node === 'string' ) {
				return node;
			}
			if ( typeof node === 'number' || typeof node === 'boolean' ) {
				return String( node );
			}
			if ( node === null || node === undefined ) {
				return '';
			}
			if ( Array.isArray( node ) ) {
				return node.map( extractTextFromReactNode ).join( '' );
			}
			if ( React.isValidElement( node ) ) {
				return extractTextFromReactNode( node.props.children );
			}
			return '';
		};

		const results = flattenedLinks.reduce( ( acc, item ) => {
			// Track if we've already added this item to prevent duplicates
			let itemAdded = false;

			// Check if any content items match
			if ( item.content ) {
				// First gather all matching content
				const matchingContent = item.content.filter(
					( contentItem ) => {
						// Match by standard fields
						const matchesStandardFields =
							extractTextFromReactNode( contentItem.label )
								.toLowerCase()
								.includes( searchTerm ) ||
							extractTextFromReactNode( contentItem.description )
								.toLowerCase()
								.includes( searchTerm ) ||
							extractTextFromReactNode( contentItem.tooltip )
								?.toLowerCase()
								.includes( searchTerm ) ||
							extractTextFromReactNode( contentItem.options )
								?.toLowerCase()
								.includes( searchTerm );

						// Match by keywords
						const matchesKeywords =
							contentItem.searchKeywords?.some( ( keyword ) =>
								keyword.toLowerCase().includes( searchTerm )
							);

						return matchesStandardFields || matchesKeywords;
					}
				);

				// Add matching items to results
				if ( matchingContent.length > 0 ) {
					// Check for custom components with only search keywords
					const hasCustomWithKeywords = matchingContent.some(
						( contentItem ) =>
							contentItem.contentType === 'custom' &&
							! contentItem.label &&
							contentItem.searchKeywords?.some( ( keyword ) =>
								keyword.toLowerCase().includes( searchTerm )
							)
					);

					// If we have custom components with only keywords, add the parent section once
					if ( hasCustomWithKeywords ) {
						acc.push( {
							path: item.path,
							label: item.label,
							section: item.section,
							icon: item.icon,
							type: 'link',
						} );
						itemAdded = true;
					}

					// If we didn't add the parent item for custom components,
					// add individual matching items
					if ( ! itemAdded ) {
						matchingContent.forEach( ( contentItem ) => {
							if (
								contentItem.label ||
								contentItem.contentType !== 'custom'
							) {
								acc.push( {
									...contentItem,
									parentPath: item.path,
									parentLabel: item.label,
									section: item.section,
									icon: item.icon,
								} );
							}
						} );
					}
				}
			}

			// Check if main item matches - only if we haven't added it already due to custom components
			if (
				! itemAdded &&
				( extractTextFromReactNode( item.label ).toLowerCase().includes( searchTerm ) ||
					extractTextFromReactNode( item.section ).toLowerCase().includes( searchTerm ) )
			) {
				acc.push( item );
			}

			return acc;
		}, [] );

		setSearchResults( results );
	};

	const handleItemClick = ( item ) => {
		let path = '';
		switch ( item.type ) {
			case 'link':
				path = item.path;
				break;
			case 'content':
				path = item.parentPath;
				break;
			default:
				path = item.parentPath;
				break;
		}

		const containsHttp = path.includes( 'http' );
		if ( containsHttp ) {
			// Open external links.
			const searchParam = item?.id ? `?scrollToElement=${ item.id }` : '';
			window.open( path + searchParam, '_self', 'noopener,noreferrer' );
			return;
		}

		// If the item is a content type, store the element identifier for scrolling after navigation
		if ( item.type === 'content' ) {
			const elementId = item.id || item.storeKey;

			// Navigate with state containing the element ID to scroll to
			navigate( {
				to: path,
				// Use the state to store the element ID to scroll to.
				state: { scrollToElement: elementId },
			} );
		} else {
			navigate( { to: path } );
		}
	};

	useEffect( () => {
		// Get scrollToElement from query params.
		const { scrollToElement: scrollToElementParamValue, ...restParams } =
			location.search;

		if ( scrollToElement || scrollToElementParamValue ) {
			scrollToElementFn( scrollToElement || scrollToElementParamValue );
		}

		// Remove scrollToElement from query params.
		if ( scrollToElementParamValue ) {
			navigate( {
				to: location.pathname,
				search: restParams,
				replace: true,
			} );
		}
	}, [ scrollToElement, location.search ] );

	return (
		<div>
			<SearchBox
				variant="secondary"
				size="sm"
				open={ open }
				setOpen={ setOpen }
				className="z-50"
				filter={ false }
			>
				<SearchBox.Input
					onChange={ handleChange }
					placeholder={ __( 'Search…', 'surerank' ) }
				/>
				<SearchBox.Content className="!max-h-96">
					<SearchBox.List className="p-1.5">
						{ searchResults.map( ( item, index ) => (
							<SearchBox.Item
								key={
									`${ item.path }-${ index }` ||
									`${ item.parentPath }-${ index }`
								}
								icon={
									item.icon ? (
										<item.icon className="size-4" />
									) : (
										<File className="size-4" />
									)
								}
								onClick={ () => handleItemClick( item ) }
								className="items-start [&>:nth-child(2)]:pt-0"
							>
								<div className="flex flex-col">
									<span className="text-sm font-medium">
										{ item.type === 'content' ? (
											<>
												<span className="text-text-tertiary">
													{ item.parentLabel } ›{ ' ' }
												</span>
												{ item.useParentLabel
													? __(
															'Settings',
															'surerank'
													  )
													: item.label }
											</>
										) : (
											item.label
										) }
									</span>
								</div>
							</SearchBox.Item>
						) ) }
						{ searchResults.length === 0 && <SearchBox.Empty /> }
					</SearchBox.List>
				</SearchBox.Content>
			</SearchBox>
		</div>
	);
};

export default GlobalSearch;
