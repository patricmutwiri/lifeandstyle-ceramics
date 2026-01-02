import {
	useCallback,
	useLayoutEffect,
	useRef,
	useState,
} from '@wordpress/element';
import Tooltip from 'components/tooltip/tooltip';
import { cn } from '@utils/utils';

const TruncatedTooltipText = ( { text, className } ) => {
	const textRef = useRef( null );
	const [ isOverflowing, setIsOverflowing ] = useState( false );

	const checkOverflow = useCallback( () => {
		if ( textRef.current ) {
			const hasOverflow =
				textRef.current.scrollWidth > textRef.current.clientWidth;
			setIsOverflowing( hasOverflow );
		}
	}, [ setIsOverflowing ] );

	// Check overflow only once after initial render and when text changes
	useLayoutEffect( () => {
		checkOverflow();
	}, [ text ] );

	// Common text element properties
	const textProps = {
		ref: textRef,
		className: cn( 'block max-w-52 truncate', className ),
	};

	// If text is overflowing, wrap in Tooltip
	if ( isOverflowing ) {
		return (
			<Tooltip content={ text } position="top" arrow>
				<span { ...textProps }>{ text }</span>
			</Tooltip>
		);
	}

	// Otherwise, render just the text
	return <span { ...textProps }>{ text }</span>;
};

export default TruncatedTooltipText;
