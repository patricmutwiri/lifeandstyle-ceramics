import { Button, Text } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';
import { cn } from '@/functions/utils';

/**
 * Component to display a notice when Google Search Console is not connected
 *
 * @param {Object} props           Component props
 * @param {string} props.className Additional classes for the component
 * @return {JSX.Element} Search console notice component
 */
const SearchConsoleNotice = ( { className = '' } ) => {
	return (
		<div
			className={ cn(
				'bg-brand-background-50 border border-solid border-brand-border-300 rounded-lg shadow-soft-shadow-md flex items-center p-4',
				className
			) }
		>
			<div className="flex items-center gap-3 flex-1">
				<div className="flex-shrink-0">
					<img
						src={ `${ surerank_globals.admin_assets_url }/images/google-search-console-icon.svg` }
						alt={ __( 'Google Search Console icon', 'surerank' ) }
						width="46"
						height="42"
					/>
				</div>
				<div className="flex flex-col gap-0.5 flex-1">
					<Text as="h3" size={ 16 } weight={ 600 } color="primary">
						{ __(
							'Google Search Console Not Connected',
							'surerank'
						) }
					</Text>
					<Text size={ 14 } weight={ 400 } color="primary">
						{ __(
							'Connecting your Google Search Console account provides valuable insights into keyword performance, indexing status, and search visibility.',
							'surerank'
						) }
					</Text>
				</div>
			</div>
			<Button
				className="p-1.5"
				size="md"
				onClick={ () => {
					window.open(
						surerank_admin_common.auth_url,
						'_self',
						'noopener,noreferrer'
					);
				} }
			>
				{ __( 'Connect Now', 'surerank' ) }
			</Button>
		</div>
	);
};

export default SearchConsoleNotice;
