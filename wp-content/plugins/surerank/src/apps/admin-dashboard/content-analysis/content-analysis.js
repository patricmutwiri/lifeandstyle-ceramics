import { Container, Title, Select, Tabs, Input, Text } from '@bsf/force-ui';
import ContentAnalysisTable from '../content-analysis-table';
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { Search } from 'lucide-react';
import {
	getCurrentBreadcrumb,
	useBreadcrumb,
} from '@/apps/admin-components/dashboard-breadcrumb';
import { cn } from '@/functions/utils';
import EmptyContentGap from '../empty-content-gap';
import {
	CONTENT_PERFORMANCE_TABS,
	CONTENT_PERFORMANCE_TABS_COUNT,
} from './constants';
const ContentAnalysis = () => {
	const [ searchQuery, setSearchQuery ] = useState( '' );
	const [ statusFilter, setStatusFilter ] = useState( 'All' );
	const breadcrumbs = useBreadcrumb(); // Get breadcrumb data
	const [ activeTab, setActiveTab ] = useState( 'analysis' );

	useEffect( () => {
		window.scrollTo( { top: 0, behavior: 'smooth' } );
	}, [] );

	return (
		<Container
			className="h-full p-5 pb-8 xl:p-8 max-[1920px]:max-w-full mx-auto box-content bg-background-secondary"
			cols={ 12 }
			containerType="grid"
			gap="2xl"
		>
			<Container
				direction="row"
				className="gap-2 col-span-12 rounded-xl justify-between"
			>
				<Container.Item className="mt-1">
					{ getCurrentBreadcrumb( breadcrumbs ) }
				</Container.Item>
				<Container.Item className="w-full max-w-[18.75rem]">
					<Input
						aria-label={ __(
							'Search by URL or title',
							'surerank'
						) }
						id="search-by-url-or-title"
						prefix={ <Search /> }
						role="search"
						value={ searchQuery }
						size="sm"
						type="search"
						onChange={ ( value ) => setSearchQuery( value ) }
						placeholder={ __( 'Search', 'surerank' ) }
					/>
				</Container.Item>
			</Container>
			<Container
				direction="column"
				className="gap-2 col-span-12 p-4 bg-background-primary rounded-xl shadow-sm border-0.5 border-solid border-border-subtle"
			>
				<Container.Item className="flex items-center">
					<Container
						align="center"
						className="gap-2 w-full"
						justify="between"
					>
						<Container.Item className="pl-2">
							<Container align="center" className="gap-2">
								<Title
									title={ __(
										'Content Analysis',
										'surerank'
									) }
									size="md"
									tag="h4"
								/>
								<Text
									size={ 16 }
									weight={ 400 }
									color="secondary"
								>
									{ __( '(Last 90 days)', 'surerank' ) }
								</Text>
							</Container>
						</Container.Item>
						<Container.Item className="flex items-center gap-2 py-2">
							<div className="w-48">
								<Select
									onChange={ ( value ) =>
										setStatusFilter( value )
									}
									size="md"
									value={ statusFilter }
									className="w-[250px]"
								>
									<Select.Button
										placeholder={ __(
											'Status',
											'surerank'
										) }
									/>
									<Select.Portal id="surerank-root">
										<Select.Options>
											<Select.Option value="All">
												{ __( 'All', 'surerank' ) }
											</Select.Option>
											<Select.Option value="Top Ranked">
												{ __(
													'Top Ranked',
													'surerank'
												) }
											</Select.Option>
											<Select.Option value="On the Rise">
												{ __(
													'On the Rise',
													'surerank'
												) }
											</Select.Option>
											<Select.Option value="Low Visibility">
												{ __(
													'Low Visibility',
													'surerank'
												) }
											</Select.Option>
										</Select.Options>
									</Select.Portal>
								</Select>
							</div>
							{ CONTENT_PERFORMANCE_TABS_COUNT > 1 && (
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
													'text-brand-800 hover:text-brand-800'
											) }
											badge={ null }
											disabled={ tab.disabled }
										/>
									) ) }
								</Tabs.Group>
							) }
						</Container.Item>
					</Container>
				</Container.Item>

				<Tabs activeItem={ activeTab }>
					<Tabs.Panel slug="analysis">
						<ContentAnalysisTable
							type="full"
							searchQuery={ searchQuery }
							statusFilter={ statusFilter }
						/>
					</Tabs.Panel>
					<Tabs.Panel slug="gap">
						<EmptyContentGap rows={ 20 } overlayFixed={ true } />
					</Tabs.Panel>
				</Tabs>
			</Container>
		</Container>
	);
};

export default ContentAnalysis;
