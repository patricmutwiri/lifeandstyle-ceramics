import { Fragment } from '@wordpress/element';
import { NoConnections as DemoImage } from 'assets/icons';
import { __ } from '@wordpress/i18n';
import { Plus } from 'lucide-react';
import EmptyState from '@components/empty-state/empty-state';

const NoConnections = ( { onClickAddConnection } ) => {
	const bulletPoints = [
		__(
			'Connect with Amazon SES, Gmail, and more in just a few clicks.',
			'suremails'
		),
		__(
			'Improve email deliverability and avoid the spam folder.',
			'suremails'
		),
		__(
			'Ensure uninterrupted email sending with backup connections.',
			'suremails'
		),
		__(
			'Keep your email credentials safe with industry-standard encryption.',
			'suremails'
		),
	];

	return (
		<Fragment>
			<EmptyState
				image={ DemoImage }
				title={ __( 'Create Your First Connection', 'suremails' ) }
				description={ __(
					"It looks like you haven't set up a SMTP connection yet. Connect to a reliable SMTP provider to ensure your emails are delivered effectively and securely.",
					'suremails'
				) }
				bulletPoints={ bulletPoints }
				action={ {
					variant: 'primary',
					size: 'md',
					icon: <Plus />,
					iconPosition: 'left',
					onClick: onClickAddConnection,
					className: 'font-medium',
					children: __( 'Add Connection', 'suremails' ),
				} }
			/>
		</Fragment>
	);
};

export default NoConnections;
