import { cn } from '@Functions/utils';
import { useEffect, useRef, useState, memo } from '@wordpress/element';
import { motion } from 'framer-motion';

export const AnimateChangeInHeight = ( {
	children,
	className,
	animationDuration = 0.3,
} ) => {
	const containerRef = useRef( null );
	const [ height, setHeight ] = useState( 'auto' );

	useEffect( () => {
		if ( containerRef.current ) {
			const resizeObserver = new ResizeObserver( ( entries ) => {
				// We only have one entry, so we can use entries[0].
				const observedHeight = entries[ 0 ].contentRect.height;
				setHeight( observedHeight );
			} );

			resizeObserver.observe( containerRef.current );

			return () => {
				// Cleanup the observer when the component is unmounted
				resizeObserver.disconnect();
			};
		}
	}, [] );

	return (
		<motion.div
			className={ cn( 'overflow-hidden', className ) }
			style={ { height } }
			animate={ { height, opacity: 1 } }
			exit={ { height, opacity: 0 } }
			transition={ { duration: animationDuration } }
		>
			<div ref={ containerRef }>{ children }</div>
		</motion.div>
	);
};

export default memo( AnimateChangeInHeight );
