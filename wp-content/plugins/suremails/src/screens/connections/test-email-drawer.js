// TestEmailDrawer.js
import { useState, useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Drawer, Input, Select, Button, toast, Label } from '@bsf/force-ui';
import { sendTestEmail as apiSendTestEmail } from '@api/connections';
import { LoaderCircle as LoaderIcon } from 'lucide-react';
import { z } from 'zod';
import { useQueryClient } from '@tanstack/react-query';

// Define validation schema
const testEmailSchema = z.object( {
	selectedConnection: z.object( {
		id: z
			.string()
			.min( 1, __( 'Please select a sender email', 'suremails' ) ),
		email: z.string().email( __( 'Invalid sender email', 'suremails' ) ),
		type: z.string(),
		connection_title: z.string(),
	} ),
	send_to_email: z
		.string()
		.email( __( 'Please enter a valid recipient email', 'suremails' ) ),
} );

const getConnectionLabel = ( connection ) => {
	return connection?.email
		? `${ connection.email } (${ connection.connection_title } - ${ connection.type })`
		: __( 'Select From Email', 'suremails' );
};

const TestEmailDrawer = ( {
	isOpen,
	onClose,
	connections,
	currentConnection,
} ) => {
	const queryClient = useQueryClient();

	const [ formState, setFormState ] = useState( {
		selectedConnection: {
			id: currentConnection?.id || '',
			email: currentConnection?.from_email || '',
			type: currentConnection?.type || '',
			connection_title: currentConnection?.connection_title || '',
		},
		send_to_email: window.suremails?.userEmail || '',
	} );
	const [ errors, setErrors ] = useState( {} );
	const [ isLoading, setIsLoading ] = useState( false );

	// Add refs for focusable fields.
	const emailToRef = useRef( null );

	// Update form state when current connection changes
	useEffect( () => {
		setFormState( ( prevState ) => ( {
			...prevState,
			selectedConnection: {
				id: currentConnection?.id || '',
				email: currentConnection?.from_email || '',
				type: currentConnection?.type || '',
				connection_title: currentConnection?.connection_title || '',
			},
			send_to_email:
				prevState.send_to_email || window.suremails?.userEmail,
		} ) );
	}, [ currentConnection ] );

	const handleChange = ( field, value ) => {
		setFormState( ( prevState ) => ( {
			...prevState,
			[ field ]: value,
		} ) );
		// Clear error for the field when it changes
		setErrors( ( prev ) => ( {
			...prev,
			[ field ]: undefined,
		} ) );
	};

	const connectionOptions = Object.entries( connections || {} ).map(
		( [ key, connection ] ) => ( {
			label: `${ connection.from_email } (${ connection.connection_title } - ${ connection.type })`,
			value: {
				id: key,
				email: connection.from_email,
				type: connection.type,
				connection_title: connection.connection_title,
			},
		} )
	);

	const validateForm = () => {
		try {
			testEmailSchema.parse( formState );
			setErrors( {} );
			return true;
		} catch ( error ) {
			const formattedErrors = {};
			error.errors.forEach( ( err ) => {
				const path = err.path.join( '.' );
				formattedErrors[ path ] = err.message;
			} );
			setErrors( formattedErrors );

			// Focus the first field with error.
			if ( error.errors[ 0 ]?.path[ 0 ] === 'send_to_email' ) {
				emailToRef.current?.focus();
			}

			return false;
		}
	};

	// Validate input field on blur.
	const handleValidateInputOnBlur = ( event ) => {
		if ( ! event?.target ) {
			return;
		}
		const field = event.target?.name;
		try {
			testEmailSchema.pick( { [ field ]: true } ).parse( {
				[ field ]: formState[ field ],
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

	const handleSubmit = async ( event ) => {
		event.preventDefault();

		if ( ! validateForm() ) {
			return;
		}

		const { selectedConnection, send_to_email } = formState;

		setIsLoading( true );

		try {
			const response = await apiSendTestEmail( {
				from_email: selectedConnection.email,
				to_email: send_to_email,
				type: selectedConnection.type,
				id: selectedConnection.id,
			} );

			if ( response.success ) {
				toast.success( __( 'Sent!', 'suremails' ), {
					description: __(
						'Test email sent successfully.',
						'suremails'
					),
				} );
			} else {
				toast.error( __( 'Failed!', 'suremails' ), {
					description:
						response.message ||
						__( 'Failed to send test email.', 'suremails' ),
				} );
			}
		} catch ( error ) {
			toast.error( __( 'Failed!', 'suremails' ), {
				description:
					error.message ||
					__(
						'An unexpected error occurred while sending the test email.',
						'suremails'
					),
			} );
		} finally {
			setIsLoading( false );
			// Refetch logs
			queryClient.refetchQueries( {
				queryKey: [ 'logs' ],
			} );
			// Refetch dashboard data
			queryClient.refetchQueries( {
				queryKey: [ 'dashboard-data' ],
			} );
		}
	};

	return (
		<Drawer
			design="footer-bordered"
			exitOnEsc
			position="right"
			scrollLock
			exitOnClickOutside
			transitionDuration={ 0.2 }
			open={ isOpen }
			setOpen={ onClose }
			onClose={ onClose }
			className="z-999999"
		>
			<Drawer.Panel className="w-[556px]">
				<form
					className="h-full flex flex-col"
					onSubmit={ handleSubmit }
					noValidate
				>
					<Drawer.Header>
						<div className="flex items-center justify-between text-text-primary">
							<Drawer.Title>
								{ __( 'Send Test Email', 'suremails' ) }
							</Drawer.Title>
							<Drawer.CloseButton type="button" />
						</div>
						<Drawer.Description>
							{ __(
								'Send a test email to verify your connection.',
								'suremails'
							) }
						</Drawer.Description>
					</Drawer.Header>
					<Drawer.Body className="overflow-x-hidden">
						<div className="space-y-6">
							<div className="flex flex-col gap-1.5">
								<Label size="sm" className="w-full">
									{ __( 'Email From', 'suremails' ) }
								</Label>
								<Select
									value={ formState.selectedConnection }
									onChange={ ( value ) => {
										const selectedOption =
											connectionOptions.find(
												( option ) =>
													option.value.id === value.id
											);
										handleChange(
											'selectedConnection',
											selectedOption?.value || {}
										);
									} }
									by="id"
									className="w-full h-[40px]"
								>
									<Select.Button type="button">
										{ getConnectionLabel(
											formState.selectedConnection
										) }
									</Select.Button>
									<Select.Options className="text-black bg-background-primary z-999999">
										{ connectionOptions.map( ( option ) => (
											<Select.Option
												key={ option.value.id }
												value={ option.value }
												selected={
													option.value.id ===
													formState.selectedConnection
														.id
												}
											>
												{ option.label }
											</Select.Option>
										) ) }
									</Select.Options>
								</Select>
								<Label tag="p" size="sm" variant="help">
									{ __(
										'Choose the sender email for this test.',
										'suremails'
									) }
								</Label>
							</div>

							<div className="flex flex-col gap-1.5">
								<Input
									ref={ emailToRef }
									type="email"
									name="send_to_email"
									value={ formState.send_to_email }
									onChange={ ( value ) =>
										handleChange( 'send_to_email', value )
									}
									placeholder={ __(
										'Enter recipient email',
										'suremails'
									) }
									className="w-full"
									required
									size="md"
									label={ __( 'Email Send To', 'suremails' ) }
									error={ errors.send_to_email }
									onBlur={ handleValidateInputOnBlur }
								/>
								{ errors.send_to_email && (
									<p className="text-text-error text-sm">
										{ errors.send_to_email }
									</p>
								) }
								<Label size="p" variant="help" tag="span">
									{ __(
										'Provide the recipient email address for this test.',
										'suremails'
									) }
								</Label>
							</div>
						</div>
					</Drawer.Body>
					<Drawer.Footer>
						<Button
							onClick={ onClose }
							variant="outline"
							className="mr-2"
							disabled={ isLoading }
							type="button"
						>
							{ __( 'Cancel', 'suremails' ) }
						</Button>
						<Button
							variant="primary"
							loading={ isLoading }
							icon={
								isLoading ? (
									<LoaderIcon
										className="mr-2 animate-spin"
										aria-hidden="true"
									/>
								) : null
							}
							type="submit"
							className="font-medium"
							size="sm"
						>
							{ isLoading
								? __( 'Testing…', 'suremails' )
								: __( 'Send Test Email', 'suremails' ) }
						</Button>
					</Drawer.Footer>
				</form>
			</Drawer.Panel>
			<Drawer.Backdrop />
		</Drawer>
	);
};

export default TestEmailDrawer;
