import { Container, Button, Label } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';
import { ArrowUpRight } from 'lucide-react';
import { QuickLink } from './quick-link';
import { quickLinks } from './dashboard-constants';

const QuickLinksSection = () => {
	const handleOpenSettings = () => {
		window.open(
			surerank_globals.wp_dashboard_url + '?page=surerank_settings',
			'_self'
		);
	};

	return (
		<Container
			className="bg-background-primary md:w-full lg:w-full border border-solid rounded-xl border-border-subtle p-3 xl:p-4 shadow-sm"
			containerType="flex"
			direction="column"
			gap="xs"
		>
			<Container.Item className="md:w-full p-1 lg:w-full">
				<Container align="center" gap="xs" justify="between">
					<Container.Item className="">
						<Label className="font-semibold text-text-primary">
							{ __( 'Quick Links', 'surerank' ) }
						</Label>
					</Container.Item>
					<Container.Item className="items-center flex" gap="xs">
						<Button
							icon={ <ArrowUpRight className="size-4" /> }
							iconPosition="right"
							variant="ghost"
							size="sm"
							className="focus:[box-shadow:none]"
							onClick={ handleOpenSettings }
						>
							{ __( 'View All', 'surerank' ) }
						</Button>
					</Container.Item>
				</Container>
			</Container.Item>
			<Container.Item className="md:w-full lg:w-full bg-field-primary-background rounded-lg">
				<Container
					className="gap-1 p-1 grid-cols-1 md:grid-cols-2 xl:grid-cols-3"
					containerType="grid"
				>
					{ quickLinks.map( ( link ) => (
						<QuickLink key={ link.label } link={ link } />
					) ) }
				</Container>
			</Container.Item>
		</Container>
	);
};

export default QuickLinksSection;
