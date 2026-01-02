import { useSelect, useDispatch } from '@wordpress/data';
import {
	Container,
	Title,
	Button,
	Label,
	toast,
	Skeleton,
	LineChart,
	Badge,
	Text,
} from '@bsf/force-ui';
import { __, sprintf } from '@wordpress/i18n';
import {
	useEffect,
	useState,
	useMemo,
	useCallback,
	useRef,
} from '@wordpress/element';
import { ArrowUp, ArrowDown, LogOut } from 'lucide-react';
import {
	cn,
	formatNumber,
	formatToISOPreserveDate,
	getLastNDays,
	formatDateRange,
	prepareURL,
	getMetricStyles,
	getMetricValues,
} from '@/functions/utils';
import Section from './section';
import { STORE_NAME } from '@/admin-store/constants';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { handleDisconnectConfirm } from '../admin-components/user-dropdown';
const { auth_url: authURL } = surerank_admin_common;

const DEFAULT_DATE_RANGE = 90;

const ClicksAndImpressions = ( { item, isLoading } ) => {
	const { renderValue, renderDifference } = getMetricValues( item );
	let renderIcon =
		item.percentageType === 'success' ? (
			<ArrowUp className="size-5" />
		) : (
			<ArrowDown className="size-5" />
		);

	const { differenceClassName, fallbackClassName } = getMetricStyles( item );

	// Render N/A and null for difference and icon when both value and previous are null.
	if ( item.value === null && item.previous === null ) {
		renderIcon = null;
	}

	if ( item.value === 0 && item.previous === 0 ) {
		renderIcon = null;
	}

	return (
		<Container.Item
			key={ item.label }
			className="px-3 py-5 space-y-4 w-full h-full bg-background-primary rounded-md shadow-sm"
		>
			<Container
				align="center"
				justify="between"
				gap="sm"
				className="p-1"
			>
				<Label tag="p" size="md" className="font-medium">
					{ item.label }
				</Label>
				<span className={ cn( 'size-2 rounded-sm', item.color ) } />
			</Container>
			<Container
				align="center"
				justify="between"
				gap="sm"
				className="p-1"
			>
				{ isLoading ? (
					<Skeleton variant="rectangular" className="w-24 h-10" />
				) : (
					<Label
						tag="p"
						size="md"
						className={ cn(
							'font-semibold text-4xl',
							fallbackClassName
						) }
					>
						{ renderValue }
					</Label>
				) }
				{ isLoading ? (
					<Skeleton variant="rectangular" className="w-16 h-6" />
				) : (
					<Label
						tag="p"
						size="sm"
						className={ cn(
							'font-medium',
							differenceClassName,
							fallbackClassName
						) }
					>
						{ renderIcon }
						<span className="text-inherit">
							{ renderDifference }
						</span>
					</Label>
				) }
			</Container>
		</Container.Item>
	);
};

const EmptyState = ( {
	onClickActionBtn,
	imageSrc = `${ surerank_globals.admin_assets_url }/images/search-console.svg`,
	title,
	description,
	actionButtonText,
} ) => {
	return (
		<Container
			gap="lg"
			direction="column"
			align="center"
			justify="center"
			className="p-[3.125rem]"
		>
			<img src={ imageSrc } alt="Site Search Traffic" />
			<Container.Item className="mx-auto text-center max-w-[39.875rem] space-y-1">
				<Label
					tag="h6"
					className="text-lg font-semibold text-center block"
				>
					{ title }
				</Label>
				<Label
					tag="p"
					size="md"
					className="font-normal text-text-secondary"
				>
					{ description }
				</Label>
			</Container.Item>
			<Button
				variant="primary"
				size="md"
				className="focus:[box-shadow:none]"
				onClick={ onClickActionBtn }
			>
				{ actionButtonText }
			</Button>
		</Container>
	);
};

