import apiFetch from '@wordpress/api-fetch';
import {
	Table,
	Badge,
	Button,
	Container,
	toast,
	Skeleton,
	Text,
	Pagination,
} from '@bsf/force-ui';
import { ArrowUpRight, ArrowUp, ArrowDown, AlertTriangle } from 'lucide-react';
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { STORE_NAME } from '@/admin-store/constants';
import { useEffect, useState, useCallback, useRef } from '@wordpress/element';
import ContentPerformanceEmptyState from './content-performance-empty-state';
import { addQueryArgs } from '@wordpress/url';
import { ADMIN_DASHBOARD_URL } from '@Global/constants/index';
import { formatToISOPreserveDate, getLastNDays } from '@/functions/utils';
import { SortableColumn } from '@GlobalComponents/sortable-column';

const isSameDomain = ( url ) => {
	const cleanUrl = url.includes( 'sc-domain:' )
		? url.replace( 'sc-domain:', '' )
		: url;
	return cleanUrl.includes( window.location.host );
};

const LoadingSkeleton = ( { sameDomain = false, numberOfRows = 10 } ) => {
	return (
		<>
			{ [ ...Array( numberOfRows ) ].map( ( _, index ) => (
				<Table.Row key={ index }>
					<Table.Cell className="w-[35%] space-y-1">
						{ sameDomain && <Skeleton className="h-5 w-3/4" /> }
						<Skeleton className="h-5 w-full" />
					</Table.Cell>
					<Table.Cell className="w-1/10">
						<Skeleton className="h-5 w-16 rounded-full" />
					</Table.Cell>
					<Table.Cell className="w-1/10">
						<Skeleton className="h-5 w-12" />
					</Table.Cell>
					<Table.Cell>
						<Skeleton className="h-5 w-14" />
					</Table.Cell>
					<Table.Cell>
						<Skeleton className="h-5 w-16" />
					</Table.Cell>
					{ /* Uncomment this block if you want to show the additional skeletons */ }
					{ /* <Table.Cell>
						<Container direction="column" className="gap-1.5">
							<Skeleton className="h-5 w-16" />
							<Skeleton className="h-2 w-5/6" />
						</Container>
					</Table.Cell>
					<Table.Cell>
						<Skeleton className="h-5 w-16" />
					</Table.Cell> */ }
				</Table.Row>
			) ) }
		</>
	);
};

