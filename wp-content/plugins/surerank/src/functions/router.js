import {
	createRoute as createRouteTanstack,
	createRouter,
	createHashHistory,
	RouterProvider,
	ErrorComponent,
	NotFoundRoute,
	Navigate,
	createRootRouteWithContext,
} from '@tanstack/react-router';

// Layout component.
import SidebarLayout from '@AdminComponents/layout/sidebar-layout';
import { SureRankLogo } from '@/global/components/icons';

/**
 * @typedef {Object} RouteConfig
 * @property {string}                        path             - The path of the route
 * @property {import('react').ComponentType} component        - The component to render for this route
 * @property {Function}                      [loader]         - Optional data loader function for the route
 * @property {Function}                      [action]         - Optional action function for the route
 * @property {import('react').ComponentType} [errorComponent] - Optional error boundary component
 * @property {RouteConfig[]}                 [children]       - Optional child routes
 * @property {Object}                        [meta]           - Optional metadata for the route
 * @property {import('react').ComponentType} [layout]         - Optional custom layout component for this route
 * @property {Object}                        [layoutProps]    - Optional props to pass to the layout component
 */

/**
 * @typedef {Object} NavLink
 * @property {string}                        path       - The path of the link
 * @property {string}                        label      - The label text to display
 * @property {import('react').ComponentType} [icon]     - Optional icon component
 * @property {NavLink[]}                     [children] - Optional child navigation links
 */

/**
 * @typedef {Object} LayoutConfig
 * @property {import('react').ComponentType} component          - The layout component to use
 * @property {Object}                        [props]            - Props to pass to the layout component
 * @property {NavLink[]}                     [props.navLinks]   - Navigation links for the layout
 * @property {boolean}                       [props.navbarOnly] - Whether to show only the navbar
 */

/**
 * Loading spinner component for pending states
 * @return {JSX.Element} Loading spinner component
 */
const LoadingSpinner = () => (
	<div className="flex items-center justify-center p-4">
		<div className="animate-spin rounded-full h-8 w-8 border-b-2 border-brand-800" />
	</div>
);

/**
 * Creates nested routes recursively
 * @param {RouteConfig} routeConfig   - The route configuration
 * @param {Object}      parentRoute   - The parent route object
 * @param {string}      completePath  - The complete path of the route
 * @param {Object}      defaultLayout - The default layout configuration
 * @return {Object} The created route
 */
const createNestedRoutes = (
	routeConfig,
	parentRoute,
	completePath = '',
	defaultLayout
) => {
	const RouteComponent = routeConfig.layout
		? () => {
				const Layout = routeConfig.layout;
				const Component = routeConfig.component;
				const layoutProps = {
					...defaultLayout.props,
					...routeConfig.layoutProps,
				};

				return (
					<Layout { ...layoutProps }>
						<Component />
					</Layout>
				);
		  }
		: routeConfig.component;

	const hasChildren = !! routeConfig.children?.length;
	const nestedRoutes = hasChildren ? [ ...routeConfig.children ] : [];

	if ( ! RouteComponent && hasChildren ) {
		completePath += routeConfig.path;
		const hasIndexRoute = nestedRoutes.some(
			( child ) => child.path === '/'
		);

		if ( ! hasIndexRoute ) {
			const firstChild = nestedRoutes[ 0 ];
			const pathToFirstChild = `${ completePath }${ firstChild.path }`;
			nestedRoutes.push( {
				path: '/',
				component: () => <Navigate to={ pathToFirstChild } replace />,
			} );
		}
	}

	let route;
	const loader = routeConfig.loader ? () => routeConfig.loader : () => void 0;
	if ( routeConfig?.component && 'loader' in routeConfig?.component ) {
		route = createRouteTanstack( {
			getParentRoute: () => parentRoute,
			path: routeConfig.path,
			errorComponent: routeConfig.errorComponent,
			pendingComponent: () => <PendingComponent />,
			loader,
		} ).lazy( routeConfig?.component?.loader );
	} else {
		route = createRouteTanstack( {
			getParentRoute: () => parentRoute,
			path: routeConfig.path,
			component: RouteComponent,
			errorComponent: routeConfig.errorComponent,
			loader,
			action: routeConfig.action,
			meta: routeConfig.meta,
		} );
	}

	if ( hasChildren ) {
		const childRoutes = nestedRoutes.map( ( childConfig ) =>
			createNestedRoutes(
				childConfig,
				route,
				completePath,
				defaultLayout
			)
		);
		route.addChildren( childRoutes );
	}

	return route;
};

