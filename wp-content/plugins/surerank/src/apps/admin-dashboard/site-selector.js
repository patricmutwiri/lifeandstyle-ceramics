import { Select } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';
import { useMemo } from '@wordpress/element';
import { InfoTooltip } from '@AdminComponents/tooltip';

const SiteSelector = ( {
	sites = [],
	selectedSite,
	onSiteSelect,
	placeholder = __( 'Select a site', 'surerank' ),
} ) => {
	// Create options with JSX components
	const options = useMemo( () => {
		const siteOptions = [];

		// Add verified sites first
		sites.forEach( ( site ) => {
			const tooltipText = site.isVerified
				? ''
				: __( 'Not Verified', 'surerank' );

			siteOptions.push( {
				label: site.siteUrl,
				value: site.siteUrl,
				tooltipText,
				isVerified: site.isVerified,
			} );
		} );

		return siteOptions;
	}, [ sites ] );

	return (
		<Select
			value={ selectedSite }
			onChange={ onSiteSelect }
			size="md"
			combobox
			className="w-full"
		>
			<Select.Button
				placeholder={ placeholder }
				render={ ( selectedValue ) => selectedValue || placeholder }
			/>
			<Select.Options>
				{ options.map( ( option ) => (
					<Select.Option
						key={ option.value }
						value={ option.value }
						title={ option.tooltipText }
					>
						{ /* Enhanced JSX */ }
						<div className="flex items-center justify-between w-full">
							<span className="truncate">{ option.label }</span>
							{ ! option.isVerified && (
								<div className="flex items-center gap-1 mr-1">
									<InfoTooltip
										content={
											option.tooltipText ||
											( option.isCurrentSite
												? __(
														'Not Connected',
														'surerank'
												  )
												: __(
														'Not Verified',
														'surerank'
												  ) )
										}
										className="z-[9999]"
									/>
								</div>
							) }
						</div>
					</Select.Option>
				) ) }
			</Select.Options>
		</Select>
	);
};

export default SiteSelector;
