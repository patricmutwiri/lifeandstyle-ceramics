import PageContentWrapper from '@/apps/admin-components/page-content-wrapper';
import { UpgradeNotice } from '@/global/components/nudges';
import { __ } from '@wordpress/i18n';

/**
 * Instant Indexing Settings - Pro Feature Placeholder
 * Displays upgrade nudge for the Instant Indexing Settings feature
 */
const InstantIndexingSettings = () => {
	return (
		<PageContentWrapper
			title={ __( 'Instant Indexing', 'surerank' ) }
			description={ __(
				'Automatically notify search engines like Bing and Yandex when your content is published. Improve indexing speed and ensure your latest content appears in search results faster with IndexNow integration.',
				'surerank'
			) }
		>
			<UpgradeNotice
				title={ __(
					'Unlock IndexNow for instant indexing',
					'surerank'
				) }
				description={ __(
					'Upgrade to SureRank Pro and let search engines know about your new or updated content instantly.',
					'surerank'
				) }
				utmMedium="surerank_instant_indexing"
			/>
		</PageContentWrapper>
	);
};

export default InstantIndexingSettings;
