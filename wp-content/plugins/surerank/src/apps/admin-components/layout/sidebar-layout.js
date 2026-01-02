import { __ } from '@wordpress/i18n';
import {
	Outlet,
	Link,
	useMatchRoute,
	useLocation,
	useNavigate,
} from '@tanstack/react-router';
import {
	Badge,
	Topbar,
	Sidebar,
	Accordion,
	HamburgerMenu,
	Button,
	Skeleton,
} from '@bsf/force-ui';
import {
	BookOpenText,
	Megaphone,
	ChartNoAxesColumnIncreasing,
} from 'lucide-react';
import withSuspense from '@AdminComponents/hoc/with-suspense';
import SidebarSkeleton from '../sidebar-skeleton';
import { cn, getSeoCheckLabel } from '@Functions/utils';
import { isProActive } from '@/functions/nudges';
import useWhatsNewRSS from '../../../../lib/useWhatsNewRSS';
import {
	renderToString,
	Suspense,
	useLayoutEffect,
	Fragment,
	useMemo,
	useEffect,
} from '@wordpress/element';
import GlobalSearch from '@AdminComponents/global-search';
import ConfirmationDialog from '@AdminComponents/confirmation-dialog';
import { useSuspenseSiteSeoAnalysis } from '@/apps/admin-dashboard/site-seo-checks/site-seo-checks-main';
import { getSeverityColor } from '@GlobalComponents/seo-checks';
import Logo from '@AdminComponents/logo';
import { Tooltip } from '@AdminComponents/tooltip';
import TanStackRouterDevtools from '@AdminComponents/tanstack-router-dev-tools';
import '@AdminStore/store';
import { UpgradeButton } from '@/global/components/nudges';
import VersionBadge from '../version-badge';

// Stylesheets
import '@Global/style.scss';

const NavLink = ( { path, children } ) => {
	const matchRoute = useMatchRoute();
	const isActive = matchRoute( { to: path } );

	return (
		<Link
			to={ path }
			className={ cn(
				'flex items-center justify-start gap-2.5 py-2 pl-2.5 pr-2 text-text-secondary [&_svg]:text-icon-secondary hover:bg-background-secondary rounded-md text-base font-normal no-underline cursor-pointer focus:outline-none focus:shadow-none transition ease-in-out duration-150 [&_svg]:size-5',
				isActive &&
					'bg-background-secondary text-text-primary [&_svg]:text-brand-800'
			) }
			role="menuitem"
			tabIndex={ 0 }
		>
			{ children }
		</Link>
	);
};

const SiteSeoAnalysisBadge = () => {
	const [ { report } ] = useSuspenseSiteSeoAnalysis();

	// Check counts of error, warning and success
	const counts = useMemo(
		() =>
			Object.values( report ).reduce(
				( acc, curr ) => {
					//if ignore is true, then it is ignored
					if ( curr.ignore ) {
						acc.ignored++;
					} else {
						acc[ curr.status ]++;
					}
					return acc;
				},
				{ error: 0, warning: 0, success: 0, ignored: 0 }
			),
		[ report ]
	);

	const selectedType =
		( counts.error && 'error' ) ||
		( counts.warning && 'warning' ) ||
		'success';

	const isDashboard = () => {
		const url = new URL( window.location.href );
		const page = url.searchParams.get( 'page' );
		return page === 'surerank';
	};

	// Add/update the badge in the WP sidebar.
	useEffect( () => {
		// WP sidebar element.
		const sidebarMenu = document.querySelector(
			'#toplevel_page_surerank > a > div.wp-menu-name'
		);
		if ( ! sidebarMenu ) {
			return;
		}

		// Check if the badge is already added.
		const notificationBadge = sidebarMenu.querySelector( '.awaiting-mod' );
		if ( notificationBadge ) {
			notificationBadge.className =
				counts.error > 0 ? 'awaiting-mod' : '';
			notificationBadge.textContent =
				counts.error > 0 ? counts.error : '';
			return;
		}

		// Add space after the menu name if not already present.
		if ( ! sidebarMenu.textContent.endsWith( ' ' ) ) {
			sidebarMenu.textContent += ' ';
		}

		// Create and add the badge.
		const badge = document.createElement( 'span' );
		badge.className = counts.error > 0 ? 'awaiting-mod' : '';
		badge.textContent = counts.error > 0 ? counts.error : '';
		sidebarMenu.appendChild( badge );
	}, [ counts ] );

	return (
		<Link
			className="no-underline hover:no-underline focus:no-underline focus:[box-shadow:none]"
			to={
				isDashboard()
					? '/site-seo-analysis'
					: `${ surerank_globals.wp_dashboard_url }?page=surerank#/site-seo-analysis`
			}
		>
			<Badge
				icon={ <ChartNoAxesColumnIncreasing /> }
				label={ getSeoCheckLabel(
					selectedType,
					counts.error || counts.warning || counts.success
				) }
				variant={ getSeverityColor( selectedType ) }
			/>
		</Link>
	);
};

