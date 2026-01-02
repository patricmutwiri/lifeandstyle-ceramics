import { useState, useEffect, useCallback } from '@wordpress/element';
import { useLocation, useNavigate } from 'react-router-dom';
import { __ } from '@wordpress/i18n';
import { Button, toast, Table } from '@bsf/force-ui';
import { Trash, PenLine, Plus, ChevronsUpDown } from 'lucide-react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import ConnectionsSkeleton from './connections-skeleton';
import ProvidersDrawer from '@screens/connections/providers-drawer';
import TestEmailDrawer from '@screens/connections/test-email-drawer';
import NoConnection from '@screens/connections/no-connections';
import ConfirmationDialog from '@components/confirmation-dialog/confirmation-dialog';
import {
	fetchSettings,
	deleteConnection as apiDeleteConnection,
} from '@api/connections';
import { formatDate, sortData } from '@utils/utils';
import Tooltip from '@components/tooltip/tooltip';
import TruncatedTooltipText from '@components/truncated-tooltip-text';
import Title from '@components/title/title';
import useProviders from './use-dynamic-providers';

const Connections = () => {
	const [ isDrawerOpen, setIsDrawerOpen ] = useState( false );
	const [ isTestEmailDrawerOpen, setIsTestEmailDrawerOpen ] =
		useState( false );
	const [ currentConnection, setCurrentConnection ] = useState( null );
	const [ sortDirection, setSortDirection ] = useState( 'asc' );
	const [ isDialogOpen, setIsDialogOpen ] = useState( false );
	const [ dialogConfig, setDialogConfig ] = useState( {
		title: '',
		description: '',
		onConfirm: null,
	} );

	const queryClient = useQueryClient();
	const location = useLocation();
	const navigate = useNavigate();

	useEffect( () => {
		if ( location.state?.openDrawer ) {
			setIsDrawerOpen( true );

			const storedFormState =
				JSON.parse( localStorage.getItem( 'formStateValues' ) ) || {};

			const expirationTime = Date.now();
			const stored = localStorage.getItem( 'formStateValuesTimestamp' );
			const storedTime = parseInt( stored, 10 );

			if ( storedTime && storedTime < expirationTime ) {
				localStorage.removeItem( 'formStateValues' );
				localStorage.removeItem( 'formStateValuesTimestamp' );
			} else {
				setCurrentConnection( storedFormState );
				localStorage.removeItem( 'formStateValues' );
				localStorage.removeItem( 'formStateValuesTimestamp' );
			}
			navigate( location.pathname, { replace: true, state: {} } );
		}
	}, [ location.state, navigate ] );

	// Query for fetching connections
	const {
		data: settings,
		isLoading,
		error,
	} = useQuery( {
		queryKey: [ 'settings' ],
		queryFn: fetchSettings,
		select: ( data ) => data?.data || {},
		refetchInterval: 100000, // Refetch every 10 minutes
		refetchOnMount: false,
		refetchOnWindowFocus: false,
		refetchOnReconnect: true,
	} );
	const { providers: providersList, isLoading: isProvidersLoading } =
		useProviders();

	const getNewConnectionSequenceId = useCallback( () => {
		const connectionCount = Math.max(
			Object.values( settings?.connections ).length,
			0
		);

		return ( connectionCount + 1 ) * 10;
	}, [ settings?.connections ] );

	const getNewConnectionCount = useCallback( () => {
		const count = {};

		Object.values( settings?.connections ).forEach( ( connectionItem ) => {
			count[ connectionItem.type ] =
				( count[ connectionItem.type ] || 0 ) + 1;
		} );

		return count;
	}, [ settings?.connections ] );

	// Mutation for deleting connections
	const deleteMutation = useMutation( {
		mutationFn: apiDeleteConnection,
		onSuccess: ( response, connection ) => {
			if ( response.success ) {
				toast.success( __( 'Deleted!', 'suremails' ), {
					description: __(
						'Connection deleted successfully.',
						'suremails'
					),
				} );

				// Update cache by removing the deleted connection
				queryClient.setQueryData( [ 'settings' ], ( oldData ) => ( {
					...oldData,
					data: {
						...oldData.data,
						connections: Object.fromEntries(
							Object.entries( oldData.data.connections ).filter(
								( [ key ] ) => key !== connection.id
							)
						),
					},
				} ) );

				// Refetch logs and dashboard data
				queryClient.refetchQueries( {
					queryKey: [ 'logs' ],
				} );
				// Refetch dashboard data
				queryClient.refetchQueries( {
					queryKey: [ 'dashboard-data' ],
					exact: true,
				} );
				// Refetch settings
				queryClient.invalidateQueries( {
					queryKey: [ 'settings' ],
				} );
			}
		},
		onError: ( delError ) => {
			toast.error( __( 'Error deleting connection', 'suremails' ), {
				description:
					delError.message ||
					__(
						'There was an issue deleting the connection.',
						'suremails'
					),
			} );
		},
		onSettled: () => {
			setIsDialogOpen( false );
		},
	} );

	// Update connections cache when a connection is added or edited
	const updateConnections = () => {
		// Invalidate the settings query to trigger a refetch
		queryClient.invalidateQueries( {
			queryKey: [ 'settings' ],
		} );
		// Refetch dashboard data
		queryClient.refetchQueries( {
			queryKey: [ 'dashboard-data' ],
		} );
	};

	// Sort functionality
	const sortTableData = () => {
		const newDirection = sortDirection === 'asc' ? 'desc' : 'asc';
		setSortDirection( newDirection );
	};

	const renderSortedConnections = () => {
		const sortedConnections = sortData(
			Object.entries( settings?.connections || {} ).map(
				( [ key, value ] ) => ( { id: key, ...value } )
			),
			'created_at',
			sortDirection
		);

		return sortedConnections;
	};

	const handleDeleteConnection = ( connection ) => {
		setDialogConfig( {
			title: __( 'Confirm Deletion', 'suremails' ),
			description: __(
				'Are you sure you want to delete this connection? This action cannot be undone.',
				'suremails'
			),
			requireConfirmation: true,
			onConfirm: () => confirmDelete( connection ),
		} );
		setIsDialogOpen( true );
	};

	const confirmDelete = async ( connection ) => {
		await deleteMutation.mutateAsync( connection );
	};

	const handleTestEmail = ( connection ) => {
		setCurrentConnection( connection );
		setIsTestEmailDrawerOpen( true );
	};

	const handleEditConnection = ( connection ) => {
		setCurrentConnection( connection );
		setIsDrawerOpen( true );
	};

	// Show loading state
	if ( isLoading ) {
		return <ConnectionsSkeleton />;
	}

	// Show error state
	if ( error ) {
		toast.error( __( 'Error loading connections', 'suremails' ), {
			description:
				error.message ||
				__( 'There was an issue fetching connections.', 'suremails' ),
		} );
	}

	// Define table headers with sorting capability
	const headers = [
		{
			label: __( 'Connection', 'suremails' ),
		},
		{
			label: __( 'Connection Title', 'suremails' ),
		},
		{
			label: __( 'Email', 'suremails' ),
		},
		{
			label: __( 'Created On', 'suremails' ),
			sortable: true,
		},
		{
			label: __( 'Test Email', 'suremails' ),
		},
		{
			label: __( 'Action', 'suremails' ),
			srOnly: true,
		},
	];

	return (
		<div className="flex items-start justify-center h-full px-8 py-8 bg-background-secondary">
			<div className="w-full h-auto px-4 py-4 space-y-2 border-0.5 border-solid shadow-sm opacity-100 rounded-xl border-border-subtle bg-background-primary">
				{ /* Header */ }
				{ renderSortedConnections().length > 0 && (
					<div className="flex items-center justify-between w-full gap-2 px-2 py-2.25 opacity-100">
						<Title
							title={ __( 'Email Connections', 'suremails' ) }
							tag="h1"
						/>
						<Button
							variant="primary"
							size="sm"
							icon={ <Plus /> }
							iconPosition="left"
							onClick={ () => setIsDrawerOpen( true ) }
							className="font-medium"
						>
							{ __( 'Add Connection', 'suremails' ) }
						</Button>
					</div>
				) }

				{ /* Content Area */ }
				{ renderSortedConnections().length > 0 ? (
					<Table>
						<Table.Head className="bg-background-secondary">
							{ headers.map( ( header, index ) => (
								<Table.HeadCell
									key={ index }
									className="whitespace-nowrap"
								>
									{ header?.sortable ? (
										<div
											key="created-at"
											className="flex items-center cursor-pointer"
											onClick={ sortTableData }
										>
											{ header.label }
											<ChevronsUpDown className="ml-1 size-4" />
										</div>
									) : (
										<span
											className={
												header?.srOnly ? 'sr-only' : ''
											}
										>
											{ header.label }
										</span>
									) }
								</Table.HeadCell>
							) ) }
						</Table.Head>
						<Table.Body>
							{ renderSortedConnections().map( ( row ) => (
								<Table.Row
									key={ row.id } // Use unique identifier for key
								>
									<Table.Cell className="w-32">
										<div className="flex items-center">
											{
												providersList.find(
													( provider ) =>
														provider.value ===
														row?.type
												)?.icon
											}
										</div>
									</Table.Cell>
									<Table.Cell>
										<TruncatedTooltipText
											className="max-w-64"
											text={ `${ row.connection_title } - ${ row.type }` }
										/>
									</Table.Cell>
									<Table.Cell>
										<TruncatedTooltipText
											className="max-w-60"
											text={ row.from_email }
										/>
									</Table.Cell>
									<Table.Cell className="text-nowrap">
										{ formatDate( row.created_at, {
											day: true,
											month: true,
											year: true,
											hour: true,
											minute: true,
											hour12: true,
										} ) }
									</Table.Cell>
									<Table.Cell>
										{ /* Send Test Email Button */ }
										<Button
											variant="outline"
											size="xs"
											className="shadow-sm whitespace-nowrap"
											onClick={ () =>
												handleTestEmail( row )
											}
										>
											{ __(
												'Send Test Email',
												'suremails'
											) }
										</Button>
										<div className="inline-flex items-center justify-between gap-2">
											{ /* Action Buttons with Tooltips */ }
										</div>
									</Table.Cell>
									<Table.Cell>
										<div className="inline-flex justify-end w-full gap-2">
											{ /* Edit Button with Tooltip */ }
											<Tooltip
												content={ __(
													'Edit',
													'suremails'
												) }
												position="top"
												arrow
											>
												<Button
													variant="ghost"
													size="xs"
													icon={ <PenLine /> }
													iconPosition="left"
													onClick={ () =>
														handleEditConnection(
															row
														)
													}
												/>
											</Tooltip>

											<Tooltip
												content={
													<span>
														{ __(
															'Delete',
															'suremails'
														) }
													</span>
												}
												position="top"
												variant="dark"
												arrow
											>
												<Button
													variant="ghost"
													size="xs"
													icon={ <Trash /> }
													iconPosition="left"
													onClick={ () =>
														handleDeleteConnection(
															row
														)
													}
												/>
											</Tooltip>
										</div>
									</Table.Cell>
								</Table.Row>
							) ) }
						</Table.Body>
					</Table>
				) : (
					<NoConnection
						onClickAddConnection={ () => setIsDrawerOpen( true ) }
					/>
				) }

				{ /* Providers Drawer */ }
				<ProvidersDrawer
					isOpen={ isDrawerOpen }
					setIsOpen={ setIsDrawerOpen }
					currentConnection={ currentConnection } // Preselect provider with form data
					onSave={ updateConnections }
					providers={ providersList }
					isProvidersLoading={ isProvidersLoading }
					sequenceId={ getNewConnectionSequenceId() }
					connectionCount={ getNewConnectionCount() }
				/>

				{ /* Test Email Drawer */ }
				<TestEmailDrawer
					isOpen={ isTestEmailDrawerOpen }
					onClose={ ( value ) => {
						setIsTestEmailDrawerOpen( false );
						if ( ! value ) {
							setCurrentConnection( null );
						}
					} }
					connections={ settings.connections }
					currentConnection={ currentConnection }
				/>

				{ /* Confirmation Dialog */ }
				<ConfirmationDialog
					isOpen={ isDialogOpen }
					title={ dialogConfig.title }
					description={ dialogConfig.description }
					onConfirm={ dialogConfig.onConfirm }
					onCancel={ () => setIsDialogOpen( false ) }
					confirmButtonText={ __( 'Delete', 'suremails' ) } // Customize button text if needed
					cancelButtonText={ __( 'Cancel', 'suremails' ) }
					requireConfirmation={ dialogConfig.requireConfirmation }
				/>
			</div>
		</div>
	);
};

export default Connections;
