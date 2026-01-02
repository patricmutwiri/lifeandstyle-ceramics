import { __, sprintf } from '@wordpress/i18n';
import { useCallback } from '@wordpress/element';
import { ProgressBar, Text, Skeleton } from '@bsf/force-ui';
import { CheckIcon, MinusIcon, Loader2Icon } from 'lucide-react';
import { cn } from '@/functions/utils';
import { PLUGIN_OPTIONS } from './constants';

// Get icon for status
const getStatusIcon = ( status ) => {
	switch ( status ) {
		case 'completed':
			return <CheckIcon size={ 12 } className="text-badge-color-green" />;
		case 'in_progress':
			return (
				<Loader2Icon
					size={ 12 }
					className="text-badge-color-gray animate-spin"
				/>
			);
		default:
			return <MinusIcon size={ 12 } className="text-badge-color-gray" />;
	}
};

// Get badge color class
const getBadgeColorClass = ( status ) => {
	switch ( status ) {
		case 'completed':
			return 'bg-badge-background-green border-badge-border-green';
		case 'in_progress':
			return 'bg-badge-background-gray border-badge-border-gray';
		default:
			return 'bg-badge-background-gray border-badge-border-gray';
	}
};

export const ProgressItem = ( { title, status, completed, total } ) => {
	return (
		<div className="flex items-center p-2 bg-white border border-solid border-border-subtle rounded-lg">
			<div className="flex items-center gap-2">
				<div
					className={ cn(
						'flex items-center justify-center w-5 h-5 rounded-full',
						getBadgeColorClass( status )
					) }
				>
					{ getStatusIcon( status ) }
				</div>
				<Text size={ 14 } weight={ 500 } color="secondary">
					{ title }
				</Text>
			</div>
			<Text as="span" color="secondary" className="mx-0.5">
				-
			</Text>
			<Text size={ 12 } weight={ 400 } color="tertiary">
				{ status === 'completed'
					? sprintf(
							// translators: %s: percentage
							__( '%s completed', 'surerank' ),
							'100%'
					  )
					: sprintf(
							// translators: %1$s: completed items, %2$s: total items
							__( '%1$s/%2$s imported', 'surerank' ),
							completed,
							total
					  ) }
			</Text>
		</div>
	);
};

