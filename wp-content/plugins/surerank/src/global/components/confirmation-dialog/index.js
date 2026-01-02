import { Dialog, Text, Button } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Loader2 } from 'lucide-react';
import { cn } from '@/functions/utils';

export const ConfirmationDialog = ( {
	open,
	setOpen,
	title,
	description,
	confirmLabel = __( 'Confirm', 'surerank' ),
	cancelLabel = __( 'Cancel', 'surerank' ),
	onConfirm,
	onCancel,
	confirmVariant = 'primary',
	confirmDestructive = false,
	loading = false,
	errorMessage,
} ) => {
	const [ internalLoading, setInternalLoading ] = useState( false );
	const [ internalErrorMessage, setInternalErrorMessage ] = useState( null );

	const handleConfirm = async () => {
		if ( ! onConfirm ) {
			setOpen( false );
			return;
		}

		setInternalLoading( true );
		setInternalErrorMessage( null );

		try {
			await onConfirm();
			setOpen( false );
		} catch ( err ) {
			setInternalErrorMessage(
				err?.message ||
					__( 'Action failed. Please try again.', 'surerank' )
			);
		} finally {
			setInternalLoading( false );
		}
	};

	const handleCancel = () => {
		if ( onCancel ) {
			onCancel();
		}
		setOpen( false );
	};

	const isButtonLoading = loading || internalLoading;
	const finalErrorMessage = errorMessage || internalErrorMessage;

	return (
		<Dialog
			design="simple"
			exitOnEsc
			scrollLock
			setOpen={ setOpen }
			open={ open }
		>
			<Dialog.Backdrop />
			<Dialog.Panel>
				<Dialog.Header>
					<div className="flex items-center justify-between">
						<Dialog.Title>{ title }</Dialog.Title>
						<Dialog.CloseButton />
					</div>
				</Dialog.Header>
				<Dialog.Body>
					{ description && (
						<Text
							as="p"
							color="secondary"
							size={ 14 }
							weight={ 400 }
						>
							{ description }
						</Text>
					) }
					{ finalErrorMessage && (
						<Text
							as="p"
							color="error"
							size={ 14 }
							weight={ 400 }
							className="mt-2"
						>
							{ finalErrorMessage }
						</Text>
					) }
				</Dialog.Body>
				<Dialog.Footer className="p-5 pt-1">
					<Button
						size="md"
						variant="outline"
						onClick={ handleCancel }
						disabled={ isButtonLoading }
					>
						{ cancelLabel }
					</Button>
					<Button
						size="md"
						variant={ confirmVariant }
						destructive={ confirmDestructive }
						onClick={ handleConfirm }
						icon={
							isButtonLoading && (
								<Loader2 className="size-4 animate-spin" />
							)
						}
						className={ cn(
							isButtonLoading && 'cursor-not-allowed opacity-50'
						) }
						iconPosition="left"
						disabled={ isButtonLoading }
					>
						{ confirmLabel }
					</Button>
				</Dialog.Footer>
			</Dialog.Panel>
		</Dialog>
	);
};
