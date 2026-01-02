import { cn } from '@/functions/utils';
import { Tooltip as TooltipComponent } from '@bsf/force-ui';
import { Info } from 'lucide-react';

const Tooltip = ( props ) => {
	if ( ! props.content && ! props.title ) {
		return props.children;
	}
	return (
		<TooltipComponent
			{ ...props }
			tooltipPortalId="surerank-root"
			boundary={
				document?.querySelector( '#surerank-root' ) ||
				'clippingAncestors'
			}
		/>
	);
};

const InfoTooltip = ( { content, ...rest } ) => {
	return (
		<Tooltip
			content={ content }
			placement="top"
			arrow
			className={ cn( 'max-w-95 z-[99999]', rest?.className ) }
			{ ...rest }
		>
			<Info className="size-4 text-icon-secondary" />
		</Tooltip>
	);
};

const SeoPopupTooltip = ( props ) => {
	if ( ! props.content && ! props.title ) {
		return props.children;
	}
	return (
		<TooltipComponent
			{ ...props }
			className={ cn( 'z-[99999] max-w-95', props.className ) }
			tooltipPortalId="surerank-root"
			boundary={
				document?.querySelector(
					'#surerank-seo-popup-modal-container'
				) || 'clippingAncestors'
			}
		/>
	);
};

export const SeoPopupInfoTooltip = ( { content, className, ...rest } ) => {
	return (
		<SeoPopupTooltip
			className={ cn( 'z-[99999] max-w-95', className ) }
			content={ content }
			placement="top"
			arrow
			{ ...rest }
		>
			<Info className="shrink-0 size-4 !text-icon-secondary" />
		</SeoPopupTooltip>
	);
};

export { Tooltip, InfoTooltip, SeoPopupTooltip };
