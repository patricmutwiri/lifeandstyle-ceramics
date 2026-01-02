import { Switch, Text } from '@bsf/force-ui';
import { cn } from '@Functions/utils';

const ToggleCard = ( { label, checked, onChange, id } ) => {
	return (
		<div
			className={ cn(
				'flex justify-between items-center p-2 rounded-md',
				'bg-background-primary shadow-sm'
			) }
		>
			<Text
				as="label"
				htmlFor={ id }
				weight={ 500 }
				size={ 14 }
				color="primary"
			>
				{ label }
			</Text>
			<Switch
				id={ id }
				checked={ checked }
				onChange={ onChange }
				size="xs"
			/>
		</div>
	);
};

export default ToggleCard;
