import { __ } from '@wordpress/i18n';
import { renderField } from '../utils';
import StepNavButtons from '../components/nav-buttons';
import { Fragment, useState, useEffect, useMemo } from '@wordpress/element';
import { useOnboardingState } from '@Onboarding/store';
import { Title, Label } from '@bsf/force-ui';
import apiFetch from '@wordpress/api-fetch';
import { InfoTooltip } from '@AdminComponents/tooltip';

const websiteTypes = [
	{
		label: __( 'Personal Website', 'surerank' ),
		value: 'personal',
	},
	{
		label: __( 'Business Website', 'surerank' ),
		value: 'business',
	},
	{
		label: __( 'Organization', 'surerank' ),
		value: 'organization',
	},
	{
		label: __( 'Personal Blog', 'surerank' ),
		value: 'blog',
	},
	{
		label: __( 'Community Blog/News Website', 'surerank' ),
		value: 'community',
	},
	{
		label: __( 'E-commerce Store', 'surerank' ),
		value: 'ecommerce',
	},
];

const WebsiteDetails = () => {
	const [ { pages = [], websiteDetails = {}, userDetails = {} }, dispatch ] =
		useOnboardingState();

	const organizationOptions = Object.values(
		surerank_globals?.schema_type_options?.Organization || {}
	);

	const [ formState, setFormState ] = useState( websiteDetails );
	const [ pageOptions, setPageOptions ] = useState( pages ); // Local state for pages

	/**
	 * Fetch pages from the WordPress REST API
	 * @param {string} search - Search query
	 * @return {Promise<Array>} Array of page objects with label and value
	 */
	const fetchPages = async ( search = '' ) => {
		try {
			const response = await apiFetch( {
				path: `/wp/v2/pages?per_page=10${
					search ? `&search=${ encodeURIComponent( search ) }` : ''
				}`,
				method: 'GET',
			} );

			return response.map( ( page ) => ( {
				label: page.title.rendered || __( 'Untitled', 'surerank' ),
				value: page.id,
			} ) );
		} catch ( error ) {
			return [];
		}
	};

	useEffect( () => {
		const loadInitialPages = async () => {
			try {
				const pagesData = await fetchPages();
				dispatch( { pages: pagesData } );
				setPageOptions( pagesData ); // Update local state
			} catch ( error ) {
				dispatch( { pages: [] } );
				setPageOptions( [] );
			}
		};
		loadInitialPages();
	}, [] );

	// Sync formState and dispatch websiteDetails/userDetails
	useEffect( () => {
		const details = surerank_admin_common?.website_details;
		const data = {
			website_type:
				websiteDetails?.website_type ||
				details?.website_represents ||
				'',
			website_name:
				websiteDetails?.website_name || details?.website_name || '',
			website_owner_name:
				websiteDetails?.website_owner_name ||
				details?.website_owner_name ||
				'',
			organization_type:
				websiteDetails?.organization_type || 'Organization',
			website_owner_phone:
				websiteDetails?.website_owner_phone ||
				details?.website_owner_phone ||
				'',
			business_description:
				websiteDetails?.business_description ||
				details?.business_description ||
				'',
			website_logo:
				websiteDetails?.website_logo || details?.website_logo || '',
			about_page:
				websiteDetails?.about_page || details?.website_about_us || '',
			contact_page:
				websiteDetails?.contact_page ||
				details?.website_contact_us ||
				'',
			first_name:
				userDetails?.first_name ||
				details?.website_lead_details?.first_name ||
				'',
			last_name:
				userDetails?.last_name ||
				details?.website_lead_details?.last_name ||
				'',
			email:
				userDetails?.email ||
				details?.website_lead_details?.email ||
				'',
		};

		const user_data = {
			first_name:
				userDetails?.first_name ||
				details?.website_lead_details?.first_name ||
				'',
			last_name:
				userDetails?.last_name ||
				details?.website_lead_details?.last_name ||
				'',
			email:
				userDetails?.email ||
				details?.website_lead_details?.email ||
				'',
		};

		dispatch( {
			websiteDetails: data,
			userDetails: user_data,
		} );

		setFormState( data );
	}, [] );

	const handleSubmit = ( event ) => {
		event.preventDefault();
	};

	const handleChangeSelection = ( name ) => ( value ) => {
		setFormState( ( prev ) => ( {
			...prev,
			[ name ]: value?.value ?? value,
		} ) );
	};

	const baseFields = [
		{
			label: __( 'This Website Represents', 'surerank' ),
			name: 'website_type',
			type: 'select',
			options: websiteTypes || [],
			width: 'half',
		},
		{
			label: __( 'Organization Type', 'surerank' ),
			name: 'organization_type',
			type: 'selectGroup',
			options: organizationOptions,
			width: 'half',
			conditionalOn: 'website_type',
			conditionalValues: [
				'business',
				'organization',
				'ecommerce',
				'community',
			],
		},
		{
			label: __( 'Website Name', 'surerank' ),
			name: 'website_name',
			type: 'text',
			width: 'half',
			conditionalOn: 'website_type',
			conditionalValues: [
				'business',
				'organization',
				'ecommerce',
				'community',
			],
		},
		{
			label: __( 'Phone Number (Optional)', 'surerank' ),
			name: 'website_owner_phone',
			type: 'text',
			width: 'half',
		},
		{
			label: (
				<>
					<div className="flex items-center justify-start gap-1">
						<Label tag="span" size="sm">
							{ __( 'Describe what you do', 'surerank' ) }
						</Label>
						<InfoTooltip
							content={ __(
								'Please describe what you do in a few sentences. This description will be used for content generation and other purposes.',
								'surerank'
							) }
						/>
					</div>
				</>
			),
			name: 'business_description',
			type: 'textarea',
			width: 'full',
			rows: 3,
		},
		{
			label: __( 'Website Owner Name', 'surerank' ),
			name: 'website_owner_name',
			type: 'text',
			width: 'half',
			conditionalOn: 'website_type',
			conditionalValues: [ 'personal', 'blog' ],
		},
		{
			label: __( 'Website Logo', 'surerank' ),
			name: 'website_logo',
			type: 'file',
			width: 'full',
			accept: 'image/*',
			description: __(
				'Recommended Logo size 112 X 112 or more, PNG / JPG format',
				'surerank'
			),
		},
	];

	const loadingFields = useMemo(
		() => [
			{
				label: __( 'Select About Page', 'surerank' ),
				name: 'about_page',
				type: 'select',
				defaultValue: formState?.about_page || {},
				options: pageOptions || [],
				width: 'half',
				combobox: true,
				by: 'value',
				searchFn: fetchPages,
			},
			{
				label: __( 'Select Contact Page', 'surerank' ),
				name: 'contact_page',
				type: 'select',
				defaultValue: formState?.contact_page || {},
				options: pageOptions || [],
				width: 'half',
				combobox: true,
				searchFn: fetchPages,
				by: 'value',
			},
		],
		[ pageOptions, formState ]
	);

	const handleSaveForm = () => {
		dispatch( { websiteDetails: formState } );
	};

	// Filter fields based on their conditions
	const filteredFields = baseFields.filter( ( field ) => {
		if ( field.conditionalOn === undefined ) {
			return true;
		}
		return field.conditionalValues?.includes(
			formState[ field.conditionalOn ]
		);
	} );

	return (
		<form className="flex flex-col gap-6" onSubmit={ handleSubmit }>
			<div className="space-y-1">
				<Title
					tag="h4"
					title={ __( 'Your Website Basic Details', 'surerank' ) }
					size="md"
				/>
				<p>
					{ __(
						'Let’s start with some basic information about your website. This info helps personalize your site and may be used in things like search results, structured data, and public details about your site.',
						'surerank'
					) }
				</p>
			</div>

			<div className="flex flex-wrap gap-6">
				{ filteredFields.map( ( field, index ) => (
					<Fragment key={ field.name }>
						{ renderField(
							field,
							formState[ field.name ],
							handleChangeSelection( field.name ),
							null,
							{
								initialFocus: index === 0,
							}
						) }
					</Fragment>
				) ) }
				{ loadingFields.map( ( field ) => (
					<Fragment key={ field.name }>
						{ renderField(
							field,
							formState[ field.name ] ?? '',
							handleChangeSelection( field.name )
						) }
					</Fragment>
				) ) }
			</div>
			<StepNavButtons
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

export default WebsiteDetails;
