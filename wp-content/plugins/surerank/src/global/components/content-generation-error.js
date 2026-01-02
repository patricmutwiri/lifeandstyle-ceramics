import { __ } from '@wordpress/i18n';
import Alert from '@GlobalComponents/alert';
import { Button } from '@bsf/force-ui';

/**
 * Content Generation Error Component
 *
 * Displays error alerts for content generation failures with conditional support links
 *
 * @param {Object}   props                 Component props
 * @param {Object}   props.error           Error object with message and code
 * @param {Function} props.onRetry         Callback function for retry action
 * @param {string}   props.title           Custom title for the error alert
 * @param {string}   props.fallbackMessage Fallback message if error.message is not available
 * @param {string}   props.supportText     Custom text for support button
 * @param {string}   props.retryText       Custom text for retry button
 */
const ContentGenerationError = ( {
	error,
	onRetry,
	title,
	fallbackMessage,
	supportText,
	retryText,
} ) => {
	// Error codes that should show support link
	const shouldShowSupportLink = [
		'content_generation_error',
		'internal_server_error',
	].includes( error?.code );
	const supportLink = window?.surerank_globals?.support_link;

	const defaultTitle = __( 'Generation Failed', 'surerank' );
	const defaultFallbackMessage = __(
		"Oops! Something went wrong while generating content. You can try again, or edit it manually to make sure it's correct.",
		'surerank'
	);
	const defaultSupportText = __(
		'Click here to contact support.',
		'surerank'
	);
	const defaultRetryText = __( 'Retry', 'surerank' );

	return (
		<Alert
			color="error"
			title={ title || defaultTitle }
			message={
				error?.message || fallbackMessage || defaultFallbackMessage
			}
			footer={
				<div className="flex flex-col gap-1">
					{ shouldShowSupportLink && supportLink && (
						<Button
							tag="a"
							size="md"
							href={ supportLink }
							target="_blank"
							variant="link"
							className="w-fit [&>span]:p-0 no-underline hover:no-underline ring-0 -mt-2 mb-2"
						>
							{ supportText || defaultSupportText }
						</Button>
					) }
					<Button
						onClick={ onRetry }
						tag="button"
						variant="link"
						className="w-fit [&>span]:p-0"
					>
						{ retryText || defaultRetryText }
					</Button>
				</div>
			}
		/>
	);
};

export default ContentGenerationError;
