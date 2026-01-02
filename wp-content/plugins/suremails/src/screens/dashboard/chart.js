// Chart.jsx
import { useState, useEffect, useRef } from '@wordpress/element';
import {
	Container,
	Input,
	Label,
	DatePicker,
	LineChart,
	Button,
} from '@bsf/force-ui';
import apiFetch from '@wordpress/api-fetch';
import Title from '@components/title/title';
import { __ } from '@wordpress/i18n';
import {
	cn,
	format,
	getDatePlaceholder,
	getSelectedDate,
	getLastNDays,
} from '@utils/utils';
import { ChartColumn, Calendar, X, Plus } from 'lucide-react';
import { useQueryClient } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';

export const Chart = ( {
	totalSent = 0,
	totalFailed = 0,
	chartData = [],
	hasConnections = true,
} ) => {
	const [ selectedDates, setSelectedDates ] = useState( {
		from: null,
		to: null,
	} );
	const [ isDatePickerOpen, setIsDatePickerOpen ] = useState( false );
	const [ dataToShow, setDataToShow ] = useState( [] );
	const [ sent, setSent ] = useState( 0 );
	const [ failed, setFailed ] = useState( 0 );
	const containerRef = useRef( null );
	const queryClient = useQueryClient();
	const navigate = useNavigate();

	// Effect to process initial chartData
	useEffect( () => {
		if ( chartData.length === 0 ) {
			setSent( 0 );
			setFailed( 0 );
			setDataToShow( [] );
		} else {
			const sortedChartData = [ ...chartData ]
				.filter(
					( data ) => ! isNaN( new Date( data.created_at ).getTime() )
				)
				.sort(
					( a, b ) =>
						new Date( a.created_at ) - new Date( b.created_at )
				);

			const formattedInitialChartData = sortedChartData.map(
				( data ) => ( {
					month: format(
						new Date( data.created_at ),
						'MMM dd, yyyy'
					),
					sent: parseInt( data.total_sent, 10 ) || 0,
					failed: parseInt( data.total_failed, 10 ) || 0,
				} )
			);
			setDataToShow( formattedInitialChartData );
			setSent( totalSent || 0 );
			setFailed( totalFailed || 0 );
		}
	}, [ totalSent, totalFailed, chartData ] );

	// Function to fetch chart data based on selected dates
	const fetchChartData = async ( dates ) => {
		if ( ! dates.from || isNaN( dates.from.getTime() ) ) {
			setSent( 0 );
			setFailed( 0 );
			setDataToShow( [] );
			return;
		}

		const formattedStartDate = format( dates.from, 'yyyy/MM/dd' );
		const formattedEndDate =
			dates.to && ! isNaN( dates.to.getTime() )
				? format( dates.to, 'yyyy/MM/dd' )
				: formattedStartDate;

		try {
			const response = await apiFetch( {
				path: '/suremails/v1/email-stats',
				method: 'POST',
				headers: {
					'X-WP-Nonce': window.suremails?.nonce,
					'Content-Type': 'application/json',
				},
				body: JSON.stringify( {
					start_date: formattedStartDate,
					end_date: formattedEndDate,
				} ),
			} );

			if ( response.success ) {
				const sortedNewChartData = [ ...response.data.chart_data ]
					.filter(
						( data ) =>
							! isNaN( new Date( data.created_at ).getTime() )
					) // Filter invalid dates
					.sort(
						( a, b ) =>
							new Date( a.created_at ) - new Date( b.created_at )
					);

				const newChartData = sortedNewChartData.map( ( data ) => ( {
					month: format(
						new Date( data.created_at ),
						'MMM dd, yyyy'
					),
					sent: parseInt( data.total_sent, 10 ) || 0,
					failed: parseInt( data.total_failed, 10 ) || 0,
				} ) );

				setSent( response.data.total_sent || 0 );
				setFailed( response.data.total_failed || 0 );
				setDataToShow( newChartData );
			} else {
				setSent( 0 );
				setFailed( 0 );
				setDataToShow( [] );
			}
		} catch ( error ) {
			setSent( 0 );
			setFailed( 0 );
			setDataToShow( [] );
		}
	};

	// Effect to fetch chart data when selectedDates change
	useEffect( () => {
		if ( selectedDates.from && ! isNaN( selectedDates.from.getTime() ) ) {
			fetchChartData( selectedDates );
		}
	}, [ selectedDates.from, selectedDates.to ] );

	// Handler to clear filters and reset to default data from Query Client
	const handleClearFilters = () => {
		setSelectedDates( { from: null, to: null } );

		// Retrieve cached 'dashboard-data' from Query Client
		const dashboardData = queryClient.getQueryData( [ 'dashboard-data' ] );

		if ( dashboardData ) {
			const sortedChartData = [ ...dashboardData.chart_data ]
				.filter(
					( data ) => ! isNaN( new Date( data.created_at ).getTime() )
				) // Filter invalid dates
				.sort(
					( a, b ) =>
						new Date( a.created_at ) - new Date( b.created_at )
				);

			const formattedDefaultChartData = sortedChartData.map(
				( data ) => ( {
					month: format(
						new Date( data.created_at ),
						'MMM dd, yyyy'
					),
					sent: parseInt( data.total_sent, 10 ) || 0,
					failed: parseInt( data.total_failed, 10 ) || 0,
				} )
			);

			setDataToShow( formattedDefaultChartData );
			setSent( dashboardData.total_sent || 0 );
			setFailed( dashboardData.total_failed || 0 );
		} else {
			// If 'dashboard-data' is not available, reset to empty state
			setSent( 0 );
			setFailed( 0 );
			setDataToShow( [] );
		}
	};

	// Handlers for DatePicker
	const handleDateApply = ( dates ) => {
		const { from, to } = dates;

		if ( from && to ) {
			const fromDate = new Date( from );
			const toDate = new Date( to );

			if ( isNaN( fromDate.getTime() ) || isNaN( toDate.getTime() ) ) {
				setSelectedDates( { from: null, to: null } );
				setIsDatePickerOpen( false );
				return;
			}

			if ( fromDate > toDate ) {
				// Swap the dates to ensure 'from' is earlier than 'to'
				setSelectedDates( { from: toDate, to: fromDate } );
			} else {
				setSelectedDates( { from: fromDate, to: toDate } );
			}
		} else if ( from && ! to ) {
			const fromDate = new Date( from );
			if ( isNaN( fromDate.getTime() ) ) {
				setSelectedDates( { from: null, to: null } );
				setIsDatePickerOpen( false );
				return;
			}
			setSelectedDates( { from: fromDate, to: fromDate } );
		} else {
			setSelectedDates( { from: null, to: null } );
		}
		setIsDatePickerOpen( false );
	};

	const handleDateCancel = () => {
		setIsDatePickerOpen( false );
	};

	// Click Outside Handler using useEffect
	useEffect( () => {
		function handleClickOutside( event ) {
			if (
				isDatePickerOpen &&
				containerRef.current &&
				! containerRef.current.contains( event.target )
			) {
				setIsDatePickerOpen( false );
			}
		}

		// Bind the event listener
		document.addEventListener( 'mousedown', handleClickOutside );
		return () => {
			// Unbind the event listener on cleanup
			document.removeEventListener( 'mousedown', handleClickOutside );
		};
	}, [ isDatePickerOpen ] );

	// Formatter for X-Axis
	const formatXAxis = ( tickItem ) => {
		return format( new Date( tickItem ), 'MMM dd, yyyy' );
	};

	return (
		<Container
			containerType="flex"
			direction="column"
			gap="xs"
			className="w-full h-full p-4 rounded-xl bg-background-primary border-border-subtle border-0.5"
		>
			<Container.Item className="flex items-center justify-between w-full p-1">
				<Title
					title={ __( 'Overview', 'suremails' ) }
					tag="h3"
					size="xs"
				/>

				<div className="flex items-center gap-2">
					{ selectedDates.from || selectedDates.to ? (
						<Button
							variant="link"
							size="xs"
							icon={ <X /> }
							onClick={ handleClearFilters }
							className="text-button-danger no-underline focus:ring-0 [box-shadow:none] focus:[box-shadow:none] hover:no-underline hover:text-button-danger"
							aria-label={ __( 'Clear Filters', 'suremails' ) }
						>
							{ __( 'Clear Filters', 'suremails' ) }
						</Button>
					) : null }

					<div className="relative" ref={ containerRef }>
						<Input
							type="text"
							size="sm"
							value={ getSelectedDate( selectedDates ) }
							suffix={
								<Calendar className="text-icon-secondary" />
							}
							onClick={ () =>
								setIsDatePickerOpen( ( prev ) => ! prev )
							}
							placeholder={ getDatePlaceholder() }
							className="w-auto min-w-[200px] cursor-pointer [&>input]:min-h-8 rounded-sm shadow-sm border border-border-subtle"
							readOnly
							aria-label={ __(
								'Select Date Range',
								'suremails'
							) }
						/>

						{ isDatePickerOpen && (
							<div className="absolute z-10 mt-2 rounded-lg shadow-lg right-0 bg-background-primary">
								<DatePicker
									applyButtonText={ __(
										'Apply',
										'suremails'
									) }
									cancelButtonText={ __(
										'Cancel',
										'suremails'
									) }
									selectionType="range"
									showOutsideDays={ false }
									variant="presets"
									onApply={ handleDateApply }
									onCancel={ handleDateCancel }
									selected={ getLastNDays( 30 ) }
								/>
							</div>
						) }
					</div>
				</div>
			</Container.Item>

			<Container.Item
				className={ cn(
					'w-full flex items-stretch justify-between gap-1 bg-background-secondary rounded-lg',
					dataToShow.length > 0 ? 'p-1' : 'p-0'
				) }
			>
				{ /* Chart Container */ }
				<Container
					className={ cn(
						'w-full flex flex-col flex-1 p-3 overflow-hidden bg-background-primary',
						dataToShow.length > 0 ? 'rounded-md shadow-sm' : ''
					) }
					containerType="flex"
					direction="column"
				>
					{ dataToShow.length > 0 ? (
						<div className="flex-1 w-full">
							<div className="w-full h-full min-h-[248px] min-[1427px]:min-h-[228px]">
								<LineChart
									data={ dataToShow }
									dataKeys={ [ 'sent', 'failed' ] }
									colors={ [
										{ stroke: '#0EA5E9' }, // Color for "Email Sent"
										{ stroke: '#A855F7' }, // Color for "Email Failed"
									] }
									showXAxis={ false }
									showYAxis={ false }
									showTooltip
									showCartesianGrid={ true }
									tooltipIndicator="dot"
									tickFormatter={ formatXAxis }
									xAxisDataKey="month"
									chartWidth="100%"
									chartHeight="100%"
									tooltipLabelKey="month"
									lineChartWrapperProps={ {
										margin: {
											top: 30,
											bottom: 30,
											right: 5,
											left: 5,
										},
									} }
								/>
							</div>
						</div>
					) : (
						<div className="flex flex-col items-center justify-center h-full  min-[1427px]:min-h-[236px] min-h-[256px] gap-3">
							<div className="flex flex-col items-center justify-center w-[29.375rem]">
								<ChartColumn className="mb-3" />
								<div className="flex flex-col items-center space-y-1">
									<Label
										tag="p"
										className="text-sm font-medium text-center text-text-primary"
									>
										{ __(
											'No Email Stats Available',
											'suremails'
										) }
									</Label>
									<Label
										tag="p"
										className="text-sm font-normal text-center text-text-secondary"
									>
										{ __(
											'Once your emails start sending, you’ll see detailed stats here to help you monitor and manage your email activity.',
											'suremails'
										) }
									</Label>
								</div>
							</div>
							<div>
								{ ! hasConnections && (
									<Button
										variant="primary"
										size="sm"
										icon={ <Plus /> }
										iconPosition="left"
										onClick={ () =>
											navigate( '/connections', {
												state: {
													openDrawer: true,
												},
											} )
										}
										className="font-medium"
									>
										{ __( 'Add Connection', 'suremails' ) }
									</Button>
								) }
							</div>
						</div>
					) }
				</Container>

				{ /* Statistics Cards */ }
				{ dataToShow.length > 0 && (
					<Container
						containerType="flex"
						direction="column"
						className="w-[30%] gap-1 bg-background-secondary rounded-lg"
					>
						<Container.Item className="flex flex-col items-start justify-center flex-1 p-3 text-left rounded-md shadow-sm bg-background-primary">
							<div className="flex items-center mb-1">
								<div className="w-3 h-3 rounded bg-[#0EA5E9]"></div>
								<Label className="p-1 text-xs text-text-tertiary">
									{ __( 'Email Sent', 'suremails' ) }
								</Label>
							</div>
							<Label className="p-1 mt-3 text-4xl font-semibold text-text-primary leading-[44px]">
								{ String( sent ) }
							</Label>
						</Container.Item>

						<Container.Item className="flex flex-col items-start justify-center flex-1 p-3 text-left rounded-md shadow-sm bg-background-primary">
							<div className="flex items-center mb-1">
								<div className="w-3 h-3 rounded bg-[#A855F7]"></div>
								<Label className="p-1 text-xs text-text-tertiary">
									{ __( 'Email Failed', 'suremails' ) }
								</Label>
							</div>
							<Label className="p-1 mt-3 text-4xl font-semibold text-text-primary leading-[44px]">
								{ String( failed ) }
							</Label>
						</Container.Item>
					</Container>
				) }
			</Container.Item>
		</Container>
	);
};

export default Chart;
