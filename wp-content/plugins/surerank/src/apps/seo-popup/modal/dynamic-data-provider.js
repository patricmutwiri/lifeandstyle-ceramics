import { useEffect } from '@wordpress/element';
import {
	useSelect,
	dispatch as staticDispatch,
	select as staticSelect,
} from '@wordpress/data';
import { STORE_NAME as storeName } from '@Store/constants';
import { cleanContent } from '@Functions/utils';

/* global elementor */

export const GutenbergData = ( ChildComponent ) => {
	const WrappedComponent = ( props ) => {
		const dynamicData = useSelect( ( select ) => {
			const coreEditorData = select( 'core/editor' );
			// Post title.
			const postTitle = coreEditorData.getEditedPostAttribute( 'title' );
			// Post excerpt.
			const postExcerpt =
				coreEditorData.getEditedPostAttribute( 'excerpt' );
			// Post content.
			const postContent = coreEditorData.getEditedPostContent();

			return { postTitle, postExcerpt, postContent };
		}, [] );

		useEffect( () => {
			const dispatchToSureRankStore = staticDispatch( storeName );

			dispatchToSureRankStore.updatePostDynamicData( {
				title: dynamicData.postTitle,
				excerpt: dynamicData.postExcerpt,
				content: cleanContent( dynamicData.postContent ),
			} );
		}, [
			dynamicData.postTitle,
			dynamicData.postExcerpt,
			dynamicData.postContent,
		] );

		return <ChildComponent { ...props } />;
	};

	return WrappedComponent;
};

export const ElementorEditorData = ( ChildComponent ) => {
	const WrappedComponent = ( props ) => {
		useEffect( () => {
			const dispatchToSureRankStore = staticDispatch( storeName );

			const contentNodes = [
				...elementor.documents
					.getCurrent()
					.$element.find( '.elementor-widget-container' ),
			];
			const contentArray = contentNodes.map( ( node ) => {
				return node.textContent;
			} );

			const title = elementor.settings.page.model.get( 'post_title' );
			const excerpt = elementor.settings.page.model.get( 'post_excerpt' );
			const featured_image = elementor.settings.page.model.get(
				'post_featured_image'
			);

			// Get current store data.
			const currentData = staticSelect( storeName ).getPostSeoMeta();
			let updateImage = false;

			if (
				! currentData.facebook_image_url &&
				! currentData.twitter_image_url
			) {
				updateImage = true;
			}

			dispatchToSureRankStore.updatePostDynamicData( {
				title,
				content: contentArray.join( ' ' ),
				excerpt,
				...( updateImage &&
					featured_image?.url && {
						facebook_image_url: featured_image.url,
						twitter_image_url: featured_image.url,
						twitter_image_id: featured_image.id,
						facebook_image_id: featured_image.id,
					} ),
			} );
		}, [] );

		return <ChildComponent { ...props } />;
	};

	return WrappedComponent;
};

export const ClassicEditorData = ( ChildComponent ) => {
	const WrappedComponent = ( props ) => {
		const setDynamicData = ( contentType, content ) => {
			const dispatchToSureRankStore = staticDispatch( storeName );

			// Clean content if it is post content.
			if ( contentType === 'content' ) {
				content = cleanContent( content );
			}

			dispatchToSureRankStore.updatePostDynamicData( {
				[ contentType ]: content,
			} );
		};

		useEffect( () => {
			// Add event listener in onChange event of content.
			const content = document.querySelector(
				"textarea[name='content']#content"
			);
			if ( content ) {
				content.addEventListener( 'input', ( e ) => {
					setDynamicData( 'content', e.target.value );
				} );
			}

			// Get iframe body content if content changes. iframe id is content_ifr
			const iframe = document.querySelector( 'iframe#content_ifr' );
			if ( iframe ) {
				iframe.contentDocument.body.addEventListener(
					'input',
					( e ) => {
						setDynamicData( 'content', e.target.innerHTML );
					}
				);
			}

			const excerpt = document.querySelector(
				"textarea[name='excerpt']#excerpt"
			);
			if ( excerpt ) {
				excerpt.addEventListener( 'input', ( e ) => {
					setDynamicData( 'excerpt', e.target.value );
				} );
			}

			const title = document.querySelector(
				"input[name='post_title']#title"
			);
			if ( title ) {
				title.addEventListener( 'input', ( e ) => {
					setDynamicData( 'title', e.target.value );
				} );
			}

			// Remove event listener on component unmount.
			return () => {
				if ( content ) {
					content.removeEventListener( 'input', ( e ) => {
						setDynamicData( 'content', e.target.value );
					} );
				}

				if ( iframe ) {
					iframe.contentDocument.body.removeEventListener(
						'input',
						( e ) => {
							setDynamicData( 'content', e.target.innerHTML );
						}
					);
				}

				if ( excerpt ) {
					excerpt.removeEventListener( 'input', ( e ) => {
						setDynamicData( 'excerpt', e.target.value );
					} );
				}

				if ( title ) {
					title.removeEventListener( 'input', ( e ) => {
						setDynamicData( 'title', e.target.value );
					} );
				}
			};
		}, [] );

		return <ChildComponent { ...props } />;
	};

	return WrappedComponent;
};
