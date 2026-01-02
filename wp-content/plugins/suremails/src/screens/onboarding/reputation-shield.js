import React, { useRef, useState } from 'react';
import { Divider, Header } from './components';
import { __, sprintf } from '@wordpress/i18n';
import { Container, Input, Label, Switch, toast } from '@bsf/force-ui';
import { cn } from '@utils/utils';
import NavigationButtons from './navigation-buttons';
import { useOnboardingNavigation, useFormValidation } from './hooks';
import { useOnboardingState } from './onboarding-state';
import { ChevronRight, Sparkles } from 'lucide-react';
import { z } from 'zod';
import { activateContentGuard, saveUserDetails } from '@api/settings';

// Constants for the component
const INITIAL_FORM_STATE = {
	first_name: '',
	last_name: '',
	email: '',
};

// Form validation schema using Zod
const validationSchema = z.object( {
	first_name: z
		.string()
		.min( 1, __( 'Please enter first name', 'suremails' ) ),
	last_name: z.string().min( 1, __( 'Please enter last name', 'suremails' ) ),
	email: z
		.string()
		.email( __( 'Please enter a valid email address', 'suremails' ) ),
} );

const formFields = [
	{
		label: __( 'First Name', 'suremails' ),
		name: 'first_name',
		type: 'text',
		placeholder: __( 'First name', 'suremails' ),
		width: '1/2',
	},
	{
		label: __( 'Last Name', 'suremails' ),
		name: 'last_name',
		type: 'text',
		placeholder: __( 'Last name', 'suremails' ),
		width: '1/2',
	},
	{
		label: __( 'Email', 'suremails' ),
		name: 'email',
		type: 'email',
		placeholder: __( 'Enter Email Address', 'suremails' ),
		width: 'full',
	},
];

const SafeGuardForm = ( {
	formData,
	errors,
	isLoading,
	handleChange,
	handleValidation,
} ) => {
	const [ { safeGuard } ] = useOnboardingState();

	if ( ! safeGuard?.showLeadForm ) {
		return null;
	}

	return (
		<>
			<Container gap="lg" wrap="wrap">
				{ formFields.map( ( field ) => (
					<div
						key={ field.name }
						className={ cn(
							'w-full space-y-1.5',
							field.width === 'full' && 'w-full',
							field.width === '1/2' && 'w-2/5 flex-1'
						) }
					>
						<Label htmlFor={ field.name }>{ field.label }</Label>
						<Input
							id={ field.name }
							name={ field.name }
							size="md"
							type={ field.type }
							placeholder={ field.placeholder }
							autoComplete="off"
							value={ formData[ field.name ] }
							onChange={ handleChange( field.name ) }
							error={ errors[ field.name ] }
							disabled={ isLoading }
							onBlur={ () => handleValidation( field.name ) }
						/>
						{ errors[ field.name ] && (
							<p className="text-text-error text-sm mt-1.5">
								{ errors[ field.name ] }
							</p>
						) }
					</div>
				) ) }
			</Container>
		</>
	);
};

const SafeGuardActivation = ( { handleActivateContentGuard } ) => {
	const [ { safeGuard } ] = useOnboardingState();

	if ( safeGuard?.showLeadForm ) {
		return null;
	}

	return (
		<>
			<div>
				<Switch
					id="reputation-shield-activation"
					label={ {
						heading: __( 'Reputation Shield', 'suremails' ),
						description: __(
							'Reputation Shield identifies potentially problematic content in your emails and blocks them from being sent to your SMTP service.',
							'suremails'
						),
					} }
					value={ safeGuard.activation }
					onChange={ handleActivateContentGuard }
				/>
			</div>
		</>
	);
};

