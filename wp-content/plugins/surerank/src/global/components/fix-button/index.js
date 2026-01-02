import { SeoPopupTooltip } from '@/apps/admin-components/tooltip';
import { Text, Button } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';
import { cn } from '@Functions/utils';
import { redirectToPricingPage } from '@Functions/nudges';

const FixButton = ( {
	size = 'xs',
	tooltipProps,
	title = __( 'Fix SEO Issues with AI', 'surerank' ),
	description = (
		<>
			<span>
				{ __(
					'Upgrade to SureRank Pro and let AI help you fix critical SEO issues and warnings, so your website stays fully optimized and ready to perform better in search results.',
					'surerank'
				) }
			</span>
			<br />
		</>
	),
	linkLabel = __( 'Upgrade Now', 'surerank' ),
	iconPosition = 'left',
	icon,
	buttonLabel = __( 'Fix It for Me', 'surerank' ),
	className,
	hidden = true,
	locked = true,
	onClick,
	runBeforeOnClick,
	runAfterOnClick,
	...props
} ) => {
	const handleOnClick = () => {
		if ( typeof onClick !== 'function' || locked ) {
			return;
		}
		onClick();
	};

	const buttonComponent = (
		<Button
			className={ cn( 'w-fit', hidden && 'hidden', className ) }
			size={ size }
			icon={ icon }
			iconPosition={ iconPosition }
			{ ...props }
			onClick={ handleOnClick }
		>
			{ buttonLabel }
		</Button>
	);

	// If locked is false, render just the button without tooltip
	if ( ! locked ) {
		return buttonComponent;
	}

	// If locked is true (default), render with tooltip
	return (
		<SeoPopupTooltip
			arrow
			interactive
			placement="top-end"
			{ ...tooltipProps }
			content={
				<div className="space-y-1">
					<Text size={ 12 } weight={ 600 } color="inverse">
						{ title }
					</Text>
					<Text
						size={ 12 }
						weight={ 400 }
						color="inverse"
						className="leading-relaxed"
					>
						{ description }
					</Text>
					<div className="mt-1.5">
						<Button
							size="xs"
							variant="link"
							className="[&>span]:px-0 no-underline hover:no-underline focus:[box-shadow:none] text-link-visited-inverse hover:text-link-visited-inverse-hover"
							onClick={ () =>
								redirectToPricingPage( 'fix_it_button' )
							}
						>
							{ linkLabel }
						</Button>
					</div>
				</div>
			}
		>
			{ buttonComponent }
		</SeoPopupTooltip>
	);
};

export default FixButton;
