/**
 * Meta Options build.
 */
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import { PluginMoreMenuItem } from '@wordpress/editor';
import {
	useEffect,
	useState,
	useCallback,
	createPortal,
} from '@wordpress/element';
import { Button as WPButton } from '@wordpress/components';
import { STORE_NAME as storeName } from '@Store/constants';
import { SureRankMonoSmallLogo } from '@GlobalComponents/icons';
import PageCheckStatusIndicator from '@AdminComponents/page-check-status-indicator';
import usePageCheckStatus from './hooks/usePageCheckStatus';
import { getTooltipText } from './utils/page-checks-status-tooltip-text';

// Inject a toolbar button directly into the Gutenberg header via a React portal.
const SureRankToolbarButtonPortal = () => {
	const { updateModalState } = useDispatch( storeName );
	const [ hostEl, setHostEl ] = useState( null );
	const isModalOpen = useSelect( ( select ) => {
		try {
			return !! select( storeName ).getModalState();
		} catch ( error ) {
			return false;
		}
	}, [] );

	// Get page checks status for indicator
	const { status, initializing, counts } = usePageCheckStatus();

	// Stable handler to open the modal; helps avoid unnecessary re-renders.
	const handleOpenModal = useCallback(
		() => updateModalState( true ),
		[ updateModalState ]
	);

	useEffect( () => {
		const findOrCreateHost = () => {
			// 1) Preferred: insert as FIRST item inside the pinned items container.
			const pinned = document.querySelector( '.interface-pinned-items' );
			if ( pinned ) {
				let host = pinned.querySelector( '#surerank-toolbar-portal' );
				if ( ! host ) {
					host = document.createElement( 'div' );
					host.id = 'surerank-toolbar-portal';
					host.className = 'surerank-root';
					if ( pinned.firstChild ) {
						pinned.insertBefore( host, pinned.firstChild );
					} else {
						pinned.appendChild( host );
					}
				} else if ( host.parentElement !== pinned ) {
					// Reparent if necessary to ensure first position in pinned area.
					if ( pinned.firstChild && pinned.firstChild !== host ) {
						pinned.insertBefore( host, pinned.firstChild );
					} else {
						pinned.appendChild( host );
					}
				}
				return host;
			}

			// 2) Fallback: right-side actions/settings area of the header (post and site editor variants).
			const rightArea = document.querySelector(
				[
					'.edit-post-header__settings',
					'.editor-header__actions',
					'.edit-site-header__actions',
				].join( ', ' )
			);
			if ( ! rightArea ) {
				return null;
			}

			let host = rightArea.querySelector( '#surerank-toolbar-portal' );
			if ( ! host ) {
				host = document.createElement( 'div' );
				host.id = 'surerank-toolbar-portal';
				host.className = 'surerank-root';

				// Try to place AFTER the View (responsive) button/dropdown when available.
				const viewCandidates = rightArea.querySelector(
					[
						'.edit-post-post-preview__button',
						'.editor-preview-dropdown',
					].join( ', ' )
				);

				const insertAfter = ( referenceNode, newNode ) => {
					if ( ! referenceNode || ! referenceNode.parentNode ) {
						rightArea.appendChild( newNode );
						return;
					}
					if ( referenceNode.nextSibling ) {
						referenceNode.parentNode.insertBefore(
							newNode,
							referenceNode.nextSibling
						);
					} else {
						referenceNode.parentNode.appendChild( newNode );
					}
				};

				if ( viewCandidates ) {
					const refNode =
						viewCandidates.closest( 'button, div, span' ) ||
						viewCandidates;
					insertAfter( refNode, host );
				} else {
					rightArea.appendChild( host );
				}
			}
			return host;
		};

		let host = findOrCreateHost();
		if ( host ) {
			setHostEl( host );
		}

		// Recreate if the header re-renders.
		const Observer =
			window.MutationObserver ||
			window.WebKitMutationObserver ||
			window.MozMutationObserver;
		if ( Observer ) {
			const observer = new Observer( () => {
				const current = document.getElementById(
					'surerank-toolbar-portal'
				);
				const pinned = document.querySelector(
					'.interface-pinned-items'
				);
				// If missing, recreate. If present but not inside pinned when pinned exists, move it.
				if ( ! current ) {
					host = findOrCreateHost();
					if ( host ) {
						setHostEl( host );
					}
				} else if ( pinned && current.parentElement !== pinned ) {
					if ( pinned.firstChild && pinned.firstChild !== current ) {
						pinned.insertBefore( current, pinned.firstChild );
					} else {
						pinned.appendChild( current );
					}
				}
			} );
			// Observe the header only.
			const editorHeader = document.querySelector(
				'.editor-header, .edit-post-header'
			);
			observer.observe( editorHeader, {
				childList: true,
				subtree: true,
			} );
			return () => observer.disconnect();
		}

		// Fallback: periodic check.
		const interval = window.setInterval( () => {
			const current = document.getElementById(
				'surerank-toolbar-portal'
			);
			if ( ! current ) {
				host = findOrCreateHost();
				if ( host ) {
					setHostEl( host );
				}
			}
		}, 1500 );
		return () => window.clearInterval( interval );
	}, [] );

	if ( ! hostEl ) {
		return null;
	}

	return createPortal(
		<div className="relative">
			<WPButton
				icon={ <SureRankMonoSmallLogo /> }
				label={ getTooltipText( counts ) }
				aria-label={ __( 'Open SureRank Meta Box', 'surerank' ) }
				aria-haspopup="dialog"
				showTooltip
				isPressed={ isModalOpen }
				aria-pressed={ isModalOpen }
				type="button"
				size="compact"
				onClick={ handleOpenModal }
			>
				<PageCheckStatusIndicator
					status={ status }
					errorAndWarnings={ counts.errorAndWarnings }
					initializing={ initializing }
				/>
			</WPButton>
		</div>,
		hostEl
	);
};

const SpectraPageSettingsPopup = () => {
	const { updateModalState } = useDispatch( storeName );

	const handleMenuClick = useCallback( () => {
		// Open the Drawer instead of sidebar
		updateModalState( true );
	}, [ updateModalState ] );

	return (
		<>
			<PluginMoreMenuItem
				onClick={ handleMenuClick }
				icon={ <SureRankMonoSmallLogo /> }
				aria-label={ __( 'Open SureRank Meta Box', 'surerank' ) }
				aria-haspopup="dialog"
			>
				{ __( 'SureRank Meta Box', 'surerank' ) }
			</PluginMoreMenuItem>
			{ /* Portal-injected toolbar button */ }
			<SureRankToolbarButtonPortal />
		</>
	);
};
export default SpectraPageSettingsPopup;
