import { NoEmailLogs } from 'assets/icons';
import { __ } from '@wordpress/i18n';
import EmptyState from '@components/empty-state/empty-state';

const EmptyLogs = () => {
	const bulletPoints = [
		__(
			'View delivery status, timestamps, and more for each sent email.',
			'suremails'
		),
		__(
			'Quickly resend any email directly from the logs if needed.',
			'suremails'
		),
		__(
			'Get insights into your email performance, including success and failure rates.',
			'suremails'
		),
		__(
			'Easily identify and resolve any issues with failed email deliveries.',
			'suremails'
		),
	];

	return (
		<div className="bg-background-primary border-0.5 border-solid border-border-subtle p-4 rounded-xl shadow-sm ml-2 mr-2 mt-2">
			<EmptyState
				image={ NoEmailLogs }
				title={ __( 'No Email Logs Available', 'suremails' ) }
				description={ __(
					"Once your emails start sending, you'll see detailed logs here to help you monitor and manage your email activity.",
					'suremails'
				) }
				bulletPoints={ bulletPoints }
			/>
		</div>
	);
};

export default EmptyLogs;
