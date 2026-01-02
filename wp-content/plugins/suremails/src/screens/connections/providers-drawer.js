// ProvidersDrawer.js
import { useState, useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Drawer, Button, toast } from '@bsf/force-ui';
import {
	LoaderCircle as LoaderIcon,
	ChevronLeft as ChevronLeftIcon,
} from 'lucide-react';
import ProviderList from '@screens/connections/provider-list';
import { testAndSaveEmailConnection as apiTestAndSaveEmailConnection } from '@api/connections';
import { useMemo } from 'react';
import ProvidersSkeleton from './providers-skeleton';
import ExtendedDynamicForm from './extended-dynamic-form';
import { isOAuthProvider } from '@oauth/oauth-providers';

const ProvidersDrawer = ( {
	isOpen,
	setIsOpen,
	currentConnection = {},
	onSave,
	providers: providersList = [],
	isProvidersLoading = false,
	sequenceId = 1,
	connectionCount = {},
} ) => {
	const [ selectedProvider, setSelectedProvider ] = useState(
		currentConnection ? currentConnection.type : ''
	);
	const [ formData, setFormData ] = useState( currentConnection );
	const [ errors, setErrors ] = useState( {} );

	const [ isLoading, setIsLoading ] = useState( false );
	// Add form refs
	const formRef = useRef( null );

	// Get the title postfix for the selected provider
	const titlePostfix = ( connectionCount[ selectedProvider ] || 0 ) + 1;

	// Get the fields for the selected provider
	const selectedProviderData = useMemo( () => {
		return providersList.find(
			( provider ) => provider.value === selectedProvider
		);
	}, [ selectedProvider, providersList, currentConnection ] );
	const fields = selectedProviderData?.fields;

	const defaultValues = useMemo( () => {
		return selectedProviderData?.fields.reduce( ( acc, field ) => {
			acc[ field.name ] = field.default;
			return acc;
		}, {} );
	}, [ selectedProviderData, currentConnection ] );

	useEffect( () => {
		if ( currentConnection?.type ) {
			setSelectedProvider( currentConnection.type );

			setFormData( ( prevData ) => ( {
				...prevData,
				...currentConnection,
			} ) );
		} else {
			setSelectedProvider( null );
			setFormData( null );
		}
	}, [ currentConnection ] );

	useEffect( () => {
		if ( ! formData || Object.keys( formData ).length === 0 ) {
			const config = selectedProviderData;
			if ( ! config ) {
				return;
			}

			// Get the current count for the selected provider and add 1 for priority
			const defaultTitle =
				titlePostfix === 1
					? config.title
					: `${ config.title } (${ titlePostfix - 1 })`;
			setFormData( {
				...defaultValues,
				connection_title: defaultTitle,
				priority: sequenceId,
			} );
		}
	}, [ selectedProvider, formData, currentConnection ] );

	const handleProviderSelect = ( provider ) => {
		setSelectedProvider( provider );
		const config = selectedProviderData;
		if ( ! config ) {
			return;
		}

		// Get the current count for the selected provider and add 1 for priority
		const defaultTitle =
			titlePostfix === 1
				? config.title
				: `${ config.title } (${ titlePostfix - 1 })`;
		setFormData( {
			...defaultValues,
			connection_title: defaultTitle,
			priority: sequenceId,
		} );
		setErrors( {} );
	};

	// On blur, validate the input.
	const handleOnBlurValidation = ( event ) => {
		if ( ! event.target ) {
			return;
		}

		const field = event.target.name;
		const config = selectedProviderData;
		if ( ! config ) {
			return;
		}

		const { schema } = config;

		try {
			schema.pick( { [ field ]: true } ).parse( {
				[ field ]: formData[ field ],
			} );
			setErrors( ( prev ) => ( {
				...prev,
				[ field ]: undefined,
			} ) );
		} catch ( error ) {
			setErrors( ( prev ) => ( {
				...prev,
				[ field ]: error.errors[ 0 ].message,
			} ) );
		}
	};

	const handleSetOpenDrawer = ( value ) => {
		setIsOpen( value );
		if ( ! value ) {
			setSelectedProvider( '' );
			setErrors( {} );
			setFormData( {} );
		}
	};

	const validateForm = () => {
		const config = selectedProviderData;
		if ( ! config ) {
			return false;
		}

		const { schema } = config;

		try {
			schema.parse( formData );
			setErrors( {} );
			return true;
		} catch ( error ) {
			const formattedErrors = {};
			error.errors.forEach( ( err ) => {
				formattedErrors[ err.path[ 0 ] ] = err.message;
			} );
			setErrors( formattedErrors );

			// Focus the first input with error
			const firstErrorField = error.errors[ 0 ]?.path[ 0 ];
			const firstErrorInput = formRef.current?.querySelector(
				`input[name="${ firstErrorField }"]`
			);
			firstErrorInput?.focus();

			return false;
		}
	};

	const resetProviderState = () => {
		setSelectedProvider( null );
		setFormData( null );
		setErrors( {} );
	};

	const hasChanges =
		JSON.stringify( formData ) !== JSON.stringify( currentConnection ) ||
		formData?.force_save === true;
	const handleSaveChanges = async () => {
		if ( ! hasChanges ) {
			toast.info( __( 'No changes to save.', 'suremails' ) );
			return;
		}

		if ( ! validateForm() ) {
			return;
		}

		const payload = {
			settings: formData,
			provider: selectedProvider.toUpperCase(),
		};
		setIsLoading( true );

		try {
			const response = await apiTestAndSaveEmailConnection( payload );

			if ( response?.success ) {
				toast.success( __( 'Saved successfully!', 'suremails' ), {
					description: __(
						'Connection details saved successfully!',
						'suremails'
					),
				} );
				setIsOpen( false ); // Close drawer on success
				onSave( response.connection );
				resetProviderState();
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
		} finally {
			setIsLoading( false );
		}
	};

	/**
	 * Captures form data from provider-specific forms.
	 *
	 * @param {Object} data - The form data.
	 */
	const handleFormSubmit = ( data ) => {
		const [ field, value ] = Object.entries( data )[ 0 ];
		setFormData( ( prev ) => ( {
			...prev,
			[ field ]: value,
		} ) );

		// Clear error only for the field being changed
		setErrors( ( prev ) => ( {
			...prev,
			[ field ]: undefined,
		} ) );
	};

	const handleClickAuthenticate = ( provider, formStateValues ) => {
		const timestampOffset = 5 * 60 * 1000;
		const providerLower = provider?.toLowerCase();

		if ( isOAuthProvider( providerLower ) ) {
			localStorage.setItem(
				'formStateValues',
				JSON.stringify( {
					...formStateValues,
				} )
			);
			localStorage.setItem(
				'formStateValuesTimestamp',
				Date.now() + timestampOffset
			);
		}
	};

	// Define drawer title and description based on selected provider
	const title = selectedProvider
		? __( 'Connection Details', 'suremails' )
		: __( 'New Connection', 'suremails' );
	const description = selectedProvider
		? selectedProviderData?.description ??
		  __(
				'Enter the details below to connect with your {providerName} account.',
				'suremails'
		  ).replace(
				'{providerName}',
				selectedProviderData?.display_name || selectedProvider
		  )
		: __(
				'Pick an email provider to ensure your WordPress emails are delivered securely and reliably.',
				'suremails'
		  );

	return (
		<Drawer
			design="footer-bordered"
			exitOnEsc
			position="right"
			scrollLock
			transitionDuration={ 0.2 }
			open={ isOpen }
			setOpen={ handleSetOpenDrawer }
			className="z-999999"
		>
			<Drawer.Backdrop />
			<form ref={ formRef } noValidate>
				<Drawer.Panel className="w-[34.75rem]">
					<Drawer.Header>
						<div className="flex items-center justify-between text-text-primary">
							<Drawer.Title>{ title }</Drawer.Title>
							<Drawer.CloseButton
								type="button"
								onClick={ () => {
									resetProviderState();
									setIsOpen( false );
								} }
							/>
						</div>
						<Drawer.Description className="text-sm font-normal text-text-secondary">
							{ description }
						</Drawer.Description>
					</Drawer.Header>
					<Drawer.Body className="overflow-x-hidden">
						{ /* Form when a provider is selected */ }
						{ ! isProvidersLoading && selectedProvider && (
							<div>
								<ExtendedDynamicForm
									fields={ fields }
									onChange={ handleFormSubmit }
									connectionData={ formData }
									errors={ errors }
									inlineValidator={ handleOnBlurValidation }
									onClickAuthenticate={
										handleClickAuthenticate
									}
									providerOptions={ selectedProviderData }
								/>
							</div>
						) }

						{ /* Provider List when no provider is selected */ }
						{ ! isProvidersLoading && ! selectedProvider && (
							<ProviderList
								onSelectProvider={ handleProviderSelect }
								providers={ providersList }
							/>
						) }

						{ /* Skeleton for loading state */ }
						{ isProvidersLoading && <ProvidersSkeleton /> }
					</Drawer.Body>
					{ selectedProvider && (
						<Drawer.Footer>
							<Button
								onClick={ () => {
									setSelectedProvider( null );
									setFormData( null );
									setErrors( {} );
								} }
								variant="outline"
								icon={ <ChevronLeftIcon /> }
								size="sm"
								iconPosition="left"
								className="font-medium"
								type="button"
							>
								{ __( 'Back', 'suremails' ) }
							</Button>
							<Button
								variant="primary"
								loading={ isLoading }
								icon={
									isLoading ? (
										<LoaderIcon className="animate-spin" />
									) : null
								}
								onClick={ handleSaveChanges }
								className="font-medium"
								size="sm"
								type="button"
							>
								{ isLoading
									? __( 'Testing…', 'suremails' )
									: __( 'Save Changes', 'suremails' ) }
							</Button>
						</Drawer.Footer>
					) }
				</Drawer.Panel>
			</form>
		</Drawer>
	);
};

export default ProvidersDrawer;
