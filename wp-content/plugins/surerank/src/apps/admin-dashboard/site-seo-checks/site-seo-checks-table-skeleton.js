import { Container, Table, Skeleton } from '@bsf/force-ui';

const SiteSeoChecksTableSkeleton = () => {
	return (
		<div className="w-full space-y-2 rounded-xl bg-background-primary shadow-sm">
			<Container align="center" justify="between" className="p-2">
				<Skeleton className="h-6 w-48" />
				<Skeleton className="h-8 w-32" />
			</Container>
			<SiteSeoChecksInnerTableSkeleton />
		</div>
	);
};

export default SiteSeoChecksTableSkeleton;

const SiteSeoChecksInnerTableSkeleton = () => {
	return (
		<Table>
			<Table.Head>
				<Table.HeadCell>
					<Skeleton className="h-4 w-32" />
				</Table.HeadCell>
				<Table.HeadCell className="w-52 text-center">
					<Skeleton className="h-4 w-24 mx-auto" />
				</Table.HeadCell>
			</Table.Head>
			<Table.Body>
				{ Array.from( { length: 10 } ).map( ( _, index ) => (
					<Table.Row key={ `skeleton-row-${ index }` }>
						<Table.Cell>
							<Container gap="xl" align="center">
								<Container.Item>
									<Skeleton className="h-6 w-16" />
								</Container.Item>
								<Container.Item>
									<Skeleton className="h-4 w-64" />
								</Container.Item>
							</Container>
						</Table.Cell>
						<Table.Cell>
							<Container gap="sm">
								<Skeleton className="h-6 w-24" />
								<Skeleton className="h-6 w-16" />
							</Container>
						</Table.Cell>
					</Table.Row>
				) ) }
			</Table.Body>
			<Table.Footer>
				<Container align="center" justify="between">
					<Skeleton className="h-4 w-32" />
					<Container gap="sm">
						<Skeleton className="h-8 w-8" />
						<Skeleton className="h-8 w-8" />
						<Skeleton className="h-8 w-8" />
						<Skeleton className="h-8 w-8" />
						<Skeleton className="h-8 w-8" />
					</Container>
				</Container>
			</Table.Footer>
		</Table>
	);
};

export { SiteSeoChecksInnerTableSkeleton };
