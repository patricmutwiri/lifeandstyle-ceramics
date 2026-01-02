import { Button, Text, Container, Loader } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import { FileText } from 'lucide-react';
import { SureRankLogo } from '@GlobalComponents/icons';
import { useState } from '@wordpress/element';

/**
 * AI Authentication Screen Component
 * Matches the Figma design for the SureRank AI authentication interface
 * @param {Object}   props
 * @param {Function} props.onClickGetStarted Get Started button click handler
 * @param {Function} props.onClickLearnMore  Learn More button click handler
 * @param {string}   props.heading           Optional custom heading text
 * @param {string}   props.subheading        Optional custom subheading text
 */
const AIAuthScreen = ( {
	onClickGetStarted,
	onClickLearnMore,
	heading,
	subheading,
} ) => {
	const [ loading, setLoading ] = useState( false );

	const features = [
		{
			id: 1,
			text: __( 'Meta Titles & Descriptions', 'surerank' ),
		},
		{
			id: 2,
			text: __( 'Social Titles & Descriptions', 'surerank' ),
		},
		{
			id: 3,
			text: __( 'Site level SEO Checks', 'surerank' ),
		},
	];

	// Apply filter for button configuration (pro can override)
	const buttonConfig = applyFilters( 'surerank.ai_auth_button_config', {
		buttonText: __( "Let's Get Started", 'surerank' ),
		onClick: null, // null means use default handleGetStarted
	} );

	const handleGetStarted = async () => {
		// If custom onClick is provided by filter, use it instead
		if ( buttonConfig.onClick ) {
			buttonConfig.onClick();
			return;
		}

		if ( loading || typeof onClickGetStarted !== 'function' ) {
			return;
		}
		setLoading( true );
		await onClickGetStarted();
		setLoading( false );
	};

	const handleLearnMore = () => {
		if ( typeof onClickLearnMore !== 'function' ) {
			return;
		}
		onClickLearnMore();
	};

	return (
		<div className="flex flex-col items-center self-stretch gap-3 px-5 py-8">
			<Container className="flex flex-col justify-center items-center gap-8 mx-auto max-w-[24.75rem]">
				{ /* Logo Section */ }
				<SureRankLogo width={ 28 } height={ 31 } />

				{ /* Text Section */ }
				<div className="flex flex-col items-center self-stretch gap-3">
					<Text
						size={ 18 }
						weight={ 600 }
						color="primary"
						className="text-center"
					>
						{ heading ||
							__(
								'Let SureRank AI optimize your SEO',
								'surerank'
							) }
					</Text>
					<Text
						size={ 14 }
						weight={ 400 }
						color="secondary"
						className="text-center self-stretch"
					>
						{ subheading ||
							__(
								"Our AI-powered assistant helps you fix SEO issues smartly and quickly. No need to dig through settings or guess what's wrong — we'll do the heavy lifting for you.",
								'surerank'
							) }
					</Text>
				</div>

				{ /* Features List */ }
				<div className="flex flex-row self-stretch flex-wrap justify-center gap-6 py-2">
					{ features.map( ( feature ) => (
						<div
							key={ feature.id }
							className="flex flex-col items-center gap-2 max-w-28"
						>
							<FileText
								width={ 16 }
								height={ 16 }
								className="text-icon-interactive"
							/>
							<Text
								size={ 14 }
								weight={ 500 }
								color="primary"
								className="text-center"
							>
								{ feature.text }
							</Text>
						</div>
					) ) }
				</div>

				{ /* Button Section */ }
				<div className="flex flex-col w-full gap-2 p-2">
					<Button
						variant="primary"
						onClick={ handleGetStarted }
						className="[&>span]:flex [&>span]:items-center [&>span]:justify-center [&>span]:gap-2"
						icon={
							loading && <Loader size="sm" variant="secondary" />
						}
					>
						{ buttonConfig.buttonText }
					</Button>
					<Button
						variant="ghost"
						className="w-fit mx-auto hover:bg-transparent focus:[box-shadow:none] text-text-tertiary hover:text-text-secondary font-normal"
						onClick={ handleLearnMore }
					>
						{ __( 'Learn more', 'surerank' ) }
					</Button>
				</div>
			</Container>
		</div>
	);
};

export default AIAuthScreen;
