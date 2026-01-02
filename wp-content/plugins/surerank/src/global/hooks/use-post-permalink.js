import { useState } from '@wordpress/element';

const usePostPermalink = () => {
	const getCurrentPermalink = () => {
		if ( ! wp?.data?.select( 'core/editor' ) ) {
			return '';
		}

		const { getPermalink, isCurrentPostPublished } =
			wp.data.select( 'core/editor' );
		const permalink = getPermalink();
		const isPublished = isCurrentPostPublished();

		if (
			( isPublished && permalink ) ||
			( permalink && ! permalink?.includes( 'auto-draft' ) )
		) {
			return permalink;
		}

		return '';
	};
	const [ postPermalink, setPostPermalink ] = useState(
		getCurrentPermalink()
	);

	wp.data.subscribe( () => {
		const permalink = getCurrentPermalink();
		if ( ! permalink || permalink === postPermalink ) {
			return;
		}
		setPostPermalink( permalink );
	} );

	return postPermalink;
};

export default usePostPermalink;
