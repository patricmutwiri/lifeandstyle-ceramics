import { SparklesIconSolid, ReloadIcon } from '@GlobalComponents/icons';
import { useDispatch, useSelect } from '@wordpress/data';
import { Button } from '@bsf/force-ui';
import { STORE_NAME } from '@/store/constants';
import { cn } from '@/functions/utils';
import { SeoPopupTooltip } from '@AdminComponents/tooltip';

const MagicButton = ( { fieldKey, onUseThis, tooltip } ) => {
	const {
		currentScreen,
		currentTab,
		currentMetaTab,
		currentAccordion,
		generatedContents,
	} = useSelect( ( select ) => {
		const selector = select( STORE_NAME );

		return {
			...selector.getPageSeoChecks(),
			...selector.getAppSettings(),
		};
	}, [] );
	const { updateAppSettings } = useDispatch( STORE_NAME );

	// Check if content has been generated for this field
	const hasGeneratedContent =
		generatedContents &&
		generatedContents[ fieldKey ] &&
		generatedContents[ fieldKey ].length > 0;

	const handleClick = () => {
		const updatedGeneratedContents = { ...generatedContents };
		if ( updatedGeneratedContents[ fieldKey ] ) {
			delete updatedGeneratedContents[ fieldKey ];
		}

		updateAppSettings( {
			currentScreen: 'fixItForMe',
			previousScreen: currentScreen,
			previousTab: currentTab,
			previousMetaTab: currentMetaTab,
			previousAccordion: currentAccordion,
			selectedFieldKey: fieldKey,
			onUseThis,
			generateContentProcess: 'idle',
			generatedContents: updatedGeneratedContents,
			error: null,
		} );
	};

	const button = (
		<Button
			size="xs"
			variant="ghost"
			className={ cn(
				'p-0.5 text-icon-interactive outline-brand-200 rounded-sm',
				hasGeneratedContent && '[&>svg]:size-3 p-1'
			) }
			icon={
				hasGeneratedContent ? <ReloadIcon /> : <SparklesIconSolid />
			}
			onClick={ handleClick }
		/>
	);

	if ( tooltip ) {
		return (
			<SeoPopupTooltip content={ tooltip } placement="top-end">
				{ button }
			</SeoPopupTooltip>
		);
	}

	return button;
};

export default MagicButton;
