import { STORE_NAME } from '@/admin-store/constants';
import {
	Button,
	Text,
	Table,
	Badge,
	ProgressBar,
	Container,
} from '@bsf/force-ui';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import ContentPerformanceEmptyState from './content-performance-empty-state';

const dummyRows = [
	{
		url: 'https://surerank.com/',
		status: 'Low Visibility',
		statusVariant: 'neutral',
		clicks: '30,967',
		position: '60.79',
		impressions: '1,870,391',
		contentScore: 50,
	},
	{
		url: 'https://surerank.com/docs/install-premium-starter-templates/',
		status: 'Top Ranked',
		statusVariant: 'green',
		clicks: '1,278',
		position: '8.06',
		impressions: '41,176',
		contentScore: 84,
	},
	{
		url: 'https://surerank.com/docs/fix-starter-template-importing-…',
		status: 'Top Ranked',
		statusVariant: 'green',
		clicks: '1,122',
		position: '9.49',
		impressions: '17,814',
		contentScore: 80,
	},
	{
		url: 'https://surerank.com/docs/getting-started-starter-templates/',
		status: 'Low Visibility',
		statusVariant: 'neutral',
		clicks: '855',
		position: '47.80',
		impressions: '236,183',
		contentScore: 45,
	},
	{
		url: 'https://surerank.com/docs/install-starter-templates/',
		status: 'Top Ranked',
		statusVariant: 'green',
		clicks: '469',
		position: '6.77',
		impressions: '65,364',
		contentScore: 76,
	},
];

const getExtendedRows = ( rowsCount = 5 ) => {
	const times = Math.ceil( rowsCount / dummyRows.length );
	return Array( times ).fill( dummyRows ).flat().slice( 0, rowsCount );
};

const EmptyContentGap = ( { rows = 5, overlayFixed = false } ) => {
	const { hasSiteSelected } = useSelect( ( select ) =>
		select( STORE_NAME ).getSearchConsole()
	);

	if ( ! hasSiteSelected ) {
		return <ContentPerformanceEmptyState />;
	}

	const data = rows > dummyRows.length ? getExtendedRows( rows ) : dummyRows;
	const overlayHeightClass = overlayFixed ? 'h-[600px]' : 'h-[400px]';

	return (
		<div
			className={ `relative w-full px-0 py-8 ${
				rows === 5 ? 'min-h-[400px]' : 'min-h-[800px]'
			}` }
		>
			<div
				className={ `absolute top-0 left-1/2 transform -translate-x-1/2 z-10 flex flex-col items-center justify-center text-center px-4 max-w-2xl pointer-events-none ${ overlayHeightClass }` }
			>
				<img
					src={ `${ surerank_globals.admin_assets_url }/images/content-gap-info.svg` }
					alt={ __( 'Content Gap Illustration', 'surerank' ) }
					className="w-25 h-25 mb-3"
				/>
				<Text tag="h2" size={ 18 } lineHeight={ 28 } className="mb-1">
					{ __(
						'Unlock Competitor Insights with Content Gap',
						'surerank'
					) }
				</Text>
				<Text
					size={ 16 }
					color="secondary"
					lineHeight={ 24 }
					className="line-clamp-2 mb-3"
				>
					{ __(
						'Discover the topics your competitors rank for but you’re missing. Find high-traffic opportunities and close the gap with data-backed content strategies.',
						'surerank'
					) }
				</Text>
				<Button
					variant="primary"
					size="md"
					onClick={ () =>
						window.open( surerank_globals.pricing_link, '_blank' )
					}
					className="pointer-events-auto"
				>
					{ __( 'Upgrade to View Full Report', 'surerank' ) }
				</Button>
			</div>

			{ /* Blurred Table Section */ }
			<div className="absolute inset-0 z-0 pointer-events-none flex justify-center">
				<div className="blur-[7px] w-full overflow-hidden">
					<Table>
						<Table.Head>
							<Table.HeadCell className="w-[35%] max-w-120 min-w-80">
								{ __( 'Page', 'surerank' ) }
							</Table.HeadCell>
							<Table.HeadCell className="w-1/10">
								{ __( 'Status', 'surerank' ) }
							</Table.HeadCell>
							<Table.HeadCell className="w-[12%]">
								{ __( 'Clicks', 'surerank' ) }
							</Table.HeadCell>
							<Table.HeadCell className="w-[12%] text-nowrap">
								{ __( 'Avg. Position', 'surerank' ) }
							</Table.HeadCell>
							<Table.HeadCell className="w-[12%]">
								{ __( 'Impressions', 'surerank' ) }
							</Table.HeadCell>
							<Table.HeadCell className="min-w-[10rem] text-nowrap">
								<Container align="center" className="gap-1">
									<span className="text-text-tertiary">
										{ __( 'Content Score', 'surerank' ) }
									</span>
									<Badge
										className="w-fit"
										size="xs"
										variant="blue"
										label={ __( 'Pro', 'surerank' ) }
									/>
								</Container>
							</Table.HeadCell>
							<Table.HeadCell className="min-w-[10%]">
								<span className="sr-only">
									{ __( 'Actions', 'surerank' ) }
								</span>
							</Table.HeadCell>
						</Table.Head>
						<Table.Body>
							{ data.map( ( row, index ) => (
								<Table.Row key={ index }>
									<Table.Cell>
										<Text
											color="secondary"
											className="line-clamp-1 no-underline text-xs font-thin text-text-tertiary"
											target="_blank"
										>
											{ row.url }
										</Text>
									</Table.Cell>
									<Table.Cell>
										<Badge
											className="w-fit"
											size="xs"
											variant={ row.statusVariant }
											label={ row.status }
											disableHover
											disabled={ true }
										/>
									</Table.Cell>
									<Table.Cell>
										<span className="text-xs font-thin text-text-tertiary">
											{ row.clicks }
										</span>
									</Table.Cell>
									<Table.Cell>
										<span className="text-xs font-thin text-text-tertiary">
											{ row.position }
										</span>
									</Table.Cell>
									<Table.Cell>
										<span className="text-xs font-thin text-text-tertiary">
											{ row.impressions }
										</span>
									</Table.Cell>
									<Table.Cell>
										<Container
											direction="column"
											className="gap-1.5"
										>
											<span className="text-xs font-thin text-text-tertiary">
												{ __(
													'Out of 100',
													'surerank'
												) }
											</span>
											<ProgressBar
												progress={ 0 }
												className="w-full max-w-32"
											/>
										</Container>
									</Table.Cell>
									<Table.Cell>
										<Button
											size="xs"
											variant="outline"
											disabled
										>
											{ __( 'View', 'surerank' ) }
										</Button>
									</Table.Cell>
								</Table.Row>
							) ) }
						</Table.Body>
					</Table>
				</div>
			</div>
		</div>
	);
};

export default EmptyContentGap;
