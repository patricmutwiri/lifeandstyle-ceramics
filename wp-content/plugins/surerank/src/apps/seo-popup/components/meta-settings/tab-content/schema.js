import { __ } from '@wordpress/i18n';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { Label, Alert, Accordion, Text } from '@bsf/force-ui';
import { Info, Trash } from 'lucide-react';
import WpSchemaProNotice from '@/global/components/wp-schema-pro-notice';
import { cn } from '@Functions/utils';
import {
	renderFieldCommon,
	renderCloneableField,
	renderCloneableGroupField,
} from '@AdminComponents/schema-utils/render-helper';
import {
	noFieldsAlert,
	generateUUID,
	isSchemaTypeValid,
} from '@AdminComponents/schema-utils/utils';
import Modal from '@/apps/admin-general/schema/modal';
import { SeoPopupTooltip } from '@AdminComponents/tooltip';
import ConfirmationPopover from '@/global/components/confirmation-popover';

const SchemaTab = ( { postMetaData, globalDefaults, updatePostMetaData } ) => {
	const closeModal = () => setIsModalOpen( false );
	const [ isModalOpen, setIsModalOpen ] = useState( false );
	const [ selectedSchema, setSelectedSchema ] = useState( '' );
	const [ selectedType, setSelectedType ] = useState( '' );
	const [ expandedSchemaId, setExpandedSchemaId ] = useState( null );
	const [ fieldItemIds, setFieldItemIds ] = useState( {} );
	const isWpSchemaProActive = surerank_globals?.wp_schema_pro_active || false;

	const defaultSchemasObject = surerank_globals?.default_schemas || {};
	const defaultSchemas = Object.entries( defaultSchemasObject ).map(
		( [ id, schema ] ) => ( {
			id,
			...schema,
		} )
	);

	const globalSchemas = globalDefaults.schemas || {};
	const schemas = postMetaData.schemas
		? postMetaData.schemas
		: globalSchemas || {};

	const validSchemas = useMemo(
		() =>
			Object.entries( schemas ).filter( ( [ , schema ] ) => {
				return isSchemaTypeValid( schema?.title );
			} ),
		[ schemas ]
	);
	const schemaTypeData = surerank_globals?.schema_type_data || {};
	const schemaTypeOptions = surerank_globals?.schema_type_options || {};

	const variableSuggestions = useMemo(
		() =>
			Object.entries( surerank_globals?.schema_variables || {} ).map(
				( [ value, label ] ) => ( {
					value,
					label,
				} )
			),
		[]
	);

	const processFields = ( fieldsData ) =>
		fieldsData.reduce( ( acc, field ) => {
			if ( field.type === 'Group' && field.fields ) {
				if ( field.cloneable ) {
					// For cloneable groups, create an array with one default item
					const defaultItem = {};
					field.fields.forEach( ( subField ) => {
						if ( subField.type === 'Group' && subField.fields ) {
							// Handle nested groups recursively
							defaultItem[ subField.id ] = processFields(
								subField.fields
							);
						} else {
							defaultItem[ subField.id ] =
								subField.std !== undefined ? subField.std : '';
						}
					} );
					acc[ field.id ] = [ defaultItem ];
				} else {
					// For non-cloneable groups, create a nested object structure
					const groupFields = {};
					field.fields.forEach( ( subField ) => {
						if ( subField.type === 'Group' && subField.fields ) {
							// Handle nested groups recursively
							groupFields[ subField.id ] = processFields(
								subField.fields
							);
						} else {
							groupFields[ subField.id ] =
								subField.std !== undefined ? subField.std : '';
						}
					} );
					acc[ field.id ] = groupFields;
				}
			} else if ( field.std !== undefined ) {
				acc[ field.id ] = field.std;
			}
			return acc;
		}, {} );

	const cleanSchemas = ( schemasData ) => {
		const cleanedSchemas = {};
		Object.entries( schemasData ).forEach( ( [ schemaId, schema ] ) => {
			const { show_on, not_show_on, ...rest } = schema; // we removed show_on and not_show_on, as we are using post meta data for schema now, cause we edited now.
			const cleanedSchema = {
				...rest,
				parent: true,
			};
			cleanedSchemas[ schemaId ] = cleanedSchema;
		} );
		return cleanedSchemas;
	};

	useEffect( () => {
		const updatedSchemas = {};
		Object.entries( schemas ).forEach( ( [ schemaId, schema ] ) => {
			const schemaFields = schemaTypeData[ schema.title ] || [];
			const existingFields = schema.fields || {};
			const defaultFields = processFields( schemaFields );
			const mergedFields = { ...existingFields, ...defaultFields };

			if ( Object.keys( existingFields ).length === 0 ) {
				mergedFields[ '@type' ] = schema?.type || '';
				updatedSchemas[ schemaId ] = {
					...schema,
					fields: mergedFields,
				};
			}
		} );

		if ( Object.keys( updatedSchemas ).length > 0 ) {
			const cleanedSchemas = cleanSchemas( {
				...schemas,
				...updatedSchemas,
			} );

			if (
				JSON.stringify( cleanedSchemas ) !== JSON.stringify( schemas )
			) {
				updatePostMetaData( {
					schemas: cleanedSchemas,
				} );
			}
		}
	}, [ schemas, schemaTypeData, updatePostMetaData ] );

	const handleDeleteSchema = ( schemaId ) => {
		const updatedSchemas = { ...schemas };
		delete updatedSchemas[ schemaId ];

		const cleanedSchemas = cleanSchemas( updatedSchemas );
		updatePostMetaData( {
			schemas: cleanedSchemas,
		} );
	};

	const handleAddSchema = () => {
		const schemaUniqueId = generateUUID();
		const newSchema = {
			title: selectedSchema,
			type: selectedType,
			show_on: {
				rules: [],
				specific: [],
				specificText: [],
			},
			fields: {},
		};

		const updatedSchemas = { ...schemas, [ schemaUniqueId ]: newSchema };
		const cleanedSchemas = cleanSchemas( updatedSchemas );

		updatePostMetaData( {
			schemas: cleanedSchemas,
		} );

		setExpandedSchemaId( schemaUniqueId );
		setIsModalOpen( false );
		setSelectedSchema( '' );
		setSelectedType( '' );
	};

	const handleFieldUpdate = ( schemaId, fieldKey, newValue ) => {
		const updatedSchemas = {
			...schemas,
			[ schemaId ]: {
				...schemas[ schemaId ],
				fields: {
					...schemas[ schemaId ].fields,
					[ fieldKey ]: newValue,
				},
			},
		};
		const cleanedSchemas = cleanSchemas( updatedSchemas );
		updatePostMetaData( {
			schemas: cleanedSchemas,
		} );
	};

	const getFieldValue = ( schemaId, fieldId ) => {
		return schemas[ schemaId ]?.fields?.[ fieldId ] || '';
	};

	const onFieldChange = ( schemaId, fieldId, newValue ) => {
		handleFieldUpdate( schemaId, fieldId, newValue );
		if ( fieldId === '@type' ) {
			const updatedSchemas = { ...schemas };
			updatedSchemas[ schemaId ].type = updatedSchemas[ schemaId ]?.title || ''; // added for backward compatibility.
			updatedSchemas[ schemaId ].fields[ '@type' ] = newValue;
			updatePostMetaData( {
				schemas: updatedSchemas,
			} );
		}
	};

	const renderSchemaFields = ( schemaId ) => {
		const schemaTitle = schemas[ schemaId ]?.title;

		// Check if schema type is valid first
		if ( ! isSchemaTypeValid( schemaTitle ) ) {
			return noFieldsAlert;
		}

		const schemaFields = schemaTypeData[ schemaTitle ] || [];

		if (
			schemaFields.length === 0 ||
			schemaFields.every(
				( field ) =>
					field.type === 'Hidden' || field.id === 'schema_name'
			)
		) {
			return noFieldsAlert;
		}

		return schemaFields.map( ( field ) => {
			if ( field.type === 'Hidden' || field.type === 'SchemaDocs' ) {
				return null;
			}

			if ( ! field.show && ! field.required ) {
				return null;
			}

			return (
				<div
					key={ field.id }
					className="flex flex-col items-start justify-start gap-1.5 w-full p-1"
				>
					{ /* Label + tooltip */ }
					<div className="flex items-center gap-1.5">
						<Label tag="span" size="sm" required={ field.required }>
							{ field.label }
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

					{ /* Field render */ }
					{ ( () => {
						if ( field.type === 'Group' && field.cloneable ) {
							return (
								<div className="flex flex-col w-full">
									{ renderCloneableGroupField( {
										field,
										schemaId,
										getFieldValue: ( fieldId ) =>
											getFieldValue( schemaId, fieldId ),
										onFieldChange: ( fieldId, newVal ) =>
											onFieldChange(
												schemaId,
												fieldId,
												newVal
											),
										variableSuggestions,
										fieldItemIds,
										setFieldItemIds,
									} ) }
								</div>
							);
						}

						if ( field.type === 'Group' && ! field.cloneable ) {
							return (
								<div className="flex flex-col w-full space-y-3 border border-border-subtle rounded-lg p-3">
									{ field.fields.map( ( subField ) => {
										if ( subField.type === 'Hidden' ) {
											return null;
										}

										return (
											<div
												key={ subField.id }
												className="flex flex-col items-start justify-start gap-1.5 w-full"
											>
												<div className="flex items-center gap-1.5">
													<Label
														tag="span"
														size="sm"
														required={
															subField.required
														}
													>
														{ subField.label }
													</Label>
													{ subField.tooltip && (
														<SeoPopupTooltip
															content={
																subField.tooltip
															}
															placement="top"
															arrow
															className="z-[99999]"
														>
															<Info
																className="size-4 text-icon-secondary"
																title={
																	subField.tooltip
																}
															/>
														</SeoPopupTooltip>
													) }
												</div>
												<div className="flex items-center gap-1.5 w-full">
													{ renderFieldCommon( {
														field: subField,
														schemaType:
															schemas[ schemaId ]
																.type,
														getFieldValue: (
															fldId
														) => {
															const groupValue =
																getFieldValue(
																	schemaId,
																	field.id
																) || {};
															return (
																groupValue[
																	fldId
																] ||
																subField.std ||
																''
															);
														},
														onFieldChange: (
															fldId,
															newVal
														) => {
															const currentGroupValue =
																getFieldValue(
																	schemaId,
																	field.id
																) || {};
															const updatedGroupValue =
																{
																	...currentGroupValue,
																	[ fldId ]:
																		newVal,
																};
															onFieldChange(
																schemaId,
																field.id,
																updatedGroupValue
															);
														},
														variableSuggestions,
														renderAsGroupComponent: false,
													} ) }
												</div>
											</div>
										);
									} ) }
								</div>
							);
						}

						if ( field.cloneable ) {
							return (
								<div className="flex items-center gap-1.5 w-full">
									{ renderCloneableField( {
										field,
										schemaType: schemas[ schemaId ].type,
										getFieldValue: ( fldId ) =>
											getFieldValue( schemaId, fldId ),
										onFieldChange: ( fldId, newVal ) =>
											onFieldChange(
												schemaId,
												fldId,
												newVal
											),
										variableSuggestions,
										renderAsGroupComponent: true,
									} ) }
								</div>
							);
						}

						return (
							<div className="flex items-center gap-1.5 w-full">
								{ renderFieldCommon( {
									field,
									schemaType: schemas[ schemaId ].type,
									getFieldValue: ( fldId ) =>
										getFieldValue( schemaId, fldId ),
									onFieldChange: ( fldId, newVal ) =>
										onFieldChange(
											schemaId,
											fldId,
											newVal
										),
									variableSuggestions,
									renderAsGroupComponent: true,
								} ) }
							</div>
						);
					} )() }
				</div>
			);
		} );
	};

	if ( isWpSchemaProActive ) {
		return <WpSchemaProNotice />;
	}

	return (
		<div className="pt-2 gap-2">
			<div className="flex items-center mb-4.5 -mt-0.5">
				<Text size={ 14 } weight={ 500 } color="label">
					{ __( 'Schemas in Use', 'surerank' ) }
				</Text>
			</div>
			<div
				className={ cn(
					'w-full bg-background-secondary flex flex-col items-center justify-center rounded p-1'
				) }
			>
				{ validSchemas.length > 0 ? (
					<Accordion
						type="simple"
						iconType="arrow"
						className="w-full space-y-1"
						autoClose={ false }
					>
						{ validSchemas.map( ( [ schemaId, schema ] ) => {
							return (
								<Accordion.Item
									key={ schemaId }
									value={ schemaId }
									className="bg-background-primary rounded-md"
									defaultExpanded={
										schemaId === expandedSchemaId
									}
								>
									<Accordion.Trigger
										iconType="arrow"
										className="hover:bg-background-primary rounded-md flex justify-between items-center [&>div]:w-full p-2 gap-2 [&>svg]:size-4 cursor-pointer"
									>
										<span className="text-base font-normal text-text-primary leading-6 ml-1">
											{ schema.title }
										</span>
										<ConfirmationPopover
											onConfirm={ () =>
												handleDeleteSchema( schemaId )
											}
											placement="bottom"
											offset={ {
												mainAxis: 8,
												crossAxis: -28,
											} }
										>
											<div
												className="inline-flex ml-auto"
												role="button"
												tabIndex={ 0 }
											>
												<Trash className="size-3.5 text-icon-secondary cursor-pointer" />
											</div>
										</ConfirmationPopover>
									</Accordion.Trigger>
									<Accordion.Content>
										<div className="mt-3 space-y-4">
											{ renderSchemaFields( schemaId ) }
										</div>
									</Accordion.Content>
								</Accordion.Item>
							);
						} ) }
					</Accordion>
				) : (
					<Alert
						className="w-full shadow-none"
						content={ __( 'No schemas configured.', 'surerank' ) }
						variant="info"
					/>
				) }
			</div>
			<div className="w-full mt-6 rounded">
				<Modal
					selectedSchema={ selectedSchema }
					setSelectedSchema={ setSelectedSchema }
					selectedType={ selectedType }
					setSelectedType={ setSelectedType }
					schemaTypeOptions={ schemaTypeOptions }
					defaultSchemas={ defaultSchemas }
					handleAddSchema={ handleAddSchema }
					isModalOpen={ isModalOpen }
					closeModal={ closeModal }
				/>
			</div>
		</div>
	);
};

export default SchemaTab;
