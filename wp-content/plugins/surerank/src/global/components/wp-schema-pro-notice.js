import { __ } from '@wordpress/i18n';
import Alert from '@/global/components/alert';

const WpSchemaProNotice = ( { className = '', ...props } ) => {
	return (
		<Alert
			color="info"
			title={ __( 'SureRank Schema Disabled', 'surerank' ) }
			message={ __(
				'We detected that WP Schema Pro is active on your website. To avoid schema conflicts, SureRank Schema functionality has been automatically disabled. Please continue using WP Schema Pro for your schema markup needs.',
				'surerank'
			) }
			className={ className }
			{ ...props }
		/>
	);
};

export default WpSchemaProNotice;
