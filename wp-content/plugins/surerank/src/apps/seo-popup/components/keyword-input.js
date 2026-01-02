import { useSelect, useDispatch } from '@wordpress/data';
import { STORE_NAME } from '@Store/constants';
import PreviewInputWithSuffix from '@AdminComponents/preview-input-with-suffix';
import { Text } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';
import { InfoTooltip } from '@AdminComponents/tooltip';

const KeywordInput = () => {
	const { updatePostMetaData } = useDispatch( STORE_NAME );
	const { focusKeyword, initialized } = useSelect( ( select ) => {
		const selectors = select( STORE_NAME );
		return {
			focusKeyword: selectors?.getPostSeoMeta?.()?.focus_keyword,
			initialized: selectors.getMetaboxState(),
		};
	} );

	const handleFocusKeywordChange = ( value ) => {
		updatePostMetaData( { focus_keyword: value } );
	};

	return (
		<div className="w-full flex flex-col gap-2 [&>div]:w-full">
			<div className="flex items-center gap-2">
				<Text
					as="label"
					size={ 14 }
					weight={ 500 }
					lineHeight={ 20 }
					htmlFor="focus-keyword-input"
				>
					{ __( 'Focus Keyword', 'surerank' ) }
				</Text>
				<InfoTooltip
					content={ __(
						'The primary keyword or phrase you want this content to rank for in search engines. Keyword checks will be based on this.',
						'surerank'
					) }
					placement="top-start"
					arrow={ false }
				/>
			</div>
			<PreviewInputWithSuffix
				id="focus-keyword-input"
				value={ focusKeyword || '' }
				onChange={ handleFocusKeywordChange }
				isLoading={ ! initialized }
			/>
		</div>
	);
};

export default KeywordInput;
