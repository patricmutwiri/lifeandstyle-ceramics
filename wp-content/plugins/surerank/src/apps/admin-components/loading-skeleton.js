import { Skeleton } from '@bsf/force-ui';
import { cn } from '@Functions/utils';

const AdminLoadingSkeleton = () => {
	// Different aspect ratio classes to mimic each section of the page.
	const aspectRatioArrayClassNames = [
		'aspect-video',
		'aspect-[7/2]',
		'aspect-[8/1]',
		'aspect-[8/2]',
		'aspect-[8/3]',
		'aspect-[8/4]',
	];
	const widthClassNames = [
		'w-1/5',
		'w-1/4',
		'w-1/6',
		'w-1/7',
		'w-1/8',
		'w-1/9',
		'w-5/12',
		'w-4/12',
		'w-3/6',
	];
	const minNumOfSections = 3;
	const maxNumOfSections = 7;
	const numOfSections = Math.floor(
		Math.random() * ( maxNumOfSections - minNumOfSections + 1 ) +
			minNumOfSections
	);

	return (
		<div className="flex-1 flex flex-col gap-7 w-full">
			<div className="flex items-center justify-between w-full">
				<Skeleton variant="rectangular" className="w-1/4 h-8" />
				<Skeleton variant="rectangular" className="w-20 h-12" />
			</div>

			<div className="w-full flex flex-col gap-10 p-4 bg-background-primary shadow-sm rounded-xl">
				{ Array.from( { length: numOfSections }, ( _, index ) => {
					const aspectRatioClassName =
						aspectRatioArrayClassNames[
							Math.floor(
								Math.random() *
									aspectRatioArrayClassNames.length
							)
						];

					return (
						<div key={ index } className="w-full h-fit space-y-5">
							<div className="flex items-center justify-between gap-4">
								<Skeleton
									variant="rectangular"
									className={ cn(
										'w-24 h-8',
										widthClassNames[
											Math.floor(
												Math.random() *
													widthClassNames.length
											)
										]
									) }
								/>
							</div>
							<div
								key={ index }
								className={ aspectRatioClassName }
							>
								<Skeleton
									variant="rectangular"
									className="w-full h-full"
								/>
							</div>
						</div>
					);
				} ) }
			</div>
		</div>
	);
};

export default AdminLoadingSkeleton;
