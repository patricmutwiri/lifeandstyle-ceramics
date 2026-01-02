import React from 'react';
import PropTypes from 'prop-types';
import { Tooltip as ForceUITooltip } from '@bsf/force-ui';

/**
 * Tooltip component using Force UI to display a message on hover.
 *
 * @param {Object}          props                     - Component props.
 * @param {React.ReactNode} props.content             - The content to display inside the tooltip.
 * @param {string}          [props.position]          - The position of the tooltip relative to the child. Options: 'top', 'bottom', 'left', 'right'.
 * @param {string}          [props.variant]           - Tooltip variant (e.g., 'light', 'dark').
 * @param {string}          [props.tooltipPortalRoot] - The root portal element for the tooltip.
 * @param {string}          [props.tooltipPortalId]   - The portal ID for rendering the tooltip.
 * @param {React.ReactNode} props.children            - The element that triggers the tooltip on hover.
 * @param {string}          props.className           - The class name to apply to the tooltip.
 * @return {JSX.Element} The Tooltip component.
 */
const Tooltip = ( {
	content,
	position = 'top',
	variant = 'dark',
	tooltipPortalRoot = 'suremails-root-app',
	tooltipPortalId = 'suremails-root-app',
	children,
	className = 'z-999999',
	...props
} ) => {
	return (
		<ForceUITooltip
			content={ content }
			placement={ position }
			variant={ variant }
			tooltipPortalRoot={ tooltipPortalRoot }
			tooltipPortalId={ tooltipPortalId }
			className={ className }
			{ ...props }
		>
			{ children }
		</ForceUITooltip>
	);
};

Tooltip.propTypes = {
	content: PropTypes.node.isRequired, // Can be string or JSX
	position: PropTypes.oneOf( [ 'top', 'bottom', 'left', 'right' ] ),
	variant: PropTypes.string,
	tooltipPortalRoot: PropTypes.string,
	tooltipPortalId: PropTypes.string,
	children: PropTypes.node.isRequired,
	className: PropTypes.string,
};

export default Tooltip;
