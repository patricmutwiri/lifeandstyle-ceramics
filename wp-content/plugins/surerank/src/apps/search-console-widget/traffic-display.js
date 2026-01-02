import { __, sprintf } from '@wordpress/i18n';
import { Container, Skeleton, Text, LineChart } from '@bsf/force-ui';
import { ArrowDown, ArrowUp, ArrowUpRight } from 'lucide-react';
import {
	cn,
	formatNumber,
	prepareURL,
	getMetricStyles,
	getMetricValues,
} from '@/functions/utils';
import { useWidgetState } from './context/widget-context';
import { InfoTooltip } from '@AdminComponents/tooltip';

/**
 * Clicks and Impressions Metric Card
 *
 * @param {Object}  props           Component props
 * @param {Object}  props.item      Metric item data
 * @param {boolean} props.isLoading Loading state
 * @return {JSX.Element} Metric card component
 */
const ClicksAndImpressions = ( { item, isLoading } ) => {
	const { renderValue, renderDifference } = getMetricValues( item );
	let Icon = item.percentageType === 'success' ? ArrowUp : ArrowDown;

	const { differenceClassName, fallbackClassName } = getMetricStyles( item );

	// Render N/A and null for difference and icon when both value and previous are null.
	if ( item.value === null && item.previous === null ) {
		Icon = null;
	}

	if ( item.value === 0 && item.previous === 0 ) {
		Icon = null;
	}

	return (
		<Container.Item
			key={ item.label }
			className="px-3 py-3 space-y-2 w-full h-full bg-background-primary shadow-sm rounded-sm first:rounded-b-none md:first:rounded-l-sm md:first:rounded-r-none last:rounded-t-none md:last:rounded-r-sm md:last:rounded-l-none last:border-t-0 md:last:border-t-0.5 md:last:border-l-0 border-0.5 border-solid border-[#C3C4C7]"
		>
			<Container
				align="center"
				justify="between"
				gap="sm"
				className="p-1"
			>
				<Text size={ 14 } weight={ 500 }>
					{ item.label }
				</Text>
				<span className={ cn( 'size-2 rounded-sm', item.color ) } />
			</Container>
			<Container
				align="center"
				justify="between"
				gap="sm"
				className="p-1"
			>
				{ isLoading ? (
					<Skeleton variant="rectangular" className="w-24 h-7" />
				) : (
					<Text
						size={ 20 }
						weight={ 600 }
						className={ cn( fallbackClassName ) }
					>
						{ renderValue }
					</Text>
				) }
				{ isLoading ? (
					<Skeleton variant="rectangular" className="w-16 h-4" />
				) : (
					<Text
						size={ 14 }
						weight={ 500 }
						className={ cn(
							differenceClassName,
							fallbackClassName
						) }
					>
						{ !! Icon && <Icon className="size-5 align-bottom" /> }
						<span className="text-inherit">
							{ renderDifference }
						</span>
					</Text>
				) }
			</Container>
		</Container.Item>
	);
};

/**
 * Traffic Display Component
 * Shows the line chart with clicks and impressions data
 *
 * @return {JSX.Element} Traffic display component
 */
const TrafficDisplay = () => {
	const { isLoading, clicksData, siteTraffic } = useWidgetState();
	const {
		settings_page_url: settingsPageURL,
		gsc_selected_site: selectedSite,
	} = window.surerank_search_console_widget || {};

	return (
		<>
			<Container direction="column" className="gap-2">
				<div className="p-2 w-full bg-background-primary rounded-sm">
					<Container
						gap="none"
						justify="between"
						align="center"
						className="p-1 mb-2"
					>
						<div className="flex items-center gap-1">
							<Text
								as="h4"
								size={ 16 }
								weight={ 600 }
								className="!m-0 !text-base !font-semibold"
							>
								{ __(
									'Traffic from last few days',
									'surerank'
								) }
							</Text>
							<InfoTooltip
								content={ sprintf(
									// translators: %s is the selected site URL.
									__( 'Site: %s', 'surerank' ),
									prepareURL( selectedSite )
								) }
							/>
						</div>
						<a
							className="text-xs font-semibold [&>span]:no-underline [&:hover>:first-child]:underline flex items-center gap-1 no-underline"
							href={ settingsPageURL }
							target="_self"
						>
							{ __( 'View More', 'surerank' ) }
							<ArrowUpRight width={ 14 } height={ 14 } />
						</a>
					</Container>
					{ isLoading && (
						<Skeleton
							variant="rectangular"
							className="w-full h-[288px]"
						/>
					) }
					{ ! isLoading && siteTraffic.length === 0 && (
						<Container
							gap="md"
							direction="column"
							align="center"
							justify="center"
							className="h-[288px] p-8 gap-2"
						>
							<Text
								size={ 14 }
								weight={ 600 }
								className="text-center"
								color="primary"
							>
								{ __( 'No data available', 'surerank' ) }
							</Text>
							<Text
								size={ 14 }
								weight={ 400 }
								color="tertiary"
								className="text-center max-w-md"
							>
								{ __(
									'Search Console data might take up to 30 days to appear for newly added sites. Please check back later.',
									'surerank'
								) }
							</Text>
						</Container>
					) }
					{ ! isLoading && siteTraffic.length > 0 && (
						<LineChart
							colors={ [
								{
									stroke: '#2171B1',
								},
								{
									stroke: '#72AEE6',
								},
							] }
							yAxisFontColor={ [ '#2171B1', '#72AEE6' ] }
							data={ siteTraffic }
							dataKeys={ [ 'impressions', 'clicks' ] }
							showTooltip
							showXAxis={ true }
							showYAxis={ true }
							biaxial
							tooltipIndicator="dot"
							variant="gradient"
							xAxisDataKey="readableDate"
							yAxisTickFormatter={ ( value ) =>
								formatNumber( value )
							}
							showLegend={ false }
							chartHeight={ 288 }
							chartWidth="100%"
							lineChartWrapperProps={ {
								margin: {
									top: 25,
									right: -18,
									bottom: 10,
									left: -18,
								},
							} }
						/>
					) }
				</div>
				<Container
					className="w-full grid grid-cols-1 md:grid-cols-2 gap-0"
					align="stretch"
				>
					{ clicksData.map( ( item ) => (
						<ClicksAndImpressions
							key={ item.label }
							item={ item }
							isLoading={ isLoading }
						/>
					) ) }
				</Container>
			</Container>
		</>
	);
};

export default TrafficDisplay;
