import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { RadioButton, Title } from '@bsf/force-ui';
import StepNavButtons from '../components/nav-buttons';
import { useOnboardingState } from '@Onboarding/store';

const options = [
	{
		label: __( 'SEOPress', 'surerank' ),
		value: 'seopress',
	},
	{
		label: __( 'AIO SEO', 'surerank' ),
		value: 'aioseo',
	},
	{
		label: __( 'Yoast SEO', 'surerank' ),
		value: 'yoastseo',
	},
];

const ImportData = () => {
	const [ { import_from = '' }, dispatch ] = useOnboardingState();
	// Local form state
	const [ formState, setFormState ] = useState( import_from );

	const handleFormSubmit = ( event ) => {
		event.preventDefault();
	};

	const handleChange = ( value ) => {
		setFormState( value );
	};

	const handleNext = () => {
		dispatch( {
			import_from: formState,
		} );
	};

	return (
		<form className="flex flex-col gap-6" onSubmit={ handleFormSubmit }>
			<div className="space-y-1">
				<Title
					tag="h4"
					title={ __(
						'Import Data From Your Current Plugins',
						'surerank'
					) }
					size="md"
				/>
				<p>
					{ __(
						'We have deducted few SEO plugins installed on your website. Select the plugin from which you want to import',
						'surerank'
					) }
				</p>
			</div>
			{ /* Settings / options */ }
			<div>
				<RadioButton.Group
					columns={ 3 }
					onChange={ handleChange }
					value={ formState }
					vertical
					size="md"
					name="import_from"
				>
					{ options.map( ( option ) => (
						<RadioButton.Button
							key={ option.value }
							buttonWrapperClasses="pl-3 py-3"
							label={ {
								heading: option.label,
							} }
							value={ option.value }
							borderOn
						/>
					) ) }
				</RadioButton.Group>
			</div>
			{ /* Nav Buttons */ }
			<StepNavButtons
				nextProps={ {
					onClick: handleNext,
				} }
			/>
		</form>
	);
};

export default ImportData;
