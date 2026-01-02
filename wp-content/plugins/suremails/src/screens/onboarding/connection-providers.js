import { useEffect, useMemo, useRef, useCallback } from '@wordpress/element';
import { RadioButton, toast, Skeleton, Container } from '@bsf/force-ui';
import { __, sprintf } from '@wordpress/i18n';
import useProviders from '@screens/connections/use-dynamic-providers';
import { cn } from '@utils/utils';
import { useOnboardingState } from './onboarding-state';
import NavigationButtons from './navigation-buttons';
import {
	useConnectionTitleAndSequence,
	useFormValidation,
	useOnboardingNavigation,
} from './hooks';
import { Header } from './components';
import ExtendedDynamicForm from '@screens/connections/extended-dynamic-form';
import { testAndSaveEmailConnection } from '@api/connections';

const useSelectedConnection = () => {
	const { providers } = useProviders();
	const [ { connection } ] = useOnboardingState();

	return providers.find( ( provider ) => provider.value === connection );
};

const ConnectionForm = ( {
	onBlurValidation,
	handleChange,
	formFields,
	errors,
	connection,
	formData,
} ) => {
	if ( ! connection ) {
		return null;
	}

	return (
		<>
			<Header
				title={ sprintf(
					// translators: %s is the provider name.
					__( "Now, Let's Connect With %s", 'suremails' ),
					connection?.display_name
				) }
				description={ sprintf(
					// translators: %s is the provider name.
					__(
						'Enter the details below to connect with your %s account.',
						'suremails'
					),
					connection?.display_name
				) }
			/>
			<ExtendedDynamicForm
				connectionData={ formData ?? {} }
				fields={ formFields ?? {} }
				onChange={ handleChange }
				errors={ errors ?? {} }
				inlineValidator={ onBlurValidation }
			/>
		</>
	);
};

const ConnectionListSkeleton = ( { count = 16 } ) => {
	return (
		<Container
			containerType="grid"
			className="gap-1 p-2"
			cols={ { sm: 1, md: 2 } }
		>
			{ Array.from( { length: count } ).map( ( _, index ) => (
				<Skeleton key={ index } className="h-12 w-full rounded-lg" />
			) ) }
		</Container>
	);
};

const ConnectionProviderList = () => {
	const [ { connection, connectionSaved }, setState ] = useOnboardingState();
	const { providers, isLoading } = useProviders();
	const toastRef = useRef( false );

	const handleProviderChange = ( value ) => {
		// Find the selected option from the data
		const selectedOption = providers.find(
			( option ) => option.value === value
		);

		// Check if the selected option has a 'badge' (i.e., "Soon")
		if ( selectedOption && selectedOption.badge ) {
			// Prevent multiple toasts by checking the ref
			if ( ! toastRef.current ) {
				const prerequisiteMessage = selectedOption.prerequisite ? (
					selectedOption.prerequisite
				) : (
					<span
						dangerouslySetInnerHTML={ {
							__html: sprintf(
								// translators: %1$s is anchor oneping tag and %2$s is the anchor closing tag.
								__(
									"This provider isn't compatible. For help, contact us %1$shere%2$s.",
									'suremails'
								),
								'<a href="' + suremails.supportURL + '">',
								'</a>'
							),
						} }
					></span>
				);
				toast.info(
					selectedOption.provider_type === 'not_compatible'
						? prerequisiteMessage
						: __( 'This provider is coming soon!', 'suremails' )
				);
				toastRef.current = true;
				setTimeout( () => {
					toastRef.current = false;
				}, 500 );
			}
			return; // Do nothing if the option is marked as "Soon"
		}

		// Proceed if the option does not have a 'badge'
		setState( {
			connection: selectedOption?.value,
			// If the selected option is different from the saved connection, update the saved connection
			...( connectionSaved !== selectedOption?.value
				? { connectionSaved: null, connectionFormData: null }
				: { connectionSaved } ),
		} );
	};

	let renderContent = null;
	if ( connection ) {
		return null;
	}

	// Loading state and skeleton
	if ( isLoading ) {
		renderContent = <ConnectionListSkeleton />;
	}

	// Data rendered
	if ( ! isLoading ) {
		renderContent = (
			<RadioButton.Group
				value={ connection || connectionSaved || '' }
				onChange={ handleProviderChange }
				columns={ 2 }
				className="p-2 rounded-lg bg-background-secondary gap-1"
			>
				{ providers.map( ( option ) => (
					<RadioButton.Button
						key={ option.value }
						value={ option.value }
						icon={ option.icon }
						badgeItem={ option.badge }
						size="md"
						inlineIcon
						buttonWrapperClasses={ cn(
							'bg-background-primary rounded-md shadow-sm items-center h-12 [&>label_p]:truncate',
							option.value === 'OUTLOOK' && '[&>label_p]:w-4/5'
						) }
						label={ {
							heading: option.display_name,
						} }
					/>
				) ) }
			</RadioButton.Group>
		);
	}

	return (
		<>
			<Header
				title={ __(
					'Select Your Primary Email Sending Service',
					'suremails'
				) }
				description={ __(
					'Pick an email provider to ensure your WordPress emails are delivered securely and reliably.',
					'suremails'
				) }
			/>
			{ renderContent }
		</>
	);
};

