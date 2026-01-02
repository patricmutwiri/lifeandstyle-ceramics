import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, Loader, toast } from '@bsf/force-ui';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { cn } from '@Functions/utils';
import { useNavigateStep } from '@Onboarding/hooks';
import { useOnboardingState } from '@Onboarding/store';
import { ONBOARDING_STEPS_CONFIG } from '@Onboarding/index';

const BUTTON_PROPS = {
	back: {
		key: 'back',
		variant: 'outline',
		iconPosition: 'left',
		className: 'mr-auto',
		children: __( 'Back', 'surerank' ),
	},
	skip: {
		key: 'skip',
		variant: 'ghost',
		iconPosition: 'left',
		className: 'ml-auto text-text-tertiary hover:text-text-primary',
		children: __( 'Skip', 'surerank' ),
	},
	next: {
		key: 'next',
		variant: 'primary',
		iconPosition: 'right',
		type: 'submit',
		children: __( 'Next', 'surerank' ),
	},
};

const StepNavButtons = ( {
	backProps = {},
	skipProps = {},
	nextProps = {},
	className,
} ) => {
	const [ isLoading, setIsLoading ] = useState( {
		back: false,
		skip: false,
		next: false,
	} );
	const { nextStep, previousStep, currentStepConfig, nextStepPath } =
		useNavigateStep();
	const [ , dispatch ] = useOnboardingState();

	const handleClick =
		( callback, key, disableNavigation = false ) =>
		async () => {
			if ( typeof callback === 'function' ) {
				try {
					await callback( setIsLoading );
				} catch ( error ) {
					// Return if error is thrown from the callback.
					// Errors are mainly from form validation.
					if (
						'cause' in error &&
						error.cause === 'form-validation'
					) {
						return;
					}

					toast.error(
						error?.message ?? __( 'An error occurred', 'surerank' )
					);
					return;
				}
			}

			setIsLoading( {
				...isLoading,
				[ key ]: false,
			} );

			if ( ! previousStep || disableNavigation ) {
				return;
			}

			switch ( key ) {
				case 'back':
					previousStep();
					break;
				case 'skip':
					nextStep();
					break;
				case 'next':
					const lastStepPath = ONBOARDING_STEPS_CONFIG.at( -1 ).path;
					if ( nextStepPath === lastStepPath ) {
						dispatch( { currentStep: lastStepPath } );
					}
					nextStep();
					break;
			}
		};

	const getIcon = ( key ) => {
		if ( isLoading[ key ] ) {
			return <Loader />;
		}

		switch ( key ) {
			case 'back':
				return <ChevronLeft />;
			case 'next':
				return <ChevronRight />;
			default:
				return null;
		}
	};

	const getButtonProps = ( key, btnProps ) => {
		const baseProps = {
			type: 'button',
			icon: getIcon( key ),
		};

		const propsConfig = BUTTON_PROPS[ key ];

		const { onClick, disableNavigation = false, ...rest } = btnProps;
		const onClickHandler = handleClick( onClick, key, disableNavigation );
		const classNames = cn( propsConfig?.className, btnProps?.className );

		return {
			...baseProps,
			...propsConfig,
			onClick: onClickHandler,
			disabled: isLoading[ key ],
			loading: isLoading[ key ],
			...rest,
			className: classNames,
		};
	};

	return (
		<div className={ cn( 'w-full flex gap-3 my-2', className ) }>
			{ ! currentStepConfig?.hideBackButton && (
				<Button { ...getButtonProps( 'back', backProps ) } />
			) }
			{ ! currentStepConfig?.hideSkipButton && (
				<Button { ...getButtonProps( 'skip', skipProps ) } />
			) }
			{ ! currentStepConfig?.hideNextButton && (
				<Button { ...getButtonProps( 'next', nextProps ) } />
			) }
		</div>
	);
};

export default StepNavButtons;
