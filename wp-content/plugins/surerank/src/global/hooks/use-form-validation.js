import { z } from 'zod';
import { useState, useCallback } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Custom hook for form validation using Zod
 * @param {Object} formState   - Current form state
 * @param {Array}  inputFields - Array of input field configurations
 * @return {Object} - Returns validation function and error states
 */
const useFormValidation = ( formState, inputFields ) => {
	const [ errors, setErrors ] = useState( {} );

	const getErrorMessage = ( issue, fieldLabel ) => {
		const requiredError = sprintf(
			// translators: %s is replaced with the field label
			__( '%s is required', 'surerank' ),
			fieldLabel
		);

		const mustBeError = sprintf(
			// translators: %1$s is replaced with the field label, %2$s is replaced with the expected type.
			__( '%1$s must be a %2$s', 'surerank' ),
			fieldLabel,
			issue.expected
		);

		return issue.input === undefined ? requiredError : mustBeError;
	};

	// Create dynamic schema based on input fields
	const createValidationSchema = useCallback( () => {
		const schema = {};

		inputFields.forEach( ( field ) => {
			let fieldSchema;

			const requiredError = sprintf(
				// translators: %s is replaced with the field label
				__( '%s is required', 'surerank' ),
				field.label
			);
			// Add validation based on field type
			switch ( field.type ) {
				case 'text':
				case 'textarea':
					fieldSchema = z.string( {
						error: ( issue ) =>
							getErrorMessage( issue, field.label ),
					} );
					break;
				case 'email':
					fieldSchema = z
						.string( {
							required_error: requiredError,
						} )
						.email(
							__(
								'Please enter a valid email address',
								'surerank'
							)
						);
					break;
				case 'number':
					fieldSchema = z.number( {
						error: ( issue ) =>
							getErrorMessage( issue, field.label ),
					} );
					break;
				case 'url':
					fieldSchema = z
						.string( {
							required_error: requiredError,
						} )
						.url( __( 'Please enter a valid URL', 'surerank' ) );
					break;
				case 'file':
					fieldSchema = z.instanceof( File );
					break;
				case 'checkbox':
					fieldSchema = z.boolean();
					break;
				default:
					fieldSchema = z.string( {
						error: ( issue ) =>
							getErrorMessage( issue, field.label ),
					} );
					break;
			}

			// Add required validation
			if ( field.required ) {
				if ( field.type === 'checkbox' ) {
					fieldSchema = fieldSchema.refine(
						( value ) => value === true,
						__( 'This field is required', 'surerank' )
					);
				} else {
					fieldSchema = fieldSchema.min(
						1,
						sprintf(
							// translators: %s is replaced with the field label
							__( '%s is required', 'surerank' ),
							field.label
						)
					);
				}
			}

			schema[ field.name ] = field.required
				? fieldSchema
				: fieldSchema.optional();
		} );

		return z.object( schema );
	}, [ inputFields ] );

	const validate = useCallback( () => {
		const schema = createValidationSchema();

		try {
			schema.parse( formState );
			setErrors( {} );
			return true;
		} catch ( validationErrors ) {
			const formattedErrors = {};

			// In zod v4 errors are in the 'issues' property, v3 errors are in the 'errors' property.
			const errorList =
				validationErrors?.issues || validationErrors?.errors || [];

			if ( Array.isArray( errorList ) && errorList.length > 0 ) {
				errorList.forEach( ( error ) => {
					const fieldName = error.path[ 0 ];
					if ( fieldName ) {
						formattedErrors[ fieldName ] = error.message;
					}
				} );

				const firstErrorField = errorList[ 0 ]?.path?.[ 0 ];
				if ( firstErrorField ) {
					const element = document.querySelector(
						`[name="${ firstErrorField }"]`
					);
					element?.focus();
				}
			}

			setErrors( formattedErrors );
			return false;
		}
	}, [ formState, createValidationSchema ] );

	/**
	 * Clear error for a specific field
	 * @param {string} fieldName - Name of the field to clear error for
	 *
	 * @return {void}
	 */
	const clearFieldError = ( fieldName ) => {
		setErrors( ( prevErrors ) => {
			if ( ! prevErrors[ fieldName ] ) {
				return prevErrors;
			}

			const newErrors = { ...prevErrors };
			newErrors[ fieldName ] = '';
			return newErrors;
		} );
	};

	return {
		errors,
		validate,
		clearFieldError,
		isValid: Object.keys( errors ).length === 0,
	};
};

export default useFormValidation;
