import { Button, Loader, Text } from '@bsf/force-ui';
import { cn } from '@Functions/utils';
import { FETCH_STATUS, themesAndPlugins } from './dashboard-constants';
import { __, sprintf } from '@wordpress/i18n';

export const PluginCard = ( {
	item,
	onInstall,
	fetchStatus,
	getPluginStatus,
	getProgressStatus,
	renderInstallButtonText,
} ) => {
	const pluginStatus = getPluginStatus( item );
	const isInstalled = pluginStatus === 'active';
	const isProgressing = getProgressStatus( item );

	const getInstallButtonText = ( pluginItem ) => {
		const status = getPluginStatus( pluginItem );

		if ( status === 'active' ) {
			return __( 'Activated', 'surerank' );
		}

		const product = themesAndPlugins.find(
			( themesAndPlugin ) => themesAndPlugin.slug === pluginItem.slug
		);

		if ( status === 'activate' ) {
			return product
				? sprintf(
						// translators: %s is the product name
						__( 'Activate %s', 'surerank' ),
						product.name
				  )
				: __( 'Activate', 'surerank' );
		}

		return product
			? sprintf(
					// translators: %s is the product name
					__( 'Install %s', 'surerank' ),
					product.name
			  )
			: __( 'Install', 'surerank' );
	};

	// Show card for all states now, since we handle the logic in dashboard.js

	return (
		<div className="bg-background-primary border border-border-subtle rounded-xl p-3 flex-1 min-h-0 shadow-sm">
			<div className="flex flex-col gap-2 h-full">
				{ /* Header with logo */ }
				<div className="flex items-center gap-1.5 p-1">
					<div className="w-[122px] h-6 flex items-center justify-start flex-shrink-0">
						<item.icon className="max-w-full max-h-full object-contain" />
					</div>
				</div>

				{ /* Title and Description */ }
				<div className="flex flex-col gap-1 p-1 flex-grow">
					<div className="flex items-center gap-1.5">
						<h3 className="text-base font-semibold text-text-primary leading-6">
							{ item.title || item.name }
						</h3>
					</div>
					<Text className="text-sm font-normal text-text-secondary leading-5">
						{ item.long_description || item.description }
					</Text>
				</div>

				{ /* Install Button */ }
				<div className="flex gap-1 p-1">
					{ fetchStatus.status === FETCH_STATUS.LOADING ? (
						<div className="w-24 h-8 bg-misc-skeleton-background animate-pulse rounded" />
					) : (
						<Button
							className={ cn(
								'flex justify-center items-center gap-0.5 px-2 py-2 h-auto border rounded shadow-sm text-xs font-semibold focus:outline-none focus:ring-0',
								isInstalled
									? 'border-badge-border-green bg-badge-background-green text-badge-color-green cursor-default'
									: 'border-border-subtle text-text-primary hover:bg-background-secondary',
								isProgressing && 'opacity-75'
							) }
							size="sm"
							variant="outline"
							onClick={ () => ! isInstalled && onInstall( item ) }
							icon={
								isProgressing && (
									<Loader className="text-icon-primary" />
								)
							}
							iconPosition="left"
							loading={ isProgressing }
							disabled={ isProgressing || isInstalled }
						>
							{ renderInstallButtonText
								? renderInstallButtonText( item )
								: getInstallButtonText( item ) }
						</Button>
					) }
				</div>
			</div>
		</div>
	);
};
