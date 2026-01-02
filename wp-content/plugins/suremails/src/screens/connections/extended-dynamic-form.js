import FormGenerator from '@components/form-generator';

const ExtendedDynamicForm = ( {
	fields,
	connectionData,
	onChange,
	errors,
	inlineValidator,
	onClickAuthenticate,
	providerOptions = {}, // Add provider options to get help_texts or any other config
} ) => {
	const handleFieldChange = ( updates ) => {
		if ( typeof onChange !== 'function' ) {
			return;
		}

		onChange?.( updates );
	};

	const processedFields = fields.map( ( field ) => {
		const processedField = { ...field };
		if (
			providerOptions.help_texts &&
			typeof providerOptions.help_texts === 'object'
		) {
			if ( providerOptions.help_texts[ field.name ] ) {
				processedField.help_text =
					providerOptions.help_texts[ field.name ];
			}
		}
		return processedField;
	} );

	return (
		<FormGenerator
			fields={ processedFields }
			values={ connectionData }
			onChange={ handleFieldChange }
			errors={ errors }
			inlineValidator={ inlineValidator }
			onClickAuthenticate={ onClickAuthenticate }
		/>
	);
};

export default ExtendedDynamicForm;