/**
 * Creates router configuration
 * @param {Object} routeTree - The route tree
 * @return {Object} Router configuration
 */
const createRouterConfig = ( routeTree ) => ( {
	routeTree,
	history: createHashHistory(),
	defaultPendingComponent: LoadingSpinner,
	defaultPendingMs: 1000,
	defaultErrorComponent: ErrorComponent,
} );

/**
 * Creates an admin router with enhanced features.
 *
 * @param {Object}                        options                         - The options for creating the admin router
 * @param {RouteConfig[]}                 options.routes                  - The routes configuration
 * @param {NavLink[]}                     [options.navLinks]              - Optional navigation links
 * @param {import('react').ComponentType} [options.notFoundComponent]     - Optional 404 page component
 * @param {import('react').ComponentType} [options.defaultErrorComponent] - Optional default error boundary component
 * @param {LayoutConfig}                  [options.defaultLayout]         - Optional default layout configuration
 * @return {Function|null} The router component or null if no routes
 */
const createAdminRouter = ( {
	routes = [],
	navLinks = [],
	notFoundComponent,
	defaultErrorComponent,
	defaultLayout = {
		component: SidebarLayout,
		props: {},
	},
} ) => {
	if ( ! routes?.length ) {
		return null;
	}

	// Create Root route with error boundary
	const rootRoute = createRootRouteWithContext()( {
		component: () => {
			const Layout = defaultLayout.component;
			return (
				<Layout
					routes={ routes }
					navLinks={ navLinks }
					{ ...defaultLayout.props }
				/>
			);
		},
		errorComponent: defaultErrorComponent,
		loader: () => void 0,
	} );

	// Create routes with nested structure
	const createdRoutes = routes.map( ( route ) =>
		createNestedRoutes( route, rootRoute, '', defaultLayout )
	);

	// Create Not Found route
	if ( notFoundComponent ) {
		const notFoundRoute = new NotFoundRoute( {
			component: notFoundComponent,
			getParentRoute: () => rootRoute,
		} );
		createdRoutes.push( notFoundRoute );
	}

	// Add routes to the root route
	const routeTree = rootRoute.addChildren( createdRoutes );

	// Create router with configuration
	const router = createRouter( createRouterConfig( routeTree ) );

	// Router State provider component
	return () => <RouterProvider router={ router } />;
};

/**
 * Creates a route configuration object
 * @param {string}    path      - Route path
 * @param {Component} component - Route component
 * @param {Array}     children  - Optional child routes
 * @return {Object} Route configuration object
 */
export const createRoute = ( path, component, children = null ) => {
	// Handle - if third param is array, treat as children example : Advanced Settings
	if ( Array.isArray( children ) ) {
		return {
			path,
			component,
			children,
		};
	}

	// Handle new options format
	return {
		path,
		component,
		...children,
	};
};

/**
 * Creates a child route configuration object
 * @param {string}    path      - Route path
 * @param {Component} component - Route component
 * @param {Array}     children  - Optional nested child routes
 * @return {Object} Child route configuration object
 */
export const createChildRoute = ( path, component, children = null ) => {
	// Handle - if third param is array, treat as children
	if ( Array.isArray( children ) ) {
		return {
			path,
			component,
			children,
		};
	}

	// Handle new options format
	return {
		path,
		component,
		...children,
	};
};

export default createAdminRouter;

const PendingComponent = () => {
	return (
		<div className="w-full h-full flex items-center justify-center p-4">
			<div className="h-96 flex-grow flex items-center justify-center w-full">
				<SureRankLogo className="w-10 h-10 animate-ping" />
			</div>
		</div>
	);
};
