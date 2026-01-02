import { SureRankLogo } from '@/global/components/icons';
import { Button, Text } from '@bsf/force-ui';
import { XIcon } from 'lucide-react';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { redirectToPricingPage } from '@/functions/nudges';

const Notice = () => {
	const handleDismiss = () => {
		apiFetch( {
			path: '/surerank/v1/nudges/disable',
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
			},
			data: {
				type: 'permalink_redirect',
			},
		} ).catch( () => {} );
		const notice = document.getElementById( 'surerank-admin-notice' );
		if ( notice ) {
			notice.remove();
		}
	};

	return (
		<div className="surerank-root">
			<div className="bg-background-primary rounded-r-lg border-l-[3px] border-brand-800 shadow-[0px_8px_32px_-12px_rgba(149,160,178,0.16)] p-3 my-4 mr-0 ml-0">
				<div className="flex items-center justify-between gap-0.5">
					<div className="flex items-center gap-3 flex-1">
						<SureRankLogo className="w-5 h-6 shrink-0 text-brand-primary-600" />
						<div className="flex items-center gap-2 px-1 flex-1">
							<Text size={ 14 } lineHeight={ 20 }>
								{ __(
									'Changed a permalink? SureRank Pro automatically redirects old URLs to keep your SEO intact.',
									'surerank'
								) }
							</Text>

							<Button
								variant="link"
								size="xs"
								onClick={ () =>
									redirectToPricingPage( 'redirect_notice' )
								}
							>
								{ __( 'Upgrade Now', 'surerank' ) }
							</Button>
						</div>
					</div>
					<Button
						className="!p-0.5 before:!hidden"
						size="sm"
						variant="ghost"
						icon={ <XIcon className="size-5 text-text-tertiary" /> }
						onClick={ handleDismiss }
					/>
				</div>
			</div>
		</div>
	);
};

export default Notice;
