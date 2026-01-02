// File: src/components/ConnectionsSkeleton.js

import { Skeleton, Table } from '@bsf/force-ui';
import { cn } from '@utils/utils';

const ConnectionsSkeleton = () => {
	return (
		<div className="p-6">
			<div className="flex items-center justify-between mb-4">
				<Skeleton className="w-40 h-8" variant="rectangular" />
				<Skeleton className="w-32 h-8" variant="rectangular" />
			</div>
			<div className="p-4 bg-background-primary rounded-lg shadow ">
				<Table>
					<Table.Head>
						{ [
							'Connection Title',
							'Email',
							'Created On',
							'Test Email',
							'Actions',
						].map( ( _, index ) => (
							<Table.HeadCell key={ index }>
								<Skeleton
									className="w-full h-6"
									variant="rectangular"
								/>
							</Table.HeadCell>
						) ) }
					</Table.Head>
					<Table.Body>
						{ Array.from( { length: 5 } ).map( ( _, rowIndex ) => (
							<Table.Row
								key={ rowIndex }
								className={ cn(
									'border-b',
									rowIndex % 2 === 0 &&
										'bg-background-secondary'
								) }
							>
								{ Array.from( { length: 5 } ).map(
									( __, cellIndex ) => (
										<Table.Cell
											key={ cellIndex }
											className="px-4 py-2"
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
			</div>
		</div>
	);
};

export default ConnectionsSkeleton;
