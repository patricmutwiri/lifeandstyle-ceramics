import { Text, Button } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';
import { Check } from 'lucide-react';
import { redirectToPricingPage } from '@/functions/nudges';
import { cn } from '@Functions/utils';

const UpgradeToPro = () => {
	const proFeatures = [
		__(
			'AI Assistant: Instantly correct technical SEO issues.',
			'surerank'
		),
		__( 'Redirection Manager: Keep every link clean.', 'surerank' ),
		__( 'Advanced Schema: Add markup without code.', 'surerank' ),
		__(
			'Instant Indexing: Get your content indexed instantly.',
			'surerank'
		),
		__( '…and everything else you need to rank smarter!', 'surerank' ),
	];

	const handleUpgradeClick = () => {
		redirectToPricingPage( 'surerank_dashboard_upgrade_card' );
	};

	// Generate image URL
	const imageUrl = window?.surerank_globals?.admin_assets_url
		? `${ window.surerank_globals.admin_assets_url }/images/upgrade-pro-card.svg`
		: null;

	return (
		<div
			className={ cn(
				'w-full h-fit bg-background-primary border-0.5 border-solid border-border-subtle rounded-xl p-3 shadow-sm relative overflow-hidden flex flex-col gap-2'
			) }
			role="article"
		>
			{ /* Illustration */ }
			<div className="flex justify-center items-center p-2 bg-brand-primary-50 rounded-md">
				{ imageUrl && (
					<img
						src={ imageUrl }
						alt={ __( 'Upgrade to Pro Illustration', 'surerank' ) }
						className="object-contain w-48 h-auto sm:w-64 md:w-80 lg:w-full"
					/>
				) }
			</div>

			{ /* Title Section */ }
			<div className="flex flex-col gap-1 p-2">
				{ /* Main Title */ }
				<Text
					size={ 18 }
					weight={ 600 }
					color="primary"
					lineHeight={ 28 }
				>
					{ __( 'Optimize Smarter with SureRank Pro', 'surerank' ) }
				</Text>

				{ /* Description */ }
				<Text
					size={ 14 }
					weight={ 400 }
					color="secondary"
					lineHeight={ 20 }
				>
					{ __(
						'Supercharge your workflow. Automate SEO tasks, get powerful insights, and rank smarter.',
						'surerank'
					) }
				</Text>
			</div>

			{ /* Features List */ }
			<div className="flex flex-col gap-1 p-2">
				{ proFeatures.map( ( feature, index ) => (
					<div
						key={ index }
						className="flex items-center gap-1 w-full"
					>
						<Check
							className="size-4 text-brand-primary-600 flex-shrink-0"
							strokeWidth={ 1.25 }
						/>
						<Text
							size={ 12 }
							weight={ 400 }
							color="primary"
							lineHeight={ 16 }
						>
							{ feature }
						</Text>
					</div>
				) ) }
			</div>
			{ /* CTA Button */ }
			<div className="p-2">
				<Button
					variant="secondary"
					size="md"
					className="w-full"
					onClick={ handleUpgradeClick }
				>
					{ __( 'Upgrade Now', 'surerank' ) }
				</Button>
			</div>
		</div>
	);
};

export default UpgradeToPro;
