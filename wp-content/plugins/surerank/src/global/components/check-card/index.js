import { Badge, Label, Button, toast } from '@bsf/force-ui';
import { cn, isURL } from '@/functions/utils';
import FixButton from '@GlobalComponents/fix-button';
import { __ } from '@wordpress/i18n';
import { CircleAlert, CircleCheck, Info, TriangleAlert } from 'lucide-react';
import {
	SeoPopupInfoTooltip,
	SeoPopupTooltip,
} from '@/apps/admin-components/tooltip';
import { Fragment } from '@wordpress/element';
import { fetchImageDataByUrl } from '@/functions/api';
import DOMPurify from 'dompurify';

const IMAGE_ID_CACHE = new Map();

const getIcon = ( type ) => {
	const commonClassName = 'size-4';
	switch ( type ) {
		case 'blue':
			return (
				<Info
					className={ cn( commonClassName, 'text-badge-color-sky' ) }
				/>
			);
		case 'red':
			return (
				<CircleAlert
					className={ cn( commonClassName, 'text-badge-color-red' ) }
				/>
			);
		case 'yellow':
			return (
				<TriangleAlert
					className={ cn(
						commonClassName,
						'text-badge-color-yellow'
					) }
				/>
			);
		case 'green':
			return (
				<CircleCheck
					className={ cn(
						commonClassName,
						'text-badge-color-green'
					) }
				/>
			);
		default:
			return null;
	}
};

const formatBrokenLinkTooltip = ( item ) => {
	if ( ! item || typeof item !== 'object' ) {
		return null;
	}

	const { status, details } = item;

	// Create a more descriptive tooltip based on the error type
	let tooltipContent = '';

	if ( details ) {
		tooltipContent += details;
	}

	// Add status-specific helpful information
	if ( status === 404 ) {
		tooltipContent +=
			' ' + __( '(The page or resource was not found)', 'surerank' );
	} else if ( status === 'http_request_failed' ) {
		tooltipContent +=
			' ' + __( '(Unable to connect to the URL)', 'surerank' );
	} else if ( status === 403 ) {
		tooltipContent +=
			' ' + __( '(Access to this resource is forbidden)', 'surerank' );
	} else if ( status === 500 ) {
		tooltipContent += ' ' + __( '(Server error occurred)', 'surerank' );
	} else if ( typeof status === 'number' && status >= 400 ) {
		tooltipContent += ` ${ __( '(HTTP error', 'surerank' ) } ${ status })`;
	}

	// Sanitize the content to prevent XSS attacks
	const purifiedContent = DOMPurify.sanitize( tooltipContent );

	return (
		<div className="space-y-1">
			<p className="m-0 text-inherit">
				<b>{ __( 'Why is this link broken?', 'surerank' ) }</b>
			</p>
			<p
				className="m-0 text-inherit"
				dangerouslySetInnerHTML={ { __html: purifiedContent } }
			/>
			{ status && (
				<p className="text-xs m-0 text-inherit">
					<b>{ __( 'Status:', 'surerank' ) }</b> { status }
				</p>
			) }
		</div>
	);
};

const renderItem = ( item ) => {
	const commonLinkProps = {
		tag: 'a',
		variant: 'link',
		className:
			'font-medium focus:outline-none focus:[box-shadow:none] [&>span]:px-0 break-all',
		target: '_blank',
		rel: 'noopener noreferrer',
	};
	if ( isURL( item ) ) {
		return (
			<li className="m-0 text-sm">
				<Button { ...commonLinkProps } href={ item }>
					{ item }
				</Button>
			</li>
		);
	} else if ( typeof item === 'object' && item?.url ) {
		// For broken links or similar objects
		return (
			<li className="my-1 first:mt-0 last:mb-0 p-2 flex items-center justify-between gap-1.5 text-sm border border-dashed border-border-subtle rounded-md bg-background-secondary">
				<Button { ...commonLinkProps } href={ item.url }>
					{ item.url }
				</Button>
				<SeoPopupInfoTooltip
					content={ formatBrokenLinkTooltip( item ) }
					interactive
					placement="top-start"
					offset={ {
						alignmentAxis: -10,
						mainAxis: 8,
					} }
				/>
			</li>
		);
	}
	return (
		<li className="m-0 text-sm font-medium text-text-secondary list-none">
			{ item }
		</li>
	);
};

