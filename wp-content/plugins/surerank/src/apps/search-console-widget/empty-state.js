import { __ } from '@wordpress/i18n';
import { Container, Label } from '@bsf/force-ui';
import { Button } from '@wordpress/components';

/**
 * Empty State Component
 * Shown when Search Console is not connected
 *
 * @return {JSX.Element} Empty state component
 */
const EmptyState = () => {
	const { settings_page_url: settingsPageUrl } =
		window.surerank_search_console_widget || {};

	const handleConnect = () => {
		window.location.href = settingsPageUrl;
	};

	return (
		<Container
			gap="lg"
			direction="column"
			align="center"
			justify="center"
			className="p-1"
		>
			<div className="flex items-center justify-center w-full bg-background-secondary rounded-lg">
				<img
					src={ `${ window.surerank_globals?.admin_assets_url }/images/search-console.svg` }
					alt="Search Console"
					className="w-48 h-48"
				/>
			</div>
			<Container.Item className="mx-auto text-center max-w-md space-y-2">
				<Label
					tag="h6"
					className="text-lg font-semibold text-center block"
				>
					{ __(
						'Unlock SEO Insights with Google Search Console',
						'surerank'
					) }
				</Label>
				<Label
					tag="p"
					size="md"
					className="font-normal text-text-secondary"
				>
					{ __(
						'Connect Google Search Console to unlock search insights and track your growth.',
						'surerank'
					) }
				</Label>
			</Container.Item>
			<Button
				variant="primary"
				size="default"
				onClick={ handleConnect }
			>
				{ __( 'Connect to Google Search Console', 'surerank' ) }
			</Button>
		</Container>
	);
};

export default EmptyState;
