import { Text } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';
import { cn } from '@Functions/utils';
import { isProActive } from '@/functions/nudges';
import UpgradeButton from './upgrade-button';

/**
 * UpgradeFeatureCard - A reusable component for showcasing Pro features
 * Based on Figma design: card component (Node ID: 25792-157529)
 *
 * @param {Object}   props                  - Component props
 * @param {string}   props.title            - The main title text (default: "Instantly Index Your Content with SureRank")
 * @param {string}   props.subtitle         - The subtitle text (default: "Get your new and updated pages discovered faster...")
 * @param {string[]} props.description      - Array of feature description strings (each will be a bullet point)
 * @param {string}   props.buttonLabel      - The button label text (default: "Upgrade Now")
 * @param {Function} props.onButtonClick    - Optional button click handler
 * @param {string}   props.className        - Additional CSS classes
 * @param {*}        props.headerContent    - Optional custom header content/image
 * @param {string}   props.imageName        - Image filename (will be combined with assets URL)
 * @param {boolean}  props.showHeader       - Whether to show the header section (default: true)
 * @param {string}   props.headerBackground - Header background color class (default: brand-background-50)
 * @param {string}   props.utmMedium        - UTM medium parameter for tracking (e.g., 'surerank_instant_indexing')
 * @return {JSX.Element} UpgradeFeatureCard component
 */
const UpgradeFeatureCard = ( {
	title = __( 'Instantly Index Your Content with SureRank', 'surerank' ),
	subtitle = __(
		'Get your new and updated pages discovered faster with Instant Indexing in SureRank Pro.',
		'surerank'
	),
	description = [
		__( 'Instantly notify Google of new or updated pages', 'surerank' ),
		__( 'Improve visibility and keep search results fresh', 'surerank' ),
		__( 'Automate indexing for posts, products, and pages', 'surerank' ),
	],
	buttonLabel = __( 'Upgrade Now', 'surerank' ),
	onButtonClick,
	className = '',
	headerContent = null,
	imageName = 'upgrade-indexNow.svg',
	showHeader = true,
	headerBackground = 'bg-brand-background-50',
	utmMedium,
	...props
} ) => {
	// Generate image URL if imageName is provided
	const imageUrl =
		imageName && window?.surerank_globals?.admin_assets_url
			? `${ window.surerank_globals.admin_assets_url }/images/${ imageName }`
			: null;

	// Don't render if Pro is active
	if ( isProActive() ) {
		return null;
	}

	return (
		<div
			className={ cn(
				'flex flex-col gap-2 p-4 bg-background-primary border-0.5 border-solid border-border-subtle rounded-xl shadow-sm',
				className
			) }
			role="article"
			{ ...props }
		>
			{ /* Wrapper */ }
			<div className="flex flex-row flex-wrap gap-2 p-2 bg-background-secondary rounded-lg">
				{ /* Modal Card */ }
				<div className="flex flex-row gap-6 p-6 bg-background-primary rounded-md shadow-sm w-full">
					{ /* Header Section */ }
					{ showHeader && (
						<div className="flex flex-col gap-2 p-2">
							<div
								className={ cn(
									'w-full h-auto rounded flex items-center justify-center',
									headerBackground
								) }
							>
								{ headerContent ||
									( imageUrl ? (
										<img
											src={ imageUrl }
											alt={ title }
											className="object-contain max-w-full max-h-full"
										/>
									) : (
										<div className="text-center text-text-tertiary">
											{ /* Placeholder for header content */ }
										</div>
									) ) }
							</div>
						</div>
					) }

					{ /* Content Section */ }
					<div className="flex flex-col justify-center gap-2 flex-1">
						{ /* Title Section */ }
						<div className="flex flex-col gap-2">
							<div className="flex flex-row items-center gap-3">
								<Text
									size={ 20 }
									weight={ 600 }
									color="primary"
									lineHeight={ 30 }
									className="tracking-tight"
								>
									{ title }
								</Text>
							</div>
							<div className="flex flex-row items-stretch">
								<Text
									size={ 16 }
									weight={ 400 }
									color="secondary"
									lineHeight={ 24 }
								>
									{ subtitle }
								</Text>
							</div>
						</div>

						{ /* Description */ }
						<div>
							{ Array.isArray( description ) ? (
								<ul className="list-disc list-inside space-y-2 text-text-secondary ml-4">
									{ description.map( ( item, index ) => (
										<li key={ index }>
											<Text
												size={ 16 }
												weight={ 400 }
												color="secondary"
												lineHeight={ 28 }
												className="inline"
											>
												{ item }
											</Text>
										</li>
									) ) }
								</ul>
							) : (
								<Text
									size={ 16 }
									weight={ 400 }
									color="secondary"
									lineHeight={ 28 }
									className="whitespace-pre-line"
								>
									{ description }
								</Text>
							) }
						</div>

						{ /* Footer Actions */ }
						<div className="flex flex-row justify-end items-center gap-3 p-2">
							<div className="flex flex-row items-center gap-3 w-full">
								<UpgradeButton
									label={ buttonLabel }
									variant="primary"
									size="md"
									showIcon={ false }
									onClick={ onButtonClick }
									utmMedium={ utmMedium }
								/>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	);
};

export default UpgradeFeatureCard;
