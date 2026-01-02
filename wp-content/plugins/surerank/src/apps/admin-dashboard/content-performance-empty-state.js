import { Container, Label } from '@bsf/force-ui';
import { Search } from 'lucide-react';
import { __ } from '@wordpress/i18n';

const ContentPerformanceEmptyState = ( {
	title = __( 'No SEO Performance to Show', 'surerank' ),
	description = __(
		"Once a site is selected, you'll see how your content is performing in search engines here.",
		'surerank'
	),
	icon = <Search />,
} ) => {
	return (
		<Container
			align="center"
			justify="center"
			direction="column"
			gap="none"
			className="py-[6.875rem] max-w-[26.5625rem] mx-auto space-y-3"
		>
			<div className="content [&>svg]:size-6 text-icon-primary">
				{ icon }
			</div>
			<div className="space-y-1">
				<Label tag="h6" size="md" className="block text-center">
					{ title }
				</Label>
				<Label
					tag="p"
					size="sm"
					className="text-text-secondary text-center"
				>
					{ description }
				</Label>
			</div>
		</Container>
	);
};

export default ContentPerformanceEmptyState;
