import { Skeleton } from '@bsf/force-ui';
import { cn } from '@utils/utils';

const ProvidersSkeleton = () => {
	return (
		<div className="w-full md:max-w-lg bg-background-primary rounded-xl">
			<div className="grid grid-cols-1 p-1 rounded-lg bg-background-secondary gap-1">
				{ Array.from( { length: 12 } ).map( ( _, index ) => (
					<div
						key={ index }
						className="flex items-center justify-between h-14 bg-background-primary px-4 py-4 pr-6 rounded-md shadow-sm"
					>
						<div className="flex items-center gap-2">
							<Skeleton
								variant="rectangular"
								className="size-6 shrink-0"
							/>
							<Skeleton
								variant="rectangular"
								className={ cn(
									'h-6 shrink-0',
									[ 'w-52', 'w-48', 'w-56', 'w-40' ][
										index % 4
									]
								) }
							/>
						</div>
						<Skeleton
							variant="circular"
							className="size-5 shrink-0"
						/>
					</div>
				) ) }
			</div>
		</div>
	);
};

export default ProvidersSkeleton;
