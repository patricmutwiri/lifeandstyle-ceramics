/**
 * Utility functions for admin dashboard
 */

import { Table, Container } from '@bsf/force-ui';
import { ChevronsUpDown, ArrowUp, ArrowDown } from 'lucide-react';
import { cn } from '@/functions/utils';

/**
 * Sortable table column component with sort indicators
 *
 * @param {Object}   props             - Component props
 * @param {*}        props.children    - Column content
 * @param {string}   props.className   - Additional CSS classes
 * @param {string}   props.sortKey     - Key used for sorting
 * @param {Function} props.onSort      - Sort handler function
 * @param {Object}   props.currentSort - Current sort configuration {key, direction}
 * @return {JSX.Element} Sortable column component
 */
export const SortableColumn = ( {
	children,
	className,
	sortKey,
	onSort,
	currentSort,
	...props
} ) => {
	const isActive = currentSort?.key === sortKey;
	let sortIcon = <ChevronsUpDown className="size-4 shrink-0" />;

	if ( isActive ) {
		sortIcon =
			currentSort.direction === 'asc' ? (
				<ArrowUp className="size-4 shrink-0" />
			) : (
				<ArrowDown className="size-4 shrink-0" />
			);
	}

	return (
		<Table.HeadCell
			className={ cn( className, 'cursor-pointer select-none' ) }
			onClick={ () => onSort( sortKey ) }
			{ ...props }
		>
			<Container align="center" className="gap-0.5">
				{ children }
				{ sortIcon }
			</Container>
		</Table.HeadCell>
	);
};