const SafeGuard = () => {
	const [ { safeGuard }, setState ] = useOnboardingState();
	const { navigateToNextRoute, navigateToPreviousRoute } =
		useOnboardingNavigation();
	const [ isLoading, setIsLoading ] = useState( false );
	const [ formData, setFormData ] = useState( INITIAL_FORM_STATE );
	const [ errors, setErrors ] = useState( {} );
	const formRef = useRef( null );

	// Use the form validation hook
	const { onBlurValidation, validateForm } = useFormValidation(
		formRef,
		formData,
		validationSchema,
		( newErrors ) => {
			setErrors( ( prevErrors ) => ( {
				...prevErrors,
				...newErrors,
			} ) );
		}
	);

	// Handles form field changes
	const handleChange = ( name ) => ( value ) => {
		if ( errors[ name ] ) {
			setErrors( ( prev ) => ( {
				...prev,
				[ name ]: undefined,
			} ) );
		}
		setFormData( ( prev ) => ( {
			...prev,
			[ name ]: value,
		} ) );
	};

	// Wrapper for field validation on blur
	const handleValidation = ( name ) => {
		// Create a mock event object with target.name to use with onBlurValidation
		onBlurValidation( { target: { name } } );
	};

	// Handles content guard activation
	const handleActivateContentGuard = async ( value ) => {
		try {
			const response = await activateContentGuard();
			if ( response.success ) {
				setState( {
					safeGuard: {
						...safeGuard,
						activation: value,
					},
				} );
				toast.success(
					sprintf(
						// translators: %1$s is the status of the Content Guard.
						__( 'Reputation Shield %s successfully', 'suremails' ),
						value
							? __( 'activated', 'suremails' )
							: __( 'deactivated', 'suremails' )
					)
				);
				if ( window.suremails ) {
					window.suremails.contentGuardActiveStatus = value
						? 'yes'
						: 'no';
				}
			}
		} catch ( error ) {
			toast.error(
				error.message ||
					__( 'Error authenticating Reputation Shield', 'suremails' )
			);
		}
	};

	// Handles saving user details and activating content guard
	const handleSaveUserDetailsAndActivate = async ( skip = false ) => {
		if ( isLoading ) {
			return;
		}

		if ( ! skip ) {
			// Use the validateForm function from the hook
			const isValid = validateForm();
			if ( ! isValid ) {
				return;
			}
		}

		setIsLoading( true );
		try {
			await saveUserDetails( {
				...formData,
				skip: skip ? 'yes' : 'no',
			} );
			const response = await activateContentGuard();
			if ( response.success ) {
				toast.success(
					__( 'Reputation Shield activated', 'suremails' ),
					{
						description: __(
							'Reputation Shield activated successfully',
							'suremails'
						),
					}
				);
				setState( {
					safeGuard: {
						...safeGuard,
						activation: true,
						showLeadForm: false,
					},
				} );
				// Set the localized variable content guard popup status to false
				if ( window.suremails ) {
					window.suremails.contentGuardPopupStatus = false;
					window.suremails.contentGuardActiveStatus = 'yes';
				}
			}
		} catch ( error ) {
			toast.error(
				error.message ||
					__( 'Error Activating Reputation Shield', 'suremails' )
			);
		} finally {
			setIsLoading( false );
		}
	};

	// Handles the main activation flow
	const handleActivation = async () => {
		if ( ! safeGuard?.activation && safeGuard?.showLeadForm ) {
			// If not activated, initiate the save user details and activate process
			await handleSaveUserDetailsAndActivate( false );
			return;
		}

		// If already activated, navigate to next step
		navigateToNextRoute();
	};

	// Get the appropriate icon for the continue button based on loading state and activation state
	const isFirstTimeActivation =
		! safeGuard?.activation && safeGuard?.showLeadForm;
	const getButtonIcon = () => {
		if ( ! isFirstTimeActivation ) {
			return <ChevronRight />;
		}

		return <Sparkles />;
	};

	return (
		<form
			className="space-y-6"
			ref={ formRef }
			onSubmit={ ( event ) => {
				event.preventDefault();
				handleActivation();
			} }
		>
			<Header
				title={ __(
					'Safeguard Your Email with Reputation Shield',
					'suremails'
				) }
				{ ...( safeGuard.showLeadForm && {
					description: __(
						'Reputation Shield validates your emails with AI for harmful and inappropriate content before they are processed. If an email contains problematic material, it is blocked before it reaches your SMTP provider.',
						'suremails'
					),
				} ) }
			/>

			<SafeGuardForm
				formData={ formData }
				errors={ errors }
				isLoading={ isLoading }
				handleChange={ handleChange }
				handleValidation={ handleValidation }
			/>

			<SafeGuardActivation
				handleActivateContentGuard={ handleActivateContentGuard }
			/>

			<Divider />

			<NavigationButtons
				backProps={ { onClick: navigateToPreviousRoute } }
				continueProps={ {
					onClick: handleActivation,
					text: isFirstTimeActivation
						? __( 'Activate Reputation Shield', 'suremails' )
						: __( 'Continue Setup', 'suremails' ),
					icon: getButtonIcon(),
					iconPosition: ! isFirstTimeActivation ? 'right' : 'left',
				} }
				skipProps={ {
					onClick: navigateToNextRoute,
					text: __( 'Skip', 'suremails' ),
				} }
			/>
		</form>
	);
};

export default SafeGuard;