const ContentAnalysisTable = ( {
	type = 'short',
	searchQuery = '',
	statusFilter = 'All',
} ) => {
	const { setSearchConsole } = useDispatch( STORE_NAME );
	const {
		contentPerformance = [],
		authenticated,
		hasSiteSelected,
		selectedSite,
		contentPerformanceFetchComplete = false,
	} = useSelect( ( select ) => select( STORE_NAME ).getSearchConsole() );
	const [ loading, setLoading ] = useState(
		authenticated && hasSiteSelected && contentPerformance.length === 0
	);
	const [ exception, setException ] = useState( {} );
	const [ sortConfig, setSortConfig ] = useState( {
		key: null,
		direction: 'asc',
	} );
	const [ currentPage, setCurrentPage ] = useState( 1 );
	const itemsPerPage = 20; // Show 20 results per page

	const previousSite = useRef( null ); // Track previous site, start with null
	// Reset currentPage when searchQuery or statusFilter changes
	useEffect( () => {
		setCurrentPage( 1 );
	}, [ searchQuery, statusFilter ] );

	const handleSort = ( key ) => {
		setSortConfig( ( current ) => {
			if ( current.key === key ) {
				return {
					key,
					direction: current.direction === 'asc' ? 'desc' : 'asc',
				};
			}
			return {
				key,
				direction: 'asc',
			};
		} );
		setCurrentPage( 1 ); // Reset to first page when sorting
	};

	// Filter data based on searchQuery and statusFilter
	const filteredData = contentPerformance.filter( ( item ) => {
		// Search filter: match url or title
		const searchMatch =
			! searchQuery ||
			( item.url &&
				item.url
					.toLowerCase()
					.includes( searchQuery.toLowerCase() ) ) ||
			( item.title &&
				item.title
					.toLowerCase()
					.includes( searchQuery.toLowerCase() ) );

		// Status filter
		const statusMatch =
			statusFilter === 'All' ||
			( statusFilter === 'Top Ranked' &&
				item?.current?.position <= 10 &&
				item?.current?.position > 0 ) ||
			( statusFilter === 'On the Rise' &&
				item?.current?.position <= 20 &&
				item?.current?.position > 10 ) ||
			( statusFilter === 'Low Visibility' &&
				item?.current?.position > 20 );

		return searchMatch && statusMatch;
	} );

	const sortedData = [ ...filteredData ].sort( ( a, b ) => {
		if ( ! sortConfig.key ) {
			return 0;
		}

		const aValue = a?.current[ sortConfig.key ];
		const bValue = b?.current[ sortConfig.key ];

		if ( aValue === bValue ) {
			return 0;
		}
		if ( aValue === null || aValue === undefined ) {
			return 1;
		}
		if ( bValue === null || bValue === undefined ) {
			return -1;
		}

		const comparison = aValue < bValue ? -1 : 1;
		return sortConfig.direction === 'asc' ? comparison : -comparison;
	} );

	// Calculate pagination
	const totalItems = sortedData.length;
	const totalPages = Math.ceil( totalItems / itemsPerPage );
	const startIndex = ( currentPage - 1 ) * itemsPerPage;
	const endIndex = startIndex + itemsPerPage;
	const paginatedData =
		type === 'full'
			? sortedData.slice( startIndex, endIndex )
			: sortedData.slice( 0, 5 );

	// Fetch content performance only when the site changes
	const fetchContentPerformance = useCallback( async () => {
		if ( ! authenticated || ! selectedSite ) {
			return;
		}
		const { from, to } = getLastNDays( 90 );
		const formattedStartDate = from
			? formatToISOPreserveDate( new Date( from ) )
			: null;
		const formattedEndDate = to
			? formatToISOPreserveDate( new Date( to ) )
			: null;

		if ( ! formattedStartDate || ! formattedEndDate ) {
			return;
		}
		try {
			setLoading( true );
			const response = await apiFetch( {
				path: addQueryArgs(
					'/surerank/v1/google-search-console/content-performance',
					{
						rowLimit: 100,
						startDate: formattedStartDate,
						endDate: formattedEndDate,
					}
				),
				method: 'GET',
			} );
			if ( ! response.success ) {
				throw new Error(
					response.message ??
						__( 'Failed to fetch content performance', 'surerank' )
				);
			}
			setSearchConsole( {
				contentPerformance: response.data,
				contentPerformanceFetchComplete: true,
			} );
		} catch ( error ) {
			toast.error( error.message );
			setException( {
				icon: <AlertTriangle className="size-4" />,
				title: __( 'Oops! Something went wrong', 'surerank' ),
				description: __(
					'Failed to get content performance. Please try again later. If the problem persists, please contact support.',
					'surerank'
				),
			} );
		} finally {
			setLoading( false );
		}
	}, [ authenticated, selectedSite ] );
	useEffect( () => {
		// Only fetch data when the site changes or on first load without data
		if (
			authenticated &&
			selectedSite &&
			previousSite.current !== selectedSite
		) {
			// Reset fetchComplete when site changes
			if ( previousSite.current !== null ) {
				setSearchConsole( {
					contentPerformanceFetchComplete: false,
				} );
			}

			// Only fetch if we haven't completed a fetch for this session or if the site actually changed
			if (
				! contentPerformanceFetchComplete ||
				previousSite.current !== null
			) {
				fetchContentPerformance();
			}
			previousSite.current = selectedSite;
		}
	}, [
		authenticated,
		selectedSite,
		fetchContentPerformance,
		contentPerformanceFetchComplete,
		setSearchConsole,
	] );

	const handlePageChange = ( page ) => {
		setCurrentPage( page );
	};

	const handleNext = () => {
		if ( currentPage < totalPages ) {
			setCurrentPage( currentPage + 1 );
		}
	};

	const handlePrevious = () => {
		if ( currentPage > 1 ) {
			setCurrentPage( currentPage - 1 );
		}
	};

	const getPageNumbers = () => {
		const delta = 1; // Number of sibling pages to show on each side
		const range = [];
		const rangeWithDots = [];

		// Calculate the range of pages to display
		let left = Math.max( 2, currentPage - delta );
		let right = Math.min( totalPages - 1, currentPage + delta );

		// Adjust the range to ensure we show enough pages
		const rangeSize = right - left + 1;
		if ( rangeSize < 2 * delta + 1 ) {
			if ( currentPage <= totalPages / 2 ) {
				// Near the start, extend right
				right = Math.min( left + 2 * delta, totalPages - 1 );
			} else {
				// Near the end, extend left
				left = Math.max( right - 2 * delta, 2 );
			}
		}

		// Always include page 1
		range.push( 1 );

		// Add pages in the calculated range
		for ( let i = left; i <= right; i++ ) {
			range.push( i );
		}

		if ( totalPages > 1 && ! range.includes( totalPages ) ) {
			range.push( totalPages );
		}

		let prevPage = 0;
		for ( const page of range ) {
			if ( page - prevPage > 1 ) {
				rangeWithDots.push( 'ellipsis' );
			}
			rangeWithDots.push( page );
			prevPage = page;
		}

		return rangeWithDots;
	};

	const pageNumbers = getPageNumbers();

	const getIcon = ( item, key ) => {
		const value = item?.changes[ key ];

		if ( typeof value !== 'number' ) {
			return null;
		}
		if ( value > 0 ) {
			if ( key === 'position' ) {
				return (
					<ArrowDown className="size-3.5 text-support-error shrink-0" />
				);
			}
			return (
				<ArrowUp className="size-3.5 text-support-success shrink-0" />
			);
		}
		if ( value < 0 ) {
			if ( key === 'position' ) {
				return (
					<ArrowUp className="size-3.5 text-support-success shrink-0" />
				);
			}
			return (
				<ArrowDown className="size-3.5 text-support-error shrink-0" />
			);
		}
		return null;
	};

	// Empty state when there is an error
	if ( exception?.title && ! loading ) {
		return (
			<ContentPerformanceEmptyState
				title={ exception?.title }
				description={ exception?.description }
				icon={ exception?.icon }
			/>
		);
	}

	// Empty state when there is no content performance data
	if ( ! filteredData?.length && ! loading ) {
		const MSG_SITE_SELECTED = __(
			"Once a site is selected, you'll see how your content is performing in search engines here.",
			'surerank'
		);
		const MSG_NO_DATA = __(
			'No content performance data available. Please check back later.',
			'surerank'
		);
		const MSG_NOT_CONNECTED = __(
			"Once connected to Google Search Console, you'll see how your content is performing in search engines here.",
			'surerank'
		);

		let description = '';

		if ( authenticated && ! hasSiteSelected ) {
			description = MSG_SITE_SELECTED;
		} else if ( authenticated && hasSiteSelected ) {
			description = MSG_NO_DATA;
		} else {
			description = MSG_NOT_CONNECTED;
		}

		return <ContentPerformanceEmptyState description={ description } />;
	}

	return (
		<Table>
			<Table.Head>
				<Table.HeadCell className="w-[35%] max-w-120 min-w-80">
					{ __( 'Page', 'surerank' ) }
				</Table.HeadCell>
				<Table.HeadCell className="w-1/10">
					{ __( 'Status', 'surerank' ) }
				</Table.HeadCell>
				<SortableColumn
					className="w-[12%]"
					sortKey="clicks"
					onSort={ handleSort }
					currentSort={ sortConfig }
				>
					{ __( 'Clicks', 'surerank' ) }
				</SortableColumn>
				<SortableColumn
					className="w-[12%] text-nowrap"
					sortKey="position"
					onSort={ handleSort }
					currentSort={ sortConfig }
				>
					{ __( 'Avg. Position', 'surerank' ) }
				</SortableColumn>
				<SortableColumn
					className="w-[12%]"
					sortKey="impressions"
					onSort={ handleSort }
					currentSort={ sortConfig }
				>
					{ __( 'Impressions', 'surerank' ) }
				</SortableColumn>
				{ /* Uncomment this when content score feature is ready. */ }
				{ /* <Table.HeadCell className="min-w-[10rem] text-nowrap">
					<Container align="center" className="gap-1">
						<span className="text-text-tertiary">
							{ __( 'Content Score', 'surerank' ) }
						</span>
						<Badge
							className="w-fit"
							size="xs"
							variant="blue"
							label={ __( 'Pro', 'surerank' ) }
						/>
					</Container>
				</Table.HeadCell> */ }
				{ /* <Table.HeadCell className="min-w-[10%]">
					<span className="sr-only">
						{ __( 'Actions', 'surerank' ) }
					</span>
				</Table.HeadCell> */ }
			</Table.Head>
			<Table.Body>
				{ loading ? (
					<LoadingSkeleton
						sameDomain={ isSameDomain( selectedSite ) }
						numberOfRows={ type === 'full' ? 10 : 5 }
					/>
				) : (
					paginatedData.map( ( item, index ) => (
						<Table.Row key={ index }>
							<Table.Cell className="space-y-1">
								<Text
									as="a"
									href={ item.url }
									color="secondary"
									className="line-clamp-1 no-underline text-xs"
									target="_blank"
								>
									{ item.url }
								</Text>
							</Table.Cell>
							<Table.Cell>
								<Badge
									className="w-fit"
									size="xs"
									variant={ ( () => {
										const pos = item?.current?.position;
										if ( ! pos || pos <= 0 ) {
											return 'neutral';
										}
										if ( pos <= 10 ) {
											return 'green';
										}
										if ( pos <= 20 ) {
											return 'yellow';
										}
										return 'neutral';
									} )() }
									label={ ( () => {
										const pos = item?.current?.position;
										if ( pos <= 10 ) {
											return __(
												'Top Ranked',
												'surerank'
											);
										}
										if ( pos <= 20 ) {
											return __(
												'On the Rise',
												'surerank'
											);
										}
										return __(
											'Low Visibility',
											'surerank'
										);
									} )() }
									disableHover
								/>
							</Table.Cell>
							{ [ 'clicks', 'position', 'impressions' ].map(
								( key ) => (
									<Table.Cell key={ key }>
										<Container
											align="center"
											className="gap-1"
										>
											<span className="text-xs">
												{ key === 'position'
													? item.current[
															key
													  ]?.toFixed( 2 )
													: item.current[
															key
													  ]?.toLocaleString() }
											</span>
											{ getIcon( item, key ) }
										</Container>
									</Table.Cell>
								)
							) }
							{ /* Uncomment this when content score feature is ready. */ }
							{ /* <Table.Cell>
								<Container
									direction="column"
									className="gap-1.5"
								>
									<span className="text-xs">
										{ __( 'Out of 100', 'surerank' ) }
									</span>
									<ProgressBar
										progress={ 50 }
										className={ cn(
											'w-full max-w-32',
											'[&>div]:bg-gray-400'
										) }
									/>
								</Container>
							</Table.Cell> */ }
							{ /* <Table.Cell>
								<Container align="center" justify="start">
									<FixButton
										buttonLabel={ __( 'View', 'surerank' ) }
										icon={ <ArrowRight /> }
										iconPosition="right"
										title={ __(
											'Unlock Content Insights',
											'surerank'
										) }
										description={ __(
											"See what's driving traffic, content score, rankings, and performance trends.",
											'surerank'
										) }
										linkLabel={ __(
											'Upgrade',
											'surerank'
										) }
										size="sm"
										tooltipProps={ {
											className: 'z-999999',
										} }
									/>
								</Container>
							</Table.Cell> */ }
						</Table.Row>
					) )
				) }
			</Table.Body>
			{ type !== 'full' && (
				<Table.Footer className="flex items-center justify-center">
					<Button
						size="md"
						variant="link"
						icon={ <ArrowUpRight /> }
						iconPosition="right"
						className="no-underline hover:no-underline"
						onClick={ () => {
							window.location.href = `${ ADMIN_DASHBOARD_URL }?page=surerank#/content-performance`;
						} }
					>
						{ __( 'View Full Report', 'surerank' ) }
					</Button>
				</Table.Footer>
			) }
			{ type === 'full' && (
				<Table.Footer className="flex items-center justify-between w-full">
					<span className="text-sm font-normal leading-5 text-text-secondary">
						{ totalItems > 0
							? `Page ${ currentPage } out of ${ totalPages }`
							: 'No pages available' }
					</span>
					{ loading ? (
						<Skeleton className="w-32 h-8" />
					) : (
						<Pagination className="w-fit">
							<Pagination.Content className="[&>li]:m-0">
								<Pagination.Previous
									onClick={ handlePrevious }
									disabled={ currentPage === 1 }
									className={
										currentPage === 1
											? 'opacity-50 cursor-not-allowed'
											: ''
									}
								/>
								{ pageNumbers.map( ( item, index ) =>
									item === 'ellipsis' ? (
										<Pagination.Ellipsis
											key={ `ellipsis-${ index }` }
										/>
									) : (
										<Pagination.Item
											key={ item }
											isActive={ currentPage === item }
											onClick={ () =>
												handlePageChange( item )
											}
										>
											{ item }
										</Pagination.Item>
									)
								) }
								<Pagination.Next
									onClick={ handleNext }
									disabled={ currentPage === totalPages }
									className={
										currentPage === totalPages
											? 'opacity-50 cursor-not-allowed'
											: ''
									}
								/>
							</Pagination.Content>
						</Pagination>
					) }
				</Table.Footer>
			) }
		</Table>
	);
};

export default ContentAnalysisTable;
