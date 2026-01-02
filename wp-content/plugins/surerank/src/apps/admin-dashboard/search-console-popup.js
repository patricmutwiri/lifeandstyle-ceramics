import { Button, Text } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';
import ModalWrapper from '@AdminComponents/modal-wrapper';

const SearchConsolePopup = ( { isOpen = true } ) => {
	if ( ! isOpen ) {
		return null;
	}

	return (
		<ModalWrapper isOpen={ isOpen } className="top-[40%]">
			<div className="bg-background-primary rounded-lg shadow-2xl w-full p-5 pointer-events-auto border-0.5 border-solid border-border-subtle">
				<div className="flex flex-col items-center w-full">
					<img
						src={ `${ surerank_globals.admin_assets_url }/images/search-console.svg` }
						alt={ __( 'Connect to Search Console', 'surerank' ) }
						className="w-48 sm:w-56 md:w-64 h-auto mb-4"
					/>

					<div className="flex flex-col text-left space-y-1">
						<Text size={ 18 } weight={ 600 } lineHeight={ 28 }>
							{ __(
								'Connect Your Site to Google Search Console',
								'surerank'
							) }
						</Text>
						<Text
							size={ 14 }
							weight={ 400 }
							lineHeight={ 20 }
							color="secondary"
						>
							{ __(
								'Link your website to Google Search Console to access detailed search analytics, track performance, and optimize your site for better search rankings.',
								'surerank'
							) }
						</Text>
						<Text
							size={ 14 }
							weight={ 400 }
							as="a"
							href="https://surerank.com/docs/google-search-console-surerank/"
							target="_blank"
							color="link"
							className="no-underline focus:ring-0"
							aria-label={ __(
								'Learn about Google Search Console and how SureRank uses it for optimizing your website.',
								'surerank'
							) }
						>
							{ __(
								'Learn More about Google Search Console.',
								'surerank'
							) }
						</Text>
					</div>

					<hr className="w-full border-solid border-border-subtle border-0.5 my-5" />

					<div className="w-full space-y-3 text-center">
						<Button
							variant="primary"
							size="md"
							className="w-full"
							onClick={ () =>
								window.open(
									surerank_admin_common.auth_url,
									'_self'
								)
							}
						>
							{ __(
								'Connect to Search Console - It’s Free',
								'surerank'
							) }
						</Button>
					</div>
				</div>
			</div>
		</ModalWrapper>
	);
};

export default SearchConsolePopup;
