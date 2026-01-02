import { Button } from '@bsf/force-ui';
import Title from '@components/title/title';

const EmptyState = ( {
	image: Image,
	title,
	description,
	bulletPoints = [],
	action = null,
} ) => {
	return (
		<div className="flex items-center justify-center w-full bg-background-secondary p-2 rounded-lg">
			<div className="flex w-full h-auto gap-6 p-8 rounded-md shadow-sm bg-background-primary">
				{ /* Image Section */ }
				<div className="flex items-center justify-center w-1/3 max-w-80">
					<Image className="w-full h-full" />
				</div>

				{ /* Content Section */ }
				<div className="flex flex-col justify-center w-full px-2 gap-2">
					<div className="space-y-2">
						{ /* Heading */ }
						<Title title={ title } tag="h1" />
						{ /* Description */ }
						<p className="mb-3 text-base font-normal text-text-secondary">
							{ description }
						</p>
					</div>

					{ /* Bullet Points */ }
					{ bulletPoints.length > 0 && (
						<ul className="ml-6 my-0 text-base font-normal list-disc text-text-secondary leading-7">
							{ bulletPoints.map( ( point, index ) => (
								<li key={ index } className="m-0">
									{ point }
								</li>
							) ) }
						</ul>
					) }

					{ /* Action Button */ }
					{ action && (
						<div className="mt-4 ml-0.5">
							<Button { ...action } />
						</div>
					) }
				</div>
			</div>
		</div>
	);
};

export default EmptyState;
