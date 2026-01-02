import { __ } from '@wordpress/i18n';
import { Button, Text } from '@bsf/force-ui';
import { X } from 'lucide-react';
import { useState } from '@wordpress/element';
import { isProActive, redirectToPricingPage } from '@/functions/nudges';
import apiFetch from '@wordpress/api-fetch';

const UpgradeBanner = () => {
	const [ isVisible, setIsVisible ] = useState( true );

	const handleClose = async () => {
		setIsVisible( false );
		try {
			await apiFetch( {
				path: '/surerank/v1/nudges/disable',
				method: 'POST',
				data: {
					type: 'upgrade_banner',
				},
			} );
		} catch ( error ) {}
	};

	const Nudges = window?.surerank_globals?.nudges?.upgrade_banner;
	const currentHash = window?.location?.hash || '';
	const isDashboard =
		currentHash === '#/dashboard' ||
		currentHash === '#/' ||
		currentHash === '';

	if (
		isProActive() ||
		Nudges?.display === false ||
		! isVisible ||
		! isDashboard
	) {
		return null;
	}

	return (
		// TODO: Change "hidden" to "flex" to enable the banner
		<div
			className="hidden flex-row items-center justify-center gap-2 p-3 bg-alert-background-info border-0 border-b border-solid border-alert-border-info relative"
			role="banner"
		>
			{ /* Content Section */ }
			<div className="flex items-center gap-1 text-center">
				<Text
					size={ 12 }
					weight={ 600 }
					color="primary"
					className="inline"
				>
					{ __( 'Ready to go beyond free plan?', 'surerank' ) }
				</Text>
				<Button
					variant="link"
					size="sm"
					className="inline-flex items-center underline ring-0"
					onClick={ () => {
						redirectToPricingPage( 'surerank_upgrade_banner' );
					} }
				>
					{ __( 'Upgrade now', 'surerank' ) }
				</Button>
				<Text
					size={ 12 }
					weight={ 400 }
					color="primary"
					className="inline"
				>
					{ __(
						' and unlock the full power of SureRank!',
						'surerank'
					) }
				</Text>
			</div>

			{ /* Close Button */ }
			<Button
				onClick={ handleClose }
				variant="ghost"
				className="absolute right-3 bg-transparent m-0 border-none ring-0 p-0.5 focus:outline-none active:outline-none text-icon-secondary"
				aria-label={ __( 'Close banner', 'surerank' ) }
				icon={ <X className="w-4 h-4" /> }
			/>
		</div>
	);
};

export default UpgradeBanner;
