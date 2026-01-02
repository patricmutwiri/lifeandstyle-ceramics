import { useMemo } from '@wordpress/element';
import { Badge } from '@bsf/force-ui';
import { useQuery } from '@tanstack/react-query';
import { getProviders } from '@api/connections';
import { z } from 'zod';
import * as Icons from '@assets/icons';
import { __, sprintf } from '@wordpress/i18n';
const getFieldDefaultValue = ( fieldDataType, fieldKey, field ) => {
	if ( field?.default !== undefined ) {
		// If field type is select, return the value of the default option
		if ( field?.input_type === 'select' ) {
			switch ( typeof field.default ) {
				// If default is a string, return it
				case 'string':
					return field.default;
				// If default is an object, return the value of the object
				case 'object':
					return field.default.value;
			}
		}
		return field.default;
	}
	// Set default values based on data type if no default value is provided
	switch ( fieldDataType ) {
		case 'int':
			return fieldKey !== 'port' ? 1 : undefined;
		case 'boolean':
			return false;
		case 'string':
		default:
			return '';
	}
};

const transformSelectField = ( field ) => {
	const fieldOptions = ! Array.isArray( field.options )
		? Object.entries( field.options )
		: field.options;

	if (
		fieldOptions &&
		! ( 'label' in fieldOptions[ 0 ] ) &&
		! ( 'value' in fieldOptions[ 0 ] )
	) {
		field.options = fieldOptions.map(
			( [ optionLabel, optionValue ] ) => ( {
				label: optionLabel,
				value: optionValue,
			} )
		);
	}

	field.getOptionLabel = ( selectedOptionValue ) => {
		const selectedOptData = field.options.find(
			( option ) => option.value === selectedOptionValue
		);
		return selectedOptData?.label;
	};

	field.combobox = field?.options?.length > 5;
	return field;
};

const prepareField = ( fieldKey, field ) => {
	const fieldDataType = field?.datatype;
	let transformedField = { ...field, name: fieldKey };

	// Transform field based on input type
	if ( field?.input_type === 'select' ) {
		transformedField = transformSelectField( transformedField );
	}

	// Set default values based on data type
	transformedField.default = getFieldDefaultValue(
		fieldDataType,
		fieldKey,
		field
	);

	// Add field transformation for integer fields
	if ( fieldDataType === 'int' ) {
		transformedField.transform = ( currentValue ) =>
			parseInt( currentValue );
	}

	// Add disabled state logic
	transformedField.disabled = ( _, stateValue ) =>
		transformedField?.depends_on?.some(
			( depItem ) => ! stateValue?.[ depItem ]
		) ?? false;

	// Add help text show more flag
	transformedField.helpShowMore = transformedField?.help_text?.length > 65;

	return transformedField;
};

export const getErrorMessage = (
	fieldLabel,
	message = __( 'is required', 'suremails' )
) => {
	if ( ! fieldLabel ) {
		return message;
	}
	return sprintf(
		// translators: %1$s is the field label and %2$s is the required message
		'%1$s %2$s',
		fieldLabel,
		message
	);
};

const generateValidationSchema = ( fields ) => {
	return z.object(
		fields.reduce( ( accSchema, field ) => {
			const baseSchema = field?.required
				? z.string().min( 1, getErrorMessage( field.label ) )
				: z.string().optional();

			switch ( field.datatype ) {
				case 'int':
					accSchema[ field.name ] = field?.required
						? z
								.number( {
									required_error: getErrorMessage(
										field.label
									),
									invalid_type_error: getErrorMessage(
										field.label
									),
								} )
								.min( 1, getErrorMessage( field.label ) )
						: z.number().optional();
					break;
				case 'boolean':
					accSchema[ field.name ] = field?.required
						? z.boolean()
						: z.boolean().optional();
					break;
				case 'email':
					accSchema[ field.name ] = baseSchema.email(
						getErrorMessage(
							'',
							__(
								'Please enter a valid email address',
								'suremails'
							)
						)
					);
					break;
				default:
					accSchema[ field.name ] = baseSchema;
					break;
			}
			return accSchema;
		}, {} )
	);
};

const prepareProviderData = ( [ key, value ] ) => {
	const fields = value.field_sequence.map( ( fieldKey ) =>
		prepareField( fieldKey, value.fields[ fieldKey ] )
	);

	const Icon = Icons[ value.icon ];
	const providerData = {
		...value,
		value: key,
		icon: <Icon className="w-6 h-6" />,
		fields,
		schema: generateValidationSchema( fields ),
	};

	if ( value?.provider_type === 'soon' ) {
		providerData.badge = (
			<Badge
				label={ __( 'Planned', 'suremails' ) }
				size="xxs"
				type="pill"
				variant="green"
			/>
		);
	}

	if ( value?.provider_type === 'not_compatible' ) {
		providerData.badge = (
			<Badge
				label={ __( 'Not Compatible', 'suremails' ) }
				size="xxs"
				type="pill"
				variant="yellow"
			/>
		);
	}

	return providerData;
};

const sortProviders = ( providers ) => {
	const sortedProviders = providers.sort( ( a, b ) => {
		// Free will come before soon providers
		if ( a.provider_type === 'free' && b.provider_type === 'soon' ) {
			return -1;
		}
		// Free will come before soon providers
		if ( a.provider_type === 'soon' && b.provider_type === 'free' ) {
			return 1;
		}
		// Sort free providers by sequence
		if ( a.provider_type === 'free' && b.provider_type === 'free' ) {
			return (
				( a?.provider_sequence ?? 0 ) - ( b?.provider_sequence ?? 0 )
			);
		}
		if ( a.provider_type === 'soon' && b.provider_type === 'soon' ) {
			return 0;
		}
		return 0;
	} );

	return sortedProviders;
};

const useProviders = () => {
	const {
		data: providers,
		isLoading,
		error,
	} = useQuery( {
		queryKey: [ 'providers' ],
		queryFn: getProviders,
		select: ( data ) => data?.data?.providers || {},
		refetchInterval: 300000,
		refetchOnMount: false,
		refetchOnWindowFocus: false,
		refetchOnReconnect: true,
	} );

	const providersList = useMemo( () => {
		if ( ! isLoading && error ) {
			return [];
		}
		if ( isLoading ) {
			return [];
		}

		const preparedProviders =
			Object.entries( providers ).map( prepareProviderData );

		return sortProviders( preparedProviders );
	}, [ isLoading, providers, error ] );

	return {
		providers: providersList,
		isLoading,
		error,
	};
};

export default useProviders;
