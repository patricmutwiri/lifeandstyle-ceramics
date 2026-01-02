import PageContentWrapper from '@AdminComponents/page-content-wrapper';
import { InfoTooltip } from '@AdminComponents/tooltip';
import { useDispatch, useSuspenseSelect } from '@wordpress/data';
import { STORE_NAME } from '@AdminStore/constants';
import {
	INPUT_VARIABLE_SUGGESTIONS as variableSuggestions,
	getDefaultPageDescription,
	DESCRIPTION_LENGTH,
	TITLE_LENGTH,
	getSearchEnginePreviewInfoText,
} from '@Global/constants';
import { __ } from '@wordpress/i18n';
import { useRef } from '@wordpress/element';
import { Label, EditorInput, Container, Text } from '@bsf/force-ui';
import {
	editorValueToString,
	stringValueToFormatJSON,
	truncateText,
} from '@Functions/utils';
import Preview from '@GlobalComponents/preview';
import replacement from '@Functions/replacement';
import withSuspense from '@AdminComponents/hoc/with-suspense';
import GeneratePageContent from '@Functions/page-content-generator';
import { createLazyRoute } from '@tanstack/react-router';
import { applyFilters } from '@wordpress/hooks';
import { UpgradeNotice } from '@/global/components/nudges';

export const get_tooltip_content = () => {
	return (
		<>
			<strong>{ __( 'Site Name: ', 'surerank' ) }</strong>{ ' ' }
			<span>
				{ __(
					'The site name used in meta titles and descriptions is taken from the WordPress General Settings, where it is defined under "Site Title."',
					'surerank'
				) }{ ' ' }
			</span>
		</>
	);
};

const TitleAndDescriptionSettings = () => {
	const { siteSettings, metaSettings } = useSuspenseSelect( ( select ) => {
		const { getSiteSettings, getMetaSettings } = select( STORE_NAME );

		return {
			siteSettings: getSiteSettings(),
			metaSettings: getMetaSettings(),
		};
	}, [] );

	const { page_title: title, page_description: description } = metaSettings;

	const { setMetaSetting } = useDispatch( STORE_NAME );

	const titleEditor = useRef( null );
	const descriptionEditor = useRef( null );

	const handleUpdateMetaSettings = ( key, value ) => {
		// if value is same as previous value, return
		if ( metaSettings[ key ] === value ) {
			return;
		}
		setMetaSetting( key, value );
	};

	const faviconImageUrl = siteSettings?.site?.favicon
		? siteSettings?.site?.favicon
		: '';
	const titleContent = replacement( title, siteSettings?.site );
	const descriptionContent = replacement( description, siteSettings?.site );
	const titleContentTruncated = truncateText( titleContent, TITLE_LENGTH );
	const descriptionContentTruncated = truncateText(
		descriptionContent,
		DESCRIPTION_LENGTH
	);

	return (
		<Container direction="column" className="w-full gap-6">
			{ /* Page title input */ }
			<div className="space-y-1.5">
				{ /* Label & Limit */ }
				<div className="flex items-center justify-start gap-1">
					<Label tag="span" size="sm" className="space-x-0.5">
						<span>{ __( 'Search Engine Title', 'surerank' ) }</span>
					</Label>
					<InfoTooltip
						content={ __(
							'Set a default title for all individual post pages to help define how they appear in search engines. You can override this title for any post by editing it directly.',
							'surerank'
						) }
					/>
				</div>
				{ /* Input */ }
				<EditorInput
					key="title"
					ref={ titleEditor }
					by="label"
					trigger="@"
					defaultValue={ stringValueToFormatJSON(
						metaSettings.page_title !== ''
							? metaSettings.page_title
							: metaSettings?.global_default?.page_title,
						variableSuggestions,
						'value'
					) }
					options={ variableSuggestions }
					onChange={ ( editorState ) => {
						handleUpdateMetaSettings(
							'page_title',
							editorValueToString( editorState.toJSON() ) !== ''
								? editorValueToString( editorState.toJSON() )
								: metaSettings?.global_default?.page_title
						);
					} }
					placeholder=""
				/>
				{ /* Hint text */ }
				<Text size={ 14 } weight={ 400 } color="help">
					{ __( 'Type @ to view variable suggestions', 'surerank' ) }
				</Text>
			</div>

			{ /* Search Engine Description input */ }
			<div className="space-y-1.5">
				{ /* Label & Limit */ }
				<div className="flex items-center justify-between gap-1">
					<div className="flex items-center gap-1">
						<Label tag="span" size="sm" className="space-x-0.5">
							<span>
								{ __(
									'Search Engine Description',
									'surerank'
								) }
							</span>
						</Label>
						<InfoTooltip
							content={ __(
								'Set a default description for all individual post pages to help define how they appear in search engines. You can override this description for any post by editing it directly.',
								'surerank'
							) }
						/>
					</div>
				</div>
				{ /* Input */ }
				<EditorInput
					ref={ descriptionEditor }
					className="[&+div]:items-start [&+div]:pt-1"
					by="label"
					trigger="@"
					defaultValue={ stringValueToFormatJSON(
						metaSettings.page_description,
						variableSuggestions,
						'value'
					) }
					options={ variableSuggestions }
					onChange={ ( editorState ) => {
						handleUpdateMetaSettings(
							'page_description',
							editorValueToString( editorState.toJSON() )
						);
					} }
					placeholder={ '' }
					maxLength={ DESCRIPTION_LENGTH }
				/>
				{ /* Hint text */ }
				<Text size={ 14 } weight={ 400 } color="help">
					{ __( 'Type @ to view variable suggestions', 'surerank' ) }
				</Text>
			</div>

			{ /* Search Engine Preview */ }
			<div className="space-y-2.5 px-0">
				<div className="flex items-center justify-start gap-1">
					<Label tag="span" size="sm" className="space-x-0.5">
						<span>
							{ __( 'Search Engine Preview', 'surerank' ) }
						</span>
					</Label>
					<InfoTooltip
						content={ getSearchEnginePreviewInfoText() }
						interactive
					/>
				</div>

				<Preview
					siteTitle={ siteSettings?.site?.site_name }
					title={ titleContentTruncated }
					faviconURL={ faviconImageUrl }
					description={
						descriptionContentTruncated ||
						getDefaultPageDescription()
					}
					permalink={ siteSettings?.site?.site_url ?? '' }
				/>
			</div>
		</Container>
	);
};

export const PAGE_CONTENT = [
	{
		container: null,
		content: [
			{
				id: 'search-engine-preview',
				type: 'custom',
				component: <TitleAndDescriptionSettings />,
				searchKeywords: [ 'page title', 'search engine preview' ],
			},
		],
	},
];

const TitleAndDescription = () => {
	return (
		<PageContentWrapper
			title={ __( 'Meta Templates', 'surerank' ) }
			info_tooltip={ get_tooltip_content() }
			description={ applyFilters(
				'surerank.extended-meta.description',
				__(
					'Create a default template for how your pages appear in Google search. You can still set custom titles and descriptions for individual pages.',
					'surerank'
				)
			) }
		>
			<GeneratePageContent json={ PAGE_CONTENT } />

			<UpgradeNotice
				title={ __(
					'Looking for options to set meta by post type or taxonomy?',
					'surerank'
				) }
				description={ __(
					'Upgrade to unlock meta template editing for specific post types and taxonomies.',
					'surerank'
				) }
				utmMedium="surerank_meta_templates"
			/>
		</PageContentWrapper>
	);
};

export const LazyRoute = createLazyRoute( '/' )( {
	component: withSuspense( TitleAndDescription ),
} );

export default withSuspense( TitleAndDescription );
