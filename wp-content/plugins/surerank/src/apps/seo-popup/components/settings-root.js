import { useSelect } from '@wordpress/data';
import { STORE_NAME } from '@/store/constants';
import { TABS } from '@SeoPopup/modal/tabs';
import { useMemo } from '@wordpress/element';
import { isPageBuilderActive } from '@SeoPopup/components/page-seo-checks/analyzer/utils/page-builder';

// Tabs related logic will be handled through this component.
const SettingsRoot = () => {
	const { currentTab } = useSelect(
		( select ) => select( STORE_NAME ).getAppSettings(),
		[]
	);

	const RenderTab = useMemo( () => {
		const tab = TABS[ currentTab ?? 'optimize' ];
		const isPageBuilder = isPageBuilderActive();

		if ( isPageBuilder ) {
			return tab?.pageBuilderComponent || tab?.component;
		}

		return tab?.component;
	}, [ currentTab ] );

	return <RenderTab />;
};

export default SettingsRoot;
