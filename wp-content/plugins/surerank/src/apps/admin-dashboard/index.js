import { mountComponent } from '@Functions/utils';
import createAdminRouter, {
	createRoute,
	createChildRoute,
} from '@Functions/router';
import Dashboard from './dashboard';
import { Toaster, toast } from '@bsf/force-ui';
import { getNavLinks } from '@Global/constants/nav-links';
import { Navigate } from '@tanstack/react-router';
import SidebarLayout from '@AdminComponents/layout/sidebar-layout';
import SearchConsole from '../admin-search-console';
import {
	ENABLE_GOOGLE_CONSOLE,
	ENABLE_SCHEMAS,
	ENABLE_MIGRATION,
} from '@Global/constants';
import { applyFilters } from '@wordpress/hooks';

// Import all the components directly
import TitleAndDescriptionRoute from '@AdminGeneral/general/title-and-description/title-and-description';
import HomePageRoute from '@AdminGeneral/general/home-page/home-page';
import ArchivePagesRoute from '@AdminGeneral/advanced/archive-pages/archive-pages';
import SocialGeneralRoute from '@AdminGeneral/social/general/general';
import FacebookRoute from '@AdminGeneral/social/facebook/facebook';
import TwitterRoute from '@AdminGeneral/social/twitter/twitter';
import AccountRoute from '@AdminGeneral/social/account/account';
import RobotInstructionsRoute from '@AdminGeneral/advanced/robot-instructions/robot-instructions';
import SitemapsRoute from '@AdminGeneral/advanced/sitemaps/sitemaps';
import ImageSeoRoute from '@AdminGeneral/advanced/image-seo/image-seo';
import FeaturesManagementRoute from '@AdminGeneral/advanced/features-management/features-management';
import ContentAnalysisRoute from '@AdminDashboard/content-analysis/content-analysis';
import SiteSeoChecksRoute from '@AdminDashboard/site-seo-checks/site-seo-checks-main';
import MigrationRoute from '@AdminGeneral/advanced/tools/migration';
import MiscellaneousRoute from '@AdminGeneral/advanced/tools/miscellaneous';
import RobotsTxtEditorRoute from '@AdminGeneral/advanced/tools/robots-txt-editor/robots-txt-editor';
import SchemaRoute from '@AdminGeneral/schema/schema';
import ImportExportSettingsRoute from '@AdminGeneral/advanced/tools/import-export-settings';
import RedirectionManager from '@AdminDashboard/link-manager/redirection-manager';
import InstantIndexingSettings from '@AdminDashboard/instant-indexing/settings';
import InstantIndexingLogs from '@AdminDashboard/instant-indexing/logs';
import EmailReportsRoute from '@AdminGeneral/advanced/email-reports';
import GoogleIndexingSettings from '@AdminDashboard/google-indexing/settings';
import GoogleIndexingLogs from '@AdminDashboard/google-indexing/logs';

// Define toast globally for PRO plugin.
if ( window && ! window?.toast ) {
	window.toast = toast;
}

// Routes
const dashboardRoutes = [
	// Default route redirects to dashboard
	createRoute( '/', () => <Navigate to="/dashboard" />, {
		navbarOnly: true,
	} ),
	// Dashboard routes
	createRoute( '/dashboard', Dashboard, { navbarOnly: true } ),
];

