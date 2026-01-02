import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import parse from 'html-react-parser';
import DOMPurify from 'dompurify';

const TruncateText = ( { characterLimit = 200, html } ) => {
	const [ showMore, setShowMore ] = useState( false );

	if ( ! html ) {
		return null;
	}

	const sanitizedHTML = DOMPurify.sanitize( html );

	const getPlainText = ( htmlContent ) => {
		const tempDiv = document.createElement( 'div' );
		tempDiv.innerHTML = htmlContent;
		return tempDiv.textContent || tempDiv.innerText || '';
	};

	const plainText = getPlainText( sanitizedHTML );
	const shouldTruncate = plainText.length > characterLimit;

	const toggleShowMore = () => {
		setShowMore( ( prev ) => ! prev );
	};

	return (
		<span className="block space-x-1">
			{ ! showMore ? (
				<>
					<span>
						{ shouldTruncate
							? `${ plainText.slice( 0, characterLimit ) }...`
							: plainText }
					</span>
					{ shouldTruncate && (
						<button
							type="button"
							onClick={ toggleShowMore }
							className="text-text-secondary [background:none] appearance-none border-0 p-0 m-0 cursor-pointer"
						>
							{ __( 'Show more', 'suremails' ) }
						</button>
					) }
				</>
			) : (
				<>
					<span>{ parse( sanitizedHTML ) }</span>
					{ shouldTruncate && (
						<button
							type="button"
							onClick={ toggleShowMore }
							className="text-text-secondary [background:none] appearance-none border-0 p-0 m-0 cursor-pointer"
						>
							{ __( 'Show less', 'suremails' ) }
						</button>
					) }
				</>
			) }
		</span>
	);
};

export default TruncateText;
