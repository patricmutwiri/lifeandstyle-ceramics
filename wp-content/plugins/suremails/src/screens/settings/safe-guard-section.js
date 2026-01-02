import { renderToString, useRef, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { Button, Dialog, Input, Loader, Switch, toast } from '@bsf/force-ui';
import { Shield, Sparkles, Check } from 'lucide-react';
import { activateContentGuard, saveUserDetails } from '@api/settings';
import { z } from 'zod';
import { cn } from '@utils/utils';
import Title from '@components/title/title';

// Constants moved to top level
const INITIAL_FORM_STATE = {
	first_name: '',
	last_name: '',
	email: '',
};

const INITIAL_LOADING_STATE = {
	activateContentGuard: false,
	skipActivateContentGuard: false,
};

const inputFields = [
	{
		label: __( 'First Name', 'suremails' ),
		name: 'first_name',
		type: 'text',
	},
	{
		label: __( 'Last Name', 'suremails' ),
		name: 'last_name',
		type: 'text',
	},
	{
		label: __( 'Email Address', 'suremails' ),
		name: 'email',
		type: 'email',
	},
];

const check_points = [
	__(
		'Better Email Deliverability – Avoid getting flagged by SMTP service providers.',
		'suremails'
	),
	__(
		'Protect Your Sender Reputation – Maintain a high sender score and keep your emails trusted.',
		'suremails'
	),
	__(
		'Stay Compliant – Prevent emails getting blacklists and avoid policy violations.',
		'suremails'
	),
];

const validationSchema = z.object( {
	first_name: z
		.string()
		.min( 1, __( 'Please enter first name', 'suremails' ) ),
	last_name: z.string().min( 1, __( 'Please enter last name', 'suremails' ) ),
	email: z
		.string()
		.email( __( 'Please enter a valid email address', 'suremails' ) ),
} );

// Separate components for better organization
const ContentGuardHeader = () => (
	<div className="space-y-1.5">
		<Shield className="w-6 h-6 text-icon-primary" strokeWidth="1.25" />
		<div className="space-y-1">
			<Title
				tag="h2"
				title={ __(
					'Safeguard Your Email with Reputation Shield',
					'suremails'
				) }
			/>
			{ window?.suremails?.contentGuardPopupStatus && (
				<>
					<p>
						{ __(
							'Reputation Shield validates your emails with AI for harmful and inappropriate content before they are processed. If an email contains problematic material, it is blocked before it reaches your SMTP provider.',
							'suremails'
						) }
					</p>

					{ check_points.length > 0 && (
						<ul className="text-sm font-normal list-none text-text-secondary leading-5 space-y-2 py-1">
							{ check_points.map( ( point, index ) => (
								<li
									key={ index }
									className="flex items-start gap-1.5"
								>
									<Check className="w-3 h-3 mt-1 text-icon-interactive" />
									<span>{ point }</span>
								</li>
							) ) }
						</ul>
					) }
				</>
			) }
		</div>
	</div>
);

const UserDetailsFormDialog = ( {
	open,
	setOpen,
	formRef,
	formData,
	errors,
	isLoading,
	handleChange,
	handleSaveUserDetailsAndActivate,
	handleValidation,
} ) => (
	<Dialog
		design="simple"
		exitOnEsc
		scrollLock
		open={ open }
		setOpen={ setOpen }
	>
		<Dialog.Backdrop />
		<Dialog.Panel>
			<form
				ref={ formRef }
				onSubmit={ ( event ) => {
					event.preventDefault();
					handleSaveUserDetailsAndActivate( false );
				} }
				noValidate
			>
				<Dialog.Header className="pb-6">
					<div className="flex items-center justify-between">
						<Dialog.Title>
							{ __(
								'Activate Reputation Shield and Protect Your Emails',
								'suremails'
							) }
						</Dialog.Title>
						<Dialog.CloseButton type="button" />
					</div>
					<Dialog.Description>
						{ __(
							'Activate Reputation Shield to safeguard your emails. Optionally, share your email address to receive valuable tips for optimising your email delivery.',
							'suremails'
						) }
					</Dialog.Description>
				</Dialog.Header>
				<Dialog.Body className="flex flex-wrap gap-x-4 gap-y-3">
					{ inputFields.map( ( field, index ) => (
						<div
							key={ field.name }
							className={ cn(
								'flex-grow',
								index === inputFields.length - 1 &&
									'flex-grow-[2] w-full'
							) }
						>
							<Input
								size="md"
								label={ field.label }
								name={ field.name }
								type={ field.type }
								value={ formData[ field.name ] }
								onChange={ handleChange( field.name ) }
								error={ errors[ field.name ] }
								disabled={
									isLoading.activateContentGuard ||
									isLoading.skipActivateContentGuard
								}
								autoComplete="off"
								onBlur={ () => handleValidation( field.name ) }
							/>
							{ errors[ field.name ] && (
								<p className="text-text-error text-sm mt-1.5">
									{ errors[ field.name ] }
								</p>
							) }
						</div>
					) ) }
				</Dialog.Body>
				<Dialog.Footer className="px-5 pb-3 pt-6 flex flex-col gap-3">
					<Button
						type="submit"
						icon={
							isLoading.activateContentGuard && (
								<Loader className="text-text-inverse" />
							)
						}
						iconPosition="left"
					>
						{ __( 'Activate Reputation Shield', 'suremails' ) }
					</Button>
					<Button
						type="button"
						variant="link"
						className="w-fit text-text-tertiary mx-auto p-1 hover:no-underline hover:text-text-primary [box-shadow:none]"
						onClick={ () =>
							handleSaveUserDetailsAndActivate( true )
						}
						icon={
							isLoading.skipActivateContentGuard && (
								<Loader className="text-text-secondary" />
							)
						}
						iconPosition="left"
					>
						{ __( 'Skip & Activate', 'suremails' ) }
					</Button>
					<TermsAndPrivacyText />
				</Dialog.Footer>
			</form>
		</Dialog.Panel>
	</Dialog>
);

const TermsAndPrivacyText = () => (
	<p
		className="mt-1 text-center text-text-tertiary"
		dangerouslySetInnerHTML={ {
			__html: sprintf(
				// translators: %1$s is the Terms link and %2$s is the Privacy Policy link.
				__(
					'By activating you agree to our %1$s and %2$s.',
					'suremails'
				),
				renderToString(
					<a
						className="no-underline"
						href={ window?.suremails?.termsURL }
						target="_blank"
						rel="noopener noreferrer"
					>
						{ __( 'Terms', 'suremails' ) }
					</a>
				),
				renderToString(
					<a
						className="no-underline"
						href={ window?.suremails?.privacyPolicyURL }
						target="_blank"
						rel="noopener noreferrer"
					>
						{ __( 'Privacy Policy', 'suremails' ) }
					</a>
				)
			),
		} }
	/>
);

const SafeGuardSection = () => {
	const [ showContentGuardLeadPopup, setShowContentGuardLeadPopup ] =
		useState( !! window?.suremails?.contentGuardPopupStatus );
	const [ activeContentGuard, setActiveContentGuard ] = useState(
		window?.suremails?.contentGuardActiveStatus === 'yes'
	);
	const [ isLoading, setIsLoading ] = useState( INITIAL_LOADING_STATE );
	const [ formData, setFormData ] = useState( INITIAL_FORM_STATE );
	const [ errors, setErrors ] = useState( {} );
	const formRef = useRef( null );
	const [ open, setOpen ] = useState( false );

	const handleConnectAccount = () => {
		setOpen( true );
	};

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

	const handleValidation = ( name ) => {
		if ( name ) {
			// Single field validation
			try {
				validationSchema.pick( { [ name ]: true } ).parse( {
					[ name ]: formData[ name ],
				} );
				setErrors( ( prev ) => ( {
					...prev,
					[ name ]: undefined,
				} ) );
				return true;
			} catch ( error ) {
				setErrors( ( prev ) => ( {
					...prev,
					[ name ]: error.errors[ 0 ].message,
				} ) );
				return false;
			}
		}
		// All fields validation
		try {
			validationSchema.parse( formData );
			setErrors( {} );
			return true;
		} catch ( error ) {
			const formattedErrors = {};
			error.errors.forEach( ( err ) => {
				formattedErrors[ err.path[ 0 ] ] = err.message;
			} );
			setErrors( formattedErrors );
			// Focus the first input field that has an error
			const firstInputWithError = error.errors[ 0 ]?.path[ 0 ];
			const firstErrorInput = formRef.current?.querySelector(
				`input[name="${ firstInputWithError }"]`
			);
			firstErrorInput?.focus();
			return false;
		}
	};

	const handleSaveUserDetailsAndActivate = async ( skip = false ) => {
		if (
			isLoading.activateContentGuard ||
			isLoading.skipActivateContentGuard
		) {
			return;
		}

		if ( ! skip ) {
			if ( ! handleValidation() ) {
				return;
			}
		}

		setIsLoading( ( prev ) => ( {
			...prev,
			activateContentGuard: ! skip,
			skipActivateContentGuard: skip,
		} ) );

		try {
			await saveUserDetails( {
				...formData,
				skip: skip ? 'yes' : 'no',
			} );
			const response = await activateContentGuard();
			if ( response.success ) {
				toast.success( __( 'Connection successful', 'suremails' ), {
					description: __(
						'Reputation Shield is now active!',
						'suremails'
					),
				} );
				setShowContentGuardLeadPopup( false );
				setActiveContentGuard( true );
				// Set the localized variable content guard popup status to false
				window.suremails.contentGuardPopupStatus = false;
				window.suremails.contentGuardActiveStatus = 'yes';
			}
		} catch ( error ) {
			toast.error(
				error.message ||
					__( 'Error Activating Reputation Shield', 'suremails' )
			);
		} finally {
			setOpen( false );
			setIsLoading( INITIAL_LOADING_STATE );
		}
	};

	const handleActivateContentGuard = async ( value ) => {
		try {
			const response = await activateContentGuard();
			if ( response.success ) {
				setActiveContentGuard( value );
				toast.success(
					sprintf(
						// translators: %1$s is the status of the Content Guard.
						__( 'Reputation Shield %s successfully', 'suremails' ),
						value
							? __( 'activated', 'suremails' )
							: __( 'deactivated', 'suremails' )
					)
				);
				window.suremails.contentGuardActiveStatus = value
					? 'yes'
					: 'no';
			}
		} catch ( error ) {
			toast.error(
				error.message ||
					__( 'Error authenticating Reputation Shield', 'suremails' )
			);
		}
	};

	return (
		<div className="max-w-settings-container w-full mx-auto p-7 space-y-4 border rounded-md shadow-sm bg-background-primary">
			<ContentGuardHeader />
			{ showContentGuardLeadPopup && (
				<div>
					<Button
						size="lg"
						variant="primary"
						icon={
							<Sparkles className="w-6 h-6" strokeWidth="1.25" />
						}
						iconPosition="left"
						onClick={ handleConnectAccount }
					>
						{ __( 'Activate Reputation Shield', 'suremails' ) }
					</Button>
				</div>
			) }
			{ ! showContentGuardLeadPopup && (
				<div>
					<Switch
						label={ {
							heading: __( 'Reputation Shield', 'suremails' ),
							description: __(
								'Reputation Shield identifies potentially problematic content in your emails and blocks them from being sent to your SMTP service.',
								'suremails'
							),
						} }
						value={ activeContentGuard }
						onChange={ handleActivateContentGuard }
					/>
				</div>
			) }
			{ /* User Details Form Dialog */ }
			{ !! window.suremails.contentGuardPopupStatus && (
				<UserDetailsFormDialog
					open={ open }
					setOpen={ setOpen }
					formRef={ formRef }
					formData={ formData }
					errors={ errors }
					handleValidation={ handleValidation }
					isLoading={ isLoading }
					handleChange={ handleChange }
					handleSaveUserDetailsAndActivate={
						handleSaveUserDetailsAndActivate
					}
				/>
			) }
		</div>
	);
};

export default SafeGuardSection;
