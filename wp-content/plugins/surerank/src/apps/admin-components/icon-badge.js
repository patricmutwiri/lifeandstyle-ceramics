import { Container } from '@bsf/force-ui';
import { cn } from '@Functions/utils';

const badgeColorsClasses = {
	green: 'bg-badge-background-green border-badge-border-green text-badge-color-green',
	red: 'bg-badge-background-red border-badge-border-red text-badge-color-red',
	yellow: 'bg-badge-background-yellow border-badge-border-yellow text-badge-color-yellow',
	gray: 'bg-badge-background-gray border-badge-border-gray text-badge-color-gray',
};

const IconBadge = ( { icon, color = 'green', className, ...props } ) => {
	return (
		<Container
			align="center"
			justify="center"
			className={ cn(
				'size-5 rounded-full border border-solid [&>svg]:shrink-0 [&>svg]:size-3',
				badgeColorsClasses[ color ],
				className
			) }
			{ ...props }
		>
			{ icon }
		</Container>
	);
};

export default IconBadge;
