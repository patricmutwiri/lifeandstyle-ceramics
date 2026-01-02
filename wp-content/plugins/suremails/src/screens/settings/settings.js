// File: src/components/Settings.js
import { useLayoutEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	Button,
	Switch,
	Select,
	Label,
	Skeleton,
	toast,
	Tooltip,
} from '@bsf/force-ui';
import { fetchSettings, saveSettings } from '@api/settings';
import SettingsSkeleton from './settings-skeleton';
import Title from '@components/title/title';
import { Info, LoaderCircle as LoaderIcon } from 'lucide-react'; // Added LoaderIcon and ChevronLeftIcon
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import SafeGuardSection from './safe-guard-section';

const Settings = () => {
	const { data: settingsData, isLoading: isSettingsLoading } = useQuery( {
		queryKey: [ 'settings' ],
		queryFn: fetchSettings,
		select: ( response ) => response.data,
		refetchInterval: 100000, // Refetch every 10 minutes
		refetchOnMount: false,
		refetchOnWindowFocus: false,
		refetchOnReconnect: true,
	} );

	const queryClient = useQueryClient();

	const { mutate: saveSettingsMutation, isPending: isSaving } = useMutation( {
		mutationFn: saveSettings,
		onSuccess: ( data ) => {
			queryClient.setQueryData( [ 'settings' ], data );
			toast.success( __( 'Settings saved successfully', 'suremails' ), {
				description: __( 'Your changes have been saved.', 'suremails' ),
			} );
		},
		onError: ( error ) => {
			toast.error( __( 'Error saving settings', 'suremails' ), {
				description:
					error.message ||
					__(
						'There was an issue saving the settings.',
						'suremails'
					),
			} );
		},
	} );

	const [ formState, setFormState ] = useState( {
		logEmails: false,
		deleteEmailLogsAfter: '30_days',
		defaultConnection: {
			type: '',
			email: '',
			id: '',
			connection_title: '',
		},
		emailSimulation: false,
		emailSummary: false,
		emailSummaryDay: 'monday',
		analytics: false,
	} );

	useLayoutEffect( () => {
		if ( settingsData ) {
			// Update form state with fetched data
			setFormState( {
				logEmails: settingsData.log_emails === 'yes',
				deleteEmailLogsAfter:
					settingsData.log_emails === 'yes'
						? settingsData.delete_email_logs_after || '30_days'
						: 'none',
				defaultConnection: {
					type: settingsData?.default_connection?.type || '',
					email: settingsData?.default_connection?.email || '',
					id: settingsData?.default_connection?.id || '',
					connection_title:
						settingsData?.default_connection?.connection_title ||
						'',
				},
				emailSimulation: settingsData.email_simulation === 'yes',
				emailSummary: settingsData?.emailSummary === 'yes',
				emailSummaryDay: settingsData?.emailSummaryDay || 'monday',
				analytics: settingsData.analytics === 'yes',
			} );
		}
	}, [ settingsData ] );

	// Define Delete Logs Options Array
	const deleteLogsOptions = [
		{ label: __( 'Delete after 1 day', 'suremails' ), value: '1_day' },
		{ label: __( 'Delete after 7 days', 'suremails' ), value: '7_days' },
		{ label: __( 'Delete after 30 days', 'suremails' ), value: '30_days' },
		{ label: __( 'Delete after 60 days', 'suremails' ), value: '60_days' },
		{ label: __( 'Delete after 90 days', 'suremails' ), value: '90_days' },
		{ label: __( 'Never', 'suremails' ), value: 'none' },
	];

	/**
	 * Summary Schedule Options
	 */
	const SUMMARY_DAYS = [
		{ label: __( 'Monday', 'suremails' ), value: 'monday' },
		{ label: __( 'Tuesday', 'suremails' ), value: 'tuesday' },
		{ label: __( 'Wednesday', 'suremails' ), value: 'wednesday' },
		{ label: __( 'Thursday', 'suremails' ), value: 'thursday' },
		{ label: __( 'Friday', 'suremails' ), value: 'friday' },
		{ label: __( 'Saturday', 'suremails' ), value: 'saturday' },
		{ label: __( 'Sunday', 'suremails' ), value: 'sunday' },
	];

	const handleChange = ( field, value ) => {
		setFormState( ( prevState ) => ( {
			...prevState,
			[ field ]: value,
		} ) );
	};

	const hasChanges = () => {
		return (
			formState.logEmails !== ( settingsData?.log_emails === 'yes' ) ||
			formState.deleteEmailLogsAfter !==
				settingsData?.delete_email_logs_after ||
			formState.defaultConnection.id !==
				settingsData?.default_connection?.id ||
			formState.emailSimulation !==
				( settingsData?.email_simulation === 'yes' ) ||
			formState.analytics !== ( settingsData?.analytics === 'yes' ) ||
			formState.emailSummary !==
				( settingsData?.emailSummary === 'yes' ) ||
			formState.emailSummaryDay !== settingsData?.emailSummaryDay
		);
	};

	const handleSave = async () => {
		if ( ! hasChanges() ) {
			toast.info( __( 'No changes to save.', 'suremails' ) );
			return;
		}

		const updatedSettings = {
			settings: {
				delete_email_logs_after: formState.deleteEmailLogsAfter,
				email_simulation: formState.emailSimulation ? 'yes' : 'no',
				log_emails: formState.logEmails ? 'yes' : 'no',
				analytics: formState.analytics ? 'yes' : 'no',
				emailSummary: formState.emailSummary ? 'yes' : 'no',
				emailSummaryDay: formState.emailSummaryDay,
				default_connection: formState.defaultConnection.email
					? formState.defaultConnection
					: {
							type: '',
							email: '',
							id: '',
							connection_title: '',
					  },
			},
		};

		saveSettingsMutation( updatedSettings );
	};

	// Prepare connection options formatted as Email (Provider) for the default connection
	const defaultConnectionOptions = Object.entries(
		settingsData?.connections || {}
	).map( ( [ key, connection ] ) => ( {
		label: `${ connection.connection_title } - ${ connection.type } : ${ connection.from_email }`,
		value: {
			id: key,
			email: connection.from_email,
			type: connection.type,
			connection_title: connection.connection_title,
		},
	} ) );

	// Find the label for the selected connection value
	const getConnectionLabel = ( connection ) => {
		return connection?.email
			? `${ connection.connection_title } - ${ connection.type } : ${ connection.email }`
			: __( 'None', 'suremails' );
	};
	const getDeleteLogsLabel = ( value ) => {
		const label = deleteLogsOptions.find(
			( option ) => option.value === value
		);
		return label ? label.label : '';
	};

	const getemailSummaryDayLabel = ( value ) =>
		SUMMARY_DAYS.find( ( d ) => d.value === value )?.label || '';
	// Handle loading state
	if ( isSettingsLoading ) {
		return <SettingsSkeleton />;
	}

	// Determine if there are connections available
	const hasConnections = defaultConnectionOptions.length > 0;

	return (
		<>
			<div className="flex flex-col gap-6 p-8 overflow-hidden overflow-x-hidden overflow-y-hidden">
				<div className="flex items-center justify-between max-w-settings-container w-full h-auto gap-2 mx-auto">
					<Title
						size="md"
						title={ __( 'General Settings', 'suremails' ) }
						tag="h1"
					/>

					<Button
						onClick={ handleSave }
						variant="primary"
						size="md"
						className="font-medium"
						loading={ isSaving }
						icon={
							isSaving ? (
								<LoaderIcon className="mr-2 animate-spin" />
							) : null
						}
					>
						{ isSaving
							? __( 'Saving…', 'suremails' )
							: __( 'Save', 'suremails' ) }
					</Button>
				</div>
				<div className="px-6 py-6 bg-background-primary rounded-xl shadow-sm max-w-settings-container w-full h-auto gap-4 opacity-100 mx-auto mt-2 flex flex-col">
					{ /* Log Emails */ }
					<div className="flex w-[648px] gap-3">
						<Switch
							checked={ formState.logEmails }
							onChange={ ( value ) => {
								handleChange( 'logEmails', value );
								if ( ! value ) {
									handleChange(
										'deleteEmailLogsAfter',
										'none'
									);
								}
							} }
							size="sm"
							label={ {
								heading: __( 'Log Emails', 'suremails' ),
								description: __(
									'Enable to log all outgoing emails for reference.',
									'suremails'
								),
							} }
						/>
					</div>

					{ /* Log Emails and Delete Logs */ }
					{ formState.logEmails && (
						<div className="flex flex-col w-full h-auto gap-1.5">
							<Select
								value={ formState.deleteEmailLogsAfter }
								onChange={ ( value ) =>
									handleChange(
										'deleteEmailLogsAfter',
										value
									)
								}
								className="w-full h-auto"
							>
								<Select.Button
									label={
										<div className="flex items-center">
											<Label tag="span" size="sm">
												{ __(
													'Delete Logs',
													'suremails'
												) }
											</Label>
											<Tooltip
												arrow
												content={
													<span>
														{ __(
															'Email logs stored in the database will be deleted after the selected duration automatically.',
															'suremails'
														) }
													</span>
												}
												placement="bottom"
												title={ __(
													'Delete Logs',
													'suremails'
												) }
												triggers={ [ 'hover' ] }
												variant="dark"
												tooltipPortalRoot="suremails-root-app"
												tooltipPortalId="suremails-root-app"
											>
												<Info className="w-4 h-4 ml-1 cursor-pointer text-icon-secondary" />
											</Tooltip>
										</div>
									}
								>
									{ getDeleteLogsLabel(
										formState.deleteEmailLogsAfter
									) }
								</Select.Button>
								<Select.Options className="z-999999">
									{ deleteLogsOptions.map( ( option ) => (
										<Select.Option
											key={ option.value }
											value={ option.value }
										>
											{ option.label }
										</Select.Option>
									) ) }
								</Select.Options>
							</Select>
							<Label tag="p" size="sm" variant="help">
								{ __(
									'Logs will be automatically deleted after the chosen duration.',
									'suremails'
								) }
							</Label>
						</div>
					) }

					<LineSkeleton />
					{ /* Default Connection */ }
					<div className="flex flex-col w-full h-auto gap-1.5">
						<Select
							by="id"
							value={ formState.defaultConnection }
							onChange={ ( value ) =>
								handleChange( 'defaultConnection', value )
							}
							className="w-full h-auto"
							disabled={ ! hasConnections }
						>
							<Select.Button
								label={ __(
									'Default Connection',
									'suremails'
								) }
							>
								{ getConnectionLabel(
									formState.defaultConnection
								) }
							</Select.Button>
							<Select.Options className="z-999999">
								{ defaultConnectionOptions.map( ( option ) => (
									<Select.Option
										key={ option.value.id }
										value={ option.value }
									>
										{ option.label }
									</Select.Option>
								) ) }
							</Select.Options>
						</Select>
						<Label tag="p" size="sm" variant="help">
							{ __(
								'This connection will be used by default unless a specific "from email" address is provided in the email headers.',
								'suremails'
							) }
						</Label>
					</div>

					<LineSkeleton />

					{ /* Email Summary  */ }
					<div className="flex w-[648px] gap-3">
						<Switch
							checked={ formState.emailSummary }
							onChange={ ( value ) => {
								handleChange( 'emailSummary', value );
							} }
							size="sm"
							label={ {
								heading: __(
									'Enable Email Summary',
									'suremails'
								),
								description: __(
									'Get a weekly summary of emails sent from your site.',
									'suremails'
								),
							} }
						/>
					</div>
					{ formState.emailSummary && (
						<div className="flex flex-col w-full h-auto gap-1.5">
							<Select
								value={ formState.emailSummaryDay }
								onChange={ ( v ) =>
									handleChange( 'emailSummaryDay', v )
								}
								className="w-full h-auto"
							>
								<Select.Button
									label={ __(
										'Schedule Reports',
										'suremails'
									) }
								>
									{ getemailSummaryDayLabel(
										formState.emailSummaryDay
									) }
								</Select.Button>
								<Select.Options className="z-999999">
									{ SUMMARY_DAYS.map( ( opt ) => (
										<Select.Option
											key={ opt.value }
											value={ opt.value }
										>
											{ opt.label }
										</Select.Option>
									) ) }
								</Select.Options>
							</Select>
							<Label tag="p" size="sm" variant="help">
								{ __(
									'Choose the day of the week to receive your summary email.',
									'suremails'
								) }
							</Label>
						</div>
					) }

					{ /* Email Simulation  */ }
					<div className="flex w-[648px] gap-3">
						<Switch
							checked={ formState.emailSimulation }
							onChange={ ( value ) => {
								handleChange( 'emailSimulation', value );
							} }
							size="sm"
							label={ {
								heading: __( 'Email Simulation', 'suremails' ),
								description: __(
									'Disable sending all emails. If you enable this, no email will be sent but the email logs will be recorded here.',
									'suremails'
								),
							} }
						/>
					</div>
					<div className="flex w-[648px] gap-3">
						<Switch
							checked={ formState.analytics }
							onChange={ ( value ) => {
								handleChange( 'analytics', value );
							} }
							size="sm"
							label={ {
								heading: __(
									'Enable Anonymous Analytics',
									'suremails'
								),
								description: (
									<span>
										<span>
											{ __(
												'Collect non-sensitive information from your website, such as the PHP version and features used, to help us fix bugs faster, make smarter decisions, and build features that actually matter to you. ',
												'suremails'
											) }
										</span>
										<a
											href="https://suremails.com/share-usage-data/"
											target="_blank"
											rel="noopener noreferrer"
											className="no-underline hover:no-underline ring-0"
										>
											{ __( 'Learn More', 'suremails' ) }
										</a>
									</span>
								),
							} }
						/>
					</div>
				</div>
				{ /* Safe Guard Settings */ }
				<SafeGuardSection />
			</div>
		</>
	);
};

/*
 * Line Skeleton Component
 * Used to show a divider line between settings sections
 */
const LineSkeleton = () => {
	return (
		<Skeleton
			className="w-full h-px mt-2 mb-2 border opacity-100 border-border-subtle"
			variant="rectangular"
		/>
	);
};

export default Settings;
