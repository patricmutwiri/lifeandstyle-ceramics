import { __ } from '@wordpress/i18n';
import { ChevronRight } from 'lucide-react';
import { Button, Text, Title } from '@bsf/force-ui';
import { exitURL } from '@Onboarding/components/exit-button';

const imagesURL = `${ window.surerank_globals.admin_assets_url }/images/`;

const Success = () => {
	const handleSubmit = ( event ) => {
		event.preventDefault();
	};

	const handleClick = () => {
		window.open( exitURL, '_self', 'noopener,noreferrer' );
	};

	return (
		<form className="flex flex-col gap-4" onSubmit={ handleSubmit }>
			<div className="flex gap-4 flex-col md:flex-row">
				<div className="space-y-6 max-w-full md:max-w-[75%]">
					<div className="space-y-2">
						<Title
							tag="h3"
							title={ __( "You're Good to Go! 🚀", 'surerank' ) }
							size="lg"
						/>
						<p>
							{ __(
								"You've successfully set up SureRank, the first step to SEO success and your site is ready. Now, let's optimize your website for search engines.",
								'surerank'
							) }
						</p>
					</div>
					<div className="space-y-2">
						<Text as="p" size={ 14 } weight={ 600 }>
							{ __(
								"Here's What You Can Do With SureRank:",
								'surerank'
							) }
						</Text>
						<ul className="list-none space-y-2 ">
							{ [
								__( 'Fix SEO issues, if any', 'surerank' ),
								__( 'Analyze your website', 'surerank' ),
								__( 'Optimize your content', 'surerank' ),
							].map( ( item ) => (
								<li
									key={ item }
									className="flex items-center gap-1.5 justify-start"
								>
									<ChevronRight className="size-3.5" />
									<span className="text-sm font-normal text-field-label">
										{ item }
									</span>
								</li>
							) ) }
						</ul>
					</div>
				</div>
				<img
					className="w-1/2 md:w-[25%] h-full mx-auto"
					src={ `${ imagesURL }/onboarding-success.svg` }
					alt={ __( 'Solar system and a rocket', 'surerank' ) }
				/>
			</div>
			{ /* Footer */ }
			<div>
				{ /* Divider */ }
				<hr className="border-b border-t-0 border-x-0 border-solid border-border-subtle mt-0 mb-4" />
				<div className="flex justify-start gap-3 flex-col md:flex-row">
					{ /* Action button */ }
					<Button
						variant="primary"
						size="md"
						className="w-full md:w-auto"
						onClick={ handleClick }
					>
						{ __( 'Go To Dashboard', 'surerank' ) }
					</Button>
				</div>
			</div>
		</form>
	);
};

export default Success;
