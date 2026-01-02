// ConfirmationDialog.js
import { Dialog, Button, Loader, Input, Label } from '@bsf/force-ui';
import PropTypes from 'prop-types';
import {
	useState,
	useLayoutEffect,
	useEffect,
	useRef,
} from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { z } from 'zod';

const CONFIRMATION_TEXT = __( 'delete', 'suremails' );
const validationSchema = z.object( {
	confirmationText: z
		.string()
		.regex( new RegExp( `^${ CONFIRMATION_TEXT }$`, 'i' ), {
			message: sprintf(
				// translators: %s is the confirmation text
				__( 'Please type "%s" in the input box', 'suremails' ),
				CONFIRMATION_TEXT
			),
		} ),
} );

const ConfirmationDialog = ( {
	isOpen,
	title,
	description,
	onConfirm = () => {},
	onCancel = () => {},
	confirmButtonText = __( 'Confirm', 'suremails' ),
	cancelButtonText = __( 'Cancel', 'suremails' ),
	destructiveConfirmButton = true,
	requireConfirmation = false,
} ) => {
	const [ confirmationText, setConfirmationText ] = useState( '' );
	const [ errorMessage, setErrorMessage ] = useState( '' );
	const [ loading, setLoading ] = useState( false );
	// Ref to check if component is mounted. Used to prevent state updates on unmounted components.
	const isMounted = useRef( true );

	useEffect( () => {
		isMounted.current = true;
		return () => {
			isMounted.current = false;
		};
	}, [] );

	const handleConfirm = async () => {
		if ( typeof onConfirm !== 'function' ) {
			return;
		}
		if ( requireConfirmation && ! handleValidation( true ) ) {
			return;
		}
		setLoading( true );

		try {
			await onConfirm();
		} catch ( error ) {
		} finally {
			if ( isMounted.current ) {
				setLoading( false );
			}
		}
	};

	const handleCancel = () => {
		if ( typeof onCancel !== 'function' ) {
			return;
		}

		onCancel();
	};

	// Validate the confirmation text and show an error message if it's not valid.
	const handleValidation = ( focusOnError = false ) => {
		const result = validationSchema.safeParse( {
			confirmationText,
		} );
		if ( ! result.success ) {
			if ( focusOnError ) {
				// Focus the input if the validation fails
				document
					.querySelector( 'input[name="delete-confirmation-text"]' )
					.focus();
			}
			setErrorMessage( result.error.issues[ 0 ].message );
			return false;
		}
		setErrorMessage( '' );
		return true;
	};

	const handleConfirmationTextChange = ( value ) => {
		setConfirmationText( value );
		if ( requireConfirmation && errorMessage ) {
			setErrorMessage( '' );
		}
	};

	useLayoutEffect( () => {
		if ( ! isOpen && requireConfirmation ) {
			setConfirmationText( '' );
			setErrorMessage( '' );
		}
	}, [ isOpen ] );

	return (
		<Dialog
			design="simple"
			exitOnEsc
			scrollLock
			setOpen={ onCancel }
			open={ isOpen }
		>
			<Dialog.Backdrop />
			<Dialog.Panel className="gap-0">
				<Dialog.Header>
					<div className="flex items-center justify-between">
						<Dialog.Title>{ title }</Dialog.Title>
						<Dialog.CloseButton onClick={ onCancel } />
					</div>
					<Dialog.Description>{ description }</Dialog.Description>
				</Dialog.Header>
				{ requireConfirmation ? (
					<Dialog.Body className="mt-3 space-y-3">
						<Label
							className="text-text-secondary font-normal"
							tag="p"
							variant="neutral"
							size="sm"
						>
							{ __(
								'To confirm, type delete in the box below:',
								'suremails'
							) }
						</Label>
						<div>
							<Input
								ref={ ( node ) => {
									if ( node ) {
										node.focus();
									}
								} }
								name="delete-confirmation-text"
								size="md"
								type="text"
								placeholder={ sprintf(
									// translators: %s is the confirmation text
									__( 'Type "%s"', 'suremails' ),
									CONFIRMATION_TEXT
								) }
								className="w-full"
								value={ confirmationText }
								onChange={ handleConfirmationTextChange }
								error={ errorMessage }
								autoComplete="off"
							/>
						</div>
					</Dialog.Body>
				) : null }
				<Dialog.Footer>
					<Button variant="ghost" onClick={ handleCancel }>
						{ cancelButtonText }
					</Button>
					<Button
						variant="primary"
						onClick={ handleConfirm }
						icon={
							loading ? (
								<Loader className="text-background-primary" />
							) : null
						}
						iconPosition="left"
						loading={ loading }
						destructive={ destructiveConfirmButton }
					>
						{ confirmButtonText }
					</Button>
				</Dialog.Footer>
			</Dialog.Panel>
		</Dialog>
	);
};

// PropTypes for type checking and better developer experience
ConfirmationDialog.propTypes = {
	isOpen: PropTypes.bool.isRequired,
	title: PropTypes.string.isRequired,
	description: PropTypes.oneOfType( [ PropTypes.string, PropTypes.element ] )
		.isRequired,
	onConfirm: PropTypes.func,
	onCancel: PropTypes.func,
	confirmButtonText: PropTypes.string,
	cancelButtonText: PropTypes.string,
	requireConfirmation: PropTypes.bool,
};

export default ConfirmationDialog;