const ConnectionProviders = () => {
	const [
		{
			connection,
			connectionFormData,
			connectionSaved,
			connectionErrors = {},
		},
		setState,
	] = useOnboardingState();
	const selectedConnection = useSelectedConnection();

	const { navigateToNextRoute, navigateToPreviousRoute } =
		useOnboardingNavigation();

	const formRef = useRef( null );

	const { titleSuffix, sequenceNumber } =
		useConnectionTitleAndSequence( selectedConnection );

	const handleBack = () => {
		if ( connection ) {
			setState( {
				connection: null,
			} );
			return;
		}
		navigateToPreviousRoute();
	};

	const handleChange = ( value ) => {
		const fieldName = Object.keys( value )[ 0 ] ?? '';

		setState( {
			connectionFormData: {
				...connectionFormData,
				...value,
			},
			// Clear the error for the field that was changed
			connectionErrors: {
				...connectionErrors,
				[ fieldName ]: undefined,
			},
		} );
	};

	const defaultValues = useMemo( () => {
		return selectedConnection?.fields.reduce( ( acc, field ) => {
			acc[ field.name ] = field.default;
			return acc;
		}, {} );
	}, [ selectedConnection ] );

	// Set the default values if the connection form data is not set
	useEffect( () => {
		if ( connectionFormData ) {
			return;
		}
		setState( {
			connectionFormData: {
				...defaultValues,
				connection_title: titleSuffix,
				priority: sequenceNumber,
			},
		} );
	}, [ selectedConnection, connectionSaved ] );

	const handleError = useCallback( ( errors ) => {
		setState( ( prev ) => ( {
			...prev,
			connectionErrors: {
				...prev.connectionErrors,
				...errors,
			},
		} ) );
	} );

	const { onBlurValidation, validateForm } = useFormValidation(
		formRef,
		connectionFormData,
		selectedConnection?.schema,
		handleError
	);

	const handleSaveChanges = async () => {
		if ( ! validateForm() ) {
			return;
		}

		const payload = {
			settings: connectionFormData,
			provider: connection?.toUpperCase(),
		};

		try {
			const response = await testAndSaveEmailConnection( payload );

			if ( response?.success ) {
				toast.success( __( 'Saved successfully!', 'suremails' ), {
					description: __(
						'Connection details saved successfully!',
						'suremails'
					),
				} );
				setState( ( prev ) => ( {
					...prev,
					connectionSaved: connection,
				} ) );
				navigateToNextRoute();
			} else {
				toast.error( __( 'Verification Failed!', 'suremails' ), {
					description: response.message,
					autoDismiss: false,
				} );
			}
		} catch ( error ) {
			toast.error( __( 'Verification Failed!', 'suremails' ), {
				description:
					error.message ||
					__(
						'An unexpected error occurred while testing the connection.',
						'suremails'
					),
				autoDismiss: false,
			} );
		}
	};

	const handleContinue = async () => {
		// If the connection is already saved and the connection is not selected, update the connection
		if ( connectionSaved && ! connection ) {
			setState( {
				connection: connectionSaved,
			} );
			return;
		}

		// If the connection is already saved and the connection is selected, navigate to the next route
		if ( connection && connectionSaved ) {
			navigateToNextRoute();
			return;
		}

		// If the connection is not saved, save the connection
		await handleSaveChanges();
	};

	return (
		<form ref={ formRef } className="space-y-6">
			<ConnectionProviderList />
			<ConnectionForm
				connection={ selectedConnection }
				formData={ connectionFormData }
				formFields={ selectedConnection?.fields }
				errors={ connectionErrors }
				onBlurValidation={ onBlurValidation }
				handleChange={ handleChange }
			/>
			<NavigationButtons
				backProps={ { onClick: handleBack } }
				continueProps={ {
					onClick: handleContinue,
					disabled: ! connection && ! connectionSaved,
				} }
			/>
		</form>
	);
};

export default ConnectionProviders;
