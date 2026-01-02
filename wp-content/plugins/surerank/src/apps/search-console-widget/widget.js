import { useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { toast } from '@bsf/force-ui';
import {
	formatToISOPreserveDate,
	getLastNDays,
	formatDateRange,
} from '@/functions/utils';
import { useWidgetDispatch, ACTIONS } from './context/widget-context';
import EmptyState from './empty-state';
import TrafficDisplay from './traffic-display';
import UpgradeCtaAlert from '@AdminComponents/upgrade-cta-alert';
import { DEFAULT_DATE_RANGE } from './constants';

/**
 * Main Widget Component
 * Handles data fetching and conditional rendering
 *
 * @return {JSX.Element} Widget component
 */
const Widget = () => {
	const dispatch = useWidgetDispatch();

	const {
		is_gsc_connected: isConnected,
		has_gsc_site_selected: hasSiteSelected,
	} = window.surerank_search_console_widget || {};

	const isAuthenticated = isConnected === '1' || isConnected === 1;
	const isSiteSelected = hasSiteSelected === '1' || hasSiteSelected === 1;

	/**
	 * Fetch clicks and impressions data
	 */
	const fetchClicksAndImpressions = useCallback(
		async ( from, to ) => {
			const formattedStartDate = formatToISOPreserveDate( from );
			const formattedEndDate = formatToISOPreserveDate( to );

			try {
				const response = await apiFetch( {
					path: '/surerank/v1/google-search-console/clicks-and-impressions',
					method: 'POST',
					data: {
						startDate: formattedStartDate,
						endDate: formattedEndDate,
					},
				} );

				if ( ! response.success ) {
					throw new Error(
						response.message ??
							__(
								'Failed to fetch clicks and impressions',
								'surerank'
							)
					);
				}

				const clicks = response?.data?.clicks;
				const impressions = response?.data?.impressions;

				const getPercentageType = ( percentage ) => {
					if ( percentage === 0 ) {
						return 'neutral';
					}
					return percentage > 0 ? 'success' : 'danger';
				};

				dispatch( {
					type: ACTIONS.SET_CLICKS_DATA,
					payload: [
						{
							label: __( 'Clicks', 'surerank' ),
							value: clicks?.current,
							previous: clicks?.previous,
							percentage: clicks?.percentage,
							percentageType: getPercentageType(
								clicks?.percentage
							),
							color: 'bg-[#72AEE6]',
						},
						{
							label: __( 'Impressions', 'surerank' ),
							value: impressions?.current,
							percentage: impressions?.percentage,
							previous: impressions?.previous,
							percentageType: getPercentageType(
								impressions?.percentage
							),
							color: 'bg-[#2171B1]',
						},
					],
				} );
			} catch ( error ) {
				toast.error( error.message );
				dispatch( {
					type: ACTIONS.SET_ERROR,
					payload: error.message,
				} );
			}
		},
		[ dispatch ]
	);

	/**
	 * Fetch site traffic data
	 */
	const fetchSiteTraffic = useCallback(
		async ( from, to ) => {
			const formattedStartDate = formatToISOPreserveDate( from );
			const formattedEndDate = formatToISOPreserveDate( to );

			try {
				const response = await apiFetch( {
					path: addQueryArgs(
						'/surerank/v1/google-search-console/site-traffic',
						{
							startDate: formattedStartDate,
							endDate: formattedEndDate,
						}
					),
					method: 'GET',
				} );

				if ( ! response.success ) {
					throw new Error(
						response.message ??
							__( 'Failed to fetch site traffic', 'surerank' )
					);
				}

				dispatch( {
					type: ACTIONS.SET_SITE_TRAFFIC,
					payload: response?.data?.length
						? response?.data?.map( ( item ) => ( {
								...item,
								readableDate: formatDateRange(
									item.date,
									null,
									null,
									'dd/MM'
								),
						  } ) )
						: [],
				} );
			} catch ( error ) {
				toast.error( error.message );
				dispatch( {
					type: ACTIONS.SET_ERROR,
					payload: error.message,
				} );
			}
		},
		[ dispatch ]
	);

	/**
	 * Initiate API calls to fetch data
	 */
	const initiateAPICalls = useCallback( async () => {
		if ( ! isAuthenticated || ! isSiteSelected ) {
			return;
		}

		dispatch( { type: ACTIONS.SET_LOADING, payload: true } );

		const { from, to } = getLastNDays( DEFAULT_DATE_RANGE );

		try {
			await fetchClicksAndImpressions( from, to );
			await fetchSiteTraffic( from, to );
		} catch ( error ) {
			// Errors are already handled in individual fetch functions
		} finally {
			dispatch( { type: ACTIONS.SET_LOADING, payload: false } );
		}
	}, [
		isAuthenticated,
		isSiteSelected,
		fetchClicksAndImpressions,
		fetchSiteTraffic,
		dispatch,
	] );

	// Fetch data on mount if authenticated and site selected
	useEffect( () => {
		if ( isAuthenticated && isSiteSelected ) {
			initiateAPICalls();
		}
	}, [ isAuthenticated, isSiteSelected, initiateAPICalls ] );

	// Render empty state if not connected or no site selected
	if ( ! isAuthenticated || ! isSiteSelected ) {
		return <EmptyState />;
	}

	// Render traffic display
	return (
		<>
			<TrafficDisplay />
			<UpgradeCtaAlert
				isProActive={ !! window?.surerank_globals?.is_pro_active }
			/>
		</>
	);
};

export default Widget;
