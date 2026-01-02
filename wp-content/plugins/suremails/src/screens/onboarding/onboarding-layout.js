import { cn } from '@utils/utils';
import { Link, Outlet, useLocation, useNavigate } from 'react-router-dom';
import { Topbar, ProgressSteps, Button } from '@bsf/force-ui';
import { SureMailLogo } from '@assets/icons';
import { XIcon } from 'lucide-react';
import { __ } from '@wordpress/i18n';
import { useOnboardingNavigation } from './hooks';
import {
	OnboardingProvider,
	ONBOARDING_SESSION_STORAGE_KEY,
} from './onboarding-state';
import { useEffect, useLayoutEffect } from 'react';
import './styles.css';
/* global sessionStorage */

const NavBar = () => {
	const { getCurrentStepNumber, navigateToStep } = useOnboardingNavigation();

	return (
		<Topbar className="p-5 bg-background-secondary">
			<Topbar.Left>
				<Topbar.Item>
					<SureMailLogo />
				</Topbar.Item>
			</Topbar.Left>
			<Topbar.Middle align="center">
				<Topbar.Item className="md:block hidden">
					<ProgressSteps
						completedVariant="number"
						currentStep={ getCurrentStepNumber() }
						size="md"
						type="inline"
						variant="number"
					>
						{ Array.from( { length: 4 }, ( _, index ) => (
							<ProgressSteps.Step
								key={ index }
								size="md"
								onClick={ () => navigateToStep( index + 1 ) }
								className="cursor-pointer hover:bg-background-secondary transition-colors duration-200"
							/>
						) ) }
					</ProgressSteps>
				</Topbar.Item>
			</Topbar.Middle>
			<Topbar.Right>
				<Topbar.Item>
					<Button
						className="no-underline"
						tag={ Link }
						to="/dashboard"
						icon={ <XIcon /> }
						size="xs"
						variant="ghost"
						iconPosition="right"
					>
						{ __( 'Exit Guided Setup', 'suremails' ) }
					</Button>
				</Topbar.Item>
			</Topbar.Right>
		</Topbar>
	);
};

const NavigationGuard = () => {
	const location = useLocation();
	const navigate = useNavigate();
	const { checkRequiredStep } = useOnboardingNavigation();

	// Check if the user is authorized to access this step
	useLayoutEffect( () => {
		const redirectUrl = checkRequiredStep();
		if ( redirectUrl ) {
			navigate( redirectUrl, { replace: true } );
		}
	}, [ location.pathname, checkRequiredStep, navigate ] );

	return null;
};

const OnboardingLayout = () => {
	const location = useLocation();

	const widthClassNames = {
		1: 'max-w-[35rem]', // 560px converted to rem (assuming 1rem = 16px)
		2: 'max-w-[46.875rem]', // 750px converted to rem (assuming 1rem = 16px)
	};

	const widthClassKey =
		location.pathname === '/onboarding/welcome' ||
		location.pathname === '/onboarding/done'
			? 1
			: 2;

	// Add body class for onboarding-specific styles
	useEffect( () => {
		document.body.classList.add( 'suremails-onboarding-page' );

		return () => {
			document.body.classList.remove( 'suremails-onboarding-page' );
		};
	}, [] );

	// Clear when on the done page
	useEffect( () => {
		if ( location.pathname === '/onboarding/done' ) {
			sessionStorage.removeItem( ONBOARDING_SESSION_STORAGE_KEY );
		}
	}, [ location.pathname ] );

	// Clear session storage when the user navigates away from the onboarding page
	useEffect( () => {
		return () => {
			sessionStorage.removeItem( ONBOARDING_SESSION_STORAGE_KEY );
		};
	}, [] );

	return (
		<OnboardingProvider>
			{ /* Navigation guard to check required state for each step */ }
			<NavigationGuard />

			<div className="bg-background-secondary h-full space-y-7 pb-10">
				{ /* Header */ }
				<NavBar />
				{ /* Content */ }
				<div className="p-7 w-full h-full">
					<div
						className={ cn(
							'w-full h-full border-0.5 border-solid border-border-subtle bg-background-primary shadow-sm rounded-xl mx-auto p-7',
							widthClassNames[ widthClassKey ]
						) }
					>
						<Outlet />
					</div>
				</div>
			</div>
		</OnboardingProvider>
	);
};

export default OnboardingLayout;
