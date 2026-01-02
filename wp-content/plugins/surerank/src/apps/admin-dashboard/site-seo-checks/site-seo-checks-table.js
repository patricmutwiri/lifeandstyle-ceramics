import {
	Container,
	Badge,
	Button,
	Pagination,
	Table,
	toast,
} from '@bsf/force-ui';
import { __, sprintf } from '@wordpress/i18n';
import { ArrowRight, ArrowUpRight, Check, Search, X } from 'lucide-react';
import SiteSeoChecksFixButton from './site-seo-checks-fix-button';
import { useSuspenseSiteSeoAnalysis } from './site-seo-checks-main';
import {
	getSeverityColor,
	getSeverityLabel,
} from '@GlobalComponents/seo-checks';
import usePagination from '@/global/hooks/use-pagination';
import ContentPerformanceEmptyState from '../content-performance-empty-state';
import { useState, useCallback, useMemo } from '@wordpress/element';
import { Link } from '@tanstack/react-router';
import apiFetch from '@wordpress/api-fetch';
import { Tooltip } from '@/apps/admin-components/tooltip';
import { cn } from '@/functions/utils';

const ITEMS_PER_PAGE = 20;
const SUMMARY_ITEMS_COUNT = 5;

// Action button component
const SiteSeoChecksActionButtons = ( {
	onViewItem,
	item,
	onIgnore,
	showFixButton,
} ) => {
	const ignoreCheck = useCallback(
		async ( id ) => {
			try {
				const response = await apiFetch( {
					path: `/surerank/v1/checks/ignore-site-check`,
					method: 'POST',
					data: { id },
				} );
				if ( response.status !== 'success' ) {
					throw new Error( 'Failed to ignore check' );
				}
				onIgnore( id, true );
				toast.success( __( 'Check ignored successfully', 'surerank' ) );
			} catch ( error ) {
				toast.error( __( 'Failed to ignore check', 'surerank' ) );
			}
		},
		[ onIgnore ]
	);

	const restoreCheck = useCallback(
		async ( id ) => {
			try {
				const response = await apiFetch( {
					path: `/surerank/v1/checks/ignore-site-check`,
					method: 'DELETE',
					data: { id },
				} );
				if ( response.status !== 'success' ) {
					throw new Error( 'Failed to restore check' );
				}
				onIgnore( id, false );
				toast.success(
					__( 'Check restored successfully', 'surerank' )
				);
			} catch ( error ) {
				toast.error( __( 'Failed to restore check', 'surerank' ) );
			}
		},
		[ onIgnore ]
	);

	const handleSelectOnly = () => {
		onViewItem( false );
	};

	// 🟢 If item is ignored, only show Restore
	if ( item.ignore ) {
		return (
			<Container justify="end">
				<Button
					size="xs"
					variant="outline"
					iconPosition="right"
					onClick={ () => restoreCheck( item.id ) }
				>
					{ __( 'Restore', 'surerank' ) }
				</Button>
			</Container>
		);
	}

	return (
		<Container justify="end">
			{ showFixButton && (
				<SiteSeoChecksFixButton
					selectedItem={ item }
					size="xs"
					variant="outline"
					runBeforeOnClick={ handleSelectOnly }
				/>
			) }
			{ item.status !== 'success' && item.status !== 'suggestion' && (
				<>
					<Tooltip
						content={ __( 'Ignore', 'surerank' ) }
						placement="top"
						arrow
					>
						<Button
							size="xs"
							variant="outline"
							icon={ <X /> }
							iconPosition="right"
							onClick={ () => ignoreCheck( item.id ) }
						/>
					</Tooltip>
				</>
			) }
			<>
				<Tooltip
					content={ __( 'View Details', 'surerank' ) }
					placement="top"
					arrow
				>
					<Button
						size="xs"
						className={ cn(
							item.status === 'success' &&
								'bg-badge-background-green text-badge-color-green hover:bg-badge-hover-green'
						) }
						variant="outline"
						icon={
							item.status === 'success' ? (
								<Check />
							) : (
								<ArrowRight />
							)
						}
						iconPosition="right"
						onClick={ onViewItem }
					/>
				</Tooltip>
			</>
		</Container>
	);
};

