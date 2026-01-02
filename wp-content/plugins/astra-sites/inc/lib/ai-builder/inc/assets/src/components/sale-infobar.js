import React, { useState, useEffect } from 'react';
import { getCookie, setCookie } from '../utils/helpers';
import { useCountdownTimer } from '../hooks/use-countdown-timer';
import CountdownDisplay from './countdown-display';
import { __ } from '@wordpress/i18n';
import Button from './button';

const SaleInfobar = () => {
	const { timeLeft, showTimer, currentSalePeriod } = useCountdownTimer();
	const { zip_plans } = aiBuilderVars;
	const [ isDismissed, setIsDismissed ] = useState(
		getCookie( `${ currentSalePeriod }-dismissed` )
	);

	// Check if the current sale period has been dismissed
	useEffect( () => {
		const dismissedCookie = getCookie( `${ currentSalePeriod }-dismissed` );
		if ( dismissedCookie === 'true' ) {
			setIsDismissed( true );
		}
	}, [ currentSalePeriod ] );

	// Don't render if sale has ended or if dismissed or if already upgraded.
	if (
		! showTimer ||
		isDismissed ||
		( zip_plans?.active_plan && zip_plans?.active_plan?.slug !== 'free' )
	) {
		return null;
	}

	// Define titles for each sale period
	const getSaleTitle = () => {
		switch ( currentSalePeriod ) {
			case 'black-friday':
				return __( 'BLACK FRIDAY SALE', 'ai-builder' );
			case 'small-business-saturday':
				return __( 'Small Business Saturday Sale', 'ai-builder' );
			case 'give-back-sunday':
				return __( 'Give Back Sunday Offer', 'ai-builder' );
			case 'cyber-monday':
				return __( 'Cyber Monday SALE', 'ai-builder' );
			default:
				return __( 'BLACK FRIDAY SALE!', 'ai-builder' );
		}
	};

	// Handle closing the infobar
	const handleClose = () => {
		setIsDismissed( true );
		setCookie(
			`${ currentSalePeriod }-dismissed`,
			'true',
			30 * 24 * 60 * 60
		); // Expires in 30 days
	};

	return (
		<div className="relative w-full">
			<div
				className="bg-cover bg-center text-white"
				style={ {
					backgroundImage: `url(${ aiBuilderVars.sale_infobar_bg })`,
				} }
			>
				<div className="container mx-auto px-4">
					{ /* Close Button */ }
					<button
						onClick={ handleClose }
						className="absolute right-6 top-8 md:top-1/2 transform -translate-y-1/2 text-white hover:text-gray-300 focus:outline-none"
						aria-label={ __( 'Close', 'ai-builder' ) }
					>
						<svg
							xmlns="http://www.w3.org/2000/svg"
							x="0px"
							y="0px"
							className="h-5 w-5"
							fill="currentColor"
							viewBox="0 0 48 48"
						>
							<path d="M 39.486328 6.9785156 A 1.50015 1.50015 0 0 0 38.439453 7.4394531 L 24 21.878906 L 9.5605469 7.4394531 A 1.50015 1.50015 0 0 0 8.484375 6.984375 A 1.50015 1.50015 0 0 0 7.4394531 9.5605469 L 21.878906 24 L 7.4394531 38.439453 A 1.50015 1.50015 0 1 0 9.5605469 40.560547 L 24 26.121094 L 38.439453 40.560547 A 1.50015 1.50015 0 1 0 40.560547 38.439453 L 26.121094 24 L 40.560547 9.5605469 A 1.50015 1.50015 0 0 0 39.486328 6.9785156 z"></path>
						</svg>
					</button>
					<div className="flex flex-col md:flex-row items-center justify-center gap-4 md:gap-8 py-3 relative">
						{ /* Timer Display - Compact version for info bar */ }
						<div className="flex items-center gap-4">
							<CountdownDisplay
								timeLeft={ timeLeft }
								variant="compact"
								showLabels={ true }
								valueClassName="text-white"
								labelClassName="text-white/80"
							/>
						</div>
						{ /* Sale Message */ }
						<div className="flex items-center md:items-start gap-[0.3rem] flex-col justify-center text-center md:text-left">
							<div className="flex items-center gap-2">
								<span className="text-sm sm:text-base font-bold">
									<span className="text-lg font-bold block md:inline">
										🔥 { getSaleTitle() } 🔥
									</span>{ ' ' }
									{ __(
										'Unlock all premium templates and useful design tools with an incredible',
										'ai-builder'
									) }{ ' ' }
									<span className="font-bold text-yellow-300">
										{ __( '41%', 'ai-builder' ) }
									</span>{ ' ' }
									{ __( 'discount.', 'ai-builder' ) }
								</span>
							</div>
							{ /* Optional: Secondary message */ }
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
									<Button className="px-2.5 py-1 text-heading-text bg-white/90 text-xs w-full">
										{ __( 'Upgrade Now', 'ai-builder' ) }
									</Button>
								</a>
							) }
						</div>
					</div>
				</div>
			</div>
		</div>
	);
};

export default SaleInfobar;
