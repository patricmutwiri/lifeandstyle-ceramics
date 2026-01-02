import { __ } from '@wordpress/i18n';
import {
	startOfToday,
	startOfYesterday,
	subMonths,
	subWeeks,
	startOfDay,
	subHours,
} from 'date-fns';

const DATE_PICKER_PRESETS = [
	{
		label: __( 'Last 24 Hours', 'surerank' ),
		range: {
			from: subHours( new Date(), 24 ),
			to: new Date(),
		},
	},
	{
		label: __( 'Yesterday', 'surerank' ),
		range: {
			from: startOfYesterday(),
			to: startOfYesterday(),
		},
	},
	{
		label: __( 'Last 7 Days', 'surerank' ),
		range: {
			from: startOfDay( subWeeks( new Date(), 1 ) ),
			to: startOfToday(),
		},
	},
	{
		label: __( 'Last 30 Days', 'surerank' ),
		range: {
			from: startOfDay( subMonths( new Date(), 1 ) ),
			to: startOfToday(),
		},
	},
	{
		label: __( 'Last 90 Days', 'surerank' ),
		range: {
			from: startOfDay( subMonths( new Date(), 3 ) ),
			to: startOfToday(),
		},
	},
	{
		label: __( 'Last 365 Days', 'surerank' ),
		range: {
			from: startOfDay( subMonths( new Date(), 12 ) ),
			to: startOfToday(),
		},
	},
];

export default DATE_PICKER_PRESETS;
