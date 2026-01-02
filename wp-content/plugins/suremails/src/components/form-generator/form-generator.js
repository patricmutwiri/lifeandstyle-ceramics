import { Input, Select, Label, Checkbox, Button, toast } from '@bsf/force-ui';
import TruncateText from '@components/truncate-text';
import parse from 'html-react-parser';
import DOMPurify from 'dompurify';
import { cn } from '@utils/utils';
import CopyButton from '@components/copy-button';
import { get_auth_url } from '@api/auth';
import { __ } from '@wordpress/i18n';

const SHOW_MORE_CHARACTER_LIMIT = 50;

const HelpText = ( { text, showMore = false } ) => {
	// For security, override target and rel attributes to links
	DOMPurify.addHook( 'afterSanitizeAttributes', function ( node ) {
		// set all elements owning target to target=_blank and rel=noopener noreferrer
		if ( 'target' in node ) {
			node.setAttribute( 'target', '_blank' );
			node.setAttribute( 'rel', 'noopener noreferrer' );
		}
	} );
	return (
		<Label tag="span" size="sm" variant="help">
			{ !! showMore ? (
				<TruncateText
					html={ text }
					characterLimit={ SHOW_MORE_CHARACTER_LIMIT }
				/>
			) : (
				<span>{ parse( DOMPurify.sanitize( text ) ) }</span>
			) }
		</Label>
	);
};

