import { __ } from '@wordpress/i18n';
import { useStateValue } from '../../../store/store';
import Tooltip from '../../../components/tooltip/tooltip';

const blockVersions = {
	v2: __( 'v2', 'astra-sites' ),
	v3: __( 'v3', 'astra-sites' ),
};

/**
 * Spectra Blocks Version Selector component
 */
const SpectraBlocksVersionSelector = () => {
	const [ { builder, spectraBlocksVersion }, dispatch ] = useStateValue();

	// Bail early if the selector is not enabled or the builder is not Gutenberg.
	if (
		! astraSitesVars?.spectraBlocks?.selectorEnabled ||
		builder !== 'gutenberg'
	) {
		return;
	}

	/**
	 * Handle version change
	 *
	 * @param {string} value Selected version
	 */
	const onChangeVersion = ( value ) => {
		dispatch( {
			type: 'set',
			spectraBlocksVersion: value,
		} );
	};

	return (
		<Tooltip
			content={ __( 'Spectra Blocks Version', 'astra-sites' ) }
			interactive={ true }
			delay={ 100 }
		>
			<div
				className="ml-2 mr-4 flex"
				role="radiogroup"
				aria-label={ __(
					'Select Spectra Blocks Version',
					'astra-sites'
				) }
			>
				{ Object.entries( blockVersions ).map(
					( [ version, label ] ) => {
						const isChecked = spectraBlocksVersion === version;

						return (
							<label
								key={ version }
								className="cursor-pointer [&:first-child>span]:rounded-l-[4px] [&:last-child>span]:rounded-r-[4px]"
							>
								<input
									type="radio"
									name="spectra-blocks-version"
									value={ version }
									className="peer sr-only"
									checked={ isChecked }
									onChange={ ( e ) =>
										onChangeVersion( e.target.value )
									}
									aria-checked={ isChecked }
								/>
								<span
									className="inline-block px-4 py-1.5 text-sm transition-all border border-solid border-gray-300 peer-checked:bg-classic-button peer-checked:text-white peer-checked:border-classic-button focus-visible:outline focus-visible:outline-2 focus-visible:outline-classic-button"
									role="radio"
									aria-checked={ isChecked }
									tabIndex={ isChecked ? 0 : -1 }
								>
									{ label }
								</span>
							</label>
						);
					}
				) }
			</div>
		</Tooltip>
	);
};

export default SpectraBlocksVersionSelector;
