import { __ } from '@wordpress/i18n';
import { Button, Text } from '@bsf/force-ui';
import { ArrowLeft, X } from 'lucide-react';
import { cn } from '@/functions/utils';

const FixItForMeHeader = ( {
	title = __( 'Here are a few suggestions', 'surerank' ),
	onBack,
	onClose,
	className,
} ) => {
	return (
		<div
			className={ cn(
				'flex flex-col self-stretch gap-2 px-4 pt-3.5 pb-2',
				className
			) }
		>
			<div className="flex justify-between items-center self-stretch gap-8">
				<Text
					size={ 16 }
					lineHeight={ 20 }
					color="primary"
					weight={ 600 }
				>
					{ title }
				</Text>
				<div className="flex items-center gap-2">
					<Button
						variant="outline"
						size="xs"
						onClick={ onBack }
						className="px-1 py-1 border border-gray-300 shadow-sm bg-white"
						icon={ <ArrowLeft className="text-text-primary" /> }
						iconPosition="left"
					>
						<span>{ __( 'Back', 'surerank' ) }</span>
					</Button>
					{ typeof onClose === 'function' && (
						<Button
							variant="ghost"
							size="xs"
							onClick={ onClose }
							className="p-1 text-icon-secondary hover:text-icon-primary hover:bg-transparent bg-transparent focus:outline-none"
							icon={ <X /> }
						/>
					) }
				</div>
			</div>
		</div>
	);
};

export default FixItForMeHeader;
