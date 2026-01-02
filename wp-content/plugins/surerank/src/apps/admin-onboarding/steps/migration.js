import { __ } from '@wordpress/i18n';
import { Loader, RadioButton, Text } from '@bsf/force-ui';
import { PLUGIN_OPTIONS } from '@AdminGeneral/advanced/tools/migration/constants';
import DeactivatePluginCheckbox from '@AdminGeneral/advanced/tools/migration/deactivate-plugin-checkbox';
import { MigrationProgressStatus } from '@AdminGeneral/advanced/tools/migration/progress-components';
import {
	MigrateDone,
	ResumeMigration,
} from '@AdminGeneral/advanced/tools/migration/components';
import MigrationError from '@AdminGeneral/advanced/tools/migration/migration-error';
import { useMigration } from '@AdminGeneral/advanced/tools/migration/hooks';
import { useNavigateStep } from '@Onboarding/hooks';
import StepNavButtons from '../components/nav-buttons';
import { useOnboardingState } from '../store';
import { cn } from '@/functions/utils';
import { getMigratedData } from '@/functions/api';
import { useMemo } from '@wordpress/element';
import CachePluginAlert from '@/apps/admin-general/advanced/tools/migration/cache-notice';
/**
 * Migration step for onboarding screen
 *
 * @return {JSX.Element} Migration component
 */

const Migration = () => {
	const { nextStep, previousStep } = useNavigateStep();
	const [ { isMigrationDone = false }, dispatch ] = useOnboardingState();

	// Use the migration hook - can be used with external state management
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
	} = useMigration( {
		localStorageKey: 'surerank_onboarding_migration_progress',
	} );
	const selectedPlugin = PLUGIN_OPTIONS.find(
		( plugin ) => plugin.slug === plugin_slug
	);

	const migrationDone = useMemo( () => {
		return isDone || isMigrationDone;
	}, [ isDone, isMigrationDone ] );

	// Custom handler to go to next step after migration is complete
	const handleMigrateAndContinue = async () => {
		if ( ! plugin_slug ) {
			return;
		}
		if ( migrationDone ) {
			nextStep();
			dispatch( { isMigrationDone: true } );
			return;
		}
		if ( isMigrating || ! plugin_slug ) {
			return;
		}
		await handleMigrate();

		// Run after migration is done, fetch migrated data and update onboarding state
		try {
			const migratedData = await getMigratedData();

			// Update the onboarding state with the migrated data
			dispatch( {
				socialProfilesURLs: migratedData.social_profiles,
				websiteDetails: migratedData.website_details,
				isMigrationDone: true,
			} );
		} catch ( err ) {
			// Failed to fetch migrated data
		}
	};

	// Handler for radio button change
	const handleRadioChange = ( value ) => {
		handleSelectPlugin( value );
	};

	// Show resume UI if there's a migration in progress
	if ( showResume ) {
		return (
			<ResumeMigration
				onResume={ handleResumeMigration }
				onStartOver={ handleStartOver }
				onCancel={ handleCancelMigration }
			/>
		);
	}

	let content = (
		<div className="flex flex-col gap-6 w-full">
			<div className="space-y-1">
				<Text size={ 20 } weight={ 600 } color="primary">
					{ __(
						'Bring Your SEO Settings into SureRank',
						'surerank'
					) }
				</Text>

				<Text size={ 14 } weight={ 400 } color="secondary">
					{ __(
						"We found other SEO plugins installed on your website. Choose the ones you'd like to import data from into SureRank.",
						'surerank'
					) }
				</Text>
			</div>

			<div className="flex flex-col gap-4 w-full">
				<div className="flex items-start gap-4 w-full">
					<div className="flex-grow">
						<RadioButton.Group
							columns={ 1 }
							onChange={ handleRadioChange }
							value={ plugin_slug }
							vertical
							size="md"
							name="import_from"
						>
							{ PLUGIN_OPTIONS.filter( ( item ) => {
								if ( isMigrating || error ) {
									return item.slug === plugin_slug;
								}
								return true;
							} ).map( ( plugin ) => (
								<RadioButton.Button
									key={ plugin.slug }
									buttonWrapperClasses="pl-3 py-3"
									label={ { heading: plugin.name } }
									value={ plugin.slug }
									borderOn
								/>
							) ) }
						</RadioButton.Group>
					</div>
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
						isDisabled={ isMigrating || ! plugin_slug }
					/>
				) }

				{ /* Migration Progress */ }
				{ isMigrating && (
					<div className="flex flex-col gap-2 mt-4 w-full">
						<MigrationProgressStatus
							migrationData={ state.migrationData }
							pluginSlug={ plugin_slug }
							currentStatus={ state.currentStatus }
							progress={ state.progress }
						/>
					</div>
				) }
			</div>
		</div>
	);

	if ( migrationDone ) {
		content = <MigrateDone hideAcknowledge={ true } />;
	}

	const getNextButtonText = () => {
		if ( isMigrating ) {
			return __( 'Migrating', 'surerank' );
		}
		if ( error ) {
			return __( 'Retry', 'surerank' );
		}
		if ( migrationDone ) {
			return __( 'Next', 'surerank' );
		}
		return __( 'Migrate', 'surerank' );
	};

	return (
		<>
			{ content }
			<div className="mt-6">
				<CachePluginAlert />
			</div>
			{ ! showResume && (
				<StepNavButtons
					className="mt-12"
					skipProps={ {
						onClick: nextStep,
						disabled: isMigrating,
						className: cn( migrationDone && 'hidden' ),
					} }
					backProps={ {
						onClick: previousStep,
					} }
					nextProps={ {
						children: getNextButtonText(),
						onClick: handleMigrateAndContinue,
						disableNavigation: ! migrationDone,
						type: 'button',
						className: cn(
							( isMigrating ||
								( ! plugin_slug && ! migrationDone ) ) &&
								'opacity-70 cursor-not-allowed focus:ring-0',
							migrationDone && 'ml-auto'
						),
						...( isMigrating && {
							icon: <Loader variant="secondary" />,
						} ),
					} }
				/>
			) }
		</>
	);
};

export default Migration;