// Table row component
const SiteSeoChecksTableRow = ( { item, onIgnore } ) => {
	const [ , dispatch ] = useSuspenseSiteSeoAnalysis();

	const handleViewItem = useCallback(
		( openModal = true ) => {
			dispatch( {
				open: typeof openModal === 'boolean' ? openModal : true,
				selectedItem: item,
				currentScreen: 'overview',
			} );
		},
		[ item, dispatch ]
	);

	return (
		<Table.Row>
			<Table.Cell className="max-w-none">
				<Container gap="xs" align="center" className="flex-nowrap">
					<Container.Item className="flex-shrink-0">
						<Badge
							label={ getSeverityLabel(
								item?.status,
								item?.ignore
							) }
							variant={ getSeverityColor( item?.status ) }
							disabled={ item?.ignore }
						/>
					</Container.Item>
					<Container.Item className="flex-1">
						{ item?.message }
					</Container.Item>
				</Container>
			</Table.Cell>
			<Table.Cell className="w-1 whitespace-nowrap">
				<SiteSeoChecksActionButtons
					onViewItem={ handleViewItem }
					showFixButton={ item?.status !== 'success' }
					item={ item }
					onIgnore={ onIgnore }
				/>
			</Table.Cell>
		</Table.Row>
	);
};

// Table component
const SiteSeoChecksTable = ( { limit, showViewAll = false } ) => {
	const [ { searchKeyword, report = [] }, dispatch ] =
		useSuspenseSiteSeoAnalysis();
	const [ currentPage, setCurrentPage ] = useState( 1 );

	const itemsPerPage = limit
		? Math.max( limit, SUMMARY_ITEMS_COUNT )
		: ITEMS_PER_PAGE;
	const showPagination = ! limit;

	// Handle ignoring a check locally
	const handleIgnoreCheck = useCallback(
		( id, isIgnored ) => {
			const newReport = { ...report };
			if ( newReport[ id ] ) {
				newReport[ id ] = {
					...newReport[ id ],
					ignore: isIgnored,
				};
			}
			dispatch( { report: newReport } );
		},
		[ report, dispatch ]
	);

	const filteredContent = useMemo( () => {
		const statusPriority = {
			error: 0,
			warning: 1,
			suggestion: 2,
			success: 3,
		};

		return Object.entries( report )
			.filter( ( [ , item ] ) => {
				if ( typeof item !== 'object' ) {
					return false;
				}
				return item.message
					.toLowerCase()
					.includes( searchKeyword.toLowerCase() );
			} )
			.map( ( [ key, item ] ) => ( { ...item, id: key } ) )
			.sort( ( a, b ) => {
				const aPriority = a.ignore
					? 4
					: statusPriority[ a.status ] ?? 4;
				const bPriority = b.ignore
					? 4
					: statusPriority[ b.status ] ?? 4;
				return aPriority - bPriority;
			} );
	}, [ searchKeyword, report ] );

	const {
		pages,
		currentPage: validCurrentPage,
		totalPages,
		isPreviousDisabled,
		isNextDisabled,
		handlePageChange,
		goToPreviousPage,
		goToNextPage,
	} = usePagination(
		{
			totalPages: Math.ceil( filteredContent.length / itemsPerPage ),
			currentPage,
			showEllipsis: true,
			maxVisiblePages: 5,
			onPageChange: ( page ) => {
				setCurrentPage( page );
			},
		},
		[ searchKeyword ]
	);

	const filteredPaginatedContent = useMemo( () => {
		if ( limit ) {
			return filteredContent.slice( 0, limit );
		}
		return filteredContent.slice(
			( currentPage - 1 ) * itemsPerPage,
			currentPage * itemsPerPage
		);
	}, [ filteredContent, currentPage, itemsPerPage, limit ] );

	if ( filteredContent.length === 0 ) {
		return (
			<ContentPerformanceEmptyState
				title={ __( 'No Results Found', 'surerank' ) }
				description={ __(
					"Your search didn't match any results. Please try a different keyword or refine your search criteria.",
					'surerank'
				) }
				icon={ <Search /> }
			/>
		);
	}

	return (
		<div className="overflow-x-auto -mx-3 px-3 sm:mx-0 sm:px-0">
			<Table>
				<Table.Head>
					<Table.HeadCell>
						{ __( 'Checkpoints', 'surerank' ) }
					</Table.HeadCell>
					<Table.HeadCell className="w-1 text-center whitespace-nowrap"></Table.HeadCell>
				</Table.Head>
				<Table.Body>
					{ filteredPaginatedContent.map( ( item, index ) => (
						<SiteSeoChecksTableRow
							key={ `row-${ index }-${ currentPage }` }
							item={ item }
							onIgnore={ handleIgnoreCheck }
						/>
					) ) }
				</Table.Body>
				{ showViewAll && (
					<Table.Footer className="bg-brand-background-50 p-0 cursor-pointer group/link">
						<Link
							to="/site-seo-analysis"
							className="flex justify-center items-center w-full p-3 no-underline focus:outline-none active:outline-none focus:[box-shadow:none]"
						>
							<Button
								tag="span"
								size="md"
								variant="link"
								icon={ <ArrowUpRight /> }
								iconPosition="right"
							>
								{ __( 'View All Results', 'surerank' ) }
							</Button>
						</Link>
					</Table.Footer>
				) }
				{ showPagination && filteredContent?.length > itemsPerPage && (
					<Table.Footer>
						<SiteSeoChecksPagination
							pages={ pages }
							validCurrentPage={ validCurrentPage }
							totalPages={ totalPages }
							isPreviousDisabled={ isPreviousDisabled }
							isNextDisabled={ isNextDisabled }
							handlePageChange={ handlePageChange }
							goToPreviousPage={ goToPreviousPage }
							goToNextPage={ goToNextPage }
						/>
					</Table.Footer>
				) }
			</Table>
		</div>
	);
};

