import { __ } from '@wordpress/i18n';
import { useState, Fragment, useMemo } from '@wordpress/element';
import {
	FacebookIcon,
	TwitterIcon,
	InstagramIcon,
	LinkedInIcon,
	YouTubeIcon,
	PinterestIcon,
	TikTokIcon,
	MediumIcon,
	TumblrIcon,
	ThreadsIcon,
	YelpIcon,
	WhatsAppIcon,
	TelegramIcon,
	BlueSkyIcon,
} from '../icons';
import { Button, DropdownMenu, Title } from '@bsf/force-ui';
import { PlusIcon, LinkIcon } from 'lucide-react';
import StepNavButtons from '../components/nav-buttons';
import { useOnboardingState } from '@Onboarding/store';
import { focusHelper } from '../utils';

const iconMap = {
	facebook: FacebookIcon,
	twitter: TwitterIcon,
	instagram: InstagramIcon,
	linkedin: LinkedInIcon,
	youtube: YouTubeIcon,
	pinterest: PinterestIcon,
	tiktok: TikTokIcon,
	medium: MediumIcon,
	tumblr: TumblrIcon,
	threads: ThreadsIcon,
	yelp: YelpIcon,
	whatsapp: WhatsAppIcon,
	telegram: TelegramIcon,
	link: LinkIcon,
	bluesky: BlueSkyIcon,
};

const socialProfiles = surerank_admin_common?.social_profiles
	.filter( ( item ) => ! item.extra )
	.map( ( item ) => ( {
		...item,
		icon: iconMap[ item.id ] || iconMap.link,
	} ) );

const dropdownOptions = surerank_admin_common?.social_profiles
	.filter( ( item ) => item.extra )
	.map( ( item ) => ( {
		...item,
		icon: iconMap[ item.id ] || iconMap.link,
	} ) );

/**
 * Get the initial list of social profiles to render.
 *
 * @param {Object} formState - The form state.
 * @return {Array} The initial list of social profiles to render.
 */
const getInitialList = ( formState ) => {
	return [
		...socialProfiles,
		...dropdownOptions.filter( ( item ) => item.id in formState ),
	];
};

const SocialProfiles = () => {
	const [ { socialProfilesURLs = {} }, dispatch ] = useOnboardingState();
	// Local states
	const [ formState, setFormState ] = useState( socialProfilesURLs );
	const [ socialProfileLists, setSocialProfileLists ] = useState(
		getInitialList( formState )
	);

	const handleSubmit = ( event ) => {
		event.preventDefault();
	};

	const handleAddProfileToList = ( socialMediaItem ) => {
		setSocialProfileLists( ( prev ) => [ ...prev, socialMediaItem ] );
	};

	const filteredDropdownOptions = useMemo( () => {
		return dropdownOptions.filter(
			( item ) =>
				! socialProfileLists.some(
					( listItem ) => listItem.id === item.id
				)
		);
	}, [ socialProfileLists ] );

	const handleChange = ( id ) => ( event ) => {
		setFormState( ( prev ) => ( { ...prev, [ id ]: event.target.value } ) );
	};

	const handleSaveForm = () => {
		dispatch( {
			socialProfilesURLs: formState,
		} );
	};

	return (
		<form className="flex flex-col gap-6" onSubmit={ handleSubmit }>
			<div className="space-y-1">
				<Title
					tag="h4"
					title={ __( 'Social Profiles', 'surerank' ) }
					size="md"
				/>
				<p className="w-full">
					{ __(
						'Please enter your social media profiles. These links can appear in the knowledge panel of the search results for your website.',
						'surerank'
					) }
				</p>
			</div>
			{ /* Settings / options */ }
			<div className="flex flex-col border border-solid border-border-subtle rounded-lg p-1">
				{ socialProfileLists.map(
					( { label, id, placeholder, icon: Icon }, index ) => (
						<Fragment key={ id }>
							<div className="flex items-center gap-3 w-full p-2.5">
								<div className="flex items-center gap-3 w-2/4">
									<Icon className="size-5" />
									<span className="hidden md:inline-block text-field-label text-sm font-medium whitespace-nowrap">
										{ label }
									</span>
								</div>
								<input
									className="text-sm text-right text-text-primary placeholder:text-text-tertiary w-full border-none bg-transparent focus:outline-none focus:ring-0"
									placeholder={ placeholder }
									onChange={ handleChange( id ) }
									value={ formState[ id ] || '' }
									{ ...( index === 0 && {
										ref: focusHelper,
									} ) }
								/>
							</div>
							{ ( !! filteredDropdownOptions.length ||
								index < socialProfileLists.length - 1 ) && (
								<span className="w-full block px-2.5">
									<hr className="border-border-subtle border-b border-t-0 border-x-0 my-1 w-full" />
								</span>
							) }
						</Fragment>
					)
				) }
				{ !! filteredDropdownOptions.length && (
					<DropdownMenu>
						<DropdownMenu.Trigger>
							<Button
								type="button"
								variant="ghost"
								className="w-max my-2 mx-auto"
								size="xs"
								icon={ <PlusIcon className="size-4" /> }
								iconPosition="right"
							>
								{ __( 'Add another profile', 'surerank' ) }
							</Button>
						</DropdownMenu.Trigger>
						<DropdownMenu.Portal id="surerank-root">
							<DropdownMenu.ContentWrapper>
								<DropdownMenu.Content className="w-60">
									<DropdownMenu.List>
										{ filteredDropdownOptions.map(
											( {
												label,
												id,
												icon: Icon,
												placeholder,
											} ) => (
												<DropdownMenu.Item
													key={ id }
													onClick={ () =>
														handleAddProfileToList(
															{
																label,
																id,
																icon: Icon,
																placeholder,
															}
														)
													}
												>
													<div className="flex items-center gap-3 w-full">
														<Icon className="size-4" />
														<span className="text-field-label text-sm font-medium">
															{ label }
														</span>
													</div>
												</DropdownMenu.Item>
											)
										) }
									</DropdownMenu.List>
								</DropdownMenu.Content>
							</DropdownMenu.ContentWrapper>
						</DropdownMenu.Portal>
					</DropdownMenu>
				) }
			</div>
			<StepNavButtons
				className="my-0"
				nextProps={ {
					onClick: handleSaveForm,
				} }
				backProps={ {
					onClick: handleSaveForm,
				} }
			/>
		</form>
	);
};

export default SocialProfiles;
