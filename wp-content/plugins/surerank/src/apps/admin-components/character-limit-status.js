import { cn } from '@Functions/utils';

const CharacterLimitStatus = ( { length, maxLength, align = 'right' } ) => {
	return (
		<span
			className={ cn( 'text-xs leading-4 font-normal text-field-helper', {
				'ml-auto': align === 'right',
				'mr-auto': align === 'left',
			} ) }
		>
			<span
				className={ cn( {
					'text-text-error': length > maxLength,
				} ) }
			>
				{ length ?? 0 }
			</span>
			/{ maxLength }
		</span>
	);
};

export default CharacterLimitStatus;
