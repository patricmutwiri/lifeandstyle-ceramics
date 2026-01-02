import { Skeleton } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';

const PageChecksListSkeleton = () => {
	return (
		<div
			className="p-2 overflow-y-auto"
			role="status"
			aria-live="polite"
			aria-busy="true"
		>
			<span className="sr-only">
				{ __( 'Loading page SEO checks…', 'surerank' ) }
			</span>

			{ /* Skeleton for Check Cards */ }
			<div className="space-y-3">
				{ Array.from( { length: 4 } ).map( ( _, index ) => (
					<CheckCardSkeleton key={ `check-skeleton-${ index }` } />
				) ) }
			</div>
		</div>
	);
};

const CheckCardSkeleton = () => {
	return (
		<div className="relative flex flex-col gap-3 p-3 bg-background-primary rounded-lg shadow-sm border-0.5 border-solid border-border-subtle">
			{ /* Badge and Title Row */ }
			<div className="w-full flex items-start gap-2">
				{ /* Badge Skeleton */ }
				<Skeleton
					variant="rectangular"
					className="h-6 w-16 rounded-full"
				/>

				{ /* Title Skeleton */ }
				<div className="flex-1">
					<Skeleton variant="rectangular" className="h-5 w-48" />
				</div>

				{ /* Button Skeleton */ }
				<Skeleton variant="rectangular" className="h-6 w-20 rounded" />
			</div>

			{ /* Description List Skeleton */ }
			<div className="ml-3 space-y-1">
				<Skeleton variant="rectangular" className="h-4 w-full" />
				<Skeleton variant="rectangular" className="h-4 w-3/4" />
			</div>
		</div>
	);
};

export default PageChecksListSkeleton;
