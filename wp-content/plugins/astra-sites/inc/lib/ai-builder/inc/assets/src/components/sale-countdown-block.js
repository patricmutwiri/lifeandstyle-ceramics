import React from 'react';
import { useCountdownTimer } from '../hooks/use-countdown-timer';
import CountdownDisplay from './countdown-display';
import { __ } from '@wordpress/i18n';
import Button from './button';

const SaleCountdownBlock = () => {
	const { timeLeft, showTimer, currentSalePeriod } = useCountdownTimer();
	const { zip_plans } = aiBuilderVars;

	// Don't render if sale has ended or if already upgraded.
	if (
		! showTimer ||
		( zip_plans?.active_plan && zip_plans?.active_plan?.slug !== 'free' )
	) {
		return null;
	}

	// Define titles for each sale period
	const getSaleTitle = () => {
		switch ( currentSalePeriod ) {
			case 'black-friday':
				return __( 'Special BLACK FRIDAY Offer', 'ai-builder' );
			case 'small-business-saturday':
				return __( 'Small Business Saturday Offer', 'ai-builder' );
			case 'give-back-sunday':
				return __( 'Give Back Sunday Offer', 'ai-builder' );
			case 'cyber-monday':
				return __( 'Cyber Monday Offer', 'ai-builder' );
			default:
				return __( 'Special BLACK FRIDAY Offer', 'ai-builder' );
		}
	};

	return (
		<div className="mt-5">
			<div className="flex flex-col p-4 rounded-md border border-dashed bg-[#27313F] border-gray-600 gap-4 text-white">
				{ /* Title */ }
				<div className="text-center">
					<h3 className="text-lg font-semibold tracking-wide mb-2 text-white">
						🔥 { getSaleTitle() } 🔥
					</h3>
					<p className="text-sm text-gray-300 leading-relaxed font-medium">
						{ __(
							'Unlock this premium template and many powerful tools with an incredible',
							'ai-builder'
						) }{ ' ' }
						<span className="text-yellow-400 font-semibold text-sm">
							41%
						</span>{ ' ' }
						{ __( 'discount!', 'ai-builder' ) }
					</p>
				</div>

				{ /* Timer using the reusable component */ }
				<CountdownDisplay timeLeft={ timeLeft } variant="default" />

				{ /* Footer message */ }
				{ typeof zip_plans !== 'object' && (
					<div className="text-center pb-2 text-xs sm:text-sm text-white/90">
						<p className="text-xs text-center text-[#E4EAF1]">
							{ __(
								"You'll pay only on the final step before your site is generated.",
								'ai-builder'
							) }
						</p>
					</div>
				) }

				{ zip_plans?.active_plan?.slug === 'free' && (
					<a
						href={ `https://app.zipwp.com/st-pricing?source=${ wpApiSettings?.zipwp_auth?.source }` }
						target="_blank"
						rel="noreferrer"
					>
						<Button className="m-auto px-2.5 py-1 text-heading-text bg-white/90 text-xs">
							{ __( 'Upgrade Now', 'ai-builder' ) }
						</Button>
					</a>
				) }
			</div>
		</div>
	);
};

export default SaleCountdownBlock;
