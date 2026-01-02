import { useEffect } from '@wordpress/element';

const usePagination = (
	{
		totalPages = 1,
		currentPage = 1,
		showEllipsis = true,
		maxVisiblePages = 5,
		onPageChange = () => {},
	},
	dependencies = []
) => {
	// Ensure current page is within valid range
	const validCurrentPage = Math.min( Math.max( 1, currentPage ), totalPages );

	// Generate page numbers array
	const generatePageNumbers = () => {
		if ( totalPages <= maxVisiblePages ) {
			return Array.from( { length: totalPages }, ( _, i ) => i + 1 );
		}

		const pages = [];
		const halfVisible = Math.floor( maxVisiblePages / 2 );
		let startPage = Math.max( 1, validCurrentPage - halfVisible );
		const endPage = Math.min( totalPages, startPage + maxVisiblePages - 1 );

		// Adjust start page if we're near the end
		if ( endPage - startPage + 1 < maxVisiblePages ) {
			startPage = Math.max( 1, endPage - maxVisiblePages + 1 );
		}

		// Add first page
		pages.push( 1 );

		// Add ellipsis after first page if needed
		if ( showEllipsis && startPage > 2 ) {
			pages.push( '...' );
		}

		// Add middle pages
		for ( let i = startPage; i <= endPage; i++ ) {
			if ( i !== 1 && i !== totalPages ) {
				pages.push( i );
			}
		}

		// Add ellipsis before last page if needed
		if ( showEllipsis && endPage < totalPages - 1 ) {
			pages.push( '...' );
		}

		// Add last page if there is more than one page
		if ( totalPages > 1 ) {
			pages.push( totalPages );
		}

		return pages;
	};

	// Handle page change
	const handlePageChange = ( page ) => {
		if ( page >= 1 && page <= totalPages && page !== validCurrentPage ) {
			onPageChange( page );
		}
	};

	// Calculate if previous/next buttons should be disabled
	const isPreviousDisabled = validCurrentPage <= 1;
	const isNextDisabled = validCurrentPage >= totalPages;

	// reset pagination when dependencies change
	useEffect( () => {
		onPageChange( 1 );
	}, dependencies );

	return {
		pages: generatePageNumbers(),
		currentPage: validCurrentPage,
		totalPages,
		isPreviousDisabled,
		isNextDisabled,
		handlePageChange,
		goToPreviousPage: () => handlePageChange( validCurrentPage - 1 ),
		goToNextPage: () => handlePageChange( validCurrentPage + 1 ),
	};
};

export default usePagination;
