import {
	Container,
	Title,
	Tabs,
	Text,
	Table,
	Badge,
	ProgressBar,
	Button,
} from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import Section from './section';
import EmptyContentGap from './empty-content-gap';
import { ArrowRight } from 'lucide-react';
import { cn } from '@/functions/utils';
import {
	CONTENT_PERFORMANCE_TABS,
	CONTENT_PERFORMANCE_TABS_COUNT,
} from './content-analysis/constants';

const dummyData = [
	{
		url: 'https://example.com/blog/seo-guide',
		status: 'Top Ranked',
		statusVariant: 'green',
		clicks: 3200,
		position: 5.2,
		impressions: 25000,
		contentScore: 88,
	},
	{
		url: 'https://example.com/blog/wordpress-performance',
		status: 'On the Rise',
		statusVariant: 'yellow',
		clicks: 1800,
		position: 14.6,
		impressions: 12000,
		contentScore: 72,
	},
	{
		url: 'https://example.com/blog/content-writing-tips',
		status: 'Low Visibility',
		statusVariant: 'neutral',
		clicks: 500,
		position: 38.1,
		impressions: 4000,
		contentScore: 55,
	},
	{
		url: 'https://example.com/blog/seo-guide',
		status: 'Top Ranked',
		statusVariant: 'green',
		clicks: 3200,
		position: 5.2,
		impressions: 25000,
		contentScore: 88,
	},
	{
		url: 'https://example.com/blog/seo-guide',
		status: 'Top Ranked',
		statusVariant: 'green',
		clicks: 3200,
		position: 5.2,
		impressions: 25000,
		contentScore: 88,
	},
];

const ContentPerformanceDummy = () => {
	const [ activeTab, setActiveTab ] = useState( 'analysis' );

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
						<Text size={ 16 } weight={ 400 } color="secondary">
							{ __( '(Last 90 days)', 'surerank' ) }
						</Text>
					</Container>
					{ CONTENT_PERFORMANCE_TABS_COUNT > 1 && (
						<Container.Item className="w-fit block">
							<Tabs.Group
								activeItem={ activeTab }
								onChange={ ( { value: { slug } } ) =>
									setActiveTab( slug )
								}
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
												'text-brand-800'
										) }
									/>
								) ) }
							</Tabs.Group>
						</Container.Item>
					) }
				</Container>

				<Tabs.Panel slug="analysis">
					<Table>
						<Table.Head>
							<Table.HeadCell>
								{ __( 'Page', 'surerank' ) }
							</Table.HeadCell>
							<Table.HeadCell>
								{ __( 'Status', 'surerank' ) }
							</Table.HeadCell>
							<Table.HeadCell>
								{ __( 'Clicks', 'surerank' ) }
							</Table.HeadCell>
							<Table.HeadCell>
								{ __( 'Avg. Position', 'surerank' ) }
							</Table.HeadCell>
							<Table.HeadCell>
								{ __( 'Impressions', 'surerank' ) }
							</Table.HeadCell>
							<Table.HeadCell>
								{ __( 'Content Score', 'surerank' ) }
							</Table.HeadCell>
							<Table.HeadCell>
								<span className="sr-only">
									{ __( 'Actions', 'surerank' ) }
								</span>
							</Table.HeadCell>
						</Table.Head>
						<Table.Body>
							{ dummyData.map( ( item, index ) => (
								<Table.Row key={ index }>
									<Table.Cell>
										<Text
											target="_blank"
											className="text-xs line-clamp-1"
										>
											{ item.url }
										</Text>
									</Table.Cell>
									<Table.Cell>
										<Badge
											size="xs"
											variant={ item.statusVariant }
											label={ item.status }
											disableHover
										/>
									</Table.Cell>
									<Table.Cell>
										<span className="text-xs">
											{ item.clicks.toLocaleString() }
										</span>
									</Table.Cell>
									<Table.Cell>
										<span className="text-xs">
											{ item.position.toFixed( 2 ) }
										</span>
									</Table.Cell>
									<Table.Cell>
										<span className="text-xs">
											{ item.impressions.toLocaleString() }
										</span>
									</Table.Cell>
									<Table.Cell>
										<Container
											direction="column"
											className="gap-1.5"
										>
											<span className="text-xs">
												{ __(
													'Out of 100',
													'surerank'
												) }
											</span>
											<ProgressBar
												progress={ item.contentScore }
												className={ cn(
													'w-full max-w-32',
													'[&>div]:bg-gray-400'
												) }
											/>
										</Container>
									</Table.Cell>
									<Table.Cell>
										<Button
											size="xs"
											variant="ghost"
											icon={
												<ArrowRight className="size-4" />
											}
											iconPosition="right"
										>
											{ __( 'View', 'surerank' ) }
										</Button>
									</Table.Cell>
								</Table.Row>
							) ) }
						</Table.Body>
					</Table>
				</Tabs.Panel>

				<Tabs.Panel slug="gap">
					<EmptyContentGap />
				</Tabs.Panel>
			</Tabs>
		</Section>
	);
};

export default ContentPerformanceDummy;
