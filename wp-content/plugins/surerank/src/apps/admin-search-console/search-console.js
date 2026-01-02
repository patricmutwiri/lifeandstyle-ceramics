import { useSelect } from '@wordpress/data';
import { STORE_NAME } from '@/admin-store/constants';
import { cn } from '@/functions/utils';
import SearchConsolePopup from '@/apps/admin-dashboard/search-console-popup';
import SiteSelectorPopup from '@/apps/admin-dashboard/site-selector-popup';
import '@AdminStore/store';
import RenderContent from '@AdminDashboard/render-content';
import { useMemo } from '@wordpress/element';
import { Container } from '@bsf/force-ui';

const SearchConsole = () => {
	const { searchConsole } = useSelect( ( select ) => {
		const selectors = select( STORE_NAME );
		return {
			searchConsole: selectors.getSearchConsole(),
		};
	}, [] );

	const isSearchConsoleConnected = useMemo(
		() => searchConsole?.authenticated === '1',
		[ searchConsole?.authenticated ]
	);

	const isSiteSelected = useMemo(
		() => !! searchConsole?.hasSiteSelected,
		[ searchConsole?.hasSiteSelected ]
	);

	const { openSiteSelectorModal } = useSelect( ( select ) => {
		const selectors = select( STORE_NAME );
		return {
			openSiteSelectorModal: selectors.getOpenSiteSelectorModal(),
		};
	} );

	const shouldShowOverlay =
		! isSearchConsoleConnected || ! isSiteSelected || openSiteSelectorModal;

	return (
		<>
			<SearchConsolePopup isOpen={ ! isSearchConsoleConnected } />

			{ isSearchConsoleConnected && ! isSiteSelected && (
				<SiteSelectorPopup />
			) }

			{ openSiteSelectorModal && isSiteSelected && <SiteSelectorPopup /> }

			<div className={ cn( 'relative h-full', shouldShowOverlay && "after:content-[''] after:fixed after:block after:w-full after:h-full after:inset-0 after:bg-black/40 after:backdrop-blur-[5px] after:z-0" ) }>
				<Container
					className="h-full p-5 pb-8 xl:p-8 max-[1920px]:max-w-full mx-auto box-content bg-background-secondary"
					cols={ 12 }
					containerType="grid"
					gap="2xl"
				>
					<Container
						direction="column"
						className={ cn(
							'gap-8 col-span-12 relative',
							shouldShowOverlay && 'blur-sm'
						) }
					>
						<RenderContent
							connected={ isSearchConsoleConnected }
							siteSelected={ isSiteSelected }
						/>
					</Container>
				</Container>
			</div>
		</>
	);
};

export default SearchConsole;
