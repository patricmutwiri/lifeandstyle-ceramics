import { __ } from '@wordpress/i18n';
import { Container, Label, Button } from '@bsf/force-ui';
import { Star } from 'lucide-react';

export const RatingWidget = () => {
	return (
		<Container
			className="bg-background-primary border border-solid rounded-xl border-border-subtle p-3 shadow-sm"
			containerType="flex"
			direction="column"
			gap="xs"
		>
			<Container.Item className="md:w-full lg:w-full p-1">
				<Label className="font-semibold text-text-primary">
					{ __( 'Rate Us', 'surerank' ) }
				</Label>
			</Container.Item>
			<Container.Item className="flex flex-col md:w-full lg:w-full bg-field-primary-background gap-1 p-1 rounded-lg">
				<Container
					direction="column"
					align="start"
					justify="center"
					className="p-5 gap-2 bg-background-primary rounded-md shadow-sm"
				>
					<Container align="center" className="gap-1">
						{ Array.from( { length: 5 } ).map( ( _, index ) => (
							<Container.Item
								key={ index }
								className="py-1 inline-flex"
							>
								<Star className="size-4 text-amber-500 fill-amber-500" />
							</Container.Item>
						) ) }
					</Container>
					<p className="text-sm text-text-secondary font-normal m-0">
						{ __(
							'We would love hear form you, we would appreciate every single review.',
							'surerank'
						) }
					</p>
					<Button
						tag="a"
						className="w-fit focus:[box-shadow:none] [&>span]:p-0 no-underline mt-2"
						variant="link"
						href={ surerank_globals.rating_link }
						target="_blank"
						rel="noopener noreferrer"
					>
						{ __( 'Submit Review', 'surerank' ) }
					</Button>
				</Container>
			</Container.Item>
		</Container>
	);
};
