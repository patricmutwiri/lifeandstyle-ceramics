// src/components/Title.js
import { Title as FuiTitle } from '@bsf/force-ui';

const Title = ( {
	description = '',
	icon = null,
	iconPosition = '',
	size = 'sm',
	tag = 'h1',
	title,
	className = 'text-text-primary',
} ) => {
	return (
		<FuiTitle
			description={ description }
			icon={ icon }
			iconPosition={ iconPosition }
			size={ size }
			tag={ tag }
			title={ title }
			className={ className } // Apply the color class from prop here
		/>
	);
};

export default Title;
