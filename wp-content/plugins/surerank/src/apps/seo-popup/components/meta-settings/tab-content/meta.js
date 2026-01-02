import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import { Accordion } from '@bsf/force-ui';
import { useDispatch, useSelect } from '@wordpress/data';
import GeneralTab from './meta/general-tab';
import SocialTab from './meta/social-tab';
import AdvancedTab from './meta/advanced-tab';
import SchemaTab from './schema';
import { ENABLE_SCHEMAS } from '@/global/constants';
import { STORE_NAME } from '@Store/constants';

const MetaTab = ( { postMetaData, updatePostMetaData, globalDefaults } ) => {
	const { currentAccordion } = useSelect( ( select ) =>
		select( STORE_NAME ).getAppSettings()
	);
	const { updateAppSettings } = useDispatch( STORE_NAME );

	const activeAccordion = currentAccordion || 'general';

	useEffect( () => {
		if ( ! currentAccordion ) {
			updateAppSettings( { currentAccordion: 'general' } );
		}
	}, [] );

	const handleAccordionClick = ( value ) => {
		updateAppSettings( { currentAccordion: value } );
	};

	return (
		<Accordion
			autoClose={ true }
			defaultValue={ activeAccordion }
			type="boxed"
			className="flex flex-col"
		>
			<Accordion.Item
				value="general"
				className="bg-background-primary border rounded-md overflow-hidden"
			>
				<Accordion.Trigger
					className="text-base [&>svg]:size-4 px-4 py-4 hover:bg-background-secondary transition-colors"
					onClick={ () => handleAccordionClick( 'general' ) }
					aria-label={ __(
						'Toggle general SEO settings',
						'surerank'
					) }
				>
					{ __( 'General', 'surerank' ) }
				</Accordion.Trigger>
				<Accordion.Content>
					<GeneralTab
						postMetaData={ postMetaData }
						updatePostMetaData={ updatePostMetaData }
						globalDefaults={ globalDefaults }
					/>
				</Accordion.Content>
			</Accordion.Item>
			<Accordion.Item
				value="social"
				className="bg-background-primary border rounded-md overflow-hidden"
			>
				<Accordion.Trigger
					className="text-base [&>svg]:size-4 px-4 py-4 hover:bg-background-secondary transition-colors"
					onClick={ () => handleAccordionClick( 'social' ) }
					aria-label={ __(
						'Toggle social media settings',
						'surerank'
					) }
				>
					{ __( 'Social', 'surerank' ) }
				</Accordion.Trigger>
				<Accordion.Content>
					<SocialTab
						postMetaData={ postMetaData }
						updatePostMetaData={ updatePostMetaData }
						globalDefaults={ globalDefaults }
					/>
				</Accordion.Content>
			</Accordion.Item>
			<Accordion.Item
				value="advanced"
				className="bg-background-primary border rounded-md overflow-hidden"
			>
				<Accordion.Trigger
					className="text-base [&>svg]:size-4 px-4 py-4 hover:bg-background-secondary transition-colors"
					onClick={ () => handleAccordionClick( 'advanced' ) }
					aria-label={ __(
						'Toggle advanced SEO settings',
						'surerank'
					) }
				>
					{ __( 'Advanced', 'surerank' ) }
				</Accordion.Trigger>
				<Accordion.Content>
					<AdvancedTab
						postMetaData={ postMetaData }
						updatePostMetaData={ updatePostMetaData }
						globalDefaults={ globalDefaults }
					/>
				</Accordion.Content>
			</Accordion.Item>
			{ ENABLE_SCHEMAS && (
				<Accordion.Item
					value="schema"
					className="bg-background-primary border rounded-md overflow-hidden"
				>
					<Accordion.Trigger
						className="text-base [&>svg]:size-4 px-4 py-4 hover:bg-background-secondary transition-colors"
						onClick={ () => handleAccordionClick( 'schema' ) }
						aria-label={ __(
							'Toggle schema markup settings',
							'surerank'
						) }
					>
						{ __( 'Schema', 'surerank' ) }
					</Accordion.Trigger>
					<Accordion.Content>
						<SchemaTab
							postMetaData={ postMetaData }
							updatePostMetaData={ updatePostMetaData }
							globalDefaults={ globalDefaults }
						/>
					</Accordion.Content>
				</Accordion.Item>
			) }
		</Accordion>
	);
};

export default MetaTab;
