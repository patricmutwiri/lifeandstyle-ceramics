import { Badge } from '@bsf/force-ui';
import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import { Tooltip } from './tooltip';

const { version } = surerank_globals;

const VersionBadge = () => {
	const proBadge = applyFilters(
		'surerank-pro.admin-layout.pro-version-badge',
		null
	);
	const coreBadge = (
		<Tooltip content={ __( 'Core', 'surerank' ) } placement="bottom" arrow>
			<Badge label={ `V ${ version }` } size="xs" variant="neutral" />
		</Tooltip>
	);

	if ( ! proBadge ) {
		return coreBadge;
	}

	return (
		<>
			{ coreBadge }
			{ proBadge }
		</>
	);
};

export default VersionBadge;
