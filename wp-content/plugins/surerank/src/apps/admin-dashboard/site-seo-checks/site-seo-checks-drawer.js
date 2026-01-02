import { Drawer, Container, Badge, Button, Text } from '@bsf/force-ui';
import { Fragment, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import { useSuspenseSiteSeoAnalysis } from './site-seo-checks-main';
import {
	getSeverityColor,
	getSeverityLabel,
} from '@GlobalComponents/seo-checks';
import SiteSeoChecksFixButton from './site-seo-checks-fix-button';
import DOMPurify from 'dompurify';
import { ArrowLeftIcon } from 'lucide-react';
import { cn, isURL } from '@/functions/utils';
import { ImageGrid } from '@/global/components/check-card';

/**
 * Checks if the given HTML string does NOT start with an <h6> tag.
 * @param {string} html - The HTML string to check.
 * @return {boolean} True if the string does not start with an <h6> tag, otherwise false.
 */
const shouldWrapInParagraph = ( html ) => {
	const trimmed = html.trim();
	// Regex to match opening <h6> tag, case-insensitive, possibly with attributes
	return ! /^<h6\b[^>]*>/i.test( trimmed );
};

const CheckHeader = ( {
	title = __( 'Site Analysis', 'surerank' ),
	selectedItem,
	showBack = false,
	onBackClick,
} ) => {
	return (
		<>
			<Container justify="between">
				<Drawer.Title>{ title }</Drawer.Title>
				<div className="inline-flex items-center gap-2">
					{ showBack ? (
						<Button
							size="xs"
							icon={ <ArrowLeftIcon /> }
							iconPosition="left"
							variant="outline"
							onClick={ onBackClick }
						>
							{ __( 'Back', 'surerank' ) }
						</Button>
					) : (
						<Badge
							size="xs"
							label={ getSeverityLabel( selectedItem?.status ) }
							variant={ getSeverityColor( selectedItem?.status ) }
						/>
					) }
					<Drawer.CloseButton />
				</div>
			</Container>
			{ ! showBack && (
				<Drawer.Description>
					{ selectedItem?.message }
				</Drawer.Description>
			) }
		</>
	);
};

const CheckOverview = ( { selectedItem } ) => {
	// Render the description as a list or paragraph
	const renderDescription = useCallback(
		( list, type = 'paragraph', isImage = false ) => {
			if ( ! list || ! list?.filter( Boolean )?.length ) {
				return;
			}

			if ( isImage && !! list?.filter( Boolean )?.length ) {
				return (
					<div className="my-4">
						<ImageGrid images={ list } />
					</div>
				);
			}
			if ( type === 'list' ) {
				const listContent = list.map( ( item ) => {
					if ( isURL( item ) ) {
						return (
							<li
								className="m-0 text-text-secondary text-sm mb-0.5"
								key={ item }
							>
								<Button
									className="no-underline hover:no-underline focus:[box-shadow:none] font-normal"
									variant="link"
									tag="a"
									href={ item }
									target="_blank"
									rel="noopener noreferrer"
								>
									{ item }
								</Button>
							</li>
						);
					}
					return (
						<li
							className="m-0 text-text-secondary text-sm mb-0.5"
							key={ item }
							dangerouslySetInnerHTML={ {
								__html: DOMPurify.sanitize( item ),
							} }
						/>
					);
				} );
				return (
					<ul className="my-0 ml-2 mr-0 text-text-secondary list-disc list-inside">
						{ listContent }
					</ul>
				);
			}

			if ( typeof list === 'string' && type === 'paragraph' ) {
				return (
					<p
						className="m-0 text-text-primary"
						dangerouslySetInnerHTML={ {
							__html: DOMPurify.sanitize( list ),
						} }
					/>
				);
			}

			return (
				<div className="flex flex-col gap-y-2 pt-2 pb-2">
					{ list.map( ( item, idx ) => {
						if (
							typeof item === 'object' &&
							Array.isArray( item.list )
						) {
							const nextItem = list[ idx + 1 ];
							const isImgFlag =
								nextItem &&
								typeof nextItem === 'object' &&
								( nextItem?.img === true ||
									nextItem?.img === 'true' );

							const render = renderDescription(
								item.list,
								'list',
								isImgFlag
							);
							return !! render ? (
								<div key={ idx }>{ render }</div>
							) : (
								<Fragment key={ idx } />
							);
						}

						// Skip img flag objects from rendering they are handled in the image grid
						if ( typeof item === 'object' && item.img ) {
							return null;
						}

						const purifiedItem = DOMPurify.sanitize( item );
						const itemClassName =
							'm-0 text-text-secondary text-sm font-normal [&_a]:no-underline [&_a]:ring-0';

						if ( shouldWrapInParagraph( purifiedItem ) ) {
							return (
								<p
									key={ idx }
									className={ itemClassName }
									dangerouslySetInnerHTML={ {
										__html: purifiedItem,
									} }
								/>
							);
						}

						return (
							<div
								key={ idx }
								className={ cn(
									itemClassName,
									'[&_h6]:mt-2.5'
								) }
								dangerouslySetInnerHTML={ {
									__html: purifiedItem,
								} }
							/>
						);
					} ) }
				</div>
			);
		},
		[]
	);

	return (
		<>
			<div className="px-2 space-y-0.5 w-full border border-border-subtle border-dashed rounded-md bg-background-secondary">
				{ renderDescription( selectedItem?.description ) || (
					<Text color="secondary" className="m-0">
						{ __(
							'No additional information to show.',
							'surerank'
						) }
					</Text>
				) }
			</div>
			{ /* Only show fix button if check hasn't passed */ }
			{ selectedItem?.status !== 'success' && (
				<SiteSeoChecksFixButton
					selectedItem={ selectedItem }
					size="sm"
				/>
			) }
		</>
	);
};

const SCREENS = applyFilters(
	'surerank-pro.dashboard.site-seo-checks-screens',
	{
		overview: {
			title: __( 'Site Analysis', 'surerank' ),
			component: CheckOverview,
		},
	}
);

const SiteSeoChecksDrawer = () => {
	// Using suspense version inside Suspense boundary
	const [
		{ open, selectedItem = {}, currentScreen = 'overview' },
		dispatch,
	] = useSuspenseSiteSeoAnalysis();

	const handleSetDrawerOpen = ( value ) => {
		const resetState = {
			selectedItem: null,
			currentScreen: 'overview',
		};
		dispatch( {
			open: value,
			...( ! value ? resetState : {} ),
		} );
	};

	const handleBack = () => {
		dispatch( {
			currentScreen: 'overview',
		} );
	};

	// Get the current screen configuration
	const currentScreenConfig = SCREENS[ currentScreen ] || SCREENS.overview;
	const CurrentScreenComponent = currentScreenConfig.component;
	const showBack = currentScreen !== 'overview';

	return (
		<Drawer
			exitOnEsc
			position="right"
			scrollLock
			setOpen={ handleSetDrawerOpen }
			open={ open }
			className="z-999999"
			exitOnClickOutside
		>
			<Drawer.Panel>
				<Drawer.Header>
					<CheckHeader
						selectedItem={ selectedItem }
						showBack={ showBack }
						onBackClick={ handleBack }
					/>
				</Drawer.Header>
				<Drawer.Body className="overflow-x-hidden space-y-3">
					<CurrentScreenComponent selectedItem={ selectedItem } />
				</Drawer.Body>
			</Drawer.Panel>
			<Drawer.Backdrop />
		</Drawer>
	);
};

export default SiteSeoChecksDrawer;