const SubmenuAccordion = ( { label, icon: Icon, submenu } ) => {
	const navigate = useNavigate();

	return (
		<Accordion defaultValue="item1" iconType="arrow" type="simple">
			<Accordion.Item value="item1">
				<Accordion.Trigger
					iconType="arrow"
					className="p-2 pl-2.5 text-base font-normal [&_svg]:text-icon-secondary hover:bg-background-primary rounded-md no-underline cursor-pointer focus:outline-none focus:shadow-none transition ease-in-out duration-150 [&_svg]:size-5 [&_div]:font-normal [&_div]:text-text-primary"
					aria-label={ `${ label } submenu` }
					onClick={ ( event ) => {
						event.preventDefault();
						event.stopPropagation();

						if ( submenu?.length <= 0 || ! submenu[ 0 ]?.path ) {
							return;
						}

						// Redirect to the first item in the submenu.
						navigate( {
							to: submenu[ 0 ].path,
						} );
					} }
				>
					{ Icon && <Icon className="size-4" /> }
					{ label }
				</Accordion.Trigger>
				<Accordion.Content className="p-2 [&>div]:pb-0">
					<div
						className="border-l border-solid border-r-0 border-t-0 border-b-0 border-border-subtle pl-2 ml-1 space-y-0.5"
						role="menu"
					>
						{ submenu.map(
							( { path, label: subLabel, icon: SubIcon } ) => (
								<NavLink key={ path } path={ path }>
									{ SubIcon && (
										<SubIcon className="size-4" />
									) }
									{ subLabel }
								</NavLink>
							)
						) }
					</div>
				</Accordion.Content>
			</Accordion.Item>
		</Accordion>
	);
};

const SidebarSection = ( { section, links } ) => {
	if ( ! links?.length ) {
		return null;
	}

	return (
		<Sidebar.Item
			key={ section }
			arrow
			heading={ section }
			open={ true }
			className="space-y-0.5"
		>
			{ links.map( ( { path, label, icon: Icon, submenu } ) =>
				submenu ? (
					<SubmenuAccordion
						key={ path || label }
						label={ label }
						icon={ Icon }
						submenu={ submenu }
					/>
				) : (
					<NavLink key={ path } path={ path }>
						{ Icon && <Icon className="size-4" /> }
						{ label }
					</NavLink>
				)
			) }
		</Sidebar.Item>
	);
};

const SidebarNavigation = ( { navLinks = [] } ) => {
	return (
		<div className="relative h-full w-full before:content-[''] before:block before:fixed before:top-0 before:bottom-0 before:w-[289px] before:h-full before:bg-background-primary before:border-r before:border-l-0 before:border-y-0 before:border-solid before:border-border-subtle before:-z-10">
			<Sidebar borderOn className="!h-full w-full p-4">
				<Sidebar.Body>
					<Sidebar.Item
						role="navigation"
						aria-label="Main Navigation"
					>
						{ navLinks.map(
							( { section, links, path } ) =>
								! path &&
								links?.length > 0 && (
									<SidebarSection
										key={ section }
										section={ section }
										links={ links }
									/>
								)
						) }
					</Sidebar.Item>
				</Sidebar.Body>
			</Sidebar>
		</div>
	);
};

const SuspenseNavbar = withSuspense( SidebarNavigation, SidebarSkeleton );

