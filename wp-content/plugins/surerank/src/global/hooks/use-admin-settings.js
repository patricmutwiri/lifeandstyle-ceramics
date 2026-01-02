import { useDispatch, useSuspenseSelect } from '@wordpress/data';
import { STORE_NAME } from '@AdminStore/constants';

const useSettings = () => {
	const { setMetaSetting, setSiteSettings } = useDispatch( STORE_NAME );
	const { siteSettings, metaSettings } = useSuspenseSelect( ( select ) => {
		const { getSiteSettings, getMetaSettings } = select( STORE_NAME );
		return {
			siteSettings: getSiteSettings(),
			metaSettings: getMetaSettings(),
		};
	}, [] );

	return { siteSettings, metaSettings, setMetaSetting, setSiteSettings };
};

export default useSettings;
