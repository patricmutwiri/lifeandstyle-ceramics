// src/screens/Dashboard/Dashboard.js
import { Chart } from './chart';
import RecommendedPlugins from '@screens/dashboard/recommended-plugins';
import { QuickAccess } from './quick-access';
import RecentLogs from './recent-logs';
import DashboardSkeleton from './dashboard-skeleton';
import apiFetch from '@wordpress/api-fetch';
import { useQuery } from '@tanstack/react-query';
import { useCallback } from '@wordpress/element';
import AuthCodeDisplay from '@components/auth-code-display/auth-code-display';
import Welcome from './welcome';
const Dashboard = () => {
	const restApiNonce = window.suremails?.nonce;
	const fetchData = useCallback( () => {
		return apiFetch( {
			path: '/suremails/v1/dashboard-data',
			method: 'GET',
			headers: {
				'X-WP-Nonce': restApiNonce,
			},
		} );
	}, [] );

	const { isLoading, data } = useQuery( {
		queryKey: [ 'dashboard-data' ],
		queryFn: fetchData,
		refetchInterval: 100000, // Refetch every 10 minutes
		refetchOnReconnect: true,
	} );

	// Show skeleton if loading
	if ( isLoading ) {
		return <DashboardSkeleton />;
	}
	// Determine if there are connections based on total_connections
	const hasConnections =
		typeof data?.total_connections === 'number'
			? data.total_connections > 0
			: false;
	return (
		<>
			<AuthCodeDisplay />
			<div className="grid w-full grid-cols-12 gap-6 p-8">
				{ /* Left Column: Chart and Recent Logs (Spans 2/3 of the space) */ }
				<div className="flex flex-col w-full col-span-12 space-y-8 lg:col-span-8">
					{ ! hasConnections ? (
						<div className="w-full h-full border-0.5 border-solid shadow-sm rounded-xl bg-background-primary border-border-subtle">
							<Welcome />
						</div>
					) : (
						<>
							{ /* Chart Card */ }
							<div className="w-full h-auto border-0.5 border-solid shadow-sm rounded-xl bg-background-primary border-border-subtle">
								<Chart
									totalSent={ data.total_sent }
									totalFailed={ data.total_failed }
									chartData={ data.chart_data }
									hasConnections={ 1 }
								/>
							</div>

							{ /* Recent Logs Card */ }
							<div className="w-full h-auto p-4 space-y-2 border-[.5px] border-border-subtle border-solid shadow-sm bg-background-primary rounded-xl">
								<RecentLogs
									recentLogs={ data.recent_logs }
									hasConnections={ 1 }
								/>
							</div>
						</>
					) }
				</div>

				{ /* Right Column: Recommended Plugins and Quick Access (Spans 1/3 of the space) */ }
				<div className="flex flex-col col-span-12 gap-1 space-y-7 lg:col-span-4">
					{ /* Recommended Plugins Card */ }
					<div className="h-auto shadow-sm bg-background-primary rounded-xl">
						<RecommendedPlugins />
					</div>

					{ /* Quick Access Card */ }
					<div className="w-full h-auto bg-background-primary rounded-xl">
						<QuickAccess />
					</div>
				</div>
			</div>
		</>
	);
};

export default Dashboard;
