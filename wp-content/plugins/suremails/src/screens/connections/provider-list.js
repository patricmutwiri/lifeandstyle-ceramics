import { useState, useRef } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { RadioButton, toast } from '@bsf/force-ui'; // Import toast for notifications

const ProviderList = ( { onSelectProvider, providers } ) => {
	const [ selectedProvider, setSelectedProvider ] = useState( '' );
	const toastRef = useRef( false ); // Ref to track toast display

	/**
	 * Handles the change event when a provider is selected.
	 *
	 * @param {string} value - The value of the selected provider.
	 */
	const handleProviderChange = ( value ) => {
		// Find the selected option from the data
		const selectedOption = providers.find(
			( option ) => option.value === value
		);

		// Check if the selected option has a 'badge' (i.e., "Soon")
		if ( selectedOption && selectedOption.badge ) {
			// Prevent multiple toasts by checking the ref
			if ( ! toastRef.current ) {
				const prerequisiteMessage = selectedOption.prerequisite ? (
					selectedOption.prerequisite
				) : (
					<span
						dangerouslySetInnerHTML={ {
							__html: sprintf(
								// translators: %1$s is anchor oneping tag and %2$s is the anchor closing tag.
								__(
									"This provider isn't compatible. For help, contact us %1$shere%2$s.",
									'suremails'
								),
								'<a href="' + suremails.supportURL + '">',
								'</a>'
							),
						} }
					></span>
				);
				toast.info(
					selectedOption.provider_type === 'not_compatible'
						? prerequisiteMessage
						: __( 'This provider is coming soon!', 'suremails' )
				);
				toastRef.current = true;
				setTimeout( () => {
					toastRef.current = false;
				}, 500 );
			}
			return; // Do nothing if the option is marked as "Soon"
		}

		// Proceed if the option does not have a 'badge'
		setSelectedProvider( value );
		onSelectProvider( value );
	};

	return (
		<div className="w-full md:max-w-lg bg-background-primary rounded-xl">
			{ /* RadioButton Group */ }
			<RadioButton.Group
				columns={ 1 }
				value={ selectedProvider } // Make it a controlled component
				onChange={ handleProviderChange }
				className="p-1 rounded-lg bg-background-secondary gap-1"
			>
				{ providers.map( ( option ) => (
					<RadioButton.Button
						key={ option.value }
						value={ option.value }
						icon={ option.icon }
						badgeItem={ option.badge }
						size="md"
						inlineIcon
						buttonWrapperClasses="bg-background-primary rounded-lg shadow-sm"
						label={ {
							heading: option.display_name,
						} }
					/>
				) ) }
			</RadioButton.Group>
		</div>
	);
};

export default ProviderList;
