/**
 * WordPress Media Modal Utilities
 *
 * Functions to handle wp.media modal interactions and fix conflicts
 * with plugins like Astra Sites that modify the global media frame.
 */

/**
 * Ensures the media modal button text stays correct even when other plugins modify the frame.
 * This fixes issues where button text becomes empty after image selection.
 *
 * @param {Object} mediaFrame - The WordPress media frame object
 * @param {string} buttonText - The text to display on the button
 */
export const ensureMediaButtonText = ( mediaFrame, buttonText ) => {
	// Function to set button text
	const setButtonText = () => {
		const buttonSelectors = [
			'.media-button-select',
			'.media-button-insert',
			'.button-primary',
		];

		for ( const selector of buttonSelectors ) {
			const button = mediaFrame.$( selector );
			if ( button.length && button.is( ':visible' ) ) {
				button.text( buttonText );
				break;
			}
		}
	};

	// Ensure button text is set correctly even if other plugins modify the frame
	mediaFrame.on( 'ready', setButtonText );

	// Also set button text when selection changes
	mediaFrame.on( 'selection:toggle', setButtonText );

	// Set button text when content changes
	mediaFrame.on( 'content:render', setButtonText );
};

// Creates a WordPress media frame with consistent button text handling.
// This is a wrapper around wp.media() that automatically applies the button text fix.
export const createMediaFrame = ( options ) => {
	const frame = wp.media( {
		...options,
		library: {
			type: 'image',
			...( options.library || {} ),
		},
	} );

	// Apply button text fix
	if ( options.button && options.button.text ) {
		ensureMediaButtonText( frame, options.button.text );
	}

	return frame;
};
