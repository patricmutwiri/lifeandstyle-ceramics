import { HashRouter as Router, useLocation } from 'react-router-dom'; // Using HashRouter for routing
import ContentArea from '@routes/routes.js'; // Ensure this path is correct and points to your route definitions
import './styles.css'; // Ensure Tailwind CSS is imported properly
import NavMenu from '@components/nav-nenu.js'; // Import NavMenu for the top navigation
import { Toaster } from '@bsf/force-ui'; // Import Toaster for notifications
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { cn } from '@utils/utils';

const queryClient = new QueryClient();

const App = () => {
	return (
		<QueryClientProvider client={ queryClient }>
			<Router>
				<AppLayout />
			</Router>
		</QueryClientProvider>
	);
};

// Separate layout component to handle conditional rendering of NavMenu
const AppLayout = () => {
	const location = useLocation();

	// Check if the current path is '/onboarding'
	const isOnboarding = location.pathname.startsWith( '/onboarding' );

	return (
		<>
			<div className="w-full h-full">
				{ /* Only render NavMenu if not on the onboarding screen */ }
				{ ! isOnboarding && <NavMenu /> }
				<div
					className={ cn(
						'w-full bg-background-secondary',
						isOnboarding
							? 'min-h-[calc(100dvh-46px)] md:min-h-[calc(100dvh_-_32px)] lg:min-h-[calc(100vh_-_32px)]'
							: 'min-h-[calc(100dvh_-_110px)] md:min-h-[calc(100dvh_-_96px)] lg:min-h-[calc(100vh_-_96px)]'
					) }
				>
					<ContentArea />
				</div>
			</div>
			<Toaster dismissAfter={ 3000 } className="z-999999" />
		</>
	);
};

export default App;
