import { __ } from '@wordpress/i18n';

// Utility functions
export const getSeverityColor = ( severity, ignore = false ) => {
	if ( ignore ) {
		return 'gray';
	}
	if ( severity === 'error' ) {
		return 'red';
	}
	if ( severity === 'warning' ) {
		return 'yellow';
	}
	if ( severity === 'suggestion' ) {
		return 'blue';
	}

	return 'green';
};

// We are referring bad as critical and fair as warning and good as passed.
export const getSeverityLabel = ( severity, ignore = false ) => {
	if ( ignore ) {
		return __( 'Ignored', 'surerank' );
	}
	if ( severity === 'error' ) {
		return __( 'Critical', 'surerank' );
	}
	if ( severity === 'warning' ) {
		return __( 'Warning', 'surerank' );
	}
	if ( severity === 'suggestion' ) {
		return __( 'Suggestion', 'surerank' );
	}

	return __( 'Passed', 'surerank' );
};
