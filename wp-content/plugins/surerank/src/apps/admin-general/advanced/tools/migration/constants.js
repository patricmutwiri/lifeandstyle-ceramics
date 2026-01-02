import { __ } from '@wordpress/i18n';
import { getNavLinks } from '@Global/constants/nav-links';

export const PAGE_TITLE = __( 'Migrate', 'surerank' );
export const PAGE_DESCRIPTION = __(
	'Simplify your SEO management by migrating your settings from a plugin to SureRank, ensuring a seamless transition and consistent optimization across your website.',
	'surerank'
);

export const PLUGIN_OPTIONS = Object.entries(
	surerank_admin_common?.plugins_for_migration ?? {}
).map( ( [ slug, data ] ) => ( {
	slug,
	name: data.name,
	active: !! data.active,
} ) );
const MIGRATED_PLUGINS = Object.keys(
	surerank_admin_common?.migration_completed_plugins ?? {}
);
export const ACTIVE_MIGRATED_PLUGINS = PLUGIN_OPTIONS.filter(
	( plugin ) => plugin.active
).filter( ( { slug } ) => MIGRATED_PLUGINS.includes( slug ) );

// Get parent items from navLinks excluding Schema

export const MIGRATED_ITEMS = [
	...new Set(
		getNavLinks().flatMap( ( section ) =>
			section.links
				.filter( ( link ) => link.migratable )
				.map( ( link ) => link.label )
		)
	),
];

// Only include Schema in the not migrated list
export const NOT_MIGRATED_ITEMS = [ __( 'Schema', 'surerank' ) ];

// Local storage key for migration progress
export const MIGRATION_PROGRESS_KEY = 'surerank_migration_progress';

// Migration state actions
export const ACTIONS = {
	INIT_MIGRATION: 'INIT_MIGRATION',
	SET_PLUGIN: 'SET_PLUGIN',
	START_MIGRATION: 'START_MIGRATION',
	COMPLETE_GLOBAL_SETTINGS: 'COMPLETE_GLOBAL_SETTINGS',
	COMPLETE_TERM: 'COMPLETE_TERM',
	COMPLETE_POST: 'COMPLETE_POST',
	SET_ERROR: 'SET_ERROR',
	COMPLETE_MIGRATION: 'COMPLETE_MIGRATION',
	RESET_MIGRATION: 'RESET_MIGRATION',
	LOAD_SAVED_STATE: 'LOAD_SAVED_STATE',
	SET_MIGRATION_DATA: 'SET_MIGRATION_DATA',
	SET_MIGRATION_STATUS: 'SET_MIGRATION_STATUS',
	SET_DEACTIVATE_PLUGIN: 'SET_DEACTIVATE_PLUGIN',
};
