import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Button } from '@bsf/force-ui';
import Tooltip from '@components/tooltip/tooltip';
import { ClipboardCheckIcon, ClipboardIcon } from 'lucide-react';
import { cn } from '@utils/utils';

const CopyButton = ( {
	text,
	onCopy,
	size = 'sm',
	variant = 'outline',
	className,
} ) => {
	const [ isCopied, setIsCopied ] = useState( false );

	const handleCopy = () => {
		try {
			navigator.clipboard.writeText( text );
			setIsCopied( true );
			// If onCopy is not a function, do nothing
			if ( typeof onCopy === 'function' ) {
				onCopy( text );
			}
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.error( error );
		} finally {
			// after 2 seconds, reset the copied state
			setTimeout( () => {
				setIsCopied( false );
			}, 3000 );
		}
	};

	return (
		<Tooltip
			content={
				isCopied
					? __( 'Copied to clipboard', 'suremails' )
					: __( 'Copy to clipboard', 'suremails' )
			}
			arrow
		>
			<Button
				type="button"
				className={ cn( 'w-fit', className ) }
				variant={ variant ?? 'outline' }
				onClick={ handleCopy }
				icon={
					isCopied ? (
						<ClipboardCheckIcon className="w-4 h-4" />
					) : (
						<ClipboardIcon className="w-4 h-4" />
					)
				}
				size={ size ?? 'sm' }
			/>
		</Tooltip>
	);
};

export default CopyButton;
