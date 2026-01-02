import { Suspense, useEffect, memo } from '@wordpress/element';
import { FilePreview, Skeleton } from '@bsf/force-ui';
import ErrorBoundary from './error-boundary';
import { createResourcePromise } from '@Functions/utils';
import { fetchImageDataByUrl } from '@Functions/api';

// Create a resource cache for fetching image data
const imageCache = new Map();

const fetchImageDataById = ( imageId ) => {
	const image = wp.media.attachment( imageId );
	return image.fetch();
};

// Function to fetch image data with caching
const fetchImageData = ( { imageId = '', imageUrl = '' } ) => {
	// Return from cache if available
	if ( imageCache.has( imageId || imageUrl ) ) {
		return imageCache.get( imageId || imageUrl );
	}

	// Create a new resource if not in cache
	const resource = createResourcePromise(
		new Promise( ( resolve ) => {
			if ( ! imageId && ! imageUrl ) {
				resolve( {} );
				return;
			}

			const processById = ( id ) => {
				if ( ! id ) {
					resolve( {} );
					return;
				}

				const image = fetchImageDataById( id );

				// Handle both promise and non-promise returns
				if ( image && typeof image.then === 'function' ) {
					image
						.then( () => {
							resolve( image );
						} )
						.catch( () => {
							resolve( {} );
						} );
				} else {
					resolve( image );
				}
			};

			// If image url is provided, fetch the image data by url first
			if ( imageUrl ) {
				fetchImageDataByUrl( imageUrl )
					.then( ( images ) => {
						if ( images ) {
							processById( images.id );
						} else {
							resolve( {} );
						}
					} )
					.catch( () => {
						resolve( {} );
					} );
			} else if ( imageId ) {
				// If only ID is provided, fetch directly by ID
				processById( imageId );
			} else {
				resolve( {} );
			}
		} )
	);

	// Store in cache and return
	imageCache.set( imageId || imageUrl, resource );
	return resource;
};

// Loading fallback component
const LoadingFallback = () => <Skeleton className="h-14 w-full"></Skeleton>;

// Main component that reads from the resource
const MediaPreviewContent = ( { imageId, imageUrl, onRemove } ) => {
	// Use the cached resource
	let imageData = fetchImageData( { imageId, imageUrl } ).read() || {};

	if ( 'attributes' in imageData ) {
		imageData = imageData.attributes;
	}

	// Safely extract properties with defaults
	const filename = imageData.filename || '';
	const filesizeHumanReadable = imageData.filesizeInBytes || '';
	const url = imageData.url || '';
	const type = imageData.type || '';

	return (
		<div className="[&>div]:m-0">
			<FilePreview
				file={ {
					name: filename,
					url,
					type,
					size: filesizeHumanReadable,
				} }
				onRemove={ onRemove }
				size="md"
			/>
		</div>
	);
};

// Wrapper component with Suspense
const MediaPreview = ( { imageId, imageUrl, onRemove } ) => {
	useEffect( () => {
		const handleBeforeUnload = () => {
			if ( imageId || imageUrl ) {
				imageCache.delete( imageId || imageUrl );
			}
		};

		window.addEventListener( 'beforeunload', handleBeforeUnload );
		return () => {
			window.removeEventListener( 'beforeunload', handleBeforeUnload );
		};
	}, [ imageId, imageUrl ] );

	// Don't render anything if no imageId is provided
	if ( ! imageId && ! imageUrl ) {
		return null;
	}

	return (
		<ErrorBoundary>
			<Suspense fallback={ <LoadingFallback /> }>
				<MediaPreviewContent
					imageId={ imageId }
					imageUrl={ imageUrl }
					onRemove={ onRemove }
				/>
			</Suspense>
		</ErrorBoundary>
	);
};

export default memo( MediaPreview );
