import { Fragment } from '@wordpress/element';
import {
	Input,
	TextArea,
	Select,
	RadioButton,
	EditorInput,
	Label,
	Switch,
	Container,
	Checkbox,
	Title,
	toast,
} from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';
import { cn, editorValueToString, stringValueToFormatJSON } from './utils';
import { InfoIcon } from 'lucide-react';
import { Tooltip } from '@AdminComponents/tooltip';
import CharacterLimitStatus from '@AdminComponents/character-limit-status';
import Tabs from '@AdminComponents/tabs';
import { STORE_NAME } from '@AdminStore/constants';
import { useDispatch, useSuspenseSelect } from '@wordpress/data';
import { motion } from 'framer-motion';
import { SaveSettingsButton } from '@/apps/admin-components/global-save-button';

/**
 * Collect field configurations from PAGE_CONTENT
 * @param {Array} formJson - The form configuration array
 */
const collectFieldConfigurations = ( formJson ) => {
	const globalFieldConfigMap = {};
	if ( ! formJson || ! Array.isArray( formJson ) ) {
		return;
	}

	const extractFields = ( content ) => {
		if ( ! content || ! Array.isArray( content ) ) {
			return;
		}

		content.forEach( ( item ) => {
			if ( item.content && Array.isArray( item.content ) ) {
				extractFields( item.content );
			} else if ( item.storeKey && item.shouldReload ) {
				globalFieldConfigMap[ item.storeKey ] = true;
			}
		} );
	};

	formJson.forEach( ( section ) => {
		if ( section.content ) {
			extractFields( section.content );
		}
	} );

	return globalFieldConfigMap;
};

// Types
/**
 * @typedef {Object} FieldProps
 * @property {string}   id             - Field identifier
 * @property {string}   type           - Field type
 * @property {string}   [storeKey]     - Key for storing in state
 * @property {string}   [label]        - Field label
 * @property {string}   [description]  - Field description
 * @property {string}   [className]    - Additional CSS classes
 * @property {any}      [defaultValue] - Default field value
 * @property {string}   [dataType]     - Data type for the field
 * @property {Function} [transform]    - Value transformer function
 */

// Constants
const CUSTOM_LABEL_EXCLUSIONS = [ 'checkbox', 'switch' ];

const GAP_CLASSNAMES = {
	0: 'gap-0',
	1: 'gap-1',
	1.5: 'gap-1.5',
	2: 'gap-2',
	3: 'gap-3',
	4: 'gap-4',
	5: 'gap-5',
	6: 'gap-6',
	7: 'gap-7',
	8: 'gap-8',
	9: 'gap-9',
	10: 'gap-10',
};

// Helper Functions
const getNestedValue = ( obj, path ) => {
	if ( ! path || ! obj ) {
		return obj;
	}

	const keys = typeof path === 'string' ? path.split( '.' ) : path;
	return keys.reduce( ( acc, key ) => acc?.[ key ], obj );
};

const handleArrayValue = ( currentValue, newValue, action ) => {
	if ( ! Array.isArray( currentValue ) ) {
		currentValue = [];
	}

	switch ( action ) {
		case 'add':
			return [ ...new Set( [ ...currentValue, newValue ] ) ];
		case 'toggle':
			return currentValue.includes( newValue )
				? currentValue.filter( ( item ) => item !== newValue )
				: [ ...new Set( [ ...currentValue, newValue ] ) ];
		default:
			return currentValue.filter( ( item ) => item !== newValue );
	}
};

