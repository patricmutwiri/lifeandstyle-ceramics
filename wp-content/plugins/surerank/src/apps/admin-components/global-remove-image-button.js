import { X } from 'lucide-react'; // Import icons
import { Button, Tooltip } from '@bsf/force-ui';
/*
 * GlobalRemoveButton
 * Reusable Remove Button component
 *
 */

const GlobalRemoveButton = ( {
	variant = 'outline',
	size = 'xs',
	strokeWidth = 2,
	icon = <X strokeWidth={ strokeWidth } className="h-4 w-4" />,
	onClick,
	className = 'absolute top-2 right-2 flex items-center p-1 h-8 w-8 rounded leading-[0]',
	disabled = false, // Optional disabled prop
	tooltip_label,
} ) => {
	const button = (
		<Button
			variant={ variant }
			size={ size }
			className={ className }
			onClick={ onClick }
			disabled={ disabled }
		>
			{ icon }
		</Button>
	);

	if ( tooltip_label ) {
		return (
			<Tooltip
				placement="top"
				offset={ 10 }
				variant="dark"
				title={ tooltip_label }
				tooltipPortalId="surerank-root"
			>
				{ button }
			</Tooltip>
		);
	}

	return button;
};

export default GlobalRemoveButton;
