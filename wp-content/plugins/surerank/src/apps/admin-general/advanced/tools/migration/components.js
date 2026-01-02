import { __ } from '@wordpress/i18n';
import { Button, Text } from '@bsf/force-ui';
import { CheckIcon, MinusIcon } from 'lucide-react';
import IconBadge from '@AdminComponents/icon-badge';
import { MIGRATED_ITEMS, NOT_MIGRATED_ITEMS } from './constants';

export const ResumeMigration = ( { onResume, onStartOver, onCancel } ) => {
	return (
		<div className="flex flex-col gap-6 w-full">
			<Text size={ 18 } weight={ 600 } color="primary">
				{ __( 'Your SEO Settings Migration is Paused', 'surerank' ) }
			</Text>

			<div className="flex flex-col gap-2 p-3 bg-alert-background-warning border border-solid border-alert-border-warning rounded-lg">
				<Text size={ 14 } weight={ 400 } color="primary">
					{ __(
						"We've successfully imported some of your SEO settings, but the migration wasn't completed. This may happen if the process was interrupted or exited early. Don't worry — everything imported so far is safely saved, and no duplicate data will be created.",
						'surerank'
					) }
				</Text>
			</div>

			<div className="flex flex-row justify-between items-center gap-2">
				<div className="flex gap-2">
					<Button variant="primary" size="sm" onClick={ onResume }>
						{ __( 'Resume Migration', 'surerank' ) }
					</Button>
					<Button variant="outline" size="sm" onClick={ onStartOver }>
						{ __( 'Start Over', 'surerank' ) }
					</Button>
				</div>
				<Button variant="ghost" size="sm" onClick={ onCancel }>
					{ __( 'Cancel Migration', 'surerank' ) }
				</Button>
			</div>
		</div>
	);
};

export const MigrateDone = ( { hideAcknowledge = false } ) => {
	const handleAcknowledge = () => {
		window.location.reload();
	};

	return (
		<div className="flex flex-col gap-6 w-full">
			<Text size={ 18 } weight={ 600 } color="primary">
				{ __(
					"We've Imported SEO Settings. Here's What's Done:",
					'surerank'
				) }
			</Text>

			<div className="grid grid-cols-2 gap-2">
				{ MIGRATED_ITEMS.map( ( item ) => (
					<div key={ item } className="flex items-center gap-2 p-1">
						<IconBadge icon={ <CheckIcon /> } color="green" />
						<Text size={ 14 } weight={ 500 } color="secondary">
							{ item }
						</Text>
					</div>
				) ) }
			</div>

			<div
				className="flex items-center p-4 bg-alert-background-info border border-solid border-alert-border-info rounded-lg"
				role="alert"
			>
				<Text size={ 14 } weight={ 400 } color="primary">
					{ __(
						'Since SureRank has different features compared to the plugin you imported from, and this was an automatic migration, some settings may not have carried over. Please review and test everything to ensure it works as expected.',
						'surerank'
					) }
				</Text>
			</div>

			<Text size={ 16 } weight={ 600 } color="primary">
				{ __(
					'Settings which may not be migrated completely.',
					'surerank'
				) }
			</Text>

			<div className="grid grid-cols-2 gap-2">
				{ NOT_MIGRATED_ITEMS.map( ( item ) => (
					<div key={ item } className="flex items-center gap-2 p-1">
						<IconBadge icon={ <MinusIcon /> } color="gray" />
						<Text size={ 14 } weight={ 500 } color="secondary">
							{ item }
						</Text>
					</div>
				) ) }
			</div>
			{ ! hideAcknowledge && (
				<Button
					variant="primary"
					className="w-fit"
					size="sm"
					onClick={ handleAcknowledge }
				>
					{ __( 'Done', 'surerank' ) }
				</Button>
			) }
		</div>
	);
};