// Custom Hooks
const useFieldActions = ( field, formValues, setFormValues ) => {
	const stateValue = getNestedValue( formValues, field.storeKey );
	const isChecked =
		field?.dataType === 'array'
			? stateValue?.includes( field.value )
			: !! stateValue;

	const handleChange = ( newValue ) => {
		let transformedValue = newValue;
		let currentValue = stateValue;

		switch ( field?.dataType ) {
			case 'array':
				transformedValue = handleArrayValue(
					currentValue || [],
					field.value,
					transformedValue ? 'add' : 'remove'
				);
				break;
			case 'boolean':
				transformedValue = !! newValue;
				break;
			case 'object':
				currentValue = Array.isArray( currentValue )
					? {}
					: currentValue;
				transformedValue = {
					...currentValue,
					[ field.name ]: newValue,
				};
				break;
			default:
				if (
					field.transform &&
					typeof field.transform === 'function'
				) {
					transformedValue = field.transform( newValue );
				}
				break;
		}

		// Handle pendingAction when field value matches any value in the pendingAction array
		if (
			field.pendingAction &&
			Array.isArray( field.pendingAction ) &&
			field.pendingAction.includes( transformedValue )
		) {
			try {
				const actionName = field.id || field.storeKey;
				const pendingActions = JSON.parse(
					window.localStorage.getItem( 'surerank_pending_actions' ) ||
						'[]'
				);
				if ( ! pendingActions.includes( actionName ) ) {
					pendingActions.push( actionName );
					window.localStorage.setItem(
						'surerank_pending_actions',
						JSON.stringify( pendingActions )
					);
				}
			} catch ( error ) {
				// Silently fail if localStorage is not available
			}
		}

		const [ mainKey, subKey ] = field.storeKey.split( '.' );
		if ( subKey ) {
			setFormValues( {
				[ mainKey ]: {
					...formValues[ mainKey ],
					[ subKey ]: transformedValue,
				},
			} );
			return;
		}
		setFormValues( {
			[ mainKey ]: transformedValue,
		} );
	};

	let fieldValue;
	switch ( field?.dataType ) {
		case 'object':
			fieldValue = stateValue[ field.name ];
			break;
		case 'array':
		case 'boolean':
			fieldValue = isChecked;
			break;
		default:
			fieldValue = stateValue;
			// Apply transform for display if it exists
			if (
				field.transform &&
				typeof field.transform === 'function' &&
				fieldValue !== undefined
			) {
				fieldValue = field.transform( fieldValue );
			}
	}

	return {
		handleChange,
		stateValue,
		isChecked,
		fieldValue,
	};
};

// Components
const FormFieldLabel = ( {
	label,
	tag = 'label',
	size = 'sm',
	variant = 'neutral',
	className = '',
	required = false,
	tooltip = '',
	currentLength = null,
	maxLength = null,
	htmlFor = '',
} ) => {
	if ( ! label ) {
		return null;
	}

	const labelContent = tooltip ? (
		<>
			<span>{ label }</span>
			<Tooltip
				content={ tooltip }
				placement="top"
				arrow
				className="z-999999"
			>
				<InfoIcon className="size-4" />
			</Tooltip>
		</>
	) : (
		label
	);

	const labelElement = (
		<Label
			tag={ tag }
			className={ cn(
				'space-x-0.5',
				className,
				'[&>svg]:text-icon-secondary'
			) }
			variant={ variant }
			size={ size }
			required={ required }
			{ ...( htmlFor ? { htmlFor } : {} ) }
		>
			{ labelContent }
		</Label>
	);

	if ( ! maxLength ) {
		return labelElement;
	}

	return (
		<Container
			direction="row"
			align="center"
			justify="start"
			className="gap-1 w-full"
		>
			<div className="inline-flex">{ labelElement }</div>
			<CharacterLimitStatus
				length={ currentLength }
				maxLength={ maxLength }
				align="right"
			/>
		</Container>
	);
};

const FieldHelperText = ( {
	tag = 'p',
	size = 'xs',
	variant = 'help',
	className = '',
	description = '',
} ) => {
	if ( ! description ) {
		return null;
	}

	return (
		<Label
			tag={ tag }
			size={ size }
			variant={ variant }
			className={ cn( 'm-0', className ) }
		>
			{ description }
		</Label>
	);
};

const InputField = ( { id, name, value, onChange, field } ) => (
	<div className="w-full">
		<Input
			id={ id }
			name={ name }
			className={ cn( 'w-full', field?.className ) }
			value={ value }
			onChange={ onChange }
			type={ field.type }
			placeholder={ field?.placeholder }
			size={ field?.size ?? 'md' }
			autoComplete="off"
		/>
	</div>
);

const EditorField = ( { id, name, value, onChange, field } ) => (
	<EditorInput
		id={ id }
		{ ...( field?.className ? { className: field.className } : {} ) }
		name={ name }
		defaultValue={ stringValueToFormatJSON( value ) }
		onChange={ ( editorState ) => {
			onChange( editorValueToString( editorState.toJSON() ) );
		} }
		trigger="@"
		by={ field?.by ?? 'label' }
		options={ field?.options ?? [] }
		placeholder={
			field?.placeholder ??
			__( 'Type @ to view variable suggestions', 'surerank' )
		}
	/>
);

const RadioField = ( { id, name, value, onChange, field } ) => (
	<RadioButton.Group
		id={ id }
		name={ name }
		value={ value }
		onChange={ onChange }
		style={ field?.style ?? 'simple' }
		columns={ field?.options?.length ?? 2 }
		size={ field?.size ?? 'sm' }
	>
		{ field?.options?.map( ( option, index ) => (
			<RadioButton.Button
				buttonWrapperClasses={ field?.optionWrapperClassName ?? '' }
				borderOn={ field?.showBorder ?? false }
				borderOnActive={ field?.showBorderOnActive ?? false }
				key={ option.id || `${ option.value }-${ index }` }
				value={ option.value }
				label={ {
					heading: option.label,
					description: option?.description,
				} }
			/>
		) ) }
	</RadioButton.Group>
);

