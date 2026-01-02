// src/screens/Dashboard/DashboardSkeleton.js

import { Skeleton } from '@bsf/force-ui';

const DashboardSkeleton = () => {
	return (
		<div className="grid grid-cols-1 gap-6 p-8 lg:grid-cols-3">
			{ /* Left Column Skeleton: Chart and Recent Logs (Spans 2/3 of the space) */ }
			<div className="flex flex-col space-y-6 lg:col-span-2">
				{ /* Chart Skeleton */ }
				<div className="h-auto p-6 overflow-hidden bg-background-primary rounded-md shadow-lg">
					<Skeleton className="w-full h-56" variant="rectangular" />
				</div>

				{ /* Recent Logs Skeleton */ }
				<div className="h-auto p-4 bg-background-primary rounded-md shadow-lg">
					<div className="mb-4">
						<Skeleton className="w-48 h-6" variant="rectangular" />
					</div>
					<div className="space-y-4">
						{ Array.from( { length: 4 } ).map( ( _, index ) => (
							<div
								key={ index }
								className="flex items-center space-x-4"
							>
								<Skeleton
									className="w-12 h-12"
									variant="circular"
								/>
								<Skeleton
									className="w-full h-6"
									variant="rectangular"
								/>
							</div>
						) ) }
					</div>
				</div>
			</div>

			{ /* Right Column Skeleton: Recommended Plugins and Quick Access (Spans 1/3 of the space) */ }
			<div className="flex flex-col space-y-6">
				{ /* Recommended Plugins Skeleton */ }
				<div className="p-4 bg-background-primary rounded-md shadow-lg h-auto min-h-[17rem]">
					<Skeleton
						className="w-full h-5 mb-4"
						variant="rectangular"
					/>
					<div className="space-y-3">
						{ Array.from( { length: 6 } ).map( ( _, index ) => (
							<Skeleton
								key={ index }
								className="w-full h-5"
								variant="rectangular"
							/>
						) ) }
					</div>
				</div>

				{ /* Quick Access Skeleton */ }
				<div className="p-4 bg-background-primary rounded-md shadow-lg h-auto min-h-[200px]">
					<Skeleton
						className="w-full h-6 mb-4"
						variant="rectangular"
					/>
					<div className="space-y-3">
						{ Array.from( { length: 3 } ).map( ( _, index ) => (
							<Skeleton
								key={ index }
								className="w-full h-6"
								variant="rectangular"
							/>
						) ) }
					</div>
				</div>
			</div>
		</div>
	);
};

export default DashboardSkeleton;
