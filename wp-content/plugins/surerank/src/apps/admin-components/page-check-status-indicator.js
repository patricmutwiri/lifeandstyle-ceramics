import { memo, useMemo } from '@wordpress/element';
import {
	cn,
	getStatusIndicatorClasses,
	getStatusIndicatorAriaLabel,
} from '@/functions/utils';
import { ENABLE_PAGE_LEVEL_SEO } from '@/global/constants';

/**
 * PageCheckStatusIndicator - A reusable status indicator component
 * that displays a small status dot in the top-right corner of its container
 *
 * @param {Object}  props                  Component props
 * @param {string}  props.status           Status of page checks ('error', 'warning', 'suggestion', 'success')
 * @param {number}  props.errorAndWarnings Count of errors and warnings
 * @param {boolean} props.initializing     Whether page checks are initializing
 * @param {string}  props.className        Additional CSS classes
 * @return {JSX.Element|null} Status indicator element
 */
const PageCheckStatusIndicator = ( {
	status,
	errorAndWarnings = 0,
	initializing = false,
	className = '',
} ) => {
	// Status indicator colors based on check status
	const statusClasses = useMemo( () => {
		return getStatusIndicatorClasses( status );
	}, [ status ] );

	// Accessibility label for the indicator
	const ariaLabel = useMemo( () => {
		return getStatusIndicatorAriaLabel( errorAndWarnings );
	}, [ errorAndWarnings ] );

	// Don't show indicator during initialization, disabled or if no status
	if ( initializing || ! status || ! ENABLE_PAGE_LEVEL_SEO ) {
		return null;
	}

	return (
		<div
			className={ cn(
				'absolute top-0.5 right-1 size-2 rounded-full z-10 duration-200',
				statusClasses,
				className
			) }
			aria-label={ ariaLabel }
			title={ ariaLabel }
		/>
	);
};

export default memo( PageCheckStatusIndicator );
