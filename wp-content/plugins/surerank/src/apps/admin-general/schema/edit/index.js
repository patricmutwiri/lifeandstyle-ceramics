import Properties from './properties';
import DisplayConditions from './display-conditions';
import PageContentWrapper from '@AdminComponents/page-content-wrapper';
import { __ } from '@wordpress/i18n';
import { Tabs, Container, Button } from '@bsf/force-ui';
import { useState, useCallback, memo } from '@wordpress/element';
import { motion } from 'framer-motion';
import { ArrowLeft } from 'lucide-react';
import { SaveSettingsButton } from '@/apps/admin-components/global-save-button';

const EditSchema = ( {
	schema,
	type,
	onBack,
	setMetaSetting,
	schemaId,
	metaSettings,
} ) => {
	const [ activeTab, setActiveTab ] = useState( 'properties' );

	const handleFieldUpdate = ( fieldId, fieldValue ) => {
		const updatedSchema = {
			...metaSettings.schemas[ schemaId ],
			fields: {
				...metaSettings.schemas[ schemaId ]?.fields,
				[ fieldId ]: fieldValue,
			},
		};

		const updatedSchemas = {
			...metaSettings.schemas,
			[ schemaId ]: updatedSchema, // Update the specific schema
		};
		setMetaSetting( 'schemas', updatedSchemas ); // Update the entire schemas object
	};

	const renderTabComponent = useCallback( () => {
		switch ( activeTab ) {
			case 'properties':
				return (
					<Properties
						settings={ metaSettings }
						schema={ schema }
						type={ type || schema }
						handleFieldUpdate={ handleFieldUpdate }
						schemaId={ schemaId }
					/>
				);
			case 'display-conditions':
				return (
					<DisplayConditions
						settings={ metaSettings }
						handleFieldUpdate={ handleFieldUpdate }
						schemaId={ schemaId }
						schemaType={ type }
					/>
				);
			default:
				return null;
		}
	}, [ activeTab, metaSettings ] );

	const handleTabChange = useCallback( ( { value } ) => {
		setActiveTab( value.slug );
	}, [] );

	const ReturnButton = () => (
		<Button
			onClick={ onBack }
			variant="outline"
			icon={ <ArrowLeft className="size-4" /> }
			iconPosition="left"
		>
			{ __( 'Back', 'surerank' ) }
		</Button>
	);

	return (
		<PageContentWrapper
			title={ schema }
			secondaryButton={ <ReturnButton /> }
		>
			<div className="flex flex-col items-start p-4 gap-2 bg-white shadow-sm rounded-[12px] order-1 flex-none flex-grow-0">
				<Container direction="column" className="w-full">
					<Container.Item className="md:w-full lg:w-full p-2 pt-0">
						<Tabs.Group
							activeItem={ activeTab }
							variant="rounded"
							size="sm"
							width="full"
							onChange={ handleTabChange }
							className="justify-around font-medium"
						>
							<Tabs.Tab
								className="text-field-label max-w-none font-medium"
								slug="properties"
								text={ __( 'Properties', 'surerank' ) }
							/>
							<Tabs.Tab
								className="text-field-label max-w-none font-medium"
								slug="display-conditions"
								text={ __( 'Display Conditions', 'surerank' ) }
							/>
						</Tabs.Group>
					</Container.Item>
				</Container>
				<motion.div
					key={ activeTab }
					initial={ { opacity: 0, x: 0 } }
					animate={ { opacity: 1, y: 0 } }
					exit={ { opacity: 0, x: -10 } }
					transition={ { duration: 0.3 } }
					className="w-full"
				>
					{ renderTabComponent() }
				</motion.div>
				<Container className="py-2 px-0" gap="sm">
					<SaveSettingsButton />
				</Container>
			</div>
		</PageContentWrapper>
	);
};

export default memo( EditSchema );
