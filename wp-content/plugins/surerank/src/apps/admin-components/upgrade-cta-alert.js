import { __ } from '@wordpress/i18n';
import Alert from '@/global/components/alert';
import { SureRankLogo } from '@/global/components/icons';
import { Button } from '@bsf/force-ui';
import { redirectToPricingPage } from '@/functions/nudges';

const upgradeMessages = [
	__( 'Create a site index visitors actually use', 'surerank' ),
	__( 'Stay ahead with Instant Indexing support', 'surerank' ),
	__( 'Build smarter Meta Templates for all post content', 'surerank' ),
	__( 'Detect and Fix Issues before they hurt SEO', 'surerank' ),
	__( 'Redirect old URLs instantly, no setup needed', 'surerank' ),
];
const currentIndex = Math.floor( Math.random() * upgradeMessages.length );
const message = upgradeMessages[ currentIndex ];

/**
 * Upgrade CTA Alert Component to prompt users to upgrade to Pro
 * with a random benefit message.
 *
 * @param {Object}  props             - Component props
 * @param {boolean} props.isProActive - Whether the Pro version is active
 * @return {JSX.Element|null} The Upgrade CTA Alert component or null if Pro is active
 */
const UpgradeCtaAlert = ( { isProActive = false } ) => {
	if ( isProActive ) {
		return null;
	}
	return (
		<Alert
			message={ message }
			showIcon={ true }
			color="info"
			renderIcon={
				<SureRankLogo width={ 20 } height={ 20 } enableFill={ false } />
			}
			className="items-center"
			action={
				<Button
					className="no-underline hover:underline"
					size="sm"
					variant="link"
					onClick={ () =>
						redirectToPricingPage(
							'wp_admin_search_console_widget'
						)
					}
				>
					{ __( 'Upgrade', 'surerank' ) }
				</Button>
			}
		/>
	);
};

export default UpgradeCtaAlert;