const FormField = ( {
	field,
	value,
	onChange,
	errors,
	inlineValidator,
	formStateValues,
	onClickAuthenticate,
} ) => {
	const check_auth_code = () => {
		if ( formStateValues?.refresh_token || formStateValues?.auth_code ) {
			return false;
		}
		return true;
	};

	if ( field?.name === 'auth_code' && formStateValues?.refresh_token ) {
		return null;
	}
	const render_auth_code = check_auth_code();
	const handleProviderAuth = async (
		provider,
		client_id,
		client_secret,
		redirect_url
	) => {
		if ( typeof onClickAuthenticate === 'function' ) {
			onClickAuthenticate(
				provider,
				formStateValues,
				client_id,
				client_secret,
				redirect_url
			);
		}

		try {
			const response = await get_auth_url(
				provider,
				client_id,
				client_secret,
				redirect_url
			);
			if ( response?.auth_url ) {
				window.open(
					response.auth_url,
					'_self',
					'noopener noreferrer'
				);
			} else {
				toast.error( __( 'Error In Auth URL', 'suremails' ), {
					description: __(
						'There was an issue generating the auth URL.',
						'suremails'
					),
				} );
			}
		} catch ( error ) {
			toast.error( __( 'Error In Auth URL', 'suremails' ), {
				description:
					error.message ||
					__(
						'There was an issue generating the auth URL.',
						'suremails'
					),
			} );
		}
	};

	const handleChange = ( newValue ) => {
		let convertedValue = newValue;
		try {
			// If field type is number, convert to number
			convertedValue =
				field.type === 'number' ? Number( newValue ) : newValue;
		} catch ( error ) {
			// Do nothing
		}
		// If field has a transform function, apply it
		const transformedValue = field.transform
			? field.transform( convertedValue )
			: convertedValue;
		onChange?.( field.name, transformedValue );
	};

	const handleButtonClick = () => {
		// If field has a href, open it in a new tab
		if ( field.href ) {
			try {
				window.open(
					field.href,
					field?.target || '_self',
					'noopener noreferrer'
				);
			} catch ( error ) {
				// eslint-disable-next-line no-console
				console.error( error );
			}
		}
	};

	let renderField = null;
	switch ( field.input_type ) {
		case 'text':
		case 'password':
		case 'email':
		case 'number':
			renderField = (
				<div className="flex w-full flex-col gap-1.5 py-2 pl-2">
					<div className="w-full flex items-end justify-start gap-2 [&>div]:w-full">
						<Input
							name={ field.name }
							type={ field.input_type }
							size="md"
							value={ value || '' }
							onChange={ handleChange }
							onBlur={ inlineValidator }
							error={ errors?.[ field.name ] }
							placeholder={ field.placeholder }
							label={ field.label }
							required={ field.required }
							className={ field.class_name || 'w-full' }
							min={ field.min }
							autoComplete="off"
							readOnly={ field.read_only || false }
						/>
						{ field.copy_button && (
							<CopyButton text={ value } className="size-10" />
						) }
					</div>
					{ !! errors?.[ field.name ] && (
						<p className="text-text-error text-sm">
							{ errors?.[ field.name ] }
						</p>
					) }
					{ field.help_text && (
						<HelpText
							text={ field.help_text }
							showMore={ field?.helpShowMore }
						/>
					) }
				</div>
			);
			break;
		case 'select':
			renderField = (
				<div className="flex flex-col gap-1.5 py-2 pl-2">
					<Label
						size="sm"
						className="w-full"
						required={ field.required }
					>
						{ field.label }
					</Label>
					<Select
						value={ value ?? field.default }
						onChange={ handleChange }
						className="w-full h-10"
						combobox={ field.combobox }
					>
						<Select.Button
							type="button"
							render={ ( selectedValue ) =>
								field.getOptionLabel?.( selectedValue ) ??
								selectedValue
							}
						/>
						<Select.Options className="z-999999">
							{ field.options.map( ( option ) => (
								<Select.Option
									key={ option.label }
									value={ option.value }
								>
									{ option.label }
								</Select.Option>
							) ) }
						</Select.Options>
					</Select>
					{ field.help_text && (
						<HelpText
							text={ field.help_text }
							showMore={ field?.helpShowMore ?? false }
						/>
					) }
				</div>
			);
			break;
		case 'checkbox':
			renderField = (
				<div className="p-2">
					<Checkbox
						name={ field.name }
						checked={ value }
						size="sm"
						onChange={ handleChange }
						label={ {
							heading: field.label,
							description: field.help_text && (
								<HelpText
									text={ field.help_text }
									showMore={ field?.helpShowMore ?? false }
								/>
							),
						} }
						disabled={ field.disabled?.( value, formStateValues ) }
					/>
				</div>
			);
			break;
		case 'button':
			renderField = (
				<div className="w-full space-y-1.5 py-2 pl-2">
					{ field.label && (
						<Label
							size="sm"
							className="w-full"
							required={ field.required }
						>
							{ field.label }
						</Label>
					) }
					<Button
						className={ cn( 'w-full', field?.className ) }
						variant={ field.variant ?? 'primary' }
						onClick={ () => {
							if ( field?.on_click && field.on_click?.params ) {
								const provider =
									field?.on_click?.params?.provider;
								const client_id =
									formStateValues?.client_id || '';
								const client_secret =
									formStateValues?.client_secret || '';
								const redirect_url =
									formStateValues?.redirect_url || '';
								handleProviderAuth(
									provider,
									client_id,
									client_secret,
									redirect_url,
									formStateValues
								);
							} else {
								handleButtonClick();
							}
						} }
						size={ field.size ?? 'sm' }
						disabled={ field.disabled?.( formStateValues ) }
						destructive={ ! render_auth_code }
					>
						{ formStateValues?.refresh_token ||
						formStateValues?.auth_code
							? field.alt_button_text
							: field.button_text }
					</Button>
					{ field.help_text && (
						<HelpText
							text={ field.help_text }
							showMore={ field?.helpShowMore ?? false }
						/>
					) }
				</div>
			);
			break;
		default:
			renderField = null;
	}

	return renderField;
};

const FormGenerator = ( {
	fields,
	values,
	onChange,
	errors,
	inlineValidator,
	onClickAuthenticate,
} ) => {
	const handleFieldChange = ( field, value ) => {
		onChange?.( { [ field ]: value } );
	};

	return (
		<div className="flex flex-col gap-4">
			{ fields.map( ( field ) => (
				<FormField
					key={ field.name }
					field={ field }
					value={ values?.[ field.name ] }
					formStateValues={ values }
					onChange={ handleFieldChange }
					errors={ errors }
					inlineValidator={ inlineValidator }
					onClickAuthenticate={ onClickAuthenticate }
				/>
			) ) }
		</div>
	);
};

export default FormGenerator;
