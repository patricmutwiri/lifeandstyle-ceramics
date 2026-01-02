/**
 * Calculate page check status and counts from categorized checks
 *
 * @param {Object} categorizedChecks - The categorized checks object (should be pre-filtered)
 * @return {Object} Object containing status and counts
 */
export const calculateCheckStatus = ( categorizedChecks = {} ) => {
	// Use pre-filtered checks directly (filtering should happen at store level)
	const badChecks = categorizedChecks.badChecks || [];
	const fairChecks = categorizedChecks.fairChecks || [];
	const passedChecks = categorizedChecks.passedChecks || [];
	const suggestionChecks = categorizedChecks.suggestionChecks || [];

	// Calculate status
	let status = 'success';
	if ( badChecks.length > 0 ) {
		status = 'error';
	} else if ( fairChecks.length > 0 ) {
		status = 'warning';
	} else if ( suggestionChecks.length > 0 ) {
		status = 'suggestion';
	}

	// Calculate counts
	const counts = {
		errorAndWarnings:
			badChecks.length + fairChecks.length,
		success: passedChecks.length,
		error: badChecks.length,
		warning: fairChecks.length,
		suggestion: suggestionChecks.length,
	};

	return { status, counts };
};
