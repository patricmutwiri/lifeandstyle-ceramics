import { __, sprintf } from '@wordpress/i18n';
import { Checkbox } from '@bsf/force-ui';

const DeactivatePluginCheckbox = ( { plugin, value, onChange } ) => {
	if ( ! plugin ) {
		return null;
	}
	return (
		<div className="flex items-start mt-4 bg-background-secondary p-2 rounded-md border border-solid border-border-subtle">
			<div className="mt-0.5 mr-1.5">
				<Checkbox
					checked={ value }
					size="sm"
					onChange={ onChange }
					label={ {
						heading: sprintf(
							// translators: %s is the plugin name.
							__(
								'%s will be deactivated after migration. Uncheck to keep it active.',
								'surerank'
							),
							plugin?.name || __( 'The plugin', 'surerank' )
						),
					} }
				/>
			</div>
		</div>
	);
};

export default DeactivatePluginCheckbox;
