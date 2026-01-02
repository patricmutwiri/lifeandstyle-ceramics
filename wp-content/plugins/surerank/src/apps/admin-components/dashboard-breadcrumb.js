import { useLocation } from '@tanstack/react-router';
import { __ } from '@wordpress/i18n';
import { Home } from 'lucide-react';
import { getNavLinks } from '@Global/constants/nav-links';
import { Breadcrumb } from '@bsf/force-ui';

const generateBreadcrumbMap = () => {
	const navLinks = getNavLinks();
	const breadcrumbMap = {};

	breadcrumbMap[ '/' ] = __( 'Search Console', 'surerank' );

	navLinks.forEach( ( section ) => {
		section.links.forEach( ( link ) => {
			// Add top-level link
			if ( link.path.startsWith( '/' ) ) {
				breadcrumbMap[ link.path ] = link.label;
			} else {
				const normalizedPath = link.path.includes( '#/' )
					? link.path.split( '#/' )[ 1 ] || '/'
					: link.path;
				breadcrumbMap[ `/${ normalizedPath }` ] = link.label;
			}

			if ( link.submenu ) {
				link.submenu.forEach( ( subLink ) => {
					if ( subLink.path.startsWith( '/' ) ) {
						breadcrumbMap[ subLink.path ] = subLink.label;
					} else {
						const normalizedSubPath = subLink.path.includes( '#/' )
							? subLink.path.split( '#/' )[ 1 ]
							: subLink.path;
						breadcrumbMap[ `/${ normalizedSubPath }` ] =
							subLink.label;
					}
				} );
			}
		} );
	} );

	return breadcrumbMap;
};

const breadcrumbMap = generateBreadcrumbMap();

export const useBreadcrumb = () => {
	const location = useLocation();
	const pathname = location.pathname; // e.g., "/", "/content-performance", "/social/facebook"

	// Always include the Dashboard as the root
	const breadcrumbs = [
		{
			label: breadcrumbMap[ '/' ],
			path: '/',
			icon: Home, // Add icon for Dashboard
		},
	];

	// Add current route if not the root
	if ( pathname !== '/' ) {
		// Split pathname for nested routes (e.g., "/social/facebook")
		const pathSegments = pathname
			.split( '/' )
			.filter( ( segment ) => segment );
		let currentPath = '';

		// Build breadcrumb for each segment
		pathSegments.forEach( ( segment ) => {
			currentPath += `/${ segment }`;
			if ( breadcrumbMap[ currentPath ] ) {
				breadcrumbs.push( {
					label: breadcrumbMap[ currentPath ],
					path: currentPath,
				} );
			}
		} );
	}

	return breadcrumbs;
};

export const getCurrentBreadcrumb = ( breadcrumbs ) => {
	return (
		<Breadcrumb size="md">
			<Breadcrumb.List>
				{ breadcrumbs.map( ( crumb, index ) => (
					<>
						<Breadcrumb.Item key={ index }>
							{ index === breadcrumbs.length - 1 ? (
								<Breadcrumb.Page>
									{ crumb.label }
								</Breadcrumb.Page>
							) : (
								<Breadcrumb.Link
									href="#/search-console"
									className="flex items-center gap-2 hover:no-underline"
								>
									{ crumb.icon && index === 0 && (
										<Home className="w-4 h-4 text-text-primary" />
									) }
									{ crumb.label }
								</Breadcrumb.Link>
							) }
						</Breadcrumb.Item>
						{ index < breadcrumbs.length - 1 && (
							<Breadcrumb.Separator type="slash" />
						) }
					</>
				) ) }
			</Breadcrumb.List>
		</Breadcrumb>
	);
};
