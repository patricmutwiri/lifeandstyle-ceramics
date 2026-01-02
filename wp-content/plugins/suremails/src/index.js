// File: src/index.js

// Import createRoot from @wordpress/element for WordPress compatibility
import { createRoot } from 'react-dom/client';
import App from './App';
import './styles.css'; // Ensure your styles are imported

// Find the root element in the WordPress page where the app will be rendered
const rootElement = document.getElementById( 'suremails-root-app' );

if ( rootElement ) {
	// Create a root and render the App component
	const root = createRoot( rootElement );

	// Render the App within the root
	root.render( <App /> );
}
