import { useEffect, useState, useMemo } from '@wordpress/element';
import PageContentWrapper from '@AdminComponents/page-content-wrapper';
import { __ } from '@wordpress/i18n';
import { Button, Container, Table, Tooltip } from '@bsf/force-ui';
import withSuspense from '@AdminComponents/hoc/with-suspense';
import { useSuspenseSelect, useDispatch } from '@wordpress/data';
import { STORE_NAME } from '@AdminStore/constants';
import EditSchema from './edit';
import { Edit, Trash } from 'lucide-react';
import {
	generateUUID,
	isSchemaTypeValid,
} from '@AdminComponents/schema-utils/utils';
import Modal from './modal';
import { SaveSettingsButton } from '@/apps/admin-components/global-save-button';
import { createLazyRoute } from '@tanstack/react-router';
import { UpgradeNotice } from '@/global/components/nudges';
import WpSchemaProNotice from '@/global/components/wp-schema-pro-notice';

// Schema categories
const SCHEMA_CATEGORIES = {
	global: { value: 'global', label: __( 'Global Level', 'surerank' ) },
	content: { value: 'content', label: __( 'Content Level', 'surerank' ) },
};

// Schema categorization mapping
const SCHEMA_CATEGORY_MAP = {
	WebSite: 'global',
	WebPage: 'global',
	Organization: 'global',
	SearchAction: 'global',
	Person: 'global',
	BreadcrumbList: 'content',
	Article: 'content',
	Product: 'content',
	Dataset: 'content',
	FAQ: 'content',
	'Fact Check': 'content',
	HowTo: 'content',
	Movie: 'content',
	'Podcast Episode': 'content',
	Book: 'content',
	Course: 'content',
	Event: 'content',
	'Job Posting': 'content',
	Recipe: 'content',
	Service: 'content',
	'Software app': 'content',
	Video: 'content',
};

