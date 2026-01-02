import { Suspense } from '@wordpress/element';
import AdminLoadingSkeleton from '../loading-skeleton';

const withSuspense = (
	Component,
	FallbackComponent = AdminLoadingSkeleton
) => {
	return ( props ) => {
		return (
			<Suspense fallback={ <FallbackComponent /> }>
				<Component { ...props } />
			</Suspense>
		);
	};
};

export default withSuspense;
