// QuickAccess.jsx
import { Container, Label } from '@bsf/force-ui';
import { HelpCircle, MessagesSquare, Star } from 'lucide-react';
import { __ } from '@wordpress/i18n';
import Title from '@components/title/title';

const quickAccessItems = [
	{
		id: '1',
		icon: <HelpCircle className="w-4 h-4" />,
		label: __( 'Help Center', 'suremails' ),
		link: suremails?.docsURL,
	},
	{
		id: '2',
		icon: <MessagesSquare className="w-4 h-4" />,
		label: __( 'Join the Community', 'suremails' ),
		link: 'https://www.facebook.com/groups/surecrafted',
	},
	{
		id: '3',
		icon: <Star className="w-4 h-4" />,
		label: __( 'Rate Us', 'suremails' ),
		link: 'https://wordpress.org/support/plugin/suremails/reviews/#new-post',
	},
];

export const QuickAccess = ( { items = quickAccessItems } ) => (
	<Container
		containerType="flex"
		direction="column"
		className="p-3 border-0.5 border-solid rounded-xl shadow-sm border-border-subtle"
		gap="xs"
	>
		<Container.Item className="p-1 md:w-full lg:w-full">
			<Title title={ __( 'Quick Access', 'suremails' ) } tag="h4" />
		</Container.Item>
		<Container.Item className="flex flex-col gap-1 p-1 rounded-lg md:w-full lg:w-full bg-field-primary-background">
			{ items.map( ( button ) => (
				<div
					key={ button.id }
					className="flex items-center gap-1 p-2 rounded-md bg-background-primary shadow-soft-shadow-inner"
				>
					<Container
						containerType="flex"
						direction="row"
						className="items-center gap-1 p-1"
						align="center"
					>
						<Container.Item className="flex items-center justify-center text-text-primary cursor-pointer">
							{ button.icon }
						</Container.Item>
						<Container.Item className="flex items-center">
							{ button.link ? (
								<a
									href={ button.link }
									target="_blank"
									className="no-underline hover:no-underline hover:text-field-label cursor-pointer"
									rel="noreferrer"
									aria-label={ button.label } // Added for accessibility
								>
									<Label className="pl-1 pr-1 text-sm font-medium text-text-primary hover:no-underline cursor-pointer">
										{ button.label }
									</Label>
								</a>
							) : (
								// Fallback if no link is provided
								<Label className="px-1 py-0 text-sm font-medium no-underline text-text-primary">
									{ button.label }
								</Label>
							) }
						</Container.Item>
					</Container>
				</div>
			) ) }
		</Container.Item>
	</Container>
);

export default QuickAccess;
