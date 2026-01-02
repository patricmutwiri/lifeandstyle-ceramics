import { cn } from '@Functions/utils';
import { forwardRef, Fragment, useState } from '@wordpress/element';
import { Select, Input, Checkbox, Label, Text, TextArea } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';
import MediaPreview from '../admin-components/media-preview';
import { createMediaFrame } from '@/global/utils/utils';

// Track which elements have already been initially focused
const initiallyFocusedElements = new WeakMap();

export const focusHelper = ( el ) => {
	if (
		el &&
		typeof el.focus === 'function' &&
		! initiallyFocusedElements.get( el )
	) {
		// Use setTimeout to ensure the element is fully rendered and avoid focusing during re-renders
		setTimeout( () => {
			el.focus();
		}, 0 );
		initiallyFocusedElements.set( el, true );
	}
};

export const renderField = ( field, fieldValue, onChange, error, option ) => {
	const {
		label,
		name,
		type,
		options: fieldOptions,
		defaultValue,
		width = 'full',
		combobox = false,
		size = 'md',
		description = null,
	} = field;

	const handleClick = ( event ) => {
		//prevent native file input from opening
		event.preventDefault();
		const frame = createMediaFrame( {
			title: field?.label,
			button: {
				text: field?.label,
			},
			multiple: false,
		} );

		frame.on( 'select', () => {
			const attachment = frame
				.state()
				.get( 'selection' )
				.first()
				.toJSON();
			const attachmentId = attachment.id || null;
			const fileName = attachment.filename || null;
			const fileUrl = attachment.url || null;
			const fileType = attachment?.type || null;
			const fileSize = attachment?.filesizeInBytes || null;
			onChange( {
				attachment_id: attachmentId,
				name: fileName,
				size: fileSize,
				url: fileUrl,
				type: fileType,
			} );

			frame.close();
		} );

		frame.open();
	};

	let extraProps = {};
	if ( option && 'initialFocus' in option && option.initialFocus ) {
		extraProps = {
			ref: focusHelper,
		};
	}
	// If field contains description render in a common variable
	const renderDescription = description ? (
		<Text size={ 14 } weight={ 400 } color="help">
			{ description }
		</Text>
	) : null;

	let children = null;
	switch ( type ) {
		case 'select':
			children = (
				<SelectWithCustomSearch
					key={ name }
					id={ name }
					searchFn={ field.searchFn }
					size={ size }
					combobox={ combobox }
					label={ label }
					value={ fieldValue }
					defaultValue={ defaultValue }
					onChange={ onChange }
					options={ fieldOptions }
					by={ field.by || 'value' }
					{ ...extraProps }
				/>
			);
			break;
		case 'selectGroup':
			children = (
				<SelectWithGroup
					key={ name }
					id={ name }
					label={ label }
					value={ fieldValue }
					options={ fieldOptions }
					onChange={ onChange }
					{ ...extraProps }
				/>
			);
			break;
		case 'checkbox':
			children = (
				<div className="space-y-1.5" key={ name }>
					<Checkbox
						size={ size }
						id={ name }
						name={ name }
						label={ {
							heading: label,
						} }
						checked={ fieldValue }
						onChange={ onChange }
					/>
					{ error && (
						<Label variant="error" className="ml-8">
							{ __( 'This is required', 'surerank' ) }
						</Label>
					) }
				</div>
			);
			break;
		case 'textarea':
			children = (
				<div className="space-y-1.5">
					<Label htmlFor={ name }>{ label }</Label>
					<TextArea
						id={ name }
						rows={ field.rows || 4 }
						placeholder={ field.placeholder || '' }
						value={ fieldValue }
						onChange={ onChange }
						className="w-full"
						size="md"
					/>
				</div>
			);
			break;
		case 'file':
			const mediaPreviewProps = {
				imageUrl:
					typeof fieldValue === 'string' ? fieldValue : undefined,
				imageId:
					typeof fieldValue === 'object'
						? fieldValue?.attachment_id
						: undefined,
				onRemove: () => onChange( null ),
			};
			children = (
				<div key={ name } className="space-y-1.5">
					<Input
						id={ name }
						label={ label }
						type="file"
						size="md"
						onClick={ handleClick }
					/>
					{ renderDescription }
					{ fieldValue && fieldValue.attachment_id !== 0 && (
						<div className="pt-0.5">
							<MediaPreview { ...mediaPreviewProps } />
						</div>
					) }
				</div>
			);
			break;
		default:
			children = (
				<Fragment key={ name }>
					<div className="space-y-1.5">
						<Input
							id={ name }
							size={ size }
							name={ name }
							label={ label }
							type={ type }
							value={ fieldValue }
							onChange={ onChange }
							autoComplete="off"
							error={ error }
							{ ...extraProps }
						/>
						{ error && <Label variant="error">{ error }</Label> }
					</div>
				</Fragment>
			);
	}

	return (
		<div
			className={ cn(
				width !== 'full' ? 'grow w-full md:w-5/12' : 'w-full'
			) }
		>
			{ children }
		</div>
	);
};

