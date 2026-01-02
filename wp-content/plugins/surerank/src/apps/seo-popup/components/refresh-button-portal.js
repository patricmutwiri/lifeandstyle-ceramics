import { useState, useEffect, createPortal } from '@wordpress/element';
import { RefreshButton } from './page-seo-checks/analyzer/utils/page-builder';

const RefreshButtonPortal = ( {
	isRefreshing,
	isChecking,
	onClick,
	targetSelector = '.refresh-button-container',
} ) => {
	const [ targetElement, setTargetElement ] = useState( null );

	useEffect( () => {
		const findTarget = () => {
			const element = document.querySelector( targetSelector );
			if ( element ) {
				setTargetElement( element );
			}
		};

		findTarget();

		const Observer =
			window.MutationObserver ||
			window.WebKitMutationObserver ||
			window.MozMutationObserver;
		const observer = Observer ? new Observer( findTarget ) : null;
		if ( observer ) {
			observer.observe( document.body, {
				childList: true,
				subtree: true,
			} );
		}

		return () => observer.disconnect();
	}, [ targetSelector ] );

	if ( ! targetElement ) {
		return null;
	}

	return createPortal(
		<RefreshButton
			isRefreshing={ isRefreshing }
			isChecking={ isChecking }
			onClick={ onClick }
		/>,
		targetElement
	);
};

export default RefreshButtonPortal;
