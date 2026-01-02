import { Button, Dialog, Select, Container, Text } from '@bsf/force-ui';
import { Plus, X } from 'lucide-react';
import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import { isProActive } from '@/functions/nudges';
import { UpgradeButton } from '@/global/components/nudges';

const Modal = ( {
	selectedSchema,
	setSelectedSchema,
	selectedType,
	setSelectedType,
	schemaTypeOptions,
	defaultSchemas,
	handleAddSchema,
} ) => {
	const proOnlySchemas = [
		'ClaimReview',
		'Book',
		'Course',
		'Dataset',
		'Event',
		'FAQPage',
		'HowTo',
		'JobPosting',
		'Movie',
		'Service',
		'PodcastEpisode',
		'Recipe',
		'VideoObject',
		'SoftwareApplication',
	];

	const isPro = isProActive();

	const allSchemas = isPro
		? defaultSchemas
		: [
				...defaultSchemas,

				{
					title: proOnlySchemas.join( ' ' ),
					isPro: true,
					isProUpgradeNudge: true,
				},
		  ];

	const renderSchemaTypeOptions = () => {
		const options = schemaTypeOptions[ selectedSchema ];
		const isGrouped = Object.values( options ).every(
			( item ) => item.label && item.options
		);

		if ( isGrouped ) {
			return Object.values( options ).map( ( group, index ) => (
				<Select.OptionGroup key={ index } label={ group.label }>
					{ Object.entries( group.options ).map( ( [ key ] ) => (
						<Select.Option key={ key } value={ key }>
							{ key }
						</Select.Option>
					) ) }
				</Select.OptionGroup>
			) );
		}

		return Object.entries( options || {} ).map( ( [ key, value ] ) => (
			<Select.Option key={ key } value={ key }>
				{ value }
			</Select.Option>
		) );
	};

	useEffect( () => {
		if ( selectedType === '' ) {
			setSelectedType( selectedSchema );
		}
	}, [ selectedSchema, selectedType, setSelectedType ] );

	return (
		<Dialog
			trigger={
				<Button
					icon={ <Plus aria-label="icon" role="img" /> }
					iconPosition="left"
					size="md"
					variant="outline"
				>
					{ __( 'Add Schema', 'surerank' ) }
				</Button>
			}
		>
			<Dialog.Panel className="gap-2 p-3">
				{ ( { close } ) => (
					<>
						<Dialog.Header className="p-2">
							<div className="flex items-center justify-between">
								<Dialog.Title>
									{ __( 'Add Schema', 'surerank' ) }
								</Dialog.Title>
								<Button
									className="text-text-secondary [&_svg]:text-text-secondary [&_svg]:size-4 p-0"
									variant="ghost"
									onClick={ close }
									icon={ <X aria-label="icon" role="img" /> }
								/>
							</div>
						</Dialog.Header>
						<Dialog.Body className="p-2">
							<Container
								align="start"
								containerType="flex"
								direction="column"
								gap="xs"
								justify="start"
							>
								<Container.Item className="w-full pb-1">
									<label className="text-base block font-medium text-field-label">
										{ __( 'Choose Schema', 'surerank' ) }
									</label>
								</Container.Item>
								<Container.Item className="w-full">
									<Select
										combobox
										size="md"
										value={ selectedSchema }
										onChange={ ( value ) => {
											setSelectedSchema( value );
											const schemaTypeOptionsAvailable =
												schemaTypeOptions[ value ] &&
												Object.keys(
													schemaTypeOptions[ value ]
												).length;
											if (
												! schemaTypeOptionsAvailable
											) {
												setSelectedType( value );
											} else {
												setSelectedType( '' );
											}
										} }
									>
										<Select.Button />
										<Select.Options>
											{ allSchemas.map(
												( schema, index ) => {
													if ( schema.isPro ) {
														return (
															<div
																key={ index }
																className="px-2 py-1.5 cursor-default"
																role="presentation"
															>
																<div className="flex items-center justify-between gap-2 p-3 bg-brand-background-50 rounded-lg">
																	<div className="flex-1">
																		<Text
																			size={
																				14
																			}
																			lineHeight={
																				20
																			}
																			color="secondary"
																			weight={
																				500
																			}
																		>
																			{ __(
																				'Looking for more schemas?',
																				'surerank'
																			) }
																		</Text>
																	</div>
																	<UpgradeButton
																		label={ __(
																			'Upgrade now',
																			'surerank'
																		) }
																		variant="link"
																		size="md"
																		showIcon={
																			true
																		}
																		className="ml-2"
																		utmMedium="schema_dropdown"
																	/>
																</div>
																{ /* Hidden text for search functionality */ }
																<span className="sr-only">
																	{
																		schema.title
																	}
																</span>
															</div>
														);
													}

													return (
														<Select.Option
															key={ index }
															value={
																schema.title
															}
														>
															{ schema.title }
														</Select.Option>
													);
												}
											) }
										</Select.Options>
									</Select>
								</Container.Item>
							</Container>
							{ selectedSchema &&
								schemaTypeOptions[ selectedSchema ] && (
									<div className="flex flex-col mt-3 gap-1.5">
										<label className="text-base block font-medium text-field-label">
											{ __( 'Schema Type', 'surerank' ) }
										</label>
										<Select
											combobox
											size="md"
											value={
												selectedType || selectedSchema
											}
											onChange={ ( value ) =>
												setSelectedType( value )
											}
										>
											<Select.Button
												render={ ( selectedValue ) =>
													selectedValue
												}
												type="button"
											/>
											<Select.Options>
												{ renderSchemaTypeOptions() }
											</Select.Options>
										</Select>
									</div>
								) }
						</Dialog.Body>
						<Dialog.Footer className="p-2">
							<Button variant="outline" onClick={ close }>
								{ __( 'Cancel', 'surerank' ) }
							</Button>
							<Button
								variant="primary"
								onClick={ () => {
									handleAddSchema();
									close();
								} }
								disabled={ ! selectedSchema }
							>
								{ __( 'Add Schema', 'surerank' ) }
							</Button>
						</Dialog.Footer>
						<Dialog.Backdrop className="bg-misc-overlay" />
					</>
				) }
			</Dialog.Panel>
		</Dialog>
	);
};

export default Modal;
