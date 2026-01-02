import SiteSearchTrafficDummy from './site-search-dummy';
import ContentPerformanceDummy from './content-performance-dummy';
import SiteSearchTraffic from './site-search-traffic';
import ContentPerformance from './content-performance';

const RenderContent = ( { connected, siteSelected } ) => {
	if ( connected && siteSelected ) {
		return (
			<>
				<SiteSearchTraffic />
				<ContentPerformance />
			</>
		);
	}

	return (
		<>
			<SiteSearchTrafficDummy />
			<ContentPerformanceDummy />
		</>
	);
};

export default RenderContent;
