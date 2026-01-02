import { Text } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';
import { cn } from '@Functions/utils';
import { isProActive } from '@/functions/nudges';
import UpgradeButton from './upgrade-button';

/**
 * UpgradeNotice - A reusable component for promoting Pro features
 * Based on Figma design: banner component
 *
 * @param {Object}   props               - Component props
 * @param {string}   props.title         - The main title text (default: "Ready to tailor SEO by post type?")
 * @param {string}   props.description   - The description text (default: "Upgrade to unlock custom meta templates for posts, pages, products, etc.")
 * @param {string}   props.buttonLabel   - The button label text (default: "Upgrade now")
 * @param {Function} props.onButtonClick - Optional button click handler
 * @param {string}   props.position      - Icon position ('left' | 'right') (default: 'right')
 * @param {string}   props.className     - Additional CSS classes
 * @param {string}   props.variant       - Color variant ('blue' | 'green' | 'purple') (default: 'blue')
 * @param {string}   props.plan          - Plan name (default: 'starter')
 * @param {string}   props.utmMedium     - UTM medium parameter for tracking (e.g., 'surerank_schema')
 * @return {JSX.Element} UpgradeNotice component
 */
const UpgradeNotice = ( {
	title = __( 'Wants to unlock advanced SEO features?', 'surerank' ),
	description = __(
		'Upgrade to unlock Video / News Sitemaps, Instant Indexing, Redirect Manager, and more.',
		'surerank'
	),
	buttonLabel = __( 'Upgrade now', 'surerank' ),
	onButtonClick,
	position = 'right',
	className = '',
	variant = 'blue',
	plan,
	utmMedium,
	...props
} ) => {
	const variantClasses = {
		blue: 'bg-brand-background-50 border-indigo-300',
		green: 'bg-badge-background-green border-green-300',
		purple: 'bg-purple-50 border-purple-300',
	};

	// Don't render if Pro is active
	if ( isProActive( plan ) ) {
		return null;
	}

	return (
		<div
			className={ cn(
				'flex flex-row items-stretch gap-2 p-3 rounded-lg border border-solid shadow-sm',
				variantClasses[ variant ],
				className
			) }
			role="banner"
			{ ...props }
		>
			{ /* Content Section */ }
			<div className="flex flex-row items-center gap-2 flex-1">
				{ /* Text Content */ }
				<div className="flex flex-col gap-1 flex-1">
					<Text
						size={ 14 }
						weight={ 600 }
						color="primary"
						lineHeight={ 20 }
					>
						{ title }
					</Text>
					<Text
						size={ 14 }
						weight={ 400 }
						color="secondary"
						lineHeight={ 20 }
					>
						{ description }
					</Text>
				</div>

				{ /* Action Section */ }
				<div className="flex items-center gap-2">
					<UpgradeButton
						label={ buttonLabel }
						variant="link"
						size="md"
						iconPosition={ position ?? 'right' }
						onClick={ onButtonClick }
						utmMedium={ utmMedium }
					/>
				</div>
			</div>
		</div>
	);
};

export default UpgradeNotice;
