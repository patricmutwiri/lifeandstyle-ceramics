import { __ } from '@wordpress/i18n';
import { Button, Text } from '@bsf/force-ui';
import { SparklesIcon } from '@GlobalComponents/icons';
import { redirectToPricingPage } from '@Functions/nudges';

/**
 * UpgradeCpt Component
 *
 * Props:
 *  - onClickUpgrade?: function
 *      Optional callback invoked when the "Upgrade Now" button is clicked.
 *      If not provided, the component will redirect to the pricing page with
 *      UTM parameter 'ai_credits_exhausted'.
 *  - title?: string
 *      Custom title text (defaults to 'Free Plan Limit Reached')
 *  - description?: string
 *      Custom description text (defaults to upgrade message)
 *  - buttonText?: string
 *      Custom button text (defaults to 'Upgrade Now')
 *  - showButton?: boolean
 *      Whether to show the upgrade button (defaults to true)
 * Example:
 *  <UpgradeCpt
 *    title="Custom Title"
 *    description="Custom message"
 *    buttonText="Get Pro"
 *    showButton={true}
 *    onClickUpgrade={() => window.open(pricingUrl, '_blank')}
 *  />
 * @param {Object}   props                Component props
 * @param {Function} props.onClickUpgrade Optional callback for upgrade button click
 * @param {string}   props.title          Optional custom title
 * @param {string}   props.description    Optional custom description
 * @param {string}   props.buttonText     Optional custom button text
 * @param {boolean}  props.showButton     Optional flag to show/hide button
 * @return {JSX.Element}                      Rendered component
 */
const UpgradeCpt = ( {
	onClickUpgrade,
	title = __( 'Free Plan Limit Reached', 'surerank' ),
	description = __(
		"You've used all available AI requests in your Free Plan. Upgrade to SureRank Pro to keep using AI features.",
		'surerank'
	),
	buttonText = __( 'Upgrade Now', 'surerank' ),
	showButton = true,
} ) => {
	const handleUpgradeClick = () => {
		if ( typeof onClickUpgrade !== 'function' ) {
			redirectToPricingPage( 'ai_credits_exhausted' );
			return;
		}
		onClickUpgrade();
	};
	return (
		<div className="bg-background-secondary rounded-lg p-2">
			<div className="bg-background-primary rounded-md p-4 flex flex-col items-center gap-3">
				<SparklesIcon className="text-text-primary" />
				<div className="text-center">
					<Text as="h3" size={ 16 } weight={ 600 } color="primary">
						{ title }
					</Text>
					<Text color="secondary" className="mt-1">
						{ description }
					</Text>
				</div>
				{ showButton && (
					<Button
						variant="primary"
						size="md"
						onClick={ handleUpgradeClick }
					>
						{ buttonText }
					</Button>
				) }
			</div>
		</div>
	);
};

export default UpgradeCpt;
