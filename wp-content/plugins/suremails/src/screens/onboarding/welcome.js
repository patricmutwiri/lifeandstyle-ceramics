import { Button, Text } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';
import { Check } from 'lucide-react';
import { useOnboardingNavigation } from './hooks';
import { Divider } from './components';

const features = [
	__( 'Reduce the chances of your emails getting lost in spam', 'suremails' ),
	__( 'Quick and easy setup, no technical skills needed', 'suremails' ),
	__( 'Track, log, and resend emails with ease', 'suremails' ),
	__( 'Connect to multiple email providers', 'suremails' ),
	__( 'Auto-retry failed emails', 'suremails' ),
];

const Welcome = () => {
	const { navigateToNextRoute } = useOnboardingNavigation();

	return (
		<form
			onSubmit={ ( event ) => event.preventDefault() }
			className="space-y-6"
		>
			<div className="space-y-1.5">
				<Text as="h2" size={ 30 } weight={ 600 }>
					{ __( 'Welcome to SureMail', 'suremails' ) }
				</Text>
				<Text size={ 16 } weight={ 500 }>
					{ __( 'Fail-Proof Email Delivery!', 'suremails' ) }
				</Text>
			</div>
			<iframe
				className="w-full aspect-video rounded-lg"
				src="https://www.youtube.com/embed/fFKJfbWLif4?autoplay=1&mute=1"
				title="YouTube video player"
				frameBorder="0"
				allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; autoplay"
				allowFullScreen
			></iframe>
			<ul>
				{ features.map( ( feature, index ) => (
					<li key={ index } className="flex items-center gap-1">
						<Check
							className="size-3 text-icon-primary"
							strokeWidth={ 1.5 }
						/>
						<Text size={ 14 } weight={ 500 } color="label">
							{ feature }
						</Text>
					</li>
				) ) }
			</ul>

			<Divider />

			<Button onClick={ navigateToNextRoute }>
				{ __( "Let's get started", 'suremails' ) }
			</Button>
		</form>
	);
};

export default Welcome;
