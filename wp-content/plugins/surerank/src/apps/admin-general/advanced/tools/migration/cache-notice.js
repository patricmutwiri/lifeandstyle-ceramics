import { __ } from '@wordpress/i18n';
import Alert from '@/global/components/alert';
import { PLUGIN_OPTIONS } from '@AdminGeneral/advanced/tools/migration/constants';

const CachePluginAlert = () => {
	const { active_cache_plugins: activeCachePlugins = false } =
		window?.surerank_admin_common || {};

	if ( ! ( activeCachePlugins && PLUGIN_OPTIONS.length ) ) {
		return null;
	}

	return (
		<Alert
			color="warning"
			title={ __(
				'SureRank migration will run smoothly if the cache plugin is temporarily paused',
				'surerank'
			) }
			message={ __(
				'We’ve detected a cache plugin on your site. While not required, we recommend turning it off during migration to avoid any possible conflicts. You can keep it active if you prefer, but disabling it for now may help ensure everything runs smoothly.',
				'surerank'
			) }
		/>
	);
};

export default CachePluginAlert;
