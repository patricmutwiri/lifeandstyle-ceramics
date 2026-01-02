import { __ } from '@wordpress/i18n';

export const CONTENT_PERFORMANCE_TABS = {
	analysis: {
		label: __( 'Content Analysis', 'surerank' ),
	},
	// Uncomment the below code when Content Gap feature is ready.
	/* 	gap: {
        label: __( 'Content Gap', 'surerank' ),
    }, */
};
export const CONTENT_PERFORMANCE_TABS_COUNT = Object.keys(
	CONTENT_PERFORMANCE_TABS
).length;
