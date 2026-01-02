import { createRoot } from '@wordpress/element';
import domReady from '@wordpress/dom-ready';
import Notice from '@admin-notice/components/notice';
import './index.css';

domReady( () => {
	const noticeRoot = document.getElementById( 'surerank-admin-notice' );
	if ( noticeRoot ) {
		const root = createRoot( noticeRoot );
		root.render( <Notice /> );
	}
} );
