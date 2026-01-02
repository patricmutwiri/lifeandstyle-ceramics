import { Skeleton, Container } from '@bsf/force-ui';

const SidebarSkeleton = () => (
	<nav className="bg-background-primary p-4 w-full space-y-4">
		<Container direction="column" className="w-full">
			<Container.Item className="md:w-full lg:w-full p-2">
				{ [ ...Array( 6 ) ].map( ( _, i ) => (
					<Skeleton
						key={ i }
						variant="rectangular"
						className="w-full h-10 m-1"
					/>
				) ) }
			</Container.Item>
		</Container>
	</nav>
);

export default SidebarSkeleton;
