import { Skeleton } from '@bsf/force-ui';
import { cn } from '@Functions/utils';

const LoadingSkeleton = ( { tab = 'general' } ) => {
	if ( tab === 'advanced' ) {
		const inputCount = 3;
		return (
			<div className="w-full p-4 space-y-5">
				<div className="space-y-2">
					<div className="w-full flex items-center gap-2">
						<Skeleton variant="rectangular" className="w-32 h-4" />
						<Skeleton variant="circular" className="size-5" />
					</div>
					<div className="space-y-4">
						{ Array.from( { length: inputCount } ).map(
							( _, index ) => (
								<div
									key={ index }
									className="flex items-start gap-2"
								>
									<Skeleton
										variant="rectangular"
										className="size-5"
									/>
									<div className="space-y-2">
										<Skeleton
											variant="rectangular"
											className={ cn(
												'w-44 h-5',
												index % 2 === 0 && 'w-52'
											) }
										/>
										<Skeleton
											variant="rectangular"
											className="w-full h-4"
										/>
									</div>
								</div>
							)
						) }
					</div>
				</div>

				<div className="w-full p-1 space-y-1.5">
					<div className="w-full flex items-center gap-2">
						<Skeleton variant="rectangular" className="w-32 h-4" />
						<Skeleton variant="rectangular" className="w-5 h-4" />
					</div>
					<Skeleton variant="rectangular" className="w-full h-12" />
					<Skeleton variant="rectangular" className="w-full h-3" />
				</div>
			</div>
		);
	}

	const inputCount = tab === 'social' ? 2 : 1;

	return (
		<div className="w-full space-y-2 p-2">
			{ tab === 'social' && (
				<div className="w-full p-1">
					<Skeleton variant="rectangular" className="w-full h-14" />
				</div>
			) }
			<div className="w-full p-1 space-y-2">
				<div className="w-full flex items-center justify-between">
					<Skeleton variant="rectangular" className="w-32 h-4" />
					<Skeleton variant="rectangular" className="w-10 h-5" />
				</div>
				<Skeleton
					variant="rectangular"
					className={ cn( 'w-full h-40', {
						'h-48': tab === 'social',
					} ) }
				/>
			</div>

			{ Array.from( { length: inputCount } ).map( ( _, index ) => (
				<div key={ index } className="w-full p-1 space-y-1.5">
					<div className="w-full flex items-center gap-2">
						<Skeleton variant="rectangular" className="w-32 h-4" />
						<Skeleton variant="rectangular" className="w-5 h-4" />
					</div>
					<Skeleton variant="rectangular" className="w-full h-12" />
					<Skeleton variant="rectangular" className="w-full h-3" />
				</div>
			) ) }

			<div className="w-full p-1 space-y-1.5">
				<div className="w-full flex items-center gap-2">
					<Skeleton variant="rectangular" className="w-32 h-4" />
					<Skeleton variant="rectangular" className="w-5 h-4" />
				</div>
				<Skeleton variant="rectangular" className="w-full h-32" />
				<Skeleton variant="rectangular" className="w-full h-3" />
			</div>
		</div>
	);
};

export default LoadingSkeleton;
