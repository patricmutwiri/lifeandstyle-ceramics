import PageContentWrapper from '@/apps/admin-components/page-content-wrapper';
import { UpgradeNotice } from '@/global/components/nudges';
import { __ } from '@wordpress/i18n';

/**
 * Google Indexing Settings - Pro Feature Placeholder
 * Displays upgrade nudge for the Google Indexing Settings feature
 */
const GoogleIndexingSettings = () => {
	return (
		<PageContentWrapper
			title={ __( 'Google Indexing', 'surerank' ) }
			description={ __(
				'Automatically notify Google when your content is published or updated. Get your pages indexed faster with Google Indexing API integration and ensure your latest content appears in search results.',
				'surerank'
			) }
		>
			<UpgradeNotice
				title={ __(
					'Unlock Google Indexing API for faster indexing',
					'surerank'
				) }
				plan="pro"
				description={ __(
					'Upgrade to SureRank Pro or Business and let Google know about your new or updated content instantly.',
					'surerank'
				) }
				utmMedium="surerank_google_indexing"
			/>
		</PageContentWrapper>
	);
};

export default GoogleIndexingSettings;