const useNavbarLinks = ( navLinks ) => {
	const matchRoute = useMatchRoute();

	const activeSection = navLinks.find( ( { links = [] } ) =>
		links.some( ( { path, submenu = null } ) => {
			if ( submenu ) {
				return submenu.some( ( { path: subPath } ) =>
					matchRoute( { to: subPath } )
				);
			}
			return matchRoute( { to: path } );
		} )
	);

	const reConstructedNavLinks = navLinks.reduce( ( acc, curr ) => {
		acc.push( {
			label: curr.section,
			path: curr.links[ 0 ].path,
			active: curr.sectionId === activeSection?.sectionId,
		} );
		return acc;
	}, [] );

	return {
		activeSection,
		navbarLinks: reConstructedNavLinks,
	};
};

const useRouteConfig = ( routes ) => {
	const location = useLocation();
	const currentPath = location.pathname;

	// Function to recursively search for route configuration
	const findRouteConfig = ( routesList, path, parentPath = '' ) => {
		for ( const route of routesList ) {
			// Build the full path by combining parent path with current route path
			const fullPath = parentPath + route.path;
			// Check if this route matches the current path
			if ( fullPath === path ) {
				return route;
			}

			// Check child routes recursively
			if ( route.children ) {
				const childResult = findRouteConfig(
					route.children,
					path,
					fullPath
				);
				if ( childResult ) {
					return childResult;
				}
			}
		}
		return null;
	};

	const currentRoute = findRouteConfig( routes, currentPath );
	return {
		isNavbarOnly: currentRoute?.navbarOnly || false,
		isFullWidth: currentRoute?.fullWidth || false,
	};
};

