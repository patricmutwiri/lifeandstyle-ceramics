import { __ } from '@wordpress/i18n';
import { Button, Container, Label, Title } from '@bsf/force-ui';
import { Check, ChevronRight } from 'lucide-react';
import { useNavigateStep } from '@Onboarding/hooks';

const onboardingBanner =
	surerank_globals.admin_assets_url + '/images/onboarding-welcome-banner.svg';

const features = [
	__( 'Identify and fix SEO issues effortlessly', 'surerank' ),
	__(
		"Analyze and track website's performance in search engines",
		'surerank'
	),
	__( 'Optimize website for better rankings', 'surerank' ),
	__( 'Use AI to optimize your website', 'surerank' ),
	__( 'Enjoy an easy, simple setup', 'surerank' ),
];

const Welcome = () => {
	const { nextStep } = useNavigateStep();

	const handleSubmit = ( event ) => {
		event.preventDefault();
	};

	return (
		<form className="flex flex-col gap-4" onSubmit={ handleSubmit }>
			<Container className="p-1 gap-1.5" direction="column">
				<Title
					tag="h2"
					title={ __( 'Welcome to SureRank!', 'surerank' ) }
					size="lg"
					className="[&>h2]:text-3xl [&>h2]:leading-9.5"
				/>
				<Label tag="p" className="text-base">
					{ __(
						'Set up your site’s SEO easily—no advanced skills needed!',
						'surerank'
					) }
				</Label>
			</Container>
			{ /* Banner */ }
			<div className="p-1">
				<img
					src={ onboardingBanner }
					alt="Onboarding Welcome Banner"
					className="w-full h-full object-cover"
				/>
			</div>
			{ /* Feature list */ }
			<ul
				className="space-y-1.5 p-1"
				aria-label={ __( 'List of features', 'surerank' ) }
			>
				{ features.map( ( feature ) => (
					<li
						key={ feature }
						className="flex items-center gap-2"
						aria-label={ feature }
					>
						<Check
							className="size-3 text-icon-primary"
							aria-hidden="true"
						/>
						<Label
							size="sm"
							tag="p"
							className="font-medium text-field-label"
						>
							{ feature }
						</Label>
					</li>
				) ) }
			</ul>
			<hr className="border-t border-b-0 border-x-0 border-solid border-border-subtle m-1" />
			<div className="p-1">
				<Button
					variant="primary"
					size="md"
					icon={ <ChevronRight /> }
					iconPosition="right"
					onClick={ () => nextStep() }
					className="w-fit mr-auto"
				>
					{ __( "Let's Get Started", 'surerank' ) }
				</Button>
			</div>
		</form>
	);
};

export default Welcome;
