import { useState, useEffect } from '@wordpress/element';
import { useSuspenseSelect, useDispatch } from '@wordpress/data';
import { STORE_NAME } from '@AdminStore/constants';
import { Label } from '@bsf/force-ui';
import { Info } from 'lucide-react';
import {
	renderFieldCommon,
	renderCloneableField,
	GroupFieldRenderer,
	renderHelpText,
	renderCloneableGroupField,
} from '@AdminComponents/schema-utils/render-helper';
import { noFieldsAlert } from '@AdminComponents/schema-utils/utils';
import { SeoPopupTooltip } from '@AdminComponents/tooltip';

const Properties = ( { schema, type, handleFieldUpdate, schemaId } ) => {
	const { setMetaSetting } = useDispatch( STORE_NAME );
	const [ schemaType, setSchemaType ] = useState( type );
	const [ fieldItemIds, setFieldItemIds ] = useState( {} );
	const { metaSettingsObject } = useSuspenseSelect( ( select ) => {
		const { getMetaSettings } = select( STORE_NAME );
		return {
			metaSettingsObject: getMetaSettings() || { schemas: {} },
		};
	}, [] );

	const metaSettings = {
		schemas: metaSettingsObject.schemas || {},
	};

	const schemaTypeData = surerank_globals?.schema_type_data || {};

	const [ fields, setFields ] = useState( [] );

	// Helper to process default fields
	const processFields = ( fieldsData ) => {
		return fieldsData.reduce( ( acc, field ) => {
			if ( field.type === 'Group' && field.fields ) {
				if ( field.cloneable ) {
					// For cloneable groups, create an array with one default item
					const defaultItem = {};
					field.fields.forEach( ( subField ) => {
						if ( subField.type === 'Group' && subField.fields ) {
							// Handle nested groups recursively
							// Create an object with all required fields properly initialized
							const nestedGroup = {};
							subField.fields.forEach( ( nestedField ) => {
								nestedGroup[ nestedField.id ] =
									nestedField.std !== undefined
										? nestedField.std
										: '';
							} );
							defaultItem[ subField.id ] = nestedGroup;
						} else {
							defaultItem[ subField.id ] =
								subField.std !== undefined ? subField.std : '';
						}
					} );
					acc[ field.id ] = [ defaultItem ];
				} else {
					// For non-cloneable groups, process recursively
					acc[ field.id ] = processFields( field.fields );
				}
			} else {
				acc[ field.id ] = field.std !== undefined ? field.std : '';
			}
			return acc;
		}, {} );
	};

	// Initialize schema if missing
	useEffect( () => {
		if ( schemaTypeData[ schema ] ) {
			const currentSchema = metaSettings.schemas[ schemaId ] || {};
			const existingFields = currentSchema.fields || {};
			const defaultFields = processFields( schemaTypeData[ schema ] );
			const mergedFields = { ...defaultFields, ...existingFields };

			if ( Object.keys( existingFields ).length === 1 ) {
				setMetaSetting( 'schemas', {
					...metaSettings.schemas,
					[ schemaId ]: {
						...currentSchema,
						type,
						title: schema,
						fields: mergedFields,
						show_on: currentSchema.show_on || {
							rules: [],
							specific: [],
							specificText: [],
						},
					},
				} );
			}
		}
	}, [
		schema,
		schemaId,
		schemaTypeData,
		metaSettings.schemas,
		setMetaSetting,
		type,
	] );

	// Update fields to render based on schema type
	useEffect( () => {
		if ( schemaTypeData[ schema ] ) {
			const currentSchema = metaSettings.schemas[ schemaId ] || {};
			const existingFields = currentSchema.fields || {};
			const updatedFields = ( schemaTypeData[ schema ] || [] ).filter(
				( field ) =>
					// Keep fields that either exist, are required, or have parent dependency
					existingFields[ field.id ] !== undefined ||
					field.required ||
					( field.parent && field.parent_option )
			);

			setFields( updatedFields );
		}
	}, [ schema, schemaId, schemaTypeData, metaSettings.schemas ] );

	// We can wrap your original handleUpdate logic here:
	const getFieldValue = ( fieldId, parent = null ) => {
		if ( ! parent ) {
			return metaSettings.schemas[ schemaId ]?.fields?.[ fieldId ] || '';
		}

		return (
			metaSettings.schemas[ schemaId ]?.fields?.[ parent ]?.[ fieldId ] ||
			''
		);
	};

	const onFieldChange = ( fieldId, newValue, parent = null ) => {
		handleFieldUpdate( fieldId, newValue );

		const currentSchema = metaSettings.schemas[ schemaId ] || {};
		const existingFields = currentSchema.fields || {};

		const updatedFields = { ...existingFields };

		if ( parent ) {
			const groupFields =
				schemaTypeData[ schema ].find( ( f ) => f.id === parent )
					?.fields || [];
			const groupType =
				fieldId === '@type'
					? newValue
					: getFieldValue( '@type', parent );
			const filteredGroupFields = groupFields.reduce( ( acc, field ) => {
				// Include field if it has no 'main' or matches the selected groupType
				if ( ! field.main || field.main === groupType ) {
					acc[ field.id ] =
						existingFields[ parent ]?.[ field.id ] ||
						field.std ||
						'';
				}
				return acc;
			}, {} );

			// Update only the relevant field
			filteredGroupFields[ fieldId ] = newValue;

			updatedFields[ parent ] = filteredGroupFields;
		} else {
			updatedFields[ fieldId ] = newValue;

			/**
			 * Cleanup logic for any parent field - remove dependent fields when parent options are removed
			 * Here fields will be removed if there is not any parent option selected. ( from meta settings )
			 */
			const allFields = schemaTypeData[ schema ] || [];
			const hasDependentFields = allFields.some(
				( field ) => field.parent === fieldId
			);

			if ( hasDependentFields ) {
				const oldValue = getFieldValue( fieldId ) || [];
				const newValueArray = Array.isArray( newValue ) ? newValue : [];
				const oldValueArray = Array.isArray( oldValue ) ? oldValue : [];

				const removedOptions = oldValueArray.filter(
					( option ) => ! newValueArray.includes( option )
				);

				let optionsToRemove = removedOptions;
				if (
					! Array.isArray( newValue ) &&
					! Array.isArray( oldValue ) &&
					oldValue !== newValue
				) {
					/**
					 * Single value field changed, remove dependencies of the old value
					 */
					optionsToRemove = oldValue ? [ oldValue ] : [];
				}

				if ( optionsToRemove.length > 0 ) {
					const removedOptionSet = new Set( optionsToRemove );
					allFields.forEach( ( field ) => {
						if (
							field.parent === fieldId &&
							removedOptionSet.has( field.parent_option )
						) {
							delete updatedFields[ field.id ];
						}
					} );
				}
			}
		}

		setMetaSetting( 'schemas', {
			...metaSettings.schemas,
			[ schemaId ]: {
				...currentSchema,
				fields: updatedFields,
			},
		} );

		if ( fieldId === '@type' ) {
			setSchemaType( newValue );
		}
	};

	const hiddenFields = fields.filter(
		( field ) => field.type === 'Hidden' || field.hidden
	);
	const visibleFields = fields.filter(
		( field ) => ! hiddenFields.includes( field )
	);

	if ( visibleFields.length === 0 ) {
		return noFieldsAlert;
	}

	const variableSuggestions = Object.entries(
		surerank_globals?.schema_variables || {}
	).map( ( [ value, label ] ) => ( { value, label } ) );

	// Function to render the field input based on field type
	const renderFieldInput = ( field ) => {
		// Handle cloneable Group fields (like FAQ items)
		if ( field.type === 'Group' && field.cloneable ) {
			return (
				<div className="flex flex-col w-full">
					{ renderCloneableGroupField( {
						field,
						schemaId,
						getFieldValue,
						onFieldChange,
						variableSuggestions,
						fieldItemIds,
						setFieldItemIds,
						renderHelpTextFunction: renderHelpText,
					} ) }
				</div>
			);
		}

		if ( field.type === 'Group' ) {
			return (
				<GroupFieldRenderer
					field={ field }
					schemaType={ schemaType }
					getFieldValue={ getFieldValue }
					onFieldChange={ onFieldChange }
					variableSuggestions={ variableSuggestions }
				/>
			);
		}

		if ( field.cloneable ) {
			return (
				<div className="flex items-center justify-start gap-1.5 w-full">
					{ renderCloneableField( {
						field,
						schemaType,
						getFieldValue,
						onFieldChange,
						variableSuggestions,
						renderAsGroupComponent: true,
					} ) }
				</div>
			);
		}

		return (
			<div className="flex items-center justify-start gap-1.5 w-full">
				{ renderFieldCommon( {
					field,
					schemaType,
					getFieldValue,
					onFieldChange,
					variableSuggestions,
					renderAsGroupComponent: true,
				} ) }
			</div>
		);
	};

	return (
		<div className="space-y-4 w-full">
			{ visibleFields.map( ( field ) => {
				let shouldShowField = field.required || field.show;

				/**
				 * Check if field should be shown based on parent/parent_option conditional logic
				 */
				if ( field.parent && field.parent_option ) {
					const parentValue = getFieldValue( field.parent );
					if ( Array.isArray( parentValue ) ) {
						shouldShowField = parentValue.includes(
							field.parent_option
						);
					} else {
						shouldShowField = parentValue === field.parent_option;
					}
				}

				if ( ! shouldShowField ) {
					return null;
				}

				return (
					<div key={ field.id } className="space-y-1.5 p-2 w-full">
						{ /* Label row */ }
						<div className="flex items-center justify-start gap-1.5 w-full">
							<Label
								tag="span"
								size="sm"
								className="space-x-0.5"
								required={ field.required }
							>
								<span>{ field.label }</span>
							</Label>
							{ field.tooltip && (
								<SeoPopupTooltip
									content={ field.tooltip }
									placement="top"
									arrow
									className="z-[99999]"
								>
									<Info
										className="size-4 text-icon-secondary"
										title={ field.tooltip }
									/>
								</SeoPopupTooltip>
							) }
						</div>

						{ /* Field input row */ }
						{ renderFieldInput( field ) }

						{ renderHelpText( field ) }
					</div>
				);
			} ) }
		</div>
	);
};

export default Properties;
