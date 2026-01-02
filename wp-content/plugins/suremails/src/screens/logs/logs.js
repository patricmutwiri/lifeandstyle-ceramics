// Logs.js
import { useState, useEffect, useRef } from '@wordpress/element';
import {
	X,
	Eye as EyeIcon,
	Trash as DeleteIcon,
	RefreshCw as ResendIcon,
	Calendar,
	Search,
} from 'lucide-react';
import {
	toast,
	Select,
	Input,
	Button,
	Badge,
	Pagination,
	DatePicker,
	Table,
} from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';
import EmptyLogs from './empty-logs';
import NoFilteredLogs from './no-filtered-logs';
import EmailLogDrawer from './email-log-drawer';
import LogsSkeleton from './logs-skeleton';
import ConfirmationDialog from '@components/confirmation-dialog/confirmation-dialog'; // Import the ConfirmationDialog component
import {
	fetchLogs,
	deleteLogs as apiDeleteLogs,
	resendEmails as apiResendEmails,
} from '@api/logs'; // Import API functions
import {
	formatDate,
	getSelectedDate,
	getPaginationRange,
	getStatusLabel,
	getStatusVariant,
	get_pending_status,
} from '@utils/utils';
import Tooltip from '@components/tooltip/tooltip';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import TruncatedTooltipText from '@components/truncated-tooltip-text';
import Title from '@components/title/title';

const STATUS_FILTERS = [
	{ value: 'sent', label: __( 'Successful', 'suremails' ) },
	{ value: 'failed', label: __( 'Failed', 'suremails' ) },
	{ value: 'pending', label: __( 'In Progress', 'suremails' ) },
	{ value: 'blocked', label: __( 'Blocked', 'suremails' ) },
];