// MigrationProgressStatus component
export const MigrationProgressStatus = ( {
	migrationData,
	currentStatus,
	pluginSlug,
	progress,
} ) => {
	// Determine the status of each item
	const getItemStatus = useCallback(
		( type, key = null ) => {
			if ( ! currentStatus ) {
				return 'pending';
			}

			if ( type === 'global' ) {
				if ( migrationData?.global_settings_migrated ) {
					return 'completed';
				}
				if ( currentStatus.type === 'global_settings' ) {
					return 'in_progress';
				}
				return 'pending';
			}

			if ( type === 'term' && key ) {
				const termData = migrationData?.terms?.[ key ];
				if ( ! termData ) {
					return 'pending';
				}

				if (
					termData.completed?.length === termData.total &&
					termData.total > 0
				) {
					return 'completed';
				}
				if (
					currentStatus.type === 'terms' &&
					currentStatus.taxonomy === key
				) {
					return 'in_progress';
				}
				return termData.completed?.length > 0
					? 'in_progress'
					: 'pending';
			}

			if ( type === 'post' && key ) {
				const postData = migrationData?.posts?.[ key ];
				if ( ! postData ) {
					return 'pending';
				}

				if (
					postData.completed?.length === postData.total &&
					postData.total > 0
				) {
					return 'completed';
				}
				if (
					currentStatus.type === 'posts' &&
					currentStatus.postType === key
				) {
					return 'in_progress';
				}
				return postData.completed?.length > 0
					? 'in_progress'
					: 'pending';
			}

			return 'pending';
		},
		[ currentStatus, migrationData ]
	);

	// Get status message for current activity
	const getStatusMessage = useCallback( () => {
		if ( ! currentStatus ) {
			return __( 'Preparing migration…', 'surerank' );
		}

		switch ( currentStatus.type ) {
			case 'reading_terms':
				// If we know which taxonomy is being read, show its title
				if (
					currentStatus.taxonomy &&
					migrationData?.terms?.[ currentStatus.taxonomy ]
				) {
					return sprintf(
						// translators: %s: taxonomy title
						__( 'Reading %s…', 'surerank' ),
						migrationData.terms[ currentStatus.taxonomy ].title
					);
				}
				return __( 'Reading taxonomies…', 'surerank' );

			case 'reading_posts':
				// If we know which post type is being read, show its title
				if (
					currentStatus.postType &&
					migrationData?.posts?.[ currentStatus.postType ]
				) {
					return sprintf(
						// translators: %s: post type title
						__( 'Reading %s…', 'surerank' ),
						migrationData.posts[ currentStatus.postType ].title
					);
				}
				return __( 'Reading content…', 'surerank' );

			case 'global_settings':
				return __( 'Migrating global settings…', 'surerank' );

			case 'terms':
				if (
					currentStatus.taxonomy &&
					migrationData?.terms?.[ currentStatus.taxonomy ]
				) {
					return sprintf(
						// translators: %s: taxonomy title
						__( 'Migrating %s…', 'surerank' ),
						migrationData.terms[ currentStatus.taxonomy ].title
					);
				}
				return __( 'Migrating taxonomies…', 'surerank' );

			case 'posts':
				if (
					currentStatus.postType &&
					migrationData?.posts?.[ currentStatus.postType ]
				) {
					return sprintf(
						// translators: %s: post type title
						__( 'Migrating %s…', 'surerank' ),
						migrationData.posts[ currentStatus.postType ].title
					);
				}
				return __( 'Migrating content…', 'surerank' );

			default:
				return __( 'Processing…', 'surerank' );
		}
	}, [ currentStatus ] );

	const globalStatus = getItemStatus( 'global' );

	return (
		<>
			<Text color="label" weight={ 600 } size={ 16 }>
				{ __( 'Importing SEO Settings from', 'surerank' ) }{ ' ' }
				{
					PLUGIN_OPTIONS.find(
						( plugin ) => plugin.slug === pluginSlug
					)?.name
				}
				...
			</Text>
			<div className="w-full space-y-2">
				<ProgressBar progress={ progress } />
				<div className="flex items-center justify-between">
					<Text color="help" weight={ 400 } size={ 12 }>
						{ getStatusMessage() }
					</Text>
					<Text color="help" weight={ 400 } size={ 12 }>
						{ progress }%
					</Text>
				</div>
			</div>
			{ ! currentStatus.type?.match( /^reading_\S+/i ) ? (
				<div className="flex flex-col gap-2 mt-4">
					<div className="flex flex-col gap-2">
						{ /* Global Settings */ }
						<ProgressItem
							title={ __( 'Global Settings', 'surerank' ) }
							status={ globalStatus }
							completed={
								migrationData?.global_settings_migrated ? 1 : 0
							}
							total={ 1 }
						/>

						{ /* Terms - Display each taxonomy dynamically */ }
						{ Object.entries( migrationData?.terms || {} ).map(
							( [ taxonomyKey, taxonomyData ] ) => (
								<ProgressItem
									key={ `term-${ taxonomyKey }` }
									title={ taxonomyData.title }
									status={ getItemStatus(
										'term',
										taxonomyKey
									) }
									completed={
										taxonomyData.completed?.length || 0
									}
									total={ taxonomyData.total || 0 }
								/>
							)
						) }

						{ /* Posts - Display each post type dynamically */ }
						{ Object.entries( migrationData?.posts || {} ).map(
							( [ postTypeKey, postTypeData ] ) => (
								<ProgressItem
									key={ `post-${ postTypeKey }` }
									title={ postTypeData.title }
									status={ getItemStatus(
										'post',
										postTypeKey
									) }
									completed={
										postTypeData.completed?.length || 0
									}
									total={ postTypeData.total || 0 }
								/>
							)
						) }
					</div>
				</div>
			) : (
				<div className="flex flex-col gap-2 mt-4">
					{ Array.from( { length: 3 } ).map( ( _, index ) => (
						<Skeleton
							key={ index }
							className="h-[2.375rem] w-full"
						/>
					) ) }
				</div>
			) }
		</>
	);
};