const SelectWithCustomSearch = forwardRef(
	(
		{
			id,
			searchFn,
			size,
			combobox,
			label,
			defaultValue,
			value,
			onChange,
			options = [],
			by = 'value',
		},
		ref
	) => {
		const [ selectOptions, setSelectOptions ] = useState( options );

		const handleSearch = searchFn
			? async ( keyword ) => {
					const results = await searchFn( keyword );
					setSelectOptions( results );
			  }
			: undefined;

		const findSelectedValue = selectOptions.find(
			( option ) => option.value === value
		);
		return (
			<Select
				id={ id }
				size={ size }
				by={ by }
				combobox={ combobox }
				value={ findSelectedValue || defaultValue?.value }
				onChange={ onChange }
				{ ...( combobox &&
					typeof searchFn === 'function' && {
						searchFn: handleSearch,
					} ) }
			>
				<Select.Button
					label={ label }
					render={ ( selectedValue ) =>
						selectedValue?.label || defaultValue?.label
					}
					type="button"
					ref={ ref }
				/>
				<Select.Portal id="surerank-root">
					<Select.Options>
						{ selectOptions.length > 0 ? (
							selectOptions.map( ( option ) => (
								<Select.Option
									key={ option.value }
									value={ option }
									selected={
										findSelectedValue?.value ===
											option.value ||
										String( defaultValue?.value ) ===
											String( option.value )
									}
								>
									{ option.label }
								</Select.Option>
							) )
						) : (
							<Select.Option disabled>
								{ __( 'No options available', 'surerank' ) }
							</Select.Option>
						) }
					</Select.Options>
				</Select.Portal>
			</Select>
		);
	}
);

const formatWithSpace = ( value ) => {
	if ( ! value || typeof value !== 'string' ) {
		return value;
	}
	return value
		.replace( /([a-z])([A-Z])/g, '$1 $2' )
		.replace( /([A-Z])([A-Z][a-z])/g, '$1 $2' );
};

const SelectWithGroup = forwardRef(
	( { id, label, value, onChange, options = [] }, ref ) => {
		return (
			<Select size="md" value={ value } onChange={ onChange }>
				<Select.Button
					id={ id }
					label={ label }
					render={ ( selectedValue ) =>
						formatWithSpace( selectedValue )
					}
					type="button"
					ref={ ref }
				/>
				<Select.Portal id="surerank-root">
					<Select.Options>
						{ options.map( ( group, index ) => (
							<Select.OptionGroup
								key={ index }
								label={ group.label }
							>
								{ Object.entries( group.options ).map(
									( [ key, valueLabel ] ) => (
										<Select.Option
											key={ key }
											value={ key }
										>
											{ formatWithSpace( valueLabel ) }
										</Select.Option>
									)
								) }
							</Select.OptionGroup>
						) ) }
					</Select.Options>
				</Select.Portal>
			</Select>
		);
	}
);
