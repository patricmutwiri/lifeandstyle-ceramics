import React from 'react';
import { WelcomeImage } from '@assets/icons';
import { Button, Label } from '@bsf/force-ui';
import Title from '@components/title/title';
import { useNavigate } from 'react-router-dom';
import { __ } from '@wordpress/i18n';

const Welcome = () => {
	const navigate = useNavigate();
	return (
		<div className="w-full h-auto rounded-xl gap-2 border-border-subtle border-0.5 p-4 flex flex-col items-center bg-background-primary">
			{ /* Title Section */ }
			<div className="w-full h-full gap-6 p-1 flex justify-start items-center">
				<Title
					title={ __( 'Welcome to SureMail', 'suremails' ) }
					tag="h5"
					size="xs"
				/>
			</div>

			<div className="flex flex-col items-center gap-6 mt-16">
				{ ' ' }
				<div className="flex justify-center">
					<WelcomeImage />
				</div>
				<div className="flex flex-col items-center gap-1">
					<Title
						title={ __(
							'Let’s Get Your First Connection Set Up!',
							'suremails'
						) }
						tag="h2"
					/>

					<Label
						tag="p"
						className="text-base font-normal text-center text-text-secondary"
					>
						{ __(
							'Connect to a trusted SMTP provider to ensure secure and reliable email delivery.',
							'suremails'
						) }
					</Label>
				</div>
				<Button
					variant="primary"
					size="md"
					iconPosition="left"
					onClick={ () =>
						navigate( '/connections', {
							state: {
								openDrawer: true,
							},
						} )
					}
				>
					{ __( 'Connect with SMTP Provider', 'suremails' ) }
				</Button>
			</div>
		</div>
	);
};

export default Welcome;
