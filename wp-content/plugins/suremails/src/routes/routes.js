import { Route, Routes, Navigate } from 'react-router-dom';
import { Connections } from '@screens/connections/index.js';
import { Logs } from '@screens/logs/index.js';
import { Dashboard } from '@screens/dashboard/index.js';
import { Notifications } from '@screens/notifications';
import { Settings } from '@screens/settings/index.js';
import {
	ConnectionProviders,
	OnboardingLayout,
	SafeGuard,
	Welcome,
	AddOns as OnboardingAddOns,
	Done,
} from '@screens/onboarding';
import AddOns from '@screens/add-ons/add-ons';

const ContentArea = () => {
	return (
		<div className="content-area w-full">
			<Routes>
				<Route path="/connections" element={ <Connections /> } />
				<Route path="/logs" element={ <Logs /> } />
				<Route path="/dashboard" element={ <Dashboard /> } />
				<Route path="/settings" element={ <Settings /> } />
				<Route path="/notifications" element={ <Notifications /> } />
				<Route path="/add-ons" element={ <AddOns /> } />
				<Route
					path="/"
					element={ <Navigate to="/dashboard" replace /> }
				/>
				<Route path="/onboarding" element={ <OnboardingLayout /> }>
					<Route
						index
						element={
							<Navigate to="/onboarding/welcome" replace />
						}
					/>
					<Route path="welcome" element={ <Welcome /> } />
					<Route
						path="connection"
						element={ <ConnectionProviders /> }
					/>
					<Route path="reputation-shield" element={ <SafeGuard /> } />
					<Route path="add-ons" element={ <OnboardingAddOns /> } />
					<Route path="done" element={ <Done /> } />
				</Route>
			</Routes>
		</div>
	);
};

export default ContentArea;