const Schema = () => {
	const { metaSettings } = useSuspenseSelect( ( select ) => {
		const { getMetaSettings } = select( STORE_NAME );
		return {
			metaSettings: getMetaSettings(),
		};
	}, [] );

	const isWpSchemaProActive = surerank_globals?.wp_schema_pro_active || false;

	const { setMetaSetting, invalidateResolutionForStoreSelector } =
		useDispatch( STORE_NAME );

	const schemaData = metaSettings?.schemas || {};
	const schemaArray = Object.entries( schemaData ).map(
		( [ id, schema ] ) => ( {
			id,
			...schema,
		} )
	);

	const schemaTypeOptions = surerank_globals?.schema_type_options || {};
	const schemaTypeData = surerank_globals?.schema_type_data || {};

	// Categorize schemas using useMemo and reduce, filtering out schemas not in schemaTypeData
	const categorizedSchemas = useMemo( () => {
		return schemaArray
			.filter( ( schema ) => {
				// Only include schemas that exist in schemaTypeData
				return isSchemaTypeValid( schema?.title );
			} )
			.reduce(
				( acc, schema ) => {
					const type = schema?.type;
					const category =
						SCHEMA_CATEGORY_MAP[ type ] ||
						SCHEMA_CATEGORIES.content.value; // Default to content level

					if ( ! acc[ category ] ) {
						acc[ category ] = [];
					}
					acc[ category ].push( schema );
					return acc;
				},
				{
					[ SCHEMA_CATEGORIES.global.value ]: [],
					[ SCHEMA_CATEGORIES.content.value ]: [],
				}
			);
	}, [ schemaArray, schemaTypeData ] );

	// console.log( 'Schema Array:', schemaArray );

	// console.log( 'schemaArray', schemaArray );
	const defaultSchemasObject = surerank_globals?.default_schemas || {};
	const defaultSchemas = Object.entries( defaultSchemasObject ).map(
		( [ id, schema ] ) => ( {
			id,
			...schema,
		} )
	);

	const [ isModalOpen, setIsModalOpen ] = useState( false );
	const [ showEditSchema, setShowEditSchema ] = useState( false );
	const [ selectedSchema, setSelectedSchema ] = useState( '' );
	const [ selectedType, setSelectedType ] = useState( '' );
	const [ uniqueId, setUniqueId ] = useState( '' );
	const [ confirmDelete, setConfirmDelete ] = useState( null );

	const closeModal = () => setIsModalOpen( false );

	const handleBackToSchemas = () => {
		setShowEditSchema( false );
	};

	const handleEditSchema = ( schemaId ) => {
		const schemaToEdit = schemaData[ schemaId ];

		setUniqueId( schemaId );
		setSelectedSchema( schemaToEdit.title );
		setSelectedType( schemaToEdit.type );
		setShowEditSchema( true );
	};

	const handleAddSchema = () => {
		const schemaUniqueId = generateUUID();
		const newSchema = {
			title: selectedSchema || '',
			type: selectedType || '',
			show_on: {
				rules: [],
				specific: [],
				specificText: [],
			},
			fields: {
				'@type': selectedType || '',
			},
		};

		setMetaSetting( 'schemas', {
			...schemaData,
			[ schemaUniqueId ]: newSchema,
		} );
		setUniqueId( schemaUniqueId );
		setSelectedSchema( newSchema.title );
		setShowEditSchema( true );
	};

	const handleDeleteSchema = ( schemaId ) => {
		const updatedSchemas = { ...schemaData };
		delete updatedSchemas[ schemaId ];
		setMetaSetting( 'schemas', updatedSchemas );
		setConfirmDelete( null );
	};

	useEffect( () => {
		// Invalidate the resolver to ensure fresh data on next read
		invalidateResolutionForStoreSelector( 'getMetaSettings', [] );
	}, [] );

	// Render table for a specific category
	const renderSchemaTable = ( schemas, title ) => (
		<div
			key={ title }
			className="[&>div]:border-0 [&>div]:overflow-visible"
		>
			<h3 className="text-base font-semibold mb-4 text-text-primary">
				{ title }
			</h3>
			<Table className="w-full">
				<Table.Head className="border-0 [clip-path:inset(0_0_0_0_round_6px)]">
					<Table.HeadCell>
						{ __( 'Schema Title', 'surerank' ) }
					</Table.HeadCell>
					<Table.HeadCell>
						{ __( 'Schema Type', 'surerank' ) }
					</Table.HeadCell>
					<Table.HeadCell className="text-right">
						{ __( 'Actions', 'surerank' ) }
					</Table.HeadCell>
				</Table.Head>
				<Table.Body>
					{ schemas.length === 0 ? (
						<Table.Row>
							<Table.Cell
								colSpan={ 3 }
								className="text-center text-gray-500 py-4"
							>
								{ __(
									'No schemas found in this category.',
									'surerank'
								) }
							</Table.Cell>
						</Table.Row>
					) : (
						schemas.map( ( schema ) => (
							<Table.Row
								key={ schema.id }
								className="last:!border-b-0.5 last:border-x-0 last:border-t-0 last:border-solid last:border-border-subtle"
							>
								<Table.Cell className="p-3">
									<span className="text-sm">
										{ schema?.fields?.schema_name ||
											schema?.title }
									</span>
								</Table.Cell>
								<Table.Cell className="p-3">
									<span className="text-sm">
										{ schema?.fields?.[ '@type' ] ||
											schema?.type }
									</span>
								</Table.Cell>
								<Table.Cell className="p-3 leading-none">
									<div className="flex items-center justify-end gap-2">
										<Button
											variant="ghost"
											size="xs"
											icon={
												<Edit
													aria-label="icon"
													role="img"
												/>
											}
											className="text-text-secondary hover:text-icon-primary"
											onClick={ () =>
												handleEditSchema( schema.id )
											}
										/>
										<Tooltip
											open={ confirmDelete === schema.id }
											setOpen={ () =>
												setConfirmDelete( schema.id )
											}
											variant="light"
											placement="bottom"
											tooltipPortalId="surerank-root"
											className="p-2 border border-solid border-border-subtle [&>svg>path]:stroke-border-subtle z-[99999]"
											interactive
											arrow
											content={
												<div className="space-x-2">
													<Button
														size="xs"
														variant="ghost"
														className="focus:[box-shadow:none]"
														onClick={ () =>
															setConfirmDelete(
																null
															)
														}
													>
														{ __(
															'Cancel',
															'surerank'
														) }
													</Button>
													<Button
														size="xs"
														className="focus:[box-shadow:none] bg-button-danger hover:bg-button-danger-hover outline-button-danger hover:outline-button-danger-hover"
														onClick={ () =>
															handleDeleteSchema(
																schema.id
															)
														}
													>
														{ __(
															'Remove',
															'surerank'
														) }
													</Button>
												</div>
											}
										>
											<Button
												size="xs"
												variant="ghost"
												className="p-0 text-text-secondary inline-flex rounded-sm focus:[box-shadow:none]"
												icon={ <Trash /> }
												onClick={ () =>
													setConfirmDelete(
														schema.id
													)
												}
											/>
										</Tooltip>
									</div>
								</Table.Cell>
							</Table.Row>
						) )
					) }
				</Table.Body>
			</Table>
		</div>
	);

	const WpSchemaProNoticePage = () => (
		<PageContentWrapper
			title={ __( 'Schema', 'surerank' ) }
			description={ __(
				'Adds structured data to your content so search engines can better understand and present it. Most fields are already filled in to make setup easier and help your site show up better in search results.',
				'surerank'
			) }
		>
			<WpSchemaProNotice />
		</PageContentWrapper>
	);

	if ( isWpSchemaProActive ) {
		return <WpSchemaProNoticePage />;
	}

	return showEditSchema ? (
		<EditSchema
			schema={ selectedSchema }
			type={ selectedType }
			onBack={ handleBackToSchemas }
			setMetaSetting={ setMetaSetting }
			schemaId={ uniqueId }
			metaSettings={ metaSettings }
		/>
	) : (
		<PageContentWrapper
			title={ __( 'Schema', 'surerank' ) }
			description={ __(
				'Adds structured data to your content so search engines can better understand and present it. Most fields are already filled in to make setup easier and help your site show up better in search results.',
				'surerank'
			) }
		>
			<div className="flex flex-col items-start p-4 gap-6 bg-white shadow-sm rounded-xl">
				{ renderSchemaTable(
					categorizedSchemas[ SCHEMA_CATEGORIES.global.value ],
					SCHEMA_CATEGORIES.global.label
				) }
				{ renderSchemaTable(
					categorizedSchemas[ SCHEMA_CATEGORIES.content.value ],
					SCHEMA_CATEGORIES.content.label
				) }
				<Container className="py-2 px-0" gap="sm">
					<SaveSettingsButton />
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
				</Container>
			</div>
			<UpgradeNotice
				title={ __(
					"Didn't find the schema you're looking for?",
					'surerank'
				) }
				description={ __(
					'Upgrade to Pro to unlock FAQ, How-To, and many more powerful schema types.',
					'surerank'
				) }
				utmMedium="surerank_schema"
			/>
		</PageContentWrapper>
	);
};

export const LazyRoute = createLazyRoute( '/advanced/schema' )( {
	component: withSuspense( Schema ),
} );

export default withSuspense( Schema );
