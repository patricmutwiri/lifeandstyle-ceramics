import { Container, Title, Tabs, Text } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { cn } from '@/functions/utils';
import ContentAnalysisTable from './content-analysis-table';
import Section from './section';
import { STORE_NAME } from '@/admin-store/constants';
import { useSelect } from '@wordpress/data';
import EmptyContentGap from './empty-content-gap';
import {
	CONTENT_PERFORMANCE_TABS,
	CONTENT_PERFORMANCE_TABS_COUNT,
} from './content-analysis/constants';

const ContentPerformance = () => {
	const [ activeTab, setActiveTab ] = useState( 'analysis' );

	// Get search consolee data
	const {
		contentPerformance = [],
		authenticated,
		hasSiteSelected,
	} = useSelect( ( select ) => select( STORE_NAME ).getSearchConsole() );

	// Displaying the last 90 days badge only when the user is authenticated,
	const Show_Period_Badge =
		authenticated && hasSiteSelected && contentPerformance.length > 0;

	return (
		<Section>
			<Tabs activeItem={ activeTab }>
				<Container
					justify="between"
					align="center"
					className="py-2 pl-2 pr-3 flex-wrap md:flex-nowrap"
				>
					<Container align="center" className="gap-2">
						<Title
							title={ __( 'Content Analysis', 'surerank' ) }
							tag="h4"
							size="md"
						/>

						{ Show_Period_Badge && (
							<Text size={ 16 } weight={ 400 } color="secondary">
								{ __( '(Last 90 days)', 'surerank' ) }
							</Text>
						) }
					</Container>
					{ CONTENT_PERFORMANCE_TABS_COUNT > 1 && (
						<Container.Item className="w-fit block">
							<Tabs.Group
								activeItem={ activeTab }
								onChange={ ( { value: { slug } } ) => {
									setActiveTab( slug );
								} }
								size="sm"
								variant="rounded"
							>
								{ Object.entries(
									CONTENT_PERFORMANCE_TABS
								).map( ( [ key, tab ] ) => (
									<Tabs.Tab
										key={ key }
										slug={ key }
										text={ tab.label }
										className={ cn(
											'w-fit text-nowrap space-x-1.5',
											activeTab === key &&
												'text-brand-800 hover:text-brand-800'
										) }
										badge={ null }
										disabled={ tab.disabled }
									/>
								) ) }
							</Tabs.Group>
						</Container.Item>
					) }
				</Container>
				<Tabs.Panel slug="analysis">
					<ContentAnalysisTable />
				</Tabs.Panel>
				<Tabs.Panel slug="gap">
					<EmptyContentGap />
				</Tabs.Panel>
			</Tabs>
		</Section>
	);
};

export default ContentPerformance;
