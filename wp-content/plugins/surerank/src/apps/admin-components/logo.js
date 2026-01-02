import { isEqualQueryParamValue } from '@/functions/utils';
import { SureRankLogo } from '@/global/components/icons';
import { Link } from '@tanstack/react-router';
import { useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

const Logo = () => {
	const linkClassName =
		'inline-flex no-underline hover:no-underline focus:no-underline focus:[box-shadow:none] cursor-pointer';
	const renderLogo = useMemo( () => <SureRankLogo className="size-6" />, [] );

	return isEqualQueryParamValue( 'page', 'surerank_settings' ) ? (
		<a
			href={ `${ surerank_globals.wp_dashboard_url }?page=surerank#/dashboard` }
			className={ linkClassName }
			aria-label={ __( 'SureRank Dashboard', 'surerank' ) }
		>
			{ renderLogo }
		</a>
	) : (
		<Link
			to="/dashboard"
			className={ linkClassName }
			aria-label={ __( 'SureRank Dashboard', 'surerank' ) }
		>
			{ renderLogo }
		</Link>
	);
};

export default Logo;
