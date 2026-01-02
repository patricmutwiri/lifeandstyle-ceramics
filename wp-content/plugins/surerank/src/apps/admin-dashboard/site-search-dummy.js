import { Container, Title, Label, LineChart, Text } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';
import Section from './section';
import { formatNumber } from '@/functions/utils';

const rawTrafficData = [
	{ clicks: 10000, impressions: 100, date: '2025-02-06' },
	{ clicks: 1000, impressions: 1000, date: '2025-02-07' },
	{ clicks: 10000, impressions: 10000, date: '2025-02-08' },
	{ clicks: 1000, impressions: 10000, date: '2025-02-09' },
	{ clicks: 10000, impressions: 1000, date: '2025-02-10' },
	{ clicks: 1000, impressions: 10000, date: '2025-02-11' },
	{ clicks: 10000, impressions: 1000, date: '2025-02-12' },
	{ clicks: 10000, impressions: 100000, date: '2025-02-13' },
	{ clicks: 1000, impressions: 10000, date: '2025-02-14' },
	{ clicks: 10000, impressions: 1000, date: '2025-02-15' },
	{ clicks: 1000, impressions: 10000, date: '2025-02-16' },
	{ clicks: 10000, impressions: 10000, date: '2025-02-17' },
	{ clicks: 1000, impressions: 10000, date: '2025-02-18' },
	{ clicks: 10000, impressions: 10000, date: '2025-02-19' },
	{ clicks: 1000, impressions: 1000, date: '2025-02-20' },
	{ clicks: 10000, impressions: 10000, date: '2025-02-21' },
	{ clicks: 1000, impressions: 118, date: '2025-02-22' },
	{ clicks: 10000, impressions: 10000, date: '2025-02-23' },
	{ clicks: 10000, impressions: 1000, date: '2025-02-24' },
	{ clicks: 10000, impressions: 10000, date: '2025-02-25' },
];

// Convert dates to readable format + day
const formatDate = ( dateStr ) => {
	const date = new Date( dateStr );
	return {
		readableDate: date.toLocaleDateString( 'en-GB', {
			day: '2-digit',
			month: 'short',
		} ),
		day: date.toLocaleDateString( 'en-GB', { weekday: 'short' } ),
	};
};

const trafficData = rawTrafficData.map( ( { date, clicks, impressions } ) => {
	const { readableDate, day } = formatDate( date );
	return { readableDate, day, clicks, impressions };
} );

const SiteSearchTrafficDummy = () => {
	const totalClicks = trafficData.reduce( ( sum, d ) => sum + d.clicks, 0 );
	const totalImpressions = trafficData.reduce(
		( sum, d ) => sum + d.impressions,
		0
	);

	return (
		<Section>
			<Container
				gap="none"
				justify="between"
				align="center"
				className="p-1"
			>
				<div className="flex items-center gap-3">
					<Title
						title={ __( 'Site Search Traffic', 'surerank' ) }
						tag="h4"
						size="md"
					/>
					<Text size={ 16 } weight={ 400 } color="secondary">
						{ __( '(Last 20 days)', 'surerank' ) }
					</Text>
				</div>
			</Container>

			<Container className="p-1 rounded-lg bg-background-secondary gap-1 flex-wrap md:flex-nowrap">
				<div className="w-full rounded-md bg-background-primary shadow-sm">
					<LineChart
						colors={ [
							{ stroke: '#4B3BED' },
							{ stroke: '#38BDF8' },
						] }
						yAxisFontColor={ [ '#4B3BED', '#38BDF8' ] }
						data={ trafficData }
						dataKeys={ [ 'impressions', 'clicks' ] }
						showTooltip
						showXAxis
						showYAxis
						biaxial
						tooltipIndicator="dot"
						variant="gradient"
						xAxisDataKey={ ( entry ) =>
							`${ entry.readableDate } (${ entry.day })`
						}
						yAxisTickFormatter={ ( value ) =>
							formatNumber( value )
						}
						showLegend={ false }
						chartHeight={ 288 }
						chartWidth="100%"
						lineChartWrapperProps={ {
							margin: {
								top: 25,
								right: 10,
								bottom: 25,
								left: 10,
							},
						} }
					/>
				</div>

				<Container
					className="w-full md:w-[30%] gap-1 flex-row md:flex-col"
					align="stretch"
				>
					{ [ 'Clicks', 'Impressions' ].map( ( label ) => (
						<Container.Item
							key={ label }
							className="px-3 py-5 space-y-4 w-full h-full bg-background-primary rounded-md shadow-sm"
						>
							<Label tag="p" size="md" className="font-medium">
								{ label }
							</Label>
							<Label
								tag="p"
								size="md"
								className="font-semibold text-4xl"
							>
								{ label === 'Clicks'
									? formatNumber( totalClicks )
									: formatNumber( totalImpressions ) }
							</Label>
						</Container.Item>
					) ) }
				</Container>
			</Container>
		</Section>
	);
};

export default SiteSearchTrafficDummy;
