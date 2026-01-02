import { Text } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';
import { PluginCard } from './plugin-card';

export const RecommendedPlugins = ( {
	recommendedPlugin,
	onInstall,
	fetchStatus,
	getPluginStatus,
	getProgressStatus,
	renderInstallButtonText,
} ) => {
	if ( ! recommendedPlugin ) {
		return null;
	}

	return (
		<div className="w-full h-fit bg-background-primary border-0.5 border-solid rounded-xl border-border-subtle p-3 shadow-sm">
			{ /* Title */ }
			<div className="flex items-center justify-stretch gap-2 p-1 mb-2">
				<div className="flex items-center gap-2">
					<Text size={ 14 } lineHeight={ 20 } weight={ 600 }>
						{ __( 'Super Charge Your Workflow', 'surerank' ) }
					</Text>
				</div>
			</div>

			{ /* Plugin Card */ }
			<div className="bg-background-secondary rounded-lg p-1">
				<div className="flex flex-wrap gap-1">
					<PluginCard
						key={ recommendedPlugin.slug }
						item={ recommendedPlugin }
						onInstall={ onInstall }
						fetchStatus={ fetchStatus }
						getPluginStatus={ getPluginStatus }
						getProgressStatus={ getProgressStatus }
						renderInstallButtonText={ renderInstallButtonText }
					/>
				</div>
			</div>
		</div>
	);
};
