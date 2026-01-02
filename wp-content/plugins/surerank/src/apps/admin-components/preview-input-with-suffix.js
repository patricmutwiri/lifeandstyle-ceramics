import { Input, Skeleton } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';
import { PencilLine } from 'lucide-react';

const PreviewInputWithSuffix = ( {
	value,
	onChange,
	suffix = null,
	placeholder = __( 'Enter your focus keyword', 'surerank' ),
	isLoading = false,
	...props
} ) => {
	if ( isLoading ) {
		return <Skeleton className="w-full h-10" />;
	}

	return (
		<Input
			className="[&_input]:transition-[color,outline] [&_input]:duration-200"
			size="md"
			placeholder={ placeholder }
			suffix={
				suffix ?? (
					<PencilLine
						className="size-5 text-icon-primary"
						strokeWidth={ 1.5 }
					/>
				)
			}
			value={ value }
			onChange={ onChange }
			{ ...props }
		/>
	);
};

export default PreviewInputWithSuffix;
