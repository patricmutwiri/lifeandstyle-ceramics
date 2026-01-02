import { Drawer, Button } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';

function ConnectionDrawer( { isOpen, onClose } ) {
	return (
		<Drawer
			design="simple"
			exitOnEsc
			position="right"
			scrollLock
			setOpen={ onClose } // Pass the onClose handler to close the drawer when needed
			isOpen={ isOpen } // Use the isOpen prop to control visibility
			transitionDuration={ 0.2 }
			className="z-999999"
		>
			<Drawer.Panel>
				<Drawer.Header>
					<div className="flex items-center justify-between">
						<Drawer.Title>
							{ __( 'Add Connection', 'suremails' ) }
						</Drawer.Title>
						<Drawer.CloseButton onClick={ onClose } />
					</div>
					<Drawer.Description>
						{ __(
							'Fill in the details to create a new connection.',
							'suremails'
						) }
					</Drawer.Description>
				</Drawer.Header>
				<Drawer.Body className="overflow-x-hidden">
					{ /* Add form or content for adding a new connection here */ }
					<div className="flex items-center justify-center w-full h-full border border-dashed rounded-md border-border-subtle bg-background-secondary">
						<p className="m-0 text-text-secondary">
							{ __( 'Connection form goes here.', 'suremails' ) }
						</p>
					</div>
				</Drawer.Body>
				<Drawer.Footer>
					<Button onClick={ onClose } variant="outline">
						{ __( 'Close', 'suremails' ) }
					</Button>
					<Button>{ __( 'Save', 'suremails' ) }</Button>
				</Drawer.Footer>
			</Drawer.Panel>
			<Drawer.Backdrop />
		</Drawer>
	);
}

export default ConnectionDrawer;
