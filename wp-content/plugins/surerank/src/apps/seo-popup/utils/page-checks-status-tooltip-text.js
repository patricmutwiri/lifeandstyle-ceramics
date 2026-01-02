/**
 * Tooltip text utilities for SureRank
 */
import { __, _n, sprintf } from '@wordpress/i18n';

/**
 * Get dynamic tooltip text based on page check status counts
 *
 * @param {Object} counts         - The counts object from page check status
 * @param {number} counts.error   - Number of errors
 * @param {number} counts.warning - Number of warnings
 * @return {string} The tooltip text
 */
export const getTooltipText = ( counts ) => {
	// Safety check for undefined/null counts
	if ( ! counts || typeof counts !== 'object' ) {
		return __( 'SureRank Meta Box', 'surerank' );
	}

	// Ensure error and warning are numbers
	const errorCount = typeof counts.error === 'number' ? counts.error : 0;
	const warningCount =
		typeof counts.warning === 'number' ? counts.warning : 0;

	if ( errorCount > 0 ) {
		return sprintf(
			/* translators: %s is the number of issues */
			_n(
				'%s Issue Detected',
				'%s Issues Detected',
				errorCount,
				'surerank'
			),
			errorCount
		);
	}

	if ( warningCount > 0 ) {
		return sprintf(
			/* translators: %s is the number of warnings */
			_n(
				'%s Warning Detected',
				'%s Warnings Detected',
				warningCount,
				'surerank'
			),
			warningCount
		);
	}

	return __( 'SureRank Meta Box', 'surerank' );
};
