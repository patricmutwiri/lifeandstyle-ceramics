import UpgradeFeatureCard from '@/global/components/nudges/upgrade-feature-card';
import { __ } from '@wordpress/i18n';

/**
 * Redirection Manager - Pro Feature Placeholder
 * Displays upgrade nudge for the Redirection Manager feature
 */
const RedirectionManager = () => {
	return (
		<div className="mx-8 py-8">
			<UpgradeFeatureCard
				title={ __( 'Smart Redirection Manager', 'surerank' ) }
				subtitle={ __(
					'Never lose visitors or rankings when your URLs change. The Redirection Manager makes sure old links still lead to the right place, keeping your traffic safe.',
					'surerank'
				) }
				description={ [
					__(
						'Create 301 redirects instantly for smooth user experience',
						'surerank'
					),
					__(
						'Automatically capture old and new permalinks whenever you update URLs',
						'surerank'
					),
					__(
						'Choose and manage redirects with full control inside the plugin',
						'surerank'
					),
				] }
				imageName="upgrade-redirect-manager.svg"
				utmMedium="surerank_redirect_manager"
			/>
		</div>
	);
};

export default RedirectionManager;
