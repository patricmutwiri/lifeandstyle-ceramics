import { __ } from '@wordpress/i18n';
import { cloneElement, useState } from '@wordpress/element';
import { Button } from '@bsf/force-ui';
import { SeoPopupTooltip } from '@AdminComponents/tooltip';
import { cn } from '@/functions/utils';

const ConfirmationPopover = ( {
	onConfirm,
	confirmText = __( 'Remove', 'surerank' ),
	cancelText = __( 'Cancel', 'surerank' ),
	children,
	className,
	...props
} ) => {
	const [ isOpen, setIsOpen ] = useState( false );

	const preventDefault = ( event ) => {
		event.preventDefault();
		event.stopPropagation();
	};

	const handleConfirm = ( event ) => {
		preventDefault( event );
		onConfirm();
		setIsOpen( false );
	};

	const handleCancel = ( event ) => {
		preventDefault( event );
		setIsOpen( false );
	};

	const popoverContent = (
		<div className="flex gap-2 items-center">
			<Button
				size="xs"
				variant="outline"
				onClick={ handleCancel }
				aria-label={ __( 'Cancel', 'surerank' ) }
			>
				{ cancelText }
			</Button>
			<Button
				size="xs"
				className="focus:[box-shadow:none] bg-button-danger hover:bg-button-danger-hover outline-button-danger hover:outline-button-danger-hover"
				onClick={ handleConfirm }
				destructive
				aria-label={ __( 'Confirm', 'surerank' ) }
			>
				{ confirmText }
			</Button>
		</div>
	);

	return (
		<SeoPopupTooltip
			className={ cn(
				'w-fit p-1.5 rounded shadow-md border border-solid border-border-subtle [&>svg]:fill-border-subtle',
				className
			) }
			content={ popoverContent }
			open={ isOpen }
			setOpen={ setIsOpen }
			variant="light"
			arrow
			{ ...props }
		>
			{ cloneElement( children, {
				onClick: ( event ) => {
					preventDefault( event );
					setIsOpen( ( prev ) => ! prev );
				},
			} ) }
		</SeoPopupTooltip>
	);
};

export default ConfirmationPopover;
