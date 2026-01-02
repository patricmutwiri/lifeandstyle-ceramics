import { Skeleton } from '@bsf/force-ui';

const SettingsSkeleton = () => {
	return (
		<div className="flex flex-col min-h-screen p-1 bg-gray-100">
			<div className="flex items-center justify-between w-[696px] h-[40px] mb-4 gap-2 mx-auto">
				<Skeleton
					className="w-[150px] h-[28px]"
					variant="rectangular"
				/>
				<Skeleton
					className="w-[100px] h-[28px]"
					variant="rectangular"
				/>
			</div>

			<div className="px-6 py-6 bg-background-primary rounded-lg shadow-lg w-[696px] h-auto gap-4 opacity-100 mx-auto">
				{ /* Log Emails */ }
				<div className="flex w-[648px] gap-3">
					<Skeleton
						className="w-[40px] h-[20px]"
						variant="circular"
					/>
					<div className="flex flex-col w-full">
						<Skeleton
							className="w-[200px] h-[24px] mb-1"
							variant="rectangular"
						/>
						<Skeleton
							className="w-[300px] h-[16px]"
							variant="rectangular"
						/>
					</div>
				</div>

				<Skeleton
					className="w-[648px] h-[0.5px] mt-2 mb-4 border border-subtle"
					variant="rectangular"
				/>

				{ /* Delete Logs */ }
				<div className="flex flex-col w-[648px] h-auto gap-2">
					<Skeleton
						className="w-[200px] h-[24px] mb-1"
						variant="rectangular"
					/>
					<Skeleton
						className="w-full h-[40px]"
						variant="rectangular"
					/>
					<Skeleton
						className="w-[400px] h-[16px] mt-1"
						variant="rectangular"
					/>
				</div>

				<Skeleton
					className="w-[648px] h-[0.5px] mt-2 mb-4 border border-subtle"
					variant="rectangular"
				/>

				{ /* Default Connection */ }
				<div className="flex flex-col w-[648px] h-auto gap-2">
					<Skeleton
						className="w-[200px] h-[24px] mb-1"
						variant="rectangular"
					/>
					<Skeleton
						className="w-full h-[40px]"
						variant="rectangular"
					/>
					<Skeleton
						className="w-[400px] h-[16px] mt-1"
						variant="rectangular"
					/>
				</div>

				<Skeleton
					className="w-[648px] h-[0.5px] mt-2 mb-4 border border-subtle"
					variant="rectangular"
				/>

				{ /* Fallback Connection */ }
				<div className="flex flex-col w-[648px] h-auto gap-2">
					<Skeleton
						className="w-[200px] h-[24px] mb-1"
						variant="rectangular"
					/>
					<Skeleton
						className="w-full h-[40px]"
						variant="rectangular"
					/>
					<Skeleton
						className="w-[400px] h-[16px] mt-1"
						variant="rectangular"
					/>
				</div>

				<Skeleton
					className="w-[648px] h-[0.5px] mt-2 mb-4 border border-subtle"
					variant="rectangular"
				/>

				{ /* Email Simulation */ }
				<div className="flex w-[648px] gap-3">
					<Skeleton
						className="w-[40px] h-[20px]"
						variant="circular"
					/>
					<div className="flex flex-col w-full">
						<Skeleton
							className="w-[200px] h-[24px] mb-1"
							variant="rectangular"
						/>
						<Skeleton
							className="w-[300px] h-[16px]"
							variant="rectangular"
						/>
					</div>
				</div>
			</div>
		</div>
	);
};

export default SettingsSkeleton;
