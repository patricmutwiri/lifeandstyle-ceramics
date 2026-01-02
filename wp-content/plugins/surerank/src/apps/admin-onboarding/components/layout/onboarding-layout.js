import { Topbar, ProgressSteps, Toaster } from '@bsf/force-ui';
import { SureRankFullLogo } from '@GlobalComponents/icons';
import { cn } from '@Functions/utils';
import { Outlet, useLocation } from '@tanstack/react-router';
import { ONBOARDING_STEPS_CONFIG } from '@Onboarding/index';
import { OnboardingProvider } from '@Onboarding/store';
import ExitButton from '@Onboarding/components/exit-button';
import TanStackRouterDevtools from '@/apps/admin-components/tanstack-router-dev-tools';

const OnboardingLayout = () => {
	const currentStepURL = useLocation( {
		select: ( location ) => location.pathname,
	} );
	const currentStep = ONBOARDING_STEPS_CONFIG.findIndex(
		( step ) => step.path === currentStepURL
	);
	const {
		config: { containerSize = 'sm' },
	} = ONBOARDING_STEPS_CONFIG[ currentStep ] || {
		config: { containerSize: 'sm' },
	};

	let containerClassNames;
	switch ( containerSize ) {
		case 'sm':
			containerClassNames = 'max-w-onboarding-container-1';
			break;
		case 'md':
			containerClassNames = 'max-w-onboarding-container-2 p-7';
			break;
		case 'lg':
			containerClassNames = 'max-w-onboarding-container-3 p-8';
			break;
		default:
			containerClassNames = 'max-w-onboarding-container-1';
			break;
	}

	return (
		<>
			<Toaster />
			<OnboardingProvider>
				<div className="grid grid-cols-1 grid-rows-[3.5rem_1fr] w-full h-full">
					{ /* Topbar */ }
					<Topbar
						className={ cn( 'z-[1] p-4 min-h-14 bg-transparent' ) }
					>
						<Topbar.Left>
							<Topbar.Item>
								<SureRankFullLogo className="w-[127px] h-[20px]" />
							</Topbar.Item>
						</Topbar.Left>
						<Topbar.Middle
							align="center"
							className="w-full max-w-95 hidden md:flex"
						>
							<ProgressSteps
								currentStep={ currentStep + 1 }
								size="md"
								type="inline"
								variant="number"
								completedVariant="number"
							>
								{ Array.from( {
									length: ONBOARDING_STEPS_CONFIG.length - 1,
								} ).map( ( _, index ) => (
									<ProgressSteps.Step key={ index } />
								) ) }
							</ProgressSteps>
						</Topbar.Middle>
						<Topbar.Right>
							<ExitButton />
						</Topbar.Right>
					</Topbar>
					{ /* Content */ }
					<div className="flex flex-col items-center justify-start p-10">
						<div
							className={ cn(
								'w-full h-full max-w-onboarding-container mx-auto border border-border-subtle rounded-xl p-6 bg-background-primary shadow-sm',
								containerClassNames
							) }
						>
							<Outlet />
						</div>
					</div>
				</div>
			</OnboardingProvider>
			<TanStackRouterDevtools />
		</>
	);
};

export default OnboardingLayout;