const FormField = ( { field, formValues, setFormValues } ) => {
	const { handleChange, stateValue, isChecked, fieldValue } = useFieldActions(
		field,
		formValues,
		setFormValues
	);
	const value = fieldValue ?? field.defaultValue;
	const id = field?.name ?? field?.id;
	const name = field?.name ?? field?.id;
	const disabled =
		typeof field?.disabled === 'function'
			? field.disabled( formValues )
			: field?.disabled;
	const className =
		typeof field?.className === 'function'
			? field.className( formValues )
			: field?.className;

	const additionalProps = {
		...( disabled && { disabled } ),
		...( className && { className } ),
	};

	const renderFieldByType = () => {
		switch ( field.type ) {
			case 'text':
			case 'number':
			case 'email':
			case 'password':
				return (
					<InputField
						id={ id }
						name={ name }
						value={ value }
						onChange={ handleChange }
						field={ field }
						{ ...additionalProps }
					/>
				);
			case 'editor':
				return (
					<EditorField
						id={ id }
						name={ name }
						value={ value }
						onChange={ handleChange }
						field={ field }
					/>
				);
			case 'textarea':
				return (
					<TextArea
						id={ id }
						name={ name }
						value={ value }
						onChange={ handleChange }
						{ ...additionalProps }
					/>
				);
			case 'select':
				return (
					<Select
						id={ id }
						name={ name }
						value={ value }
						onChange={ handleChange }
						{ ...additionalProps }
					/>
				);
			case 'checkbox':
				return (
					<Checkbox
						id={ id }
						name={ name }
						value={ field?.value }
						checked={ isChecked }
						onChange={ handleChange }
						label={ {
							heading: field?.label,
							description: field?.description,
						} }
						size={ field?.size ?? 'sm' }
						{ ...additionalProps }
					/>
				);
			case 'switch':
				return (
					<Switch
						id={ id }
						name={ name }
						value={ fieldValue }
						onChange={ handleChange }
						label={ {
							heading: field?.label,
							description: field?.description,
						} }
						size={ field?.size ?? 'sm' }
						{ ...additionalProps }
					/>
				);
			case 'radio':
				return (
					<RadioField
						id={ id }
						name={ name }
						value={ value }
						onChange={ handleChange }
						field={ field }
					/>
				);
			case 'custom':
				if ( Object.keys( additionalProps ).length ) {
					return (
						<div { ...additionalProps }>
							{ field?.component ?? null }
						</div>
					);
				}
				return field?.component ?? null;
			default:
				return null;
		}
	};

	return (
		<Container
			direction="column"
			align="start"
			justify="start"
			className={ cn(
				'gap-1.5 w-full',
				typeof field.wrapperClassName === 'function'
					? field.wrapperClassName( formValues )
					: field.wrapperClassName
			) }
		>
			{ ! CUSTOM_LABEL_EXCLUSIONS.includes( field?.type ) && (
				<FormFieldLabel
					htmlFor={ id }
					label={ field?.label }
					tag={ field?.label?.tag }
					size={ field?.label?.size }
					variant={ field?.label?.variant }
					className={ field?.label?.className }
					required={ field?.label?.required }
					tooltip={ field?.tooltip }
					currentLength={ stateValue?.length }
					maxLength={ field?.maxLength }
				/>
			) }
			{ renderFieldByType() }
			{ ! CUSTOM_LABEL_EXCLUSIONS.includes( field?.type ) && (
				<FieldHelperText description={ field?.description } />
			) }
		</Container>
	);
};

// Field Renderer
export const renderField = ( field, formValues, setFormValues ) => {
	if ( field.container !== undefined ) {
		return generateForm(
			field.content,
			formValues,
			setFormValues,
			field.container
		);
	}

	switch ( field.type ) {
		case 'label':
			return (
				<FormFieldLabel
					key={ field?.id }
					id={ field?.id }
					label={ field?.label }
					tag={ field?.tag ?? 'label' }
					size={ field?.size ?? 'sm' }
					variant={ field?.variant ?? 'neutral' }
					className={ cn( 'm-0', field?.className ) }
					tooltip={ field?.tooltip }
				/>
			);
		case 'title':
			return (
				<div id={ field?.id }>
					<Title
						key={ field?.id }
						tag={ field?.tag ?? 'h5' }
						className={ cn( 'm-0', field?.className ) }
						title={ field?.label }
					/>
				</div>
			);
		case 'tabs':
			return (
				<Tabs
					key={ field?.id }
					field={ field }
					formValues={ formValues }
					setFormValues={ setFormValues }
				/>
			);
		default:
			return (
				<FormField
					key={ field?.id }
					field={ field }
					formValues={ formValues }
					setFormValues={ setFormValues }
				/>
			);
	}
};

