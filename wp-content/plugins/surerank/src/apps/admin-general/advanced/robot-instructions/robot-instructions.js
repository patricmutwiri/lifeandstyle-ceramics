import PageContentWrapper from '@AdminComponents/page-content-wrapper';
import { __, sprintf } from '@wordpress/i18n';
import { motion } from 'framer-motion';
import { useEffect, useCallback } from '@wordpress/element';
// import FollowingTab from './following';
// import ArchivingTab from './archiving';
// import DirectiveTab from './directive';
// import IndexingTab from './indexing';
import apiFetch from '@wordpress/api-fetch';
import withSuspense from '@AdminComponents/hoc/with-suspense';
import { POST_CONTENT_URL } from '@Global/constants/api';
import { useLocation } from '@tanstack/react-router';
import GeneratePageContent from '@Functions/page-content-generator';
import { Skeleton } from '@bsf/force-ui';
import { useDispatch, useSuspenseSelect } from '@wordpress/data';
import { STORE_NAME } from '@AdminStore/constants';

const PAGE_TITLE = {
	indexing: __( 'No Index', 'surerank' ),
	following: __( 'No Follow', 'surerank' ),
	archiving: __( 'No Archive', 'surerank' ),
	// directive: __( 'Directive', 'surerank' ),
};

const PAGE_DESCRIPTION = {
	indexing: __(
		'This setting tells search engines not to index selected post types, pages, taxonomies, or archives. It’s useful for keeping low-value or duplicate content out of search results and focusing SEO efforts on key pages.',
		'surerank'
	),
	following: __(
		'This stops search engines from following links on selected content. It’s helpful when you want to avoid passing link value to certain pages or reduce crawling on parts of your site that aren’t important for SEO.',
		'surerank'
	),
	archiving: __(
		'This prevents search engines from showing cached versions of specific content in search results. It’s helpful for keeping control over how your content appears and ensuring only the latest version is viewed.',
		'surerank'
	),
};

const renderSkeleton = () => (
	<div className="space-y-4 pt-2">
		<Skeleton variant="rectangular" className="w-full max-w-72 h-5" />
		<Skeleton variant="rectangular" className="w-full max-w-72 h-5" />
		<Skeleton variant="rectangular" className="w-full max-w-72 h-5" />
	</div>
);

const migrateOptions = ( options ) =>
	Object.entries( options ).map( ( [ value, label ] ) => ( {
		value,
		label,
	} ) );

const getPageContent = ( options, settingsType = 'no_index' ) => {
	const settingsTypes = {
		no_index: 'noindex',
		no_follow: 'nofollow',
		no_archive: 'noarchive',
	};
	// Description templates for each settings type.
	const descriptionTemplates = {
		// translators: %1$s: HTML tag, %2$s: post type
		no_index: __(
			'Use a %1$s tag on all %2$s to prevent them from appearing in search results.',
			'surerank'
		),
		// translators: %1$s: HTML tag, %2$s: post type
		no_follow: __(
			'Use a %1$s tag to stop search engines from following links on %2$s.',
			'surerank'
		),
		// translators: %1$s: HTML tag, %2$s: post type
		no_archive: __(
			'Use a %1$s tag to keep search engines from saving cached versions of %2$s.',
			'surerank'
		),
	};

	return [
		{
			container: {
				id: 'indexing-container',
				direction: 'column',
				gap: 6,
			},
			content: [
				{
					id: 'indexing-tabs',
					type: 'tabs',
					tabs: [
						{
							slug: 'post_types',
							label: __( 'Post Types', 'surerank' ),
						},
						{
							slug: 'taxonomies',
							label: __( 'Taxonomies', 'surerank' ),
						},
						{
							slug: 'archives',
							label: __( 'Archives', 'surerank' ),
						},
					].map( ( tab ) => ( {
						...tab,
						content: migrateOptions( options[ tab.slug ] ).map(
							( option ) => ( {
								type: 'switch',
								id: option.value,
								storeKey: settingsType,
								value: option.value,
								dataType: 'array',
								label: option.label,
								// eslint-disable-next-line
								description: sprintf(
									descriptionTemplates[ settingsType ],
									settingsTypes[ settingsType ],
									option.label
								),
							} )
						),
					} ) ),
				},
			],
		},
	];
};

const RobotInstructions = () => {
	const { pathname } = useLocation();
	const slug = pathname.split( '/' ).pop();

	const { updateAppSettings } = useDispatch( STORE_NAME );
	const { appSettings } = useSuspenseSelect( ( select ) => {
		const { getAppSettings } = select( STORE_NAME );
		return {
			appSettings: getAppSettings(),
		};
	}, [] );

	// Get post content from Redux store
	const postContent = appSettings.post_content;

	const renderTabComponent = () => {
		switch ( slug ) {
			case 'following':
				return (
					<GeneratePageContent
						key={ slug }
						json={ getPageContent( postContent, 'no_follow' ) }
					/>
				);
			case 'archiving':
				return (
					<GeneratePageContent
						key={ slug }
						json={ getPageContent( postContent, 'no_archive' ) }
					/>
				);
			default:
				return (
					<GeneratePageContent
						key={ slug }
						json={ getPageContent( postContent, 'no_index' ) }
					/>
				);
		}
	};

	const fetchPostContent = useCallback( async () => {
		try {
			const response = await apiFetch( {
				path: POST_CONTENT_URL,
				method: 'GET',
			} );
			// Store data in Redux store using updateAppSettings
			updateAppSettings( { post_content: response.data } );
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.error( 'Error fetching data:', error );
		}
	}, [ updateAppSettings ] );

	useEffect( () => {
		// Only fetch if post content is not already in the store
		if ( ! postContent ) {
			fetchPostContent();
		}
	}, [ postContent, fetchPostContent ] );

	return (
		<PageContentWrapper
			title={ PAGE_TITLE[ slug ] }
			description={ PAGE_DESCRIPTION[ slug ] }
		>
			<motion.div
				initial={ { opacity: 0, x: 0 } }
				animate={ { opacity: 1, y: 0 } }
				exit={ { opacity: 0, x: -10 } }
				transition={ { duration: 0.3 } }
				className="w-full"
			>
				{ ! postContent ? renderSkeleton() : renderTabComponent() }
			</motion.div>
		</PageContentWrapper>
	);
};

export default withSuspense( RobotInstructions );
