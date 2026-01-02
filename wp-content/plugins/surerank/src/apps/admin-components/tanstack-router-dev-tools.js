import { lazy } from '@wordpress/element';

const TanStackRouterDevtools =
	process.env.NODE_ENV === 'production'
		? () => null // Render nothing in production
		: lazy( () =>
				// Lazy load in development
				import( '@tanstack/router-devtools' ).then( ( res ) => ( {
					default: res.TanStackRouterDevtools,
				} ) )
		  );
export default TanStackRouterDevtools;