const SidebarLayout = ( {
	navLinks = [],
	routes = [],
	navbarOnly = false,
} ) => {
	const { activeSection, navbarLinks: topNavbarLinks } =
		useNavbarLinks( navLinks );
	const location = useLocation();
	// Get route configuration using the unified hook
	const { isNavbarOnly: routeNavbarOnly, isFullWidth } =
		useRouteConfig( routes );
	const isNavbarOnly = routeNavbarOnly || navbarOnly;

	// Use only the links of the active section
	const filteredNavLinks = activeSection ? [ activeSection ] : [];

	useWhatsNewRSS( {
		uniqueKey: 'surerank',
		rssFeedURL: 'https://surerank.com/whats-new/feed/', // TODO: domain name change to surerank.
		selector: '#surerank_whats_new',
		flyout: {
			title: __( "What's New?", 'surerank' ),
		},
		triggerButton: {
			icon: renderToString(
				<Megaphone
					className="size-4 m-1 text-icon-primary"
					strokeWidth={ 1.5 }
				/>
			),
		},
	} );

	// Manipulate the WP sidebar menu to make active menu item.
	useLayoutEffect( () => {
		const sidebarMenu = document.getElementById( 'toplevel_page_surerank' );
		if ( ! sidebarMenu ) {
			return;
		}

		const menuItems = sidebarMenu.querySelectorAll( 'a' );
		const currentPath = location.pathname.split( '/' )[ 1 ];

		// Remove current class from the currently active item
		const currentActiveItem = sidebarMenu.querySelector( '.current' );
		if ( currentActiveItem ) {
			currentActiveItem.classList.remove( 'current' );
		}
		Array.from( menuItems ).forEach( ( item ) => {
			const itemPath = item.href.split( '#' )[ 1 ]?.split( '/' )[ 1 ];

			if ( currentPath === 'dashboard' ) {
				// If path is 'dashboard', add current to the item with no path (undefined)
				if ( itemPath === undefined ) {
					item.parentElement.classList.add( 'current' );
				}
			}
			// Otherwise, add current to the matching path item
			if ( currentPath !== 'dashboard' && itemPath === currentPath ) {
				item.parentElement.classList.add( 'current' );
			}
		} );

		return () => {
			Array.from( menuItems ).forEach( ( item ) => {
				item.parentElement.classList.remove( 'current' );
			} );
		};
	}, [ location.pathname ] );

	return (
		<Fragment>
			<div id="surerank-admin-dashboard" className="grid grid-rows-[64px_auto] min-h-full bg-background-secondary">
				{ /* Header */ }
				<Topbar
					className="w-auto min-h-[unset] h-16 shadow-sm p-0 relative z-[1]"
					gap={ 0 }
				>
					<Topbar.Left className="p-5">
						<Topbar.Item className="flex md:hidden">
							<HamburgerMenu className="lg:hidden">
								<HamburgerMenu.Toggle className="size-6" />
								<HamburgerMenu.Options>
									{ topNavbarLinks.map( ( option ) => (
										<HamburgerMenu.Option
											key={ option.label }
											to={ option.path }
											tag={ Link }
											active={ option.active }
										>
											{ option.label }
										</HamburgerMenu.Option>
									) ) }
								</HamburgerMenu.Options>
							</HamburgerMenu>
						</Topbar.Item>
						<Topbar.Item>
							<Logo />
						</Topbar.Item>
					</Topbar.Left>
					<Topbar.Middle align="left" className="h-full">
						<Topbar.Item className="h-full gap-4 hidden md:flex">
							{ topNavbarLinks.map(
								( { path, label, active } ) => (
									<Link
										key={ path }
										to={ path }
										className={ cn(
											'relative content-center no-underline h-full py-0 px-3 m-0 bg-transparent outline-none shadow-none border-0 focus:outline-none text-text-secondary text-sm font-medium cursor-pointer whitespace-nowrap',
											active && 'text-text-primary'
										) }
									>
										{ label }
										{ active && (
											<span className="absolute bottom-0 left-0 w-full h-px bg-brand-800" />
										) }
									</Link>
								)
							) }
						</Topbar.Item>
						{ ! isProActive() && (
							<Topbar.Item>
								<UpgradeButton
									label={ __( 'Upgrade', 'surerank' ) }
									variant="link"
									size="md"
									iconPosition="right"
									showUnderLine={ true }
									utmMedium="surerank_topbar"
									className="hidden" // TODO: Remove this class to enable the button
								/>
							</Topbar.Item>
						) }
					</Topbar.Middle>
					<Topbar.Right className="p-5">
						<Topbar.Item>
							<GlobalSearch navLinks={ navLinks } />
						</Topbar.Item>
						<Topbar.Item className="space-x-3">
							<VersionBadge />
						</Topbar.Item>
						<Topbar.Item>
							<Suspense
								fallback={ <Skeleton className="w-20 h-6" /> }
							>
								<SiteSeoAnalysisBadge />
							</Suspense>
						</Topbar.Item>
						<Topbar.Item>
							<Tooltip
								content={ __( 'Knowledge Base', 'surerank' ) }
								placement="bottom"
								arrow
								className="z-[99999]"
							>
								<Button
									size="sm"
									tag="a"
									variant="link"
									className="text-text-primary focus:[box-shadow:none]"
									href={ surerank_globals?.help_link ?? '#' }
									target="_blank"
									rel="noreferrer noopener"
									aria-label={ __(
										'Knowledge Base',
										'surerank'
									) }
									icon={
										<BookOpenText
											className="size-4 m-1"
											strokeWidth="1.5"
										/>
									}
								/>
							</Tooltip>
						</Topbar.Item>
						<Topbar.Item>
							<div
								id="surerank_whats_new"
								className="[&>a]:p-0.5 [&>a]:pl-0"
							></div>
						</Topbar.Item>
					</Topbar.Right>
				</Topbar>
				{
					// Sidebar Navigation
					! isNavbarOnly && (
						<div className="w-full h-full grid grid-cols-[290px_1fr] max-[782px]:min-h-[calc(100dvh_-_110px)] min-h-[calc(100dvh_-_96px)]">
							{ ! isNavbarOnly && (
								<SuspenseNavbar navLinks={ filteredNavLinks } />
							) }

							{ /* Main content */ }
							<div className="bg-background-secondary p-5">
								<main
									className={ cn(
										'mx-auto',
										isFullWidth ? 'w-full' : 'max-w-[768px]'
									) }
								>
									<Outlet />
								</main>
							</div>
						</div>
					)
				}
				{
					// Without sidebar navigation
					isNavbarOnly && (
						<div className="w-full h-fit max-[782px]:min-h-[calc(100dvh_-_110px)] min-h-[calc(100dvh_-_96px)] bg-background-secondary">
							<main className="w-full h-full mx-auto relative">
								<Outlet />
							</main>
						</div>
					)
				}
				{ /* TanStack Router Devtools */ }
				{ process.env.NODE_ENV !== 'production' && (
					<Suspense>
						<TanStackRouterDevtools />
					</Suspense>
				) }
			</div>
			<ConfirmationDialog />
		</Fragment>
	);
};

export default SidebarLayout;
