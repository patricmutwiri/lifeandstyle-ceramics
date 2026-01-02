import { useState } from '@wordpress/element';
import { Dialog, Button, Loader } from '@bsf/force-ui';
import { useDispatch, useSelect } from '@wordpress/data';
import { STORE_NAME } from '@/admin-store/constants';

const ConfirmationDialog = () => {
	const [ isLoading, setIsLoading ] = useState( false );
	const { setConfirmationModal } = useDispatch( STORE_NAME );
	const {
		open,
		title,
		description,
		confirmButtonText,
		cancelButtonText,
		onConfirm,
		onCancel,
		body,
		type,
	} = useSelect( ( select ) => {
		return select( STORE_NAME ).getConfirmationModal();
	}, [] );

	const handleSetOpen = ( value ) => {
		setConfirmationModal( {
			open: value,
		} );
	};

	const handleAction = async ( action ) => {
		if ( typeof action !== 'function' ) {
			handleSetOpen( false );
			return;
		}
		// If the action is already loading, do not run it again.
		if ( isLoading ) {
			return;
		}

		setIsLoading( true );
		try {
			await action();
		} catch ( error ) {
			// Do nothing.
		} finally {
			setIsLoading( false );
		}
		handleSetOpen( false );
	};

	const handleOnConfirm = () => {
		handleAction( onConfirm );
	};

	const handleOnCancel = () => {
		handleAction( onCancel );
	};

	return (
		<Dialog
			open={ open ?? false }
			setOpen={ handleSetOpen }
			exitOnEsc
			scrollLock
		>
			<Dialog.Backdrop />
			<Dialog.Panel>
				<Dialog.Header>
					<div className="flex items-center justify-between">
						<Dialog.Title>{ title }</Dialog.Title>
						<Dialog.CloseButton />
					</div>
					{ description && (
						<Dialog.Description>{ description }</Dialog.Description>
					) }
				</Dialog.Header>
				{ body && <Dialog.Body>{ body }</Dialog.Body> }
				<Dialog.Footer className="border-t border-x-0 border-b-0 border-solid border-border-subtle">
					<Button variant="outline" onClick={ handleOnCancel }>
						{ cancelButtonText }
					</Button>
					<Button
						onClick={ handleOnConfirm }
						icon={ isLoading && <Loader variant="secondary" /> }
						iconPosition="left"
						destructive={ type === 'destructive' }
					>
						{ confirmButtonText }
					</Button>
				</Dialog.Footer>
			</Dialog.Panel>
		</Dialog>
	);
};

export default ConfirmationDialog;