// Section Content Renderer
const renderSectionContent = (
	contentItem,
	contentIndex,
	formValues,
	setFormValues
) => {
	if ( contentItem?.content ) {
		const containerContent = contentItem.content.map(
			( field, fieldIndex ) => {
				const uniqueId = field.id || `field-${ fieldIndex }`;
				return (
					<Fragment key={ uniqueId }>
						{ renderField(
							{ ...field, id: uniqueId },
							formValues,
							setFormValues
						) }
					</Fragment>
				);
			}
		);

		if ( contentItem.container ) {
			return (
				<Container
					key={
						contentItem.container?.id || `content-${ contentIndex }`
					}
					direction={ contentItem.container?.direction || 'column' }
					align={ contentItem.container?.align || 'start' }
					justify={ contentItem.container?.justify || 'start' }
					className={ cn(
						GAP_CLASSNAMES[ contentItem.container?.gap ?? 6 ],
						contentItem.container?.className
					) }
				>
					{ containerContent }
				</Container>
			);
		}

		// Wrap the array in a Fragment with a key to avoid React warning
		return (
			<Fragment key={ `content-fragment-${ contentIndex }` }>
				{ containerContent }
			</Fragment>
		);
	}

	return renderField(
		{ ...contentItem, id: contentItem.id || `field-${ contentIndex }` },
		formValues,
		setFormValues
	);
};

// Main Form Generator
export const generateForm = (
	formJson,
	formValues,
	setFormValues,
	containerProps,
	unsavedSettings = {},
	hideGlobalSaveButton = false
) => {
	if ( ! formJson?.length ) {
		return null;
	}

	const globalFieldConfigMap = collectFieldConfigurations( formJson );

	// Define onSuccess callback for SaveSettingsButton
	const onSuccess = () => {
		toast.success( __( 'Settings saved successfully', 'surerank' ), {
			description: __(
				'To apply the new settings, the page will refresh automatically in 3 seconds.',
				'surerank'
			),
		} );
		setTimeout( () => {
			window.location.reload();
		}, 500 );
	};

	const shouldReload = Object.keys( unsavedSettings ).some(
		( key ) => globalFieldConfigMap[ key ]
	);
	const renderedFields = formJson.map( ( section, index ) => (
		<Container
			key={ section.container?.id || `section-${ index }` }
			direction={ section.container?.direction || 'column' }
			align={ section.container?.align || 'start' }
			justify={ section.container?.justify || 'start' }
			className={ cn(
				'p-6 bg-white shadow-sm rounded-xl',
				GAP_CLASSNAMES[ section.container?.gap ?? 6 ],
				section.container?.className
			) }
		>
			{ section.content?.map( ( contentItem, contentIndex ) => (
				<Fragment
					key={ contentItem.id || `content-item-${ contentIndex }` }
				>
					{ renderSectionContent(
						contentItem,
						contentIndex,
						formValues,
						setFormValues
					) }
				</Fragment>
			) ) }
			{ ! hideGlobalSaveButton && (
				<SaveSettingsButton
					onSuccess={ shouldReload ? onSuccess : undefined }
				/>
			) }
		</Container>
	) );

	return (
		<motion.div
			key={ containerProps?.id }
			className="w-full"
			initial={ { opacity: 0 } }
			animate={ { opacity: 1 } }
			exit={ { opacity: 0 } }
			transition={ {
				duration: 0.2,
				type: 'tween',
				ease: 'easeInOut',
				delay: 0.1,
			} }
		>
			<Container
				direction={ containerProps?.direction || 'column' }
				align={ containerProps?.align || '' }
				justify={ containerProps?.justify || '' }
				className={ cn(
					'w-full',
					GAP_CLASSNAMES[ containerProps?.gap ?? 6 ],
					containerProps?.className
				) }
			>
				{ renderedFields }
			</Container>
		</motion.div>
	);
};

// Main Component
const GeneratePageContent = ( { json, hideGlobalSaveButton = false } ) => {
	const { setMetaSettings } = useDispatch( STORE_NAME );
	const { stateValue, unsavedSettings } = useSuspenseSelect( ( select ) => {
		const { getMetaSettings, getUnsavedSettings } = select( STORE_NAME );
		return {
			stateValue: getMetaSettings(),
			unsavedSettings: getUnsavedSettings(),
		};
	}, [] );

	return generateForm(
		json,
		stateValue,
		setMetaSettings,
		{},
		unsavedSettings,
		hideGlobalSaveButton
	);
};

export default GeneratePageContent;
