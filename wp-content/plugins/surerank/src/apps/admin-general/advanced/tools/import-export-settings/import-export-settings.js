import { __ } from '@wordpress/i18n';
import ImportSection from './import-section';
import ExportSection from './export-section';
import PageContentWrapper from '@/apps/admin-components/page-content-wrapper';

const ImportExportSettings = () => {
	return (
		<PageContentWrapper
			title={ __( 'Import/Export', 'surerank' ) }
			description={ __(
				"You can use this option to move your SureRank settings between sites. It's helpful if you're setting up multiple websites and want to reuse the same configuration. Just export from one site and import to another.",
				'surerank'
			) }
		>
			{ /* Import Section */ }
			<ImportSection />

			{ /* Export Section */ }
			<ExportSection />
		</PageContentWrapper>
	);
};

export default ImportExportSettings;
