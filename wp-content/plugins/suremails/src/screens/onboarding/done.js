import { WelcomeImage } from '@assets/icons';
import { Button, Container, Text, Title } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';
import { CheckIcon, ExternalLink } from 'lucide-react';
import { Divider } from './components';
import { useNavigate } from 'react-router-dom';

const features = [
	[
		__( 'Inbox-ready emails:', 'suremails' ),
		__( 'Reduce the chances of email getting lost in spam!', 'suremails' ),
	],
	[
		__( 'Easy tracking:', 'suremails' ),
		__( 'See every email you send in one place', 'suremails' ),
	],
	[
		__( 'Peace of mind:', 'suremails' ),
		__(
			'If sending fails, SureMail will automatically retry',
			'suremails'
		),
	],
];

const Done = () => {
	const navigate = useNavigate();

	const handleGoToDashboard = () => {
		navigate( '/dashboard' );
	};

	const handleGoToDocumentation = () => {
		window.open( suremails.docsURL, '_blank', 'noopener,noreferrer' );
	};

	return (
		<div className="space-y-6">
			<Container gap="sm" align="center" className="h-auto">
				<div className="space-y-2 max-w-[22.5rem]">
					<Title
						tag="h3"
						title={ __( "You're Good to Go! 🚀", 'suremails' ) }
						size="lg"
					/>
					<Text size={ 14 } weight={ 400 } color="secondary">
						{ __(
							'You’ve successfully set up SureMail, and your site is ready to send emails without a hitch! Now you can focus on your business and let us handle the rest.',
							'suremails'
						) }
					</Text>
				</div>
				<WelcomeImage className="w-full h-full max-w-32 mx-auto" />
			</Container>
			<div className="space-y-2">
				<Text size={ 14 } weight={ 600 } color="primary">
					{ __(
						'Here’s What SureMail Will Do for You Now:',
						'suremails'
					) }
				</Text>
				{ features.map( ( feature, index ) => (
					<Container
						key={ index }
						className="flex items-center gap-1.5"
					>
						<CheckIcon className="size-4 text-icon-interactive" />
						<Text size={ 14 } weight={ 400 } color="label">
							<Text as="b" weight={ 500 }>
								{ feature[ 0 ] }{ ' ' }
							</Text>
							{ feature[ 1 ] }
						</Text>
					</Container>
				) ) }
			</div>

			<Divider />

			<Container align="start" className="h-auto gap-3">
				<Button onClick={ handleGoToDashboard }>
					{ __( 'Go To Dashboard', 'suremails' ) }
				</Button>
				<Button
					variant="ghost"
					icon={ <ExternalLink /> }
					iconPosition="right"
					onClick={ handleGoToDocumentation }
				>
					{ __( 'Documentation', 'suremails' ) }
				</Button>
			</Container>
		</div>
	);
};

export default Done;
