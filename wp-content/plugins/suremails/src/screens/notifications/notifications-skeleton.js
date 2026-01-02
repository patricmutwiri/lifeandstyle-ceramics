import React from 'react';
import { Skeleton } from '@bsf/force-ui';

const NotificationsSkeleton = () => {
	const bulletPointSkeletons = Array.from( { length: 3 } ).map(
		( _, index ) => (
			<li key={ index }>
				<Skeleton className="w-3/4 h-4" variant="rectangular" />
			</li>
		)
	);

	return (
		<div className="flex items-center justify-center w-full p-2 rounded-lg bg-background-secondary">
			<div className="flex w-full h-auto gap-6 p-8 rounded-md shadow-sm bg-background-primary">
				{ /* Image Skeleton Section */ }
				<div className="flex items-center justify-center w-1/3 max-w-80">
					<Skeleton className="w-full h-full" variant="rectangular" />
				</div>

				{ /* Content Skeleton Section */ }
				<div className="flex flex-col justify-center w-full gap-4 px-2">
					<div className="space-y-2">
						{ /* Title Skeleton */ }
						<Skeleton className="w-2/3 h-8" variant="rectangular" />
						{ /* Description Skeleton */ }
						<Skeleton
							className="w-full h-6"
							variant="rectangular"
						/>
					</div>

					{ /* Bullet Points Skeleton */ }
					<ul className="ml-6 my-2 space-y-2 text-base font-normal list-disc text-text-secondary leading-7.5">
						{ bulletPointSkeletons }
					</ul>

					{ /* Action Button Skeleton */ }
					<div className="my-2 ml-0.5 flex items-center">
						<Skeleton
							className="w-8 h-8 rounded-full"
							variant="circular"
						/>
						<Skeleton
							className="w-32 h-10 ml-2"
							variant="rectangular"
						/>
					</div>
				</div>
			</div>
		</div>
	);
};

export default NotificationsSkeleton;
