import { Button } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';
import { ArrowUpRight } from 'lucide-react';
import { cn } from '@Functions/utils';
import { redirectToPricingPage } from '@/functions/nudges';

/**
 * UpgradeButton - A reusable button component for upgrade CTAs
 *
 * @param {Object}   props               - Component props
 * @param {string}   props.label         - Button label text (default: "Upgrade Now")
 * @param {string}   props.variant       - Button variant: 'primary', 'secondary', 'link' (default: 'primary')
 * @param {string}   props.size          - Button size: 'xs', 'sm', 'md', 'lg' (default: 'md')
 * @param {*}        props.icon          - Icon element (default: ArrowUpRight for link variant)
 * @param {string}   props.iconPosition  - Icon position: 'left', 'right' (default: 'right')
 * @param {boolean}  props.showIcon      - Whether to show icon (default: true for link variant, false for others)
 * @param {Function} props.onClick       - Custom click handler (default: redirectToPricingPage)
 * @param {boolean}  props.showUnderline - Whether to show underline (default: false)
 * @param {string}   props.className     - Additional CSS classes
 * @param {string}   props.utmMedium     - UTM medium parameter for tracking (e.g., 'surerank_schema')
 * @return {JSX.Element} UpgradeButton component
 */
const UpgradeButton = ( {
	label = __( 'Upgrade Now', 'surerank' ),
	variant = 'primary',
	size = 'md',
	icon,
	iconPosition = 'right',
	showIcon,
	onClick,
	className = '',
	showUnderline = false,
	utmMedium,
	...restProps
} ) => {
	// Determine if icon should be shown
	const shouldShowIcon =
		showIcon !== undefined ? showIcon : variant === 'link';

	// Use custom icon or default ArrowUpRight for link variant
	let buttonIcon;
	if ( icon !== undefined ) {
		buttonIcon = icon;
	} else if ( shouldShowIcon ) {
		buttonIcon = <ArrowUpRight className="w-5 h-5" />;
	} else {
		buttonIcon = undefined;
	}

	// Handle button click
	const handleClick = () => {
		if ( onClick && typeof onClick === 'function' ) {
			onClick();
		} else {
			redirectToPricingPage( utmMedium );
		}
	};

	// Filter out custom props that shouldn't be passed to Button
	const { showUnderLine, ...buttonProps } = restProps;

	return (
		<Button
			size={ size }
			variant={ variant }
			onClick={ handleClick }
			className={ cn(
				'no-underline ring-0',
				{ 'underline ring-0': showUnderline },
				className
			) }
			icon={ buttonIcon }
			iconPosition={ iconPosition }
			{ ...buttonProps }
		>
			{ label }
		</Button>
	);
};

export default UpgradeButton;