const Logs = () => {
	// State Variables
	const [ page, setPage ] = useState( 1 );
	const [ selectedDates, setSelectedDates ] = useState( {
		from: null,
		to: null,
	} );
	const [ filter, setFilter ] = useState( '' );
	const [ searchTerm, setSearchTerm ] = useState( '' ); // New search state
	const [ selectedLog, setSelectedLog ] = useState( null );
	const [ isDrawerOpen, setIsDrawerOpen ] = useState( false );
	const [ selectedLogs, setSelectedLogs ] = useState( [] );
	const [ isDatePickerOpen, setIsDatePickerOpen ] = useState( false );
	const logsPerPage = 10;

	// Dialog state for ConfirmationDialog
	const [ isDialogOpen, setIsDialogOpen ] = useState( false );
	const [ dialogConfig, setDialogConfig ] = useState( {
		title: '',
		description: '',
		onConfirm: null,
	} );
	const containerRef = useRef( null );

	// Check for URL parameter on component mount
	useEffect( () => {
		// Parse parameters from hash
		const hash = window.location.hash;
		const hashMatch = hash.match( /\/logs\?(.+)/ );

		if ( hashMatch && hashMatch[ 1 ] ) {
			const hashParams = new URLSearchParams( hashMatch[ 1 ] );
			const fromParam = hashParams.get( 'from' );
			const toParam = hashParams.get( 'to' );
			const pageParam = hashParams.get( 'page' );

			// Set date range if both from and to are provided
			if ( fromParam && toParam ) {
				const fromDate = new Date( fromParam );
				const toDate = new Date( toParam );

				// Validate that the dates are valid
				if (
					! isNaN( fromDate.getTime() ) &&
					! isNaN( toDate.getTime() )
				) {
					setSelectedDates( {
						from: fromDate,
						to: toDate,
					} );
				}
			}

			// Set page if provided
			if ( pageParam ) {
				const pageNumber = parseInt( pageParam, 10 );
				if ( ! isNaN( pageNumber ) && pageNumber > 0 ) {
					setPage( pageNumber );
				}
			}
		}
	}, [] );

	const useDebounce = ( value, delay = 500, callback ) => {
		const [ debouncedValue, setDebouncedValue ] = useState( value );
		useEffect( () => {
			const handler = setTimeout( () => {
				setDebouncedValue( value );
				callback();
			}, delay );
			return () => clearTimeout( handler );
		}, [ value, delay ] );
		return debouncedValue;
	};

	/**
	 * Debounced function to set the search term.
	 * This prevents excessive API calls during rapid input.
	 */
	const debouncedSearchTerm = useDebounce( searchTerm, 500, () =>
		setPage( 1 )
	);
	const queryClient = useQueryClient();

	// Replace fetchAndSetLogs with React Query
	const {
		data: logsData,
		isLoading,
		error,
	} = useQuery( {
		queryKey: [
			'logs',
			page,
			selectedDates.from,
			selectedDates.to,
			filter,
			debouncedSearchTerm,
		],
		queryFn: () =>
			fetchLogs( {
				pageNumber: page,
				startDate: selectedDates.from,
				endDate: selectedDates.to,
				filter,
				searchTerm: debouncedSearchTerm,
				logsPerPage,
			} ),
		keepPreviousData: true, // Preserve previous page data while loading next page
		refetchInterval: 100000, // Refetch every 10 minutes
		refetchOnReconnect: true,
	} );

	// Update the logs and totalPages from React Query data
	const logs = logsData?.logs || [];
	const totalPages = logsData?.total_count
		? Math.ceil( logsData.total_count / logsPerPage )
		: 1;

	// Add mutations for delete and resend operations
	const deleteMutation = useMutation( {
		mutationFn: apiDeleteLogs,
		onSuccess: ( response, variables ) => {
			if ( response.success ) {
				toast.success(
					__( 'Logs deleted successfully.', 'suremails' )
				);
				// Invalidate and refetch logs
				queryClient.invalidateQueries( {
					queryKey: [ 'logs' ],
				} );
				// Refetch dashboard data
				queryClient.refetchQueries( {
					queryKey: [ 'dashboard-data' ],
					exact: true,
				} );

				// Return to the previous page if required.
				if (
					logsData.logs.length === variables.length &&
					logsData.logs.length < logsPerPage &&
					page > 1
				) {
					setPage( ( prev ) => Math.max( prev - 1, 1 ) );
				}
			}
		},
		onError: ( deleteError ) => {
			toast.error( __( 'Failed to delete logs.', 'suremails' ), {
				description:
					deleteError.message ||
					__( 'There was an issue deleting logs.', 'suremails' ),
			} );
		},
		onSettled: () => {
			setIsDialogOpen( false );
			setSelectedLogs( [] );
		},
	} );

	const resendMutation = useMutation( {
		mutationFn: apiResendEmails,
		onSuccess: ( response ) => {
			if ( response.success ) {
				toast.success(
					__( 'Email(s) resent successfully.', 'suremails' )
				);

				// Invalidate and refetch logs
				queryClient.invalidateQueries( {
					queryKey: [ 'logs' ],
				} );
				// Refetch dashboard data
				queryClient.refetchQueries( {
					queryKey: [ 'dashboard-data' ],
					exact: true,
				} );
			}
		},
		onError: ( resendError ) => {
			toast.error( __( 'Failed to resend the email(s).', 'suremails' ), {
				description:
					resendError.message ||
					__( 'There was an issue resending emails.', 'suremails' ),
			} );
		},
		onSettled: () => {
			queryClient.invalidateQueries( {
				queryKey: [ 'logs' ],
			} );
			setIsDialogOpen( false );
			setSelectedLogs( [] );
		},
	} );

	// Update the confirmation handlers to use mutations
	const confirmDeleteLogs = async ( logIds ) => {
		await deleteMutation.mutateAsync( logIds );
	};

	const confirmResendEmails = async ( logIds ) => {
		await resendMutation.mutateAsync( logIds );
	};

	// Handle error from React Query
	if ( error ) {
		toast.error( __( 'Failed to fetch logs.', 'suremails' ), {
			description:
				error.message ||
				__( 'There was an issue fetching logs.', 'suremails' ),
		} );
	}

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

	const handleViewDetails = ( log ) => {
		setSelectedLog( log );
		setIsDrawerOpen( true );
	};

	const handleCloseDrawer = () => {
		setIsDrawerOpen( false );
		setSelectedLog( null );
	};

	const handleSelectLog = ( logId ) => {
		setSelectedLogs( ( prevSelected ) =>
			prevSelected.includes( logId )
				? prevSelected.filter( ( id ) => id !== logId )
				: [ ...prevSelected, logId ]
		);
	};

	const handleSelectAll = () => {
		setSelectedLogs(
			selectedLogs.length === logs.length
				? []
				: logs.map( ( log ) => log.id )
		);
	};

	// Determine if EmptyLogs condition is met
	const isEmptyLogs =
		! isLoading &&
		logs.length === 0 &&
		! selectedDates.from &&
		! selectedDates.to &&
		! filter &&
		! debouncedSearchTerm; // Include searchTerm

	// Conditional Rendering: If EmptyLogs condition is true, render only EmptyLogs
	if ( isEmptyLogs ) {
		return (
			<div className="min-h-screen p-6 overflow-hidden bg-background-secondary">
				<EmptyLogs />
			</div>
		);
	}

	// Define the handleResendSuccess callback
	const handleResendSuccess = () => {
		setIsDrawerOpen( false );
		queryClient.invalidateQueries( {
			queryKey: [ 'logs' ],
		} );
	};

	// Determine the confirm button text based on the dialog title
	const getConfirmButtonText = () => {
		const titleLower = dialogConfig.title.toLowerCase();
		if ( titleLower.includes( 'resend' ) ) {
			return __( 'Resend', 'suremails' );
		}
		if ( titleLower.includes( 'deletion' ) ) {
			return __( 'Delete', 'suremails' );
		}
		return __( 'Confirm', 'suremails' );
	};

	// Handler Functions for Pagination
	const handlePageChange = ( newPage ) => {
		setPage( newPage );
	};

	/**
	 * Determine if the Resend button should be disabled based on the selected logs.
	 * The button should be disabled if any of the selected logs are in pending status.
	 */
	const isResendDisabled = selectedLogs.some( ( id ) => {
		const log = logs.find( ( logItem ) => logItem.id === id );
		return log && get_pending_status( log.status );
	} );

	// Conditional Rendering: Determine what to display based on loading and logs
	let content;

	if ( isLoading ) {
		content = <LogsSkeleton />;
	} else if (
		logs.length === 0 &&
		( selectedDates.from ||
			selectedDates.to ||
			filter ||
			debouncedSearchTerm )
	) {
		content = (
			<NoFilteredLogs
				startDate={ selectedDates.from }
				endDate={ selectedDates.to }
				filter={ filter }
				searchTerm={ debouncedSearchTerm } // Pass searchTerm if needed
				setSelectedDates={ setSelectedDates }
				setFilter={ setFilter }
				setPage={ setPage }
			/>
		);
	} else {
		content = (
			<Table className="bg-background-primary" checkboxSelection>
				<Table.Head
					className="bg-background-secondary"
					onChangeSelection={ handleSelectAll }
					indeterminate={
						selectedLogs.length > 0 &&
						selectedLogs.length < logs.length
					}
					selected={ selectedLogs?.length > 0 }
				>
					<Table.HeadCell>
						{ __( 'Subject', 'suremails' ) }
					</Table.HeadCell>
					<Table.HeadCell className="w-1/8">
						{ __( 'Status', 'suremails' ) }
					</Table.HeadCell>
					<Table.HeadCell className="w-1/6">
						{ __( 'Email To', 'suremails' ) }
					</Table.HeadCell>
					<Table.HeadCell>
						{ __( 'Connection', 'suremails' ) }
					</Table.HeadCell>
					<Table.HeadCell className="w-1/6">
						{ __( 'Date & Time', 'suremails' ) }
					</Table.HeadCell>
					<Table.HeadCell className="w-12">
						<span className="sr-only">
							{ __( 'Actions', 'suremails' ) }
						</span>
					</Table.HeadCell>
				</Table.Head>
				<Table.Body>
					{ logs.map( ( log ) => (
						<Table.Row
							key={ log.id }
							className="whitespace-nowrap"
							selected={ selectedLogs.includes( log.id ) }
							onChangeSelection={ () =>
								handleSelectLog( log.id )
							}
						>
							<Table.Cell>
								<TruncatedTooltipText
									text={ log.subject }
									className="max-w-[21.875rem]"
								/>
							</Table.Cell>
							<Table.Cell>
								<Badge
									className="max-w-fit"
									label={ getStatusLabel(
										log.status,
										log?.response
									) }
									variant={ getStatusVariant(
										log.status,
										log?.response
									) }
									size="sm"
									disableHover
								/>
							</Table.Cell>
							<Table.Cell>
								<TruncatedTooltipText text={ log.email_to } />
							</Table.Cell>
							<Table.Cell>
								<Badge
									className="inline-block"
									label={ log.connection }
									variant="blue"
									size="sm"
									disableHover
								/>
							</Table.Cell>
							<Table.Cell>
								{ formatDate( log.updated_at, {
									day: true,
									month: true,
									year: true,
									hour: true,
									minute: true,
									hour12: true,
								} ) }
							</Table.Cell>
							<Table.Cell>
								<div className="flex justify-end gap-2">
									<Tooltip
										content={ __(
											'Resend Email',
											'suremails'
										) }
										position="top"
										arrow
									>
										<Button
											className="text-icon-secondary hover:text-icon-primary"
											size="xs"
											onClick={ () =>
												handleResend( [ log.id ] )
											}
											icon={ <ResendIcon /> }
											variant="ghost"
											aria-label={ __(
												'Resend',
												'suremails'
											) }
											disabled={ get_pending_status(
												log?.status
											) }
										/>
									</Tooltip>

									<Tooltip
										content={ __(
											'Delete Log',
											'suremails'
										) }
										position="top"
										arrow
									>
										<Button
											className="text-icon-secondary hover:text-icon-primary"
											size="xs"
											onClick={ () =>
												handleDelete( [ log.id ] )
											}
											icon={ <DeleteIcon /> }
											variant="ghost"
											aria-label={ __(
												'Delete',
												'suremails'
											) }
										/>
									</Tooltip>
									<Tooltip
										content={ __(
											'View Details',
											'suremails'
										) }
										position="top"
										arrow
									>
										<Button
											className="text-icon-secondary hover:text-icon-primary"
											size="xs"
											onClick={ () =>
												handleViewDetails( log )
											}
											icon={ <EyeIcon /> }
											variant="ghost"
											aria-label={ __(
												'View Details',
												'suremails'
											) }
											disabled={
												log.status === 'pending'
											}
										/>
									</Tooltip>
								</div>
							</Table.Cell>
						</Table.Row>
					) ) }
				</Table.Body>
				<Table.Footer className="flex items-center justify-between">
					{ /* Pagination with Page Label */ }
					{ /* Page Label - aligned to the right */ }
					<div className="text-sm font-normal text-text-secondary whitespace-nowrap">
						{ __( 'Page', 'suremails' ) } { page }{ ' ' }
						{ __( 'out of', 'suremails' ) } { totalPages }
					</div>
					{ /* Pagination Controls - aligned to the left */ }
					<div className="flex items-center space-x-2">
						<Pagination size="sm">
							<Pagination.Content className="[&>li]:m-0">
								<Pagination.Previous
									tag="button"
									onClick={ () =>
										setPage( ( prev ) =>
											Math.max( prev - 1, 1 )
										)
									}
									disabled={ page === 1 }
								/>
								{ getPaginationRange( page, totalPages, 1 ).map(
									( item, index ) => {
										if ( item === 'ellipsis' ) {
											return (
												<Pagination.Ellipsis
													key={ `ellipsis-${ index }` }
												/>
											);
										}
										return (
											<Pagination.Item
												key={ item }
												isActive={ page === item }
												onClick={ () =>
													handlePageChange( item )
												}
											>
												{ item }
											</Pagination.Item>
										);
									}
								) }
								<Pagination.Next
									tag="button"
									onClick={ () =>
										setPage( ( prev ) =>
											Math.min( prev + 1, totalPages )
										)
									}
									disabled={ page === totalPages }
								/>
							</Pagination.Content>
						</Pagination>
					</div>
				</Table.Footer>
			</Table>
		);
	}

	// Handler Functions for DatePicker
	const handleDateApply = ( dates ) => {
		const { from, to } = dates;

		if ( from && to ) {
			const fromDate = new Date( from );
			const toDate = new Date( to );

			if ( fromDate > toDate ) {
				// Swap the dates to ensure 'from' is earlier than 'to'
				setSelectedDates( { from: to, to: from } );
			} else {
				setSelectedDates( dates );
			}
		} else {
			setSelectedDates( { from, to: null } );
		}

		setIsDatePickerOpen( false );
		setPage( 1 );
	};

	const handleDateCancel = () => {
		setIsDatePickerOpen( false );
	};

	// Handler Functions for ConfirmationDialog
	const handleDelete = ( logIds ) => {
		setDialogConfig( {
			title: __( 'Confirm Deletion', 'suremails' ),
			description: __(
				'Are you sure you want to delete the selected log(s)? This action cannot be undone.',
				'suremails'
			),
			onConfirm: () => confirmDeleteLogs( logIds ),
			destructiveConfirmButton: true,
		} );
		setIsDialogOpen( true );
	};

	const handleResend = ( logIds ) => {
		setDialogConfig( {
			title: __( 'Confirm Resend', 'suremails' ),
			description: __(
				'Are you sure you want to resend the selected email(s)?',
				'suremails'
			),
			onConfirm: () => confirmResendEmails( logIds ),
			destructiveConfirmButton: false,
		} );
		setIsDialogOpen( true );
	};

	return (
		<>
			<div className="min-h-screen px-8 py-8 bg-background-secondary">
				<div className="p-4 space-y-2 border-0.5 border-solid shadow-sm bg-background-primary rounded-xl border-border-subtle">
					<div>
						{ /* Filters or Batch Actions */ }
						<div className="flex items-center justify-between p-1.25">
							<Title
								title={ __( 'Email Logs', 'suremails' ) }
								tag="h1"
							/>
							<div className="flex space-x-4">
								{ selectedLogs.length > 0 ? (
									// Batch Action Buttons
									<>
										<Button
											variant="primary"
											icon={ <ResendIcon /> }
											size="sm"
											onClick={ () =>
												handleResend( selectedLogs )
											}
											className="font-medium"
											disabled={ isResendDisabled }
										>
											{ __(
												'Resend Emails',
												'suremails'
											) }
										</Button>
										<Button
											variant="outline"
											icon={ <DeleteIcon /> }
											size="sm"
											onClick={ () =>
												handleDelete( selectedLogs )
											}
											destructive
										>
											{ __( 'Delete', 'suremails' ) }
										</Button>
									</>
								) : (
									// Filter Controls
									<>
										{ /* Conditionally Render Reset Filters Button */ }
										{ ( selectedDates.from ||
											selectedDates.to ||
											filter ||
											searchTerm ) && (
											<Button
												variant="link"
												size="sm"
												icon={ <X /> }
												onClick={ () => {
													setSelectedDates( {
														from: null,
														to: null,
													} );
													setFilter( '' );
													setSearchTerm( '' );
													setPage( 1 );
												} }
												destructive
												className="leading-4 no-underline hover:no-underline min-w-fit focus:[box-shadow:none]"
											>
												{ __(
													'Clear Filters',
													'suremails'
												) }
											</Button>
										) }

										<Input
											className="w-52"
											type="text"
											size="sm"
											onChange={ setSearchTerm }
											value={ searchTerm }
											placeholder={ __(
												'Search…',
												'suremails'
											) }
											required
											prefix={
												<Search className="text-icon-secondary" />
											}
										/>

										{ /* Status Filter */ }
										<Select
											value={ filter }
											onChange={ setFilter }
											size="sm"
										>
											<Select.Button
												className="w-52 h-[2rem] [&_div]:text-xs"
												placeholder={ __(
													'Status',
													'suremails'
												) }
											>
												{ ( { value: renderValue } ) =>
													renderValue
														? getStatusLabel(
																renderValue
														  )
														: __(
																'Select Status',
																'suremails'
														  )
												}
											</Select.Button>
											<Select.Portal
												id="suremails-root-app"
												className="z-999999"
											>
												<Select.Options>
													{ STATUS_FILTERS.map(
														( option ) => (
															<Select.Option
																key={
																	option.value
																}
																value={
																	option.value
																}
																className="text-xs"
															>
																{ option.label }
															</Select.Option>
														)
													) }
												</Select.Options>
											</Select.Portal>
										</Select>
										{ /* Date Range Picker */ }
										<div
											className="relative"
											ref={ containerRef }
										>
											<Input
												type="text"
												size="sm"
												value={ getSelectedDate(
													selectedDates
												) }
												suffix={
													<Calendar className="text-icon-secondary" />
												}
												onClick={ () =>
													setIsDatePickerOpen(
														! isDatePickerOpen
													)
												}
												placeholder={ __(
													'mm/dd/yyyy - mm/dd/yyyy',
													'suremails'
												) }
												className="cursor-pointer w-52"
												readOnly
											/>
											{ isDatePickerOpen && (
												<div className="absolute right-0 z-10 mt-2 rounded-lg shadow-lg">
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
														showOutsideDays={
															false
														}
														variant="presets"
														onApply={
															handleDateApply
														}
														onCancel={
															handleDateCancel
														}
														selected={
															selectedDates
														}
													/>
												</div>
											) }
										</div>
									</>
								) }
							</div>
						</div>
					</div>

					{ /* Conditional Rendering Based on Loading and Logs */ }
					<div className="overflow-hidden bg-background-primary">
						{ content }
					</div>
				</div>
			</div>

			{ /* Confirmation Dialog */ }
			<ConfirmationDialog
				isOpen={ isDialogOpen }
				title={ dialogConfig.title }
				description={ dialogConfig.description }
				onConfirm={ dialogConfig.onConfirm }
				onCancel={ () => setIsDialogOpen( false ) }
				confirmButtonText={ getConfirmButtonText() }
				cancelButtonText={ __( 'Cancel', 'suremails' ) }
				destructiveConfirmButton={
					!! dialogConfig?.destructiveConfirmButton
				}
			/>

			{ /* Email Log Drawer */ }
			<EmailLogDrawer
				isOpen={ selectedLog && isDrawerOpen }
				setOpen={ setIsDrawerOpen }
				log={ selectedLog }
				onClose={ handleCloseDrawer }
				onResendSuccess={ handleResendSuccess } // Pass the callback here
			/>
		</>
	);
};

export default Logs;
