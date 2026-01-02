import PageContentWrapper from '@AdminComponents/page-content-wrapper';
import { __, sprintf } from '@wordpress/i18n';
import { Button, toast } from '@bsf/force-ui';
import { useSuspenseSelect } from '@wordpress/data';
import { STORE_NAME } from '@AdminStore/constants';
import withSuspense from '@AdminComponents/hoc/with-suspense';
import { LoaderCircle, RefreshCw } from 'lucide-react';
import { Tooltip } from '@AdminComponents/tooltip';
import GeneratePageContent from '@Functions/page-content-generator';
import { cn } from '@/functions/utils';
import { applyFilters } from '@wordpress/hooks';
import { useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { UpgradeNotice } from '@/global/components/nudges';
import Alert from '@/global/components/alert';

const xmlContent = ( metaSettings ) => [
	{
		type: 'switch',
		id: 'enable_xml_sitemap',
		storeKey: 'enable_xml_sitemap',
		dataType: 'boolean',
		label: __( 'Enable XML Sitemap', 'surerank' ),
		description: (
			<>
				{ __(
					'Create a sitemap that helps search engines discover and index your pages faster.',
					'surerank'
				) }{ ' ' }
				<Button
					variant="link"
					size="sm"
					className={ cn(
						'p-0 h-auto inline-flex !text-link-primary focus:ring-0',
						{
							'cursor-not-allowed !text-field-color-disabled':
								! metaSettings?.enable_xml_sitemap,
						}
					) }
					onClick={
						metaSettings?.enable_xml_sitemap
							? () =>
									window.open(
										surerank_admin_common?.sitemap_url,
										'_blank',
										'noopener,noreferrer'
									)
							: undefined
					}
					disabled={ ! metaSettings?.enable_xml_sitemap }
				>
					{ __( 'View Sitemap', 'surerank' ) }
				</Button>
			</>
		),
	},
	{
		type: 'switch',
		id: 'enable_xml_image_sitemap',
		storeKey: 'enable_xml_image_sitemap',
		dataType: 'boolean',
		label: __( 'Include Images in XML Sitemap', 'surerank' ),
		description: __(
			'Include images so search engines can index them and show them in image results.',
			'surerank'
		),
		disabled: ( formValues ) => {
			return ! formValues.enable_xml_sitemap;
		},
	},
];

const getXmlScreen = ( metaSettings ) => {
	const content = xmlContent( metaSettings );
	return applyFilters(
		'surerank-pro.sitemap-settings',
		[
			{
				container: null,
				content: [
					{
						id: 'xml-settings',
						type: 'title',
						label: __( 'XML', 'surerank' ),
					},
				],
			},
			{
				container: null,
				content,
			},
		],
		content
	);
};

const getPageContent = ( metaSettings ) => [
	//This is the very first depth of the form. And it represents the section container of the form.
	{
		container: {
			id: 'xml-settings-container',
			direction: 'column',
			gap: 6,
		},
		content: getXmlScreen( metaSettings ),
	},
];

export const PAGE_CONTENT = [
	{
		container: {
			id: 'xml-settings-container',
			direction: 'column',
			gap: 6,
		},
		content: getXmlScreen( {} ),
	},
];

const SiteMaps = () => {
	const { metaSettings } = useSuspenseSelect( ( select ) => {
		const { getMetaSettings } = select( STORE_NAME );
		return {
			metaSettings: getMetaSettings(),
		};
	}, [] );

	const SitemapButtons = () => {
		const [ isGenerating, setIsGenerating ] = useState( false );
		const [ progress, setProgress ] = useState( 0 );
		const [ currentItem, setCurrentItem ] = useState( '' );
		const isDisabled = ! metaSettings.enable_xml_sitemap;

		const generateCache = async () => {
			setIsGenerating( true );
			setProgress( 0 );
			setCurrentItem( '' );

			try {
				const cronsAvailable = surerank_admin_common?.crons_available;

				if ( cronsAvailable ) {
					// For cron-based generation, don't set currentItem or progress
					const result = await apiFetch( {
						path: '/surerank/v1/sitemap/generate-cache',
						method: 'POST',
					} );

					toast.warning( result.message, {
						description: result.description,
						icon: <LoaderCircle className="animate-spin" />,
					} );
				} else {
					// Use manual batch processing
					toast.warning(
						__( 'Sitemap cache generation started…', 'surerank' ),
						{
							description: __(
								'Processing items in batches it will take some time, please stay on the page.',
								'surerank'
							),
							icon: <LoaderCircle className="animate-spin" />,
						}
					);

					//prepare
					const response = await apiFetch( {
						path: '/surerank/v1/prepare-cache',
						method: 'GET',
					} );

					const items = response.data;

					for ( let i = 0; i < items.length; i++ ) {
						const item = items[ i ];
						const progressPercentage = Math.round(
							( ( i + 1 ) / items.length ) * 100
						);

						// Set current item being processed
						setCurrentItem( `${ item.type }: ${ item.slug }` );
						setProgress( progressPercentage );

						await apiFetch( {
							path: '/surerank/v1/sitemap/generate-cache-manual',
							method: 'POST',
							data: {
								page: item.page,
								slug: item.slug,
								type: item.type,
							},
						} );
					}

					toast.success(
						__( 'Sitemap cache generation completed!', 'surerank' ),
						{
							description: __(
								'All content has been processed successfully.',
								'surerank'
							),
						}
					);
				}
			} catch ( error ) {
				toast.error(
					error.message ||
						__(
							'Error generating sitemap cache. Please try again.',
							'surerank'
						)
				);
			} finally {
				setIsGenerating( false );
				setProgress( 0 );
				setCurrentItem( '' );
			}
		};

		return (
			<Tooltip
				className="max-w-[18rem]"
				content={ ( () => {
					if ( ! isGenerating ) {
						return __( 'Generate sitemap cache', 'surerank' );
					}
					if ( currentItem ) {
						return sprintf(
							/* translators: 1: content type, 2: progress percentage */
							__(
								'Cache generation in progress for %1$s (%2$s%%)',
								'surerank'
							),
							currentItem,
							progress
						);
					}
					return __(
						'Sitemap cache generation is in progress…',
						'surerank'
					);
				} )() }
				arrow
			>
				<Button
					variant="outline"
					size="md"
					className={ cn( 'min-w-fit flex items-center gap-2', {
						'cursor-not-allowed': isDisabled,
					} ) }
					disabled={ isDisabled || isGenerating }
					onClick={ generateCache }
					icon={
						<RefreshCw
							className={ cn( {
								'animate-spin': isGenerating,
							} ) }
						/>
					}
					iconPosition="right"
				>
					{ isGenerating
						? __( 'Generating…', 'surerank' )
						: __( 'Regenerate', 'surerank' ) }
				</Button>
			</Tooltip>
		);
	};

	return (
		<PageContentWrapper
			title={ __( 'Sitemaps', 'surerank' ) }
			secondaryButton={ <SitemapButtons /> }
			description={ __(
				'Generates a sitemap to help search engines find and index your content more efficiently.',
				'surerank'
			) }
			afterDescription={
				surerank_admin_common?.crons_available ? null : (
					<Alert
						color="warning"
						message={ __(
							'It seems CRON is not enabled on your site. You can use the "Regenerate" button to generate the sitemap cache manually.',
							'surerank'
						) }
					/>
				)
			}
		>
			<GeneratePageContent json={ getPageContent( metaSettings ) } />
			<UpgradeNotice
				title={ __(
					'Want to unlock advanced sitemap types?',
					'surerank'
				) }
				description={ __(
					'Upgrade to generate Video, News, HTML, and Author Sitemaps for better search visibility.',
					'surerank'
				) }
				utmMedium="surerank_sitemaps"
			/>
		</PageContentWrapper>
	);
};

export default withSuspense( SiteMaps );
