export const withinIframe = () => {
	try {
		return window.self !== window.top;
	} catch ( e ) {
		// Cross-origin access blocked, we're in an iframe
		return true;
	}
};

export const getStorgeData = ( key ) => {
	return JSON.parse( localStorage.getItem( key ) );
};

export const shouldAddPreviewParam = ( element ) => {
	const link = element.href;

	if ( link === '' ) {
		return false;
	}

	if ( ! link.includes( window.location.origin ) ) {
		return false;
	}

	if ( link.includes( 'wp-admin' ) ) {
		return false;
	}

	if ( link.includes( '.php' ) ) {
		return false;
	}

	if ( link.includes( 'customize' ) ) {
		return false;
	}

	return true;
};