const SiteSearchTraffic = () => {
	const { setSearchConsole, toggleSiteSelectorModal, setConfirmationModal } =
		useDispatch( STORE_NAME );
	const {
		clicksData = [
			{
				label: __( 'Clicks', 'surerank' ),
				value: null,
				previous: null,
				percentage: null,
				percentageType: 'success',
				color: 'bg-sky-500',
			},
			{
				label: __( 'Impressions', 'surerank' ),
				value: null,
				previous: null,
				percentage: null,
				percentageType: 'success',
				color: 'bg-background-brand',
			},
		],
		authenticated,
		hasSiteSelected,
		selectedSite,
		siteTraffic = [],
		siteTrafficFetchComplete = false,
	} = useSelect( ( select ) => select( STORE_NAME ).getSearchConsole(), [] );

	const [ isLoading, setIsLoading ] = useState(
		authenticated &&
			hasSiteSelected &&
			clicksData[ 0 ].value === null &&
			siteTraffic.length === 0
	);

	const previousSite = useRef( null ); // Track previous site, start with null

	const fetchClicksAndImpressions = useCallback( async ( from, to ) => {
		const formattedStartDate = formatToISOPreserveDate( from );
		const formattedEndDate = formatToISOPreserveDate( to );

		try {
			const response = await apiFetch( {
				path: '/surerank/v1/google-search-console/clicks-and-impressions',
				method: 'POST',
				data: {
					startDate: formattedStartDate,
					endDate: formattedEndDate,
				},
			} );
			if ( ! response.success ) {
				throw new Error(
					response.message ??
						__( 'Failed to fetch matched site', 'surerank' )
				);
			}
			const clicks = response?.data?.clicks;
			const impressions = response?.data?.impressions;

			const getPercentageType = ( percentage ) => {
				if ( percentage === 0 ) {
					return 'neutral';
				}
				return percentage > 0 ? 'success' : 'danger';
			};

			setSearchConsole( {
				clicksData: [
					{
						label: __( 'Clicks', 'surerank' ),
						value: clicks?.current,
						previous: clicks?.previous,
						percentage: clicks?.percentage,
						percentageType: getPercentageType( clicks?.percentage ),
						color: 'bg-sky-500',
					},
					{
						label: __( 'Impressions', 'surerank' ),
						value: impressions?.current,
						percentage: impressions?.percentage,
						previous: impressions?.previous,
						percentageType: getPercentageType(
							impressions?.percentage
						),
						color: 'bg-background-brand',
					},
				],
			} );
		} catch ( error ) {
			toast.error( error.message );
		}
	}, [] );

	const fetchSiteTraffic = useCallback( async ( from, to ) => {
		const formattedStartDate = formatToISOPreserveDate( from );
		const formattedEndDate = formatToISOPreserveDate( to );

		try {
			const response = await apiFetch( {
				path: addQueryArgs(
					'/surerank/v1/google-search-console/site-traffic',
					{
						startDate: formattedStartDate,
						endDate: formattedEndDate,
					}
				),
				method: 'GET',
			} );
			if ( ! response.success ) {
				throw new Error(
					response.message ??
						__( 'Failed to fetch site traffic', 'surerank' )
				);
			}
			setSearchConsole( {
				siteTraffic: response?.data?.length
					? response?.data?.map( ( item ) => ( {
							...item,
							readableDate: formatDateRange(
								item.date,
								from,
								to
							),
					  } ) )
					: [],
			} );
		} catch ( error ) {
			toast.error( error.message );
		}
	}, [] );

	const initiateAPICalls = useCallback( async () => {
		if ( ! authenticated || ! selectedSite ) {
			return;
		}
		setIsLoading( true );
		const { from, to } = getLastNDays( DEFAULT_DATE_RANGE );
		try {
			await fetchClicksAndImpressions( from, to );
			await fetchSiteTraffic( from, to );
			setSearchConsole( {
				siteTrafficFetchComplete: true,
			} );
		} catch ( error ) {
			// do nothing
		} finally {
			setIsLoading( false );
		}
	}, [
		authenticated,
		selectedSite,
		fetchClicksAndImpressions,
		fetchSiteTraffic,
	] );

	const handleChangeSite = () => {
		toggleSiteSelectorModal();
	};

	const handleDisconnect = () => {
		setConfirmationModal( {
			open: true,
			title: __( 'Disconnect Search Console Account', 'surerank' ),
			description: __(
				'Are you sure you want to disconnect your Search Console account from SureRank?',
				'surerank'
			),
			onConfirm: handleDisconnectConfirm,
			confirmButtonText: __( 'Disconnect', 'surerank' ),
		} );
	};
	useEffect( () => {
		// Only fetch data when the site changes or on first load without data
		if (
			authenticated &&
			selectedSite &&
			previousSite.current !== selectedSite
		) {
			// Reset fetchComplete when site changes
			if ( previousSite.current !== null ) {
				setSearchConsole( {
					siteTrafficFetchComplete: false,
				} );
			}

			// Only fetch if we haven't completed a fetch for this session or if the site actually changed
			if ( ! siteTrafficFetchComplete || previousSite.current !== null ) {
				initiateAPICalls();
			}
			previousSite.current = selectedSite;
		}
	}, [
		authenticated,
		selectedSite,
		initiateAPICalls,
		siteTrafficFetchComplete,
		setSearchConsole,
	] );

	const handleOpenAuthURL = () => {
		window.open( authURL, '_self', 'noopener,noreferrer' );
	};

	const emptyStateProps = useMemo(
		() =>
			! authenticated
				? {
						imageSrc: `${ surerank_globals.admin_assets_url }/images/search-console.svg`,
						title: __(
							'Connect Your Site to Google Search Console',
							'surerank'
						),
						description: __(
							'Link your website to Google Search Console to access detailed search analytics, track performance, and optimize your site for better search rankings.',
							'surerank'
						),
						actionButtonText: __(
							'Connect to Search Console - It’s Free',
							'surerank'
						),
						onClickActionBtn: handleOpenAuthURL,
				  }
				: {
						imageSrc: `${ surerank_globals.admin_assets_url }/images/search-console.svg`,
						title: __(
							'Select a Site to View Analytics',
							'surerank'
						),
						description: __(
							'Select a site to access detailed search analytics, track performance metrics, and boost your visibility in search results effectively.',
							'surerank'
						),
						actionButtonText: __( 'Select a Site', 'surerank' ),
						onClickActionBtn: toggleSiteSelectorModal,
				  },
		[ authenticated ]
	);

	let renderContent = null;
	if ( ! authenticated || ! hasSiteSelected ) {
		renderContent = (
			<EmptyState
				imageSrc={ emptyStateProps.imageSrc }
				title={ emptyStateProps.title }
				description={ emptyStateProps.description }
				actionButtonText={ emptyStateProps.actionButtonText }
				onClickActionBtn={ emptyStateProps.onClickActionBtn }
			/>
		);
	} else {
		renderContent = (
			<Container className="p-1 rounded-lg bg-background-secondary gap-1 flex-wrap md:flex-nowrap">
				<div className="w-full rounded-md bg-background-primary shadow-sm">
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
									stroke: '#4B3BED',
								},
								{
									stroke: '#38BDF8',
								},
							] }
							yAxisFontColor={ [ '#4B3BED', '#38BDF8' ] }
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
									right: 10,
									bottom: 25,
									left: 10,
								},
							} }
						/>
					) }
				</div>
				<Container
					className="w-full md:w-[30%] gap-1 flex-row md:flex-col"
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
		);
	}

	const updateURL = ( site ) => {
		return sprintf(
			// translators: %s: site URL
			__( 'Site: %s', 'surerank' ),
			prepareURL( site )
		);
	};

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
					{ selectedSite && (
						<Text size={ 16 } weight={ 400 } color="secondary">
							{ __( '(Last 90 days)', 'surerank' ) }
						</Text>
					) }
				</div>
				<Container
					gap="xs"
					justify="between"
					align="center"
					className="p-1"
				>
					{ selectedSite && (
						<span
							role="button"
							tabIndex={ 0 }
							onClick={ handleChangeSite }
							onKeyDown={ ( event ) => {
								if (
									event.key === 'Enter' ||
									event.key === ' '
								) {
									handleChangeSite();
								}
							} }
							className="focus:outline-none"
						>
							<Badge
								size="md"
								label={ updateURL( selectedSite ) }
								className="cursor-pointer"
							/>
						</span>
					) }

					{ authenticated && (
						<span
							role="button"
							tabIndex={ 0 }
							onClick={ handleDisconnect }
							onKeyDown={ ( event ) => {
								if (
									event.key === 'Enter' ||
									event.key === ' '
								) {
									handleDisconnect();
								}
							} }
							className="focus:outline-none"
						>
							<Badge
								size="md"
								label={ __( 'Disconnect', 'surerank' ) }
								icon={ <LogOut /> }
								iconPosition="left"
								className="cursor-pointer pl-2"
							/>
						</span>
					) }
				</Container>
			</Container>
			{ renderContent }
		</Section>
	);
};

export default SiteSearchTraffic;
