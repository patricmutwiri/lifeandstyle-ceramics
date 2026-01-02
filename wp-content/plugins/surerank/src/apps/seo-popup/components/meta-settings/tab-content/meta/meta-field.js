import { __ } from '@wordpress/i18n';
import { Label, EditorInput, Text } from '@bsf/force-ui';
import { cn } from '@Functions/utils';
import { MAX_EDITOR_INPUT_LENGTH } from '@Global/constants';
import MagicButton from '@SeoPopup/components/fix-it-for-me/magic-button';

const MetaField = ( {
	label,
	inputContent,
	maxLength,
	editorRef,
	defaultValue,
	variableSuggestions,
	onChange,
	placeholder = '',
	className = '',
	editorKey,
	fieldKey,
	onUseThis,
} ) => {
	return (
		<div className="space-y-1.5 p-2">
			{ /* Label & Limit */ }
			<div className="flex items-center justify-start gap-1">
				<Label tag="span" size="sm" className="space-x-0.5">
					<span>{ label }</span>
				</Label>
				<div className="ml-auto inline-flex items-center gap-2">
					<Text size={ 12 } weight={ 400 } color="help">
						<span
							className={ cn( {
								'text-text-error':
									inputContent?.length > maxLength,
							} ) }
						>
							{ inputContent?.length ?? 0 }
						</span>
						/ { maxLength }
					</Text>
					<MagicButton
						fieldKey={ fieldKey }
						onUseThis={ onUseThis }
						tooltip={ __( 'Generate with AI', 'surerank' ) }
					/>
				</div>
			</div>
			{ /* Input */ }
			<EditorInput
				key={ editorKey }
				ref={ editorRef }
				by="label"
				defaultValue={ defaultValue }
				trigger="@"
				options={ variableSuggestions }
				onChange={ onChange }
				placeholder={ placeholder }
				className={ className }
				maxLength={
					editorKey === 'description'
						? MAX_EDITOR_INPUT_LENGTH
						: undefined
				}
			/>
			{ /* Hint text */ }
			<Text size={ 12 } color="help" weight={ 400 }>
				{ __( 'Type @ to view variable suggestions', 'surerank' ) }
			</Text>
		</div>
	);
};

export default MetaField;