// Pagination component
const SiteSeoChecksPagination = ( {
	pages,
	validCurrentPage,
	totalPages,
	isPreviousDisabled,
	isNextDisabled,
	handlePageChange,
	goToPreviousPage,
	goToNextPage,
} ) => {
	return (
		<Container align="center" justify="between">
			<div aria-label="Pagination status" aria-current="page">
				{ sprintf(
					// translators: %1$s is the current page number, %2$s is the total number of pages
					__( 'Page %1$s out of %2$s', 'surerank' ),
					validCurrentPage,
					totalPages
				) }
			</div>
			<div>
				<Pagination size="sm">
					<Pagination.Content>
						<Pagination.Previous
							className="disabled:opacity-50 disabled:cursor-not-allowed"
							disabled={ isPreviousDisabled }
							tag="button"
							onClick={ goToPreviousPage }
							aria-label={ __( 'Previous page', 'surerank' ) }
						/>
						{ pages.map( ( page, index ) => {
							if ( page === '...' ) {
								return (
									<Pagination.Ellipsis
										key={ `ellipsis-${ index }` }
									/>
								);
							}
							return (
								<Pagination.Item
									key={ page }
									isActive={ page === validCurrentPage }
									onClick={ () => handlePageChange( page ) }
									aria-label={ sprintf(
										// translators: %s is the page number
										__( 'Page %s', 'surerank' ),
										page
									) }
									tag="button"
								>
									{ page }
								</Pagination.Item>
							);
						} ) }
						<Pagination.Next
							className="disabled:opacity-50 disabled:cursor-not-allowed"
							disabled={ isNextDisabled }
							tag="button"
							onClick={ goToNextPage }
							aria-label={ __( 'Next page', 'surerank' ) }
						/>
					</Pagination.Content>
				</Pagination>
			</div>
		</Container>
	);
};

export default SiteSeoChecksTable;
