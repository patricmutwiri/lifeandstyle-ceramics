import { __, sprintf } from '@wordpress/i18n';
import PageContentWrapper from '@AdminComponents/page-content-wrapper';
import { Button, Loader, Select, Text, toast } from '@bsf/force-ui';
import DeactivatePluginCheckbox from './deactivate-plugin-checkbox';
import {
	PLUGIN_OPTIONS,
	PAGE_TITLE,
	PAGE_DESCRIPTION,
	ACTIVE_MIGRATED_PLUGINS,
} from './constants';
import { ResumeMigration, MigrateDone } from './components';
import { MigrationProgressStatus } from './progress-components';
import MigrationError from './migration-error';
import { useMigration } from './hooks';
import Alert from '@/global/components/alert';
import { useState } from '@wordpress/element';
import { cn } from '@/functions/utils';
import CachePluginAlert from '@/apps/admin-general/advanced/tools/migration/cache-notice';

const DeactivatePluginFooter = () => {
	const [ isDeactivating, setIsDeactivating ] = useState( false );
	const { deactivatePluginAPI, completedPlugins } = useMigration();

	const completedPluginsSlugs = Object.entries( completedPlugins ).map(
		( [ slug ] ) => slug
	);

	const migrationCompletedPlugin = ACTIVE_MIGRATED_PLUGINS.find( ( plugin ) =>
		completedPluginsSlugs.includes( plugin.slug )
	);

	const handleDeactivatePlugin = async () => {
		if ( isDeactivating ) {
			return;
		}
		setIsDeactivating( true );
		await deactivatePluginAPI( migrationCompletedPlugin.slug );
		setIsDeactivating( false );
		toast.success( __( 'Plugin deactivated successfully', 'surerank' ), {
			description: __(
				'The page will automatically refresh in 3 seconds to apply the changes made during the plugin deactivation process.',
				'surerank'
			),
		} );
		// Reload the page after 3 seconds
		setTimeout( () => {
			window.location.reload();
		}, 3000 );
	};

	return (
		<div className="flex justify-start items-center">
			<Button
				variant="link"
				onClick={ handleDeactivatePlugin }
				className={ cn(
					'h-4 hover:no-underline focus:[box-shadow:none]',
					! isDeactivating && '[&>span]:px-0',
					isDeactivating && 'opacity-70 cursor-default'
				) }
				icon={ isDeactivating && <Loader size="sm" /> }
				iconPosition="left"
			>
				{ isDeactivating
					? __( 'Deactivating…', 'surerank' )
					: sprintf(
							/* translators: %s: plugin name */
							__( 'Deactivate %s', 'surerank' ),
							migrationCompletedPlugin.name
					  ) }
			</Button>
		</div>
	);
};

const MigrateToSureRank = () => {
	const {
		state,
		plugin_slug,
		isMigrating,
		isDone,
		error,
		showResume,
		handleSelectPlugin,
		handleMigrate,
		handleResumeMigration,
		handleStartOver,
		handleCancelMigration,
		deactivatePlugin,
		handleChangeDeactivate,
		alreadyMigrated,
	} = useMigration();
	const selectedPlugin = PLUGIN_OPTIONS.find(
		( plugin ) => plugin.slug === plugin_slug
	);

	let content = (
		<>
			{ /* Migration Form */ }
			<div className="flex flex-col gap-4 w-full">
				<div className="flex items-center gap-2 w-full">
					<div className="flex-grow">
						<Select
							onChange={ handleSelectPlugin }
							size="md"
							value={ plugin_slug }
						>
							<Select.Button
								label={ __(
									'Import SEO data from',
									'surerank'
								) }
								placeholder={ __(
									'Select an option',
									'surerank'
								) }
								render={ () => (
									<span>{ selectedPlugin?.name }</span>
								) }
							/>
							<Select.Options>
								{ PLUGIN_OPTIONS.length > 0 ? (
									PLUGIN_OPTIONS.map( ( plugin ) => (
										<Select.Option
											key={ plugin.slug }
											value={ plugin }
										>
											{ plugin.name }
										</Select.Option>
									) )
								) : (
									<Text
										as="div"
										color="help"
										size={ 14 }
										className="text-center p-4"
										aria-label={ __(
											'No supported SEO plugins were found on this website.',
											'surerank'
										) }
									>
										{ __(
											'No supported SEO plugins were found on this website.',
											'surerank'
										) }
									</Text>
								) }
							</Select.Options>
						</Select>
					</div>
					<Button
						variant="primary"
						onClick={ handleMigrate }
						disabled={ isMigrating || ! plugin_slug }
						className="mt-6"
					>
						{ __( 'Migrate', 'surerank' ) }
					</Button>
				</div>

				{ /* Checkbox for deactivation */ }
				{ selectedPlugin &&
					selectedPlugin.active &&
					! isMigrating &&
					! isDone &&
					! error && (
						<DeactivatePluginCheckbox
							plugin={ selectedPlugin }
							value={ deactivatePlugin }
							onChange={ handleChangeDeactivate }
						/>
					) }

				{ error && (
					<MigrationError
						error={ error }
						onRetry={ handleMigrate }
						isDisabled={ isMigrating || ! plugin_slug }
					/>
				) }

				{ /* Migration Progress */ }
				{ isMigrating && (
					<div className="flex flex-col gap-2 mt-2 w-full">
						<MigrationProgressStatus
							migrationData={ state.migrationData }
							pluginSlug={ plugin_slug }
							currentStatus={ state.currentStatus }
							progress={ state.progress }
						/>
					</div>
				) }

				{ /* Migration Completed & Deactivate Plugin Alert */ }
				{ !! ( ACTIVE_MIGRATED_PLUGINS.length && alreadyMigrated ) && (
					<Alert
						color="info"
						title={ __( 'Migration Completed ', 'surerank' ) }
						message={ __(
							'Your SEO settings have been successfully migrated to SureRank. However, we noticed your previous SEO plugin is still active. Would you like us to deactivate it to avoid conflicts?',
							'surerank'
						) }
						footer={ <DeactivatePluginFooter /> }
					/>
				) }
			</div>
		</>
	);

	if ( isDone ) {
		content = <MigrateDone />;
	}

	if ( ! PLUGIN_OPTIONS.length && ! alreadyMigrated ) {
		content = (
			<Alert
				color="success"
				title={ __( 'No Other SEO Plugin Found', 'surerank' ) }
				message={ __(
					"SureRank can migrate settings from many popular SEO plugins. Since no supported plugin is currently installed, there's nothing to migrate.",
					'surerank'
				) }
			/>
		);
	}

	if ( showResume ) {
		content = (
			<ResumeMigration
				onResume={ handleResumeMigration }
				onStartOver={ handleStartOver }
				onCancel={ handleCancelMigration }
			/>
		);
	}

	return (
		<PageContentWrapper
			title={ PAGE_TITLE }
			description={ PAGE_DESCRIPTION }
		>
			<CachePluginAlert />
			<div className="flex flex-col items-start p-6 gap-2 bg-white shadow-sm rounded-xl order-1 flex-none flex-grow-0">
				{ content }
			</div>
		</PageContentWrapper>
	);
};

export default MigrateToSureRank;
