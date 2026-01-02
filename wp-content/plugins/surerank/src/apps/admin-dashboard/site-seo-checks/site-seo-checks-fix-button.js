import { useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import FixButton from '@GlobalComponents/fix-button';
import { isFixItForMeButton } from '@Global/constants';

/**
 * SiteSeoChecksFixButton component that renders a FixButton with consistent logic
 * @param {Object} props                 - Component props
 * @param {Object} props.selectedItem    - The selected item object containing status and other properties
 * @param {Object} props.additionalProps - Additional props to pass to the FixButton
 * @return {JSX.Element} The rendered FixButton component
 */
const SiteSeoChecksFixButton = ( { selectedItem, ...additionalProps } ) => {
	const SHOW_FIX_BUTTON_FOR = isFixItForMeButton( selectedItem?.id );

	const fixItButtonProps = useMemo( () => {
		const { runBeforeOnClick, runAfterOnClick, ...domSafeProps } =
			additionalProps;

		const baseProps = {
			...domSafeProps,
			hidden: false,
			id: selectedItem?.id,
			category: selectedItem?.category ?? '',
		};

		if ( SHOW_FIX_BUTTON_FOR ) {
			return {
				...baseProps,
				buttonLabel: __( 'Fix It For Me', 'surerank' ),
				runBeforeOnClick,
				runAfterOnClick,
			};
		}

		const handleCustomButtonClick = () => {
			if ( selectedItem?.button_url ) {
				window.location.href = selectedItem.button_url;
			}
		};

		return {
			...baseProps,
			buttonLabel:
				selectedItem?.button_label ?? __( 'Help Me Fix', 'surerank' ),
			locked: ! selectedItem?.not_locked,
			onClick: selectedItem?.button_url
				? handleCustomButtonClick
				: undefined,
		};
	}, [ selectedItem, additionalProps, SHOW_FIX_BUTTON_FOR ] );

	const ProFixButton = applyFilters(
		'surerank-pro.dashboard.site-seo-checks-fix-it-button'
	);

	return ProFixButton && ! selectedItem?.not_locked ? (
		<ProFixButton { ...fixItButtonProps } />
	) : (
		<FixButton
			tooltipProps={ { className: 'z-999999' } }
			{ ...fixItButtonProps }
		/>
	);
};

export default SiteSeoChecksFixButton;
