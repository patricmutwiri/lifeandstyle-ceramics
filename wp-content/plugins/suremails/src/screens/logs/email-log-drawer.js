// EmailLogDrawer.js
import { useState } from '@wordpress/element';
import { Container, Drawer, toast, Button } from '@bsf/force-ui';
import ConfirmationDialog from '@components/confirmation-dialog/confirmation-dialog'; // Import ConfirmationDialog
import { resendEmails } from '@api/logs'; // Import resendEmails function
import { __ } from '@wordpress/i18n';
import DrawerLogBody from './drawer-log-body';
import { Redo2, LoaderCircle as LoaderIcon } from 'lucide-react';
import { get_pending_status } from '@utils/utils';

const EmailLogDrawer = ( {
	log,
	isOpen,
	setOpen,
	onClose,
	onResendSuccess,
} ) => {
	// State for Confirmation Dialog
	const [ isDialogOpen, setIsDialogOpen ] = useState( false );
	const [ dialogConfig, setDialogConfig ] = useState( {
		title: '',
		description: '',
		onConfirm: null,
		confirmButtonText: '',
	} );

	// Loading state for Resend button
	const [ isLoading, setIsLoading ] = useState( false );

	const handleSetOpen = ( open ) => {
		setOpen( open );
		if ( ! open && onClose ) {
			onClose();
		}
	};

	// Handler to open confirmation dialog
	const handleResendClick = () => {
		setDialogConfig( {
			title: __( 'Confirm Resend', 'suremails' ),
			description: __(
				'Are you sure you want to resend this email?',
				'suremails'
			),
			onConfirm: handleResendConfirm,
			confirmButtonText: __( 'Resend', 'suremails' ),
		} );
		setIsDialogOpen( true );
	};

	// Function to get confirm button text (if needed)
	const getConfirmButtonText = () => {
		return dialogConfig.confirmButtonText || __( 'Confirm', 'suremails' );
	};

	const handleResendConfirm = async () => {
		setIsDialogOpen( false );
		setIsLoading( true );
		try {
			const data = await resendEmails( [ log.id ] );
			if ( data.success ) {
				toast.success(
					__( 'Email resent successfully.', 'suremails' )
				);
			} else {
				toast.error( __( 'Failed to resend the email.', 'suremails' ), {
					description:
						data.message ||
						__(
							'There was an issue resending emails.',
							'suremails'
						),
				} );
			}
		} catch ( error ) {
			toast.error( __( 'Failed to resend the email.', 'suremails' ), {
				description:
					error.message ||
					__( 'There was an issue resending emails.', 'suremails' ),
			} );
		} finally {
			setIsLoading( false );
			// Trigger refetch of the log even if the request failed.
			onResendSuccess();
		}
	};

	return (
		<>
			<Drawer
				design="simple"
				exitOnEsc
				position="right"
				scrollLock
				setOpen={ handleSetOpen }
				transitionDuration={ 0.2 }
				open={ isOpen }
				className="w-[752px] z-999999"
			>
				<Drawer.Panel className="w-[752px]">
					<Drawer.Header>
						<div className="flex items-center justify-between text-base font-semibold gap-3">
							<Container justify="between" className="w-full">
								<Drawer.Title>
									{ __( 'Email Log', 'suremails' ) }
								</Drawer.Title>
								<Button
									className="py-0.5 font-semibold"
									iconPosition="left"
									size="xs"
									variant="primary"
									icon={
										isLoading ? (
											<LoaderIcon className="mr-2 animate-spin" />
										) : (
											<Redo2 />
										)
									}
									onClick={ handleResendClick } // Updated onClick handler
									loading={ isLoading } // Add loading prop
									disabled={ get_pending_status(
										log?.status
									) }
								>
									{ __( 'Resend', 'suremails' ) }
								</Button>
							</Container>
							<Drawer.CloseButton />
							{ /* Use the handler */ }
						</div>
					</Drawer.Header>

					{ /* Render DrawerLogBody and pass log as a prop */ }
					<Drawer.Body className="space-y-4 overflow-x-hidden pb-10">
						<DrawerLogBody log={ log } />
					</Drawer.Body>
				</Drawer.Panel>
				<Drawer.Backdrop />
			</Drawer>
			{ /* Confirmation Dialog */ }
			<ConfirmationDialog
				isOpen={ isDialogOpen }
				title={ dialogConfig.title }
				description={ dialogConfig.description }
				onConfirm={ dialogConfig.onConfirm }
				onCancel={ () => setIsDialogOpen( false ) }
				confirmButtonText={ getConfirmButtonText() }
				cancelButtonText={ __( 'Cancel', 'suremails' ) }
				destructiveConfirmButton={ false }
			/>
		</>
	);
};

export default EmailLogDrawer;