const generalAndAdvancedRoutes = [
	// General routes
	createRoute( '/general', TitleAndDescriptionRoute ),
	createRoute( '/general/homepage', null, [
		createChildRoute( '/', HomePageRoute ),
		createChildRoute( '/social', HomePageRoute ),
		createChildRoute( '/advanced', HomePageRoute ),
	] ),
	createChildRoute( '/general/archive_pages', ArchivePagesRoute ),
	createRoute( '/general/social', null, [
		createChildRoute( '/', SocialGeneralRoute ),
		createChildRoute( '/facebook', FacebookRoute ),
		createChildRoute( '/x', TwitterRoute ),
		createChildRoute( '/accounts', AccountRoute ),
	] ),

	// Advanced routes
	createRoute( '/advanced', null, [
		createRoute( '/robot_instructions', null, [
			createChildRoute( '/indexing', RobotInstructionsRoute ),
			createChildRoute( '/following', RobotInstructionsRoute ),
			createChildRoute( '/archiving', RobotInstructionsRoute ),
		] ),
		createChildRoute( '/email-reports', EmailReportsRoute ),
		createChildRoute( '/sitemaps', SitemapsRoute ),
		createChildRoute( '/image-seo', ImageSeoRoute ),
		// Conditionally include schema route
		...( ENABLE_SCHEMAS && SchemaRoute
			? [ createChildRoute( '/schema', SchemaRoute ) ]
			: [] ),
		createChildRoute( '/robots-txt-editor', RobotsTxtEditorRoute ),
	] ),
];

const searchConsoleRoutes = [
	createRoute( '/search-console', SearchConsole, { navbarOnly: true } ),
	createRoute( '/content-performance', ContentAnalysisRoute, {
		navbarOnly: true,
	} ),
];

const siteSeoAnalysisRoutes = [
	createRoute( '/site-seo-analysis', SiteSeoChecksRoute, {
		navbarOnly: true,
	} ),
];

// Link Manager routes
const linkManagerRoutes = [
	createRoute( '/link-manager', null, [
		createChildRoute( '/redirection-manager', RedirectionManager, {
			fullWidth: true,
			navbarOnly: true,
		} ),
	] ),
];

// Instant Indexing routes
const instantIndexingRoutes = [
	createRoute( '/advanced/instant-indexing', null, [
		createChildRoute( '/settings', InstantIndexingSettings, {
			fullWidth: false,
		} ),
		createChildRoute( '/logs', InstantIndexingLogs, {
			fullWidth: false,
		} ),
	] ),
];

// Google Indexing routes
const googleIndexingRoutes = [
	createRoute( '/advanced/google-indexing', null, [
		createChildRoute( '/settings', GoogleIndexingSettings, {
			fullWidth: false,
		} ),
		createChildRoute( '/logs', GoogleIndexingLogs, {
			fullWidth: false,
		} ),
	] ),
];

// Tools routes
const toolsRoutes = [
	createRoute( '/tools', null, [
		createChildRoute( '/manage-features', FeaturesManagementRoute ),
		createChildRoute( '/import-export', ImportExportSettingsRoute ),
		...( ENABLE_MIGRATION
			? [ createChildRoute( '/migrate', MigrationRoute ) ]
			: [] ),
		createChildRoute( '/miscellaneous', MiscellaneousRoute ),
	] ),
];

// Combine all routes
const baseRoutes = [
	...dashboardRoutes,
	...generalAndAdvancedRoutes,
	...instantIndexingRoutes,
	...googleIndexingRoutes,
	...linkManagerRoutes,
	...toolsRoutes,
	...siteSeoAnalysisRoutes,
	// Conditionally include search console routes
	...( ENABLE_GOOGLE_CONSOLE ? searchConsoleRoutes : [] ),
];

const filteredRoutes = applyFilters( 'surerank-pro.routes', [ ...baseRoutes ] );

const routeMap = new Map();

filteredRoutes.forEach( ( route ) => {
	routeMap.set( route.path, route );
} );

export const routes = Array.from( routeMap.values() );

// Navigation Links
export const navLinks = getNavLinks();

// Create router using the original createAdminRouter but with custom layout
const Router = createAdminRouter( {
	navLinks,
	routes,
	defaultLayout: {
		component: SidebarLayout,
		props: {},
	},
} );

const App = () => {
	return (
		<>
			<Router />
			<Toaster className="z-999999" />
		</>
	);
};

mountComponent( '#surerank-root', <App /> );
