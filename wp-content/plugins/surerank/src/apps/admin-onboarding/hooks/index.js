import { ONBOARDING_STEPS_CONFIG } from '@Onboarding/index';
import { useLocation, useNavigate } from '@tanstack/react-router';

/**
 * The hook will return next step url based on the current step and previous step URL.
 * If there's no previous step, it will return null and if it's the last step, it will return the last step url.
 *
 * @return {Object} - An object containing the next step and previous step.
 */
const useNavigateStep = () => {
	const currentStepURL = useLocation( {
		select: ( location ) => location.pathname,
	} );
	const navigate = useNavigate();
	const currentStep = ONBOARDING_STEPS_CONFIG.findIndex(
		( item ) => item.path === currentStepURL
	);
	const nextStepObject = ONBOARDING_STEPS_CONFIG[ currentStep + 1 ] ?? null;
	const previousStepObject =
		ONBOARDING_STEPS_CONFIG[ currentStep - 1 ] ?? null;

	const handleClick = ( step ) => () => {
		if ( ! step ) {
			return;
		}

		navigate( { to: step.path } );
	};

	const nextStep = handleClick( nextStepObject );
	const previousStep = handleClick( previousStepObject );

	return Object.freeze( {
		nextStep,
		previousStep,
		nextStepPath: nextStepObject?.path ?? null,
		previousStepPath: previousStepObject?.path ?? null,
		currentStepConfig: ONBOARDING_STEPS_CONFIG[ currentStep ]?.config ?? {},
		nextStepConfig: nextStepObject?.config ?? {},
		previousStepConfig: previousStepObject?.config ?? {},
	} );
};

export { useNavigateStep };