export const CheckCard = ( {
	variant,
	label,
	title,
	data,
	showImages,
	onIgnore,
	showRestoreButton = false,
	onRestore,
	showIgnoreButton = false,
	onFix,
	fixItButtonProps = {},
} ) => {
	const { data: descriptionData, listStyleClassName } = getData( data );
	const handleIgnoreClick = async () => {
		try {
			await onIgnore();
			toast.success( __( 'Check ignored successfully', 'surerank' ) );
		} catch ( error ) {
			toast.error( __( 'Failed to ignore check', 'surerank' ) );
		}
	};

	const handleRestoreClick = async () => {
		try {
			await onRestore();
			toast.success( __( 'Check restored successfully', 'surerank' ) );
		} catch ( error ) {
			toast.error( __( 'Failed to restore check', 'surerank' ) );
		}
	};

	return (
		<>
			<div className="relative flex flex-col gap-4 p-3 bg-background-primary rounded-lg shadow-sm border-0.5 border-solid border-border-subtle">
				<div className="w-full flex items-start gap-2">
					{ showRestoreButton ? (
						<Badge
							label={ label }
							size="sm"
							type="pill"
							variant={ variant }
							disableHover
							className={ cn(
								showRestoreButton
									? 'text-badge-color-disabled'
									: ''
							) }
						/>
					) : (
						<>
							<div>
								<p className="sr-only">{ label }</p>
								<span className="p-1 flex [&>svg]:size-4">
									{ getIcon( variant ) }
								</span>
							</div>
						</>
					) }
					<div className="flex items-center flex-col gap-1.5 mt-px">
						<Label
							size="xs"
							className="space-x-1 text-sm text-text-secondary inline"
						>
							{ title }
							<SeoPopupTooltip
								content={ __(
									'Click here to discover more details about this check.',
									'surerank'
								) }
								arrow
							>
								<a
									href={ surerank_globals?.help_link }
									className="shrink-0 align-sub ml-2 focus:outline-none focus:ring-0"
									target="_blank"
									rel="noopener noreferrer"
								>
									<Info className="size-4 text-icon-secondary hidden" />
								</a>
							</SeoPopupTooltip>
						</Label>
						{ fixItButtonProps?.show && (
							<FixButton
								variant="link"
								size="xs"
								className="[&>span]:p-0 mr-auto min-w-fit shrink-0 underline"
								tooltipProps={ { className: 'z-999999' } }
								hidden={ false }
								onClick={ onFix }
								{ ...( ( { show, ...rest } ) => rest )(
									fixItButtonProps
								) }
							/>
						) }
					</div>
					{ showIgnoreButton && (
						<Button
							variant="link"
							onClick={ handleIgnoreClick }
							aria-label={ __( 'Ignore this check', 'surerank' ) }
							size="xs"
							className="underline hover:text-text-secondary ml-auto min-w-fit shrink-0 mt-1 text-text-secondary leading-4"
						>
							{ __( 'Ignore', 'surerank' ) }
						</Button>
					) }
					{ showRestoreButton && (
						<Button
							variant="link"
							type="button"
							onClick={ handleRestoreClick }
							aria-label={ __(
								'Restore this check',
								'surerank'
							) }
							size="xs"
							className="underline hover:text-text-secondary ml-auto min-w-fit shrink-0 mt-1 text-text-secondary leading-4"
						>
							{ __( 'Restore', 'surerank' ) }
						</Button>
					) }
				</div>
				{ ! showImages &&
					descriptionData &&
					descriptionData.length > 0 && (
						<ul
							className={ cn(
								'list-disc list-inside ml-3 mr-0 mt-0 mb-0.5',
								listStyleClassName
							) }
						>
							{ descriptionData.map( ( item, index ) => (
								<Fragment key={ `${ item }-${ index }` }>
									{ renderItem( item ) }
								</Fragment>
							) ) }
						</ul>
					) }
				{ showImages && <ImageGrid images={ descriptionData } /> }
			</div>
		</>
	);
};

export const ImageGrid = ( { images } ) => {
	if ( ! images || ! images.length ) {
		return null;
	}

	const handleImageClick = async ( event, image ) => {
		event?.preventDefault();

		if ( IMAGE_ID_CACHE.has( image ) ) {
			window.open(
				`/wp-admin/upload.php?item=${ IMAGE_ID_CACHE.get( image ) }`,
				'_blank',
				'noopener noreferrer'
			);
			return;
		}

		try {
			const results = await fetchImageDataByUrl( image );

			if ( ! results ) {
				throw new Error( 'No image found' );
			}

			const imageId = results?.id;
			IMAGE_ID_CACHE.set( image, imageId );
			window.open(
				`/wp-admin/upload.php?item=${ imageId }`,
				'_blank',
				'noopener noreferrer'
			);
		} catch ( error ) {
			// If we can't find the image, open the media library
			window.open(
				'/wp-admin/upload.php',
				'_blank',
				'noopener noreferrer'
			);
		}
	};

	return (
		<div className="grid grid-cols-3 gap-2 mb-0.5">
			{ images.map( ( image, index ) =>
				isURL( image ) ? (
					<Button
						variant="link"
						className="inline-flex focus:outline-none focus:[box-shadow:none] p-0 relative"
						onClick={ ( event ) =>
							handleImageClick( event, image )
						}
						key={ `${ image }-${ index }` }
					>
						<div className="absolute inset-0 bg-black bg-opacity-20 pointer-events-none"></div>
						<div className="relative w-full h-36 rounded overflow-hidden">
							<img
								src={ image }
								alt={ image }
								className="w-full h-36 object-cover rounded"
							/>
						</div>
					</Button>
				) : null
			) }
		</div>
	);
};

export const getData = ( descriptions ) => {
	if ( ! Array.isArray( descriptions ) || ! descriptions.length ) {
		return { data: [] };
	}

	// Handle text or list descriptions only
	const data = [];
	let listStyleClassName = '';
	descriptions.forEach( ( item ) => {
		if ( typeof item === 'string' ) {
			data.push( item );
		} else if (
			item &&
			typeof item === 'object' &&
			Array.isArray( item.list )
		) {
			data.push( ...item.list );
			// For SEO-Bar broken links or similar objects
			if ( item.list?.some( ( value ) => value?.url && value?.status ) ) {
				listStyleClassName = 'list-none mx-0';
			}
		} else if (
			item &&
			typeof item === 'object' &&
			! Array.isArray( item?.list ) &&
			typeof item?.list === 'object'
		) {
			data.push( ...Object.values( item.list ) );
		} else {
			// For any other object, just push it as is
			// This could be a broken link object or similar
			data.push( item );
			listStyleClassName = 'list-none mx-0'; // Reset to none for non-list items
		}
	} );
	return { data, listStyleClassName };
};
