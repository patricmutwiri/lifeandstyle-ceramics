import { __, sprintf } from '@wordpress/i18n';
import { Fragment, renderToString, useState } from '@wordpress/element';
import { Title } from '@bsf/force-ui';
import { useOnboardingState } from '@Onboarding/store';
import { renderField } from '../utils';
import StepNavButtons from '../components/nav-buttons';
import apiFetch from '@wordpress/api-fetch';
import { ONBOARDING_URL } from '@Global/constants/api';
import useFormValidation from '@Global/hooks/use-form-validation';

const inputFields = [
	{
		label: __( 'First Name', 'surerank' ),
		name: 'first_name',
		type: 'text',
		width: 'half',
		required: true,
	},
	{
		label: __( 'Last Name', 'surerank' ),
		name: 'last_name',
		type: 'text',
		width: 'half',
		required: false,
	},
	{
		label: __( 'Email Address', 'surerank' ),
		name: 'email',
		type: 'email',
		width: 'full',
		required: true,
	},
	{
		label: (
			<div
				dangerouslySetInnerHTML={ {
					__html: sprintf(
						// translators: %1$s is replaced with the privacy policy link.
						__(
							'Get notified about SEO issues on your website. Plus help improve SureRank by sharing how you use the plugin. %1$s',
							'surerank'
						),
						renderToString(
							<a
								className="no-underline focus:ring-0"
								href={ surerank_globals.privacy_policy_url }
								target="_blank"
								rel="noopener noreferrer"
							>
								{ __( 'View our Privacy Policy', 'surerank' ) }
							</a>
						)
					),
				} }
			/>
		),
		name: 'agree_to_terms',
		value: 'agree_to_terms',
		type: 'checkbox',
		required: true,
	},
];

const UserDetails = () => {
	const [
		{ userDetails = {}, websiteDetails = {}, socialProfilesURLs = {} },
		dispatch,
	] = useOnboardingState();
	// Local form state
	const [ formState, setFormState ] = useState( userDetails );
	const { errors, validate, clearFieldError } = useFormValidation(
		formState,
		inputFields
	);

	const handleSaveForm = () => {
		dispatch( { userDetails: formState } );
	};

	const handleChangeSelection = ( name ) => ( value ) => {
		clearFieldError( name );
		setFormState( {
			...formState,
			[ name ]: value,
		} );
		handleSaveForm();
	};

	const handleSubmit = ( event ) => {
		event.preventDefault();
	};

	const handleClickNext =
		( skip = false ) =>
		async ( setIsLoading ) => {
			setIsLoading( ( prevState ) => ( { ...prevState, next: true } ) );

			if ( ! skip && ! validate() ) {
				setIsLoading( ( prevState ) => ( {
					...prevState,
					next: false,
				} ) );
				throw new Error( `Form validation error!`, {
					cause: 'form-validation',
				} );
			}

			try {
				const about_page =
					websiteDetails?.about_page?.value ??
					websiteDetails?.about_page;
				const contact_page =
					websiteDetails?.contact_page?.value ??
					websiteDetails?.contact_page;

				const payload = {
					website_type: websiteDetails.website_type || '',
					website_name: websiteDetails.website_name || '',
					website_owner_name: websiteDetails.website_owner_name || '',
					business_description: websiteDetails.business_description || '',
					organization_type:
						websiteDetails.organization_type || 'Organization',
					website_owner_phone:
						websiteDetails.website_owner_phone || '',
					website_logo: websiteDetails.website_logo?.url || '',
					about_page: about_page ? parseInt( about_page ) : 0,
					contact_page: contact_page ? parseInt( contact_page ) : 0,
					social_profiles: socialProfilesURLs || [],
					...( ! skip && {
						first_name: formState.first_name || '',
						last_name: formState.last_name || '',
						email: formState.email || '',
					} ),
				};

				await apiFetch( {
					path: ONBOARDING_URL,
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
					},
					body: JSON.stringify( payload ),
				} );
			} catch ( error ) {}
		};

	return (
		<form
			className="flex flex-col gap-6"
			onSubmit={ handleSubmit }
			noValidate
		>
			<div className="space-y-1">
				<Title
					tag="h4"
					title={ __( 'Okay, just one last step…', 'surerank' ) }
					size="md"
				/>
				<p>
					{ __(
						'Help us tailor your SureRank experience and keep you updated with SEO tips, feature improvements, and helpful recommendations.',
						'surerank'
					) }
				</p>
			</div>

			<div className="flex flex-wrap gap-6">
				{ inputFields.map( ( field, index ) => (
					<Fragment key={ field.name }>
						{ renderField(
							field,
							formState[ field.name ],
							handleChangeSelection( field.name ),
							errors[ field.name ],
							{
								initialFocus: index === 0,
							}
						) }
					</Fragment>
				) ) }
			</div>

			{ /* Nav Buttons */ }
			<StepNavButtons
				nextProps={ {
					onClick: handleClickNext( false ),
					children: __( 'Finish', 'surerank' ),
				} }
				backProps={ {
					onClick: handleSaveForm,
				} }
				skipProps={ {
					onClick: handleClickNext( true ),
				} }
			/>
		</form>
	);
};

export default UserDetails;
