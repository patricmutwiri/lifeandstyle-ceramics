import React, { useEffect, useState } from 'react';
import { Download, FileText } from 'lucide-react';
import { __ } from '@wordpress/i18n';

export const AttachmentList = ( { attachments } ) => {
	// Helper function to return the full attachment URL.
	const getAttachmentUrl = ( attachment ) => {
		const attachment_url = window.suremails?.attachmentUrl;
		return `${ attachment_url }${ attachment }`;
	};

	// State to store file sizes and missing file information
	const [ fileSizes, setFileSizes ] = useState( {} );

	// Function to fetch file sizes and check if file exists
	const fetchFileSize = async ( attachment ) => {
		try {
			const url = getAttachmentUrl( attachment );
			let response = await fetch( url, { method: 'HEAD' } );

			if ( ! response.ok ) {
				setFileSizes( ( prev ) => ( {
					...prev,
					[ attachment ]: 'No File Found',
				} ) );
				return;
			}

			let size = response.headers.get( 'content-length' );

			if ( ! size ) {
				response = await fetch( url, {
					method: 'GET',
					headers: { Range: 'bytes=0-0' },
				} );
				size =
					response.headers
						.get( 'content-range' )
						?.split( '/' )[ 1 ] || null;
			}

			setFileSizes( ( prev ) => ( {
				...prev,
				[ attachment ]: size ? formatFileSize( size ) : 'No File Found',
			} ) );
		} catch ( error ) {
			setFileSizes( ( prev ) => ( {
				...prev,
				[ attachment ]: 'No File Found',
			} ) );
		}
	};

	// Format file size (bytes to KB, MB, etc.)
	const formatFileSize = ( bytes ) => {
		const sizes = [ 'Bytes', 'KB', 'MB', 'GB', 'TB' ];
		if ( ! bytes || bytes === 0 ) {
			return '0 Byte';
		}
		const i = Math.floor( Math.log( bytes ) / Math.log( 1024 ) );
		return `${ ( bytes / Math.pow( 1024, i ) ).toFixed( 2 ) } ${
			sizes[ i ]
		}`;
	};

	// Fetch file sizes when component mounts
	useEffect( () => {
		attachments.forEach( fetchFileSize );
	}, [ attachments ] );

	return (
		<div className="flex p-6 bg-background-secondary rounded-sm">
			{ attachments.length > 0 ? (
				<ul className="w-full space-y-4">
					{ attachments.map( ( attachment, index ) => {
						const { isImage, displayName } =
							isImageFile( attachment );
						const fileSizeText =
							fileSizes[ attachment ] || 'Fetching size...';
						const fileExists = fileSizeText !== 'No File Found';
						const attachmentUrl = getAttachmentUrl( attachment );

						return (
							<li
								key={ index }
								className="flex items-center space-x-3 py-3 px-3 rounded-md bg-background-primary first:-mt-[13px] last:-mb-[13px] hover:bg-blue-50 transition"
							>
								{ /* File preview wrapped in a link to open the full image. */ }
								<div className="w-10 h-10 flex-shrink-0 rounded-md overflow-hidden bg-background-primary flex items-center justify-center">
									{ isImage && fileExists ? (
										<a
											href={ add_timestamp(
												attachmentUrl
											) }
											target="_blank"
											rel="noopener noreferrer"
										>
											<img
												src={ attachmentUrl }
												alt={
													displayName ??
													__(
														'Attachment',
														'suremails'
													)
												}
												className="w-full h-full object-contain transform transition-transform duration-300 hover:scale-110"
											/>
										</a>
									) : (
										<FileText className="w-8 h-8 text-field-helper" />
									) }
								</div>

								{ /* File details (Name + Size) */ }
								<div className="flex-1 gap-1">
									<a
										href={
											fileExists
												? add_timestamp( attachmentUrl )
												: '#'
										}
										target="_blank"
										rel="noopener noreferrer"
										className={ `text-field-label text-sm no-underline border-none ml-3 focus:outline-none focus:ring-0 ${
											fileExists
												? ''
												: 'pointer-events-none text-field-label'
										}` }
									>
										{ displayName }
									</a>
									<p className="text-xs text-field-helper ml-3">
										{ fileSizeText }
									</p>
								</div>

								{ /* Download button (only if file exists) */ }
								{ fileExists && (
									<a
										href={ attachmentUrl }
										download={ displayName }
										className="bg-background-primary no-underline border-none hover:bg-blue-50"
										title={ __( 'Download', 'suremails' ) }
									>
										<Download className="w-4 h-4 text-text-secondary" />
									</a>
								) }
							</li>
						);
					} ) }
				</ul>
			) : (
				<p className="text-sm text-field-helper">No attachments.</p>
			) }
		</div>
	);
};

// Helper function to determine if a file is an image
const isImageFile = ( filename ) => {
	const imageExtensions = [ 'jpg', 'jpeg', 'png', 'gif', 'webp' ];
	const ext = filename.split( '.' ).pop().toLowerCase();
	const isImage = imageExtensions.includes( ext );
	const displayName = filename.substring( filename.indexOf( '-' ) + 1 );
	return { isImage, displayName };
};

/**
 *
 * @param {string} url URL to add timestamp to for cache busting purposes. This is used to ensure the image is reloaded when the user clicks on it.
 * @return {string} URL with timestamp appended. If no URL is provided, an empty string is returned.
 */
const add_timestamp = ( url ) => {
	if ( url ) {
		return url + `?=` + new Date().getTime();
	}
};
