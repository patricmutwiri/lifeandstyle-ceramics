import { SureRankLogo } from '@/global/components/icons';
import { Button } from '@bsf/force-ui';
import { X } from 'lucide-react';
import { createPortal, memo, useEffect, useState } from '@wordpress/element';
import PageCheckStatusIndicator from '@AdminComponents/page-check-status-indicator';
import { usePageCheckStatus } from '@SeoPopup/hooks';

/* global MutationObserver */

const PageChecksStatus = () => {
	const [ host, setHost ] = useState( null );
	const { status, initializing, counts } = usePageCheckStatus();

	useEffect( () => {
		// Initial check in case element already exists
		const findHost = () =>
			document.querySelector( '.surerank-page-checks-indicator' );

		let currentHost = findHost();
		if ( currentHost ) {
			setHost( currentHost );
		}

		// Create a MutationObserver to watch for DOM changes that may add/remove the host element
		const observer = new MutationObserver( () => {
			const newHost = findHost();
			// only update state when it actually changes to avoid re-renders
			if ( newHost !== currentHost ) {
				currentHost = newHost;
				setHost( newHost );
			}
		} );

		const metaBoxHeader = document.getElementById(
			'surerank-metabox-header'
		);
		if ( ! metaBoxHeader ) {
			return;
		}

		observer.observe( metaBoxHeader, {
			childList: true,
			subtree: true,
		} );

		// Cleanup on unmount
		return () => {
			observer.disconnect();
		};
	}, [] );

	return (
		host &&
		createPortal(
			<PageCheckStatusIndicator
				status={ status }
				errorAndWarnings={ counts.errorAndWarnings }
				initializing={ initializing }
				className="static ml-1 size-1.5"
			/>,
			host
		)
	);
};

const Header = ( { onClose } ) => {
	return (
		<div
			id="surerank-metabox-header"
			className="flex items-center justify-between gap-3 border-0 border-b-0.5 border-solid border-border-subtle"
		>
			<div className="flex items-center py-3.5 px-4">
				<SureRankLogo width={ 32 } height={ 20 } />
			</div>
			<div className="h-full flex items-center mr-auto gap-2"></div>
			<div className="flex items-center py-3.5 px-4 gap-2">
				<Button
					variant="ghost"
					size="sm"
					onClick={ onClose }
					className="p-1 text-icon-secondary hover:text-icon-primary hover:bg-transparent bg-transparent focus:outline-none"
					icon={ <X /> }
				/>
			</div>
			<PageChecksStatus />
		</div>
	);
};

export default memo( Header );
