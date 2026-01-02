// File: src/components/LogsSkeleton.js

import { Skeleton, Table } from '@bsf/force-ui';

const LogsSkeleton = () => {
	return (
		<div className="p-6 bg-background-secondary">
			<div className="p-6 bg-background-primary rounded-lg shadow-lg">
				<div className="flex items-center justify-between mb-4">
					<Skeleton className="w-32 h-6" variant="rectangular" />
					<div className="flex space-x-4">
						<Skeleton className="w-20 h-8" variant="rectangular" />
						<Skeleton className="w-40 h-8" variant="rectangular" />
						<Skeleton className="w-40 h-8" variant="rectangular" />
						<Skeleton className="w-40 h-8" variant="rectangular" />
					</div>
				</div>

				<Table>
					<Table.Head>
						{ [
							'Subject',
							'Status',
							'Email To',
							'Date & Time',
							'Actions',
						].map( ( header, index ) => (
							<Table.HeadCell
								key={ index }
								className="px-4 py-2 border"
							>
								<Skeleton
									className="w-full h-6"
									variant="rectangular"
								/>
							</Table.HeadCell>
						) ) }
					</Table.Head>
					<Table.Body>
						{ Array.from( { length: 10 } ).map( ( _, rowIndex ) => (
							<Table.Row key={ rowIndex }>
								{ Array.from( { length: 5 } ).map(
									(
										__,
										cellIndex // Use a different variable name like '__'
									) => (
										<Table.Cell
											key={ cellIndex }
											className="px-4 py-2 border"
										>
											<Skeleton
												className="w-full h-6"
												variant="rectangular"
											/>
										</Table.Cell>
									)
								) }
							</Table.Row>
						) ) }
					</Table.Body>
				</Table>

				<div className="flex justify-end mt-4">
					<Skeleton className="w-48 h-8" variant="rectangular" />
				</div>
			</div>
		</div>
	);
};

export default LogsSkeleton;
