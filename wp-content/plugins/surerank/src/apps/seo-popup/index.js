import { createRoot } from 'react-dom';
import Modal from '@SeoPopup/modal';
import RegisterMenu from './register-menu';
import { registerPlugin } from '@wordpress/plugins';
import { select, useDispatch } from '@wordpress/data';
import { STORE_NAME } from '@Store/constants';
import { SureRankMonoLogo } from '@GlobalComponents/icons';
import { useEffect } from '@wordpress/element';
import PageCheckStatusIndicator from '@AdminComponents/page-check-status-indicator';
import usePageCheckStatus from './hooks/usePageCheckStatus';

import '@Store/store';
import './style.scss';

if ( select( 'core/editor' ) ) {
	// If Gutenberg editor, then only.
	registerPlugin( 'surerank-page-level-settings', { render: RegisterMenu } );
}

const RenderTriggerPopupButton = () => {
	const { updateModalState } = useDispatch( STORE_NAME );

	// Get page checks status for indicator
	const { status, initializing, counts } = usePageCheckStatus();

	useEffect( () => {
		const adminBar = document.querySelector( '#wpadminbar' );
		if ( adminBar ) {
			adminBar.style.zIndex = '10';
		}
	}, [] );

	return (
		<div className="relative inline-flex">
			<button
				className="inline-flex w-auto h-auto p-1 rounded-full border-0 bg-transparent focus:outline-none outline-none cursor-pointer"
				type="button"
				onClick={ () => updateModalState( true ) }
			>
				<SureRankMonoLogo className="size-6" />
			</button>
			<PageCheckStatusIndicator
				className="z-auto"
				status={ status }
				errorAndWarnings={ counts.errorAndWarnings }
				initializing={ initializing }
			/>
		</div>
	);
};

const insertRoot = () => {
	const targetNode = document.querySelector( '.wrap > h1' );
	if ( targetNode ) {
		const rootContainer = document.createElement( 'span' );
		rootContainer.id = 'seo-popup';
		rootContainer.className = 'surerank-root';
		targetNode.appendChild( rootContainer );
		return rootContainer;
	}
	return null;
};

if ( surerank_seo_popup.editor_type === 'classic' ) {
	const targetElement = insertRoot();
	if ( targetElement ) {
		const root = createRoot( targetElement );
		root.render( <RenderTriggerPopupButton /> );
	}
}

document.addEventListener( 'DOMContentLoaded', function () {
	let node = document.querySelector( '#surerank-root' );

	if ( ! node ) {
		node = document.body.appendChild( document.createElement( 'div' ) );
		node.id = 'surerank-root';
		node.className = 'surerank-root';
	}

	setTimeout( function () {
		const root = createRoot( node );
		root.render( <Modal /> );
	}, 1000 );
} );
