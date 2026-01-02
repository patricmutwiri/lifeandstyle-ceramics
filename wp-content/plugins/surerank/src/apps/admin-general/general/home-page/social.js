import { __, sprintf } from '@wordpress/i18n';
import { motion } from 'framer-motion';
import {
	Tabs,
	Label,
	Input,
	EditorInput,
	Switch,
	Container,
	Text,
} from '@bsf/force-ui';
import { useCallback, useRef, useState } from '@wordpress/element';
import { Facebook } from 'lucide-react';
import SocialPreview from '@GlobalComponents/social-preview';
import {
	INPUT_VARIABLE_SUGGESTIONS as variableSuggestions,
	MAX_EDITOR_INPUT_LENGTH,
} from '@Global/constants';
import {
	editorValueToString,
	stringValueToFormatJSON,
	truncateText,
} from '@Functions/utils';
import replacement from '@Functions/replacement';
import GeneratePageContent from '@Functions/page-content-generator';
import useSettings from '@/global/hooks/use-admin-settings';
import { InfoTooltip } from '@AdminComponents/tooltip';
import { XSocialIcon } from '@/global/components/icons';
import MediaPreview from '@/apps/admin-components/media-preview';
import { createMediaFrame } from '@/global/utils/utils';
const socialMedia = [
	{
		label: 'Facebook',
		icon: Facebook,
		tabSlug: 'facebook',
	},
	{
		label: 'X',
		icon: XSocialIcon,
		tabSlug: 'twitter',
	},
];

const renderIf = ( condition, content, fallbackContent ) => {
	if ( condition ) {
		return typeof content === 'function' ? content() : content;
	}

	return typeof fallbackContent === 'function'
		? fallbackContent()
		: fallbackContent;
};

const SocialSettings = () => {
	const { metaSettings, siteSettings, setMetaSetting, setSiteSettings } =
		useSettings();
	const [ activeSocialTab, setActiveSocialTab ] = useState( 'facebook' );

	const handleTabChange = useCallback( ( { value } ) => {
		setActiveSocialTab( value.slug );
	}, [] );

	const handleClickInput = ( event ) => {
		event.preventDefault();

		const mediaUploader = createMediaFrame( {
			title: 'Select Image',
			button: {
				text: 'Use this image',
			},
			multiple: false,
		} );

		mediaUploader.on( 'select', () => {
			const attachment = mediaUploader
				.state()
				.get( 'selection' )
				.first()
				.toJSON();
			setMetaSetting(
				`home_page_${ activeSocialTab }_image_url`,
				attachment.url
			);
			setSiteSettings( {
				...siteSettings,
				home_page_featured_image: attachment.url,
			} );
		} );

		mediaUploader.open();
	};

	const getPreviewData = useCallback(
		( key, fallbackValue ) => {
			let mainKey = `home_page_${ activeSocialTab }`;

			// If the active tab is Twitter and it's set to use Facebook data
			if (
				activeSocialTab === 'twitter' &&
				!! metaSettings?.twitter_same_as_facebook
			) {
				mainKey = 'home_page_facebook'; // Use Facebook's data
			}

			// Fetch the data from metaSettings or fallback value
			return (
				metaSettings?.[ `${ mainKey }_${ key }` ] ||
				fallbackValue?.[ `${ mainKey }_${ key }` ]
			);
		},
		[ activeSocialTab, metaSettings ]
	);

	const { home_page_title: title, home_page_description: description } =
		metaSettings;

	const titlePreview = truncateText(
		replacement(
			getPreviewData( 'title', metaSettings ) || title,
			siteSettings?.site
		),
		null
	);

	const descriptionPreview = truncateText(
		replacement(
			getPreviewData( 'description', metaSettings ) || description,
			siteSettings?.site
		),
		78
	);

	const defaultTitleValue =
		metaSettings[ `home_page_${ activeSocialTab }_title` ] || title;
	const defaultDescriptionValue =
		metaSettings[ `home_page_${ activeSocialTab }_description` ] ||
		description;

	const titleEditor = useRef( null );
	const descriptionEditor = useRef( null );

	const handleUpdatePostMetaData = ( key, value ) => {
		setMetaSetting( key, value );
	};

	const removeImage = useCallback( () => {
		setMetaSetting( `home_page_${ activeSocialTab }_image_url`, '' );
		setSiteSettings( {
			...siteSettings,
			home_page_featured_image: '',
		} );
	}, [ setMetaSetting, activeSocialTab ] );

	return (
		<Container direction="column" className="w-full gap-6">
			<div>
				<Tabs.Group
					activeItem={ activeSocialTab }
					variant="rounded"
					width="full"
					onChange={ handleTabChange }
				>
					{ socialMedia.map( ( { label, icon: Icon, tabSlug } ) => (
						<Tabs.Tab
							key={ tabSlug }
							slug={ tabSlug }
							text={ label }
							icon={ <Icon /> }
						/>
					) ) }
				</Tabs.Group>
			</div>

			<motion.div
				key={ activeSocialTab }
				className="flex-1 flex flex-col gap-6"
				initial={ { opacity: 0 } }
				animate={ { opacity: 1 } }
				exit={ { opacity: 0 } }
				transition={ { duration: 0.2 } }
			>
				{ /* Use data from Facebook tab toggle button */ }
				{ activeSocialTab === 'twitter' && (
					<div className="flex items-center gap-3">
						<Switch
							id="facebook_same_as_twitter"
							name="facebook_same_as_twitter"
							size="sm"
							defaultValue={
								!! metaSettings?.twitter_same_as_facebook
							}
							onChange={ ( value ) => {
								setMetaSetting(
									'twitter_same_as_facebook',
									value ? '1' : ''
								);
							} }
						/>
						<Label htmlFor="facebook_same_as_twitter" size="sm">
							{ __( 'Use data from Facebook Tab', 'surerank' ) }
						</Label>
					</div>
				) }

				{ renderIf(
					activeSocialTab === 'twitter' &&
						!! metaSettings?.twitter_same_as_facebook,
					null,
					<>
						{ /* Social Title */ }
						<div className="space-y-1.5">
							{ /* Label & Limit */ }
							<div className="flex items-center justify-between gap-1">
								<Label
									tag="span"
									size="sm"
									className="space-x-0.5"
								>
									<span>
										{ __( 'Social Title', 'surerank' ) }
									</span>
								</Label>
							</div>
							{ /* Input */ }
							<EditorInput
								ref={ titleEditor }
								by="label"
								trigger="@"
								defaultValue={ stringValueToFormatJSON(
									defaultTitleValue,
									variableSuggestions,
									'value'
								) }
								options={ variableSuggestions }
								onChange={ ( editorState ) => {
									handleUpdatePostMetaData(
										`home_page_${ activeSocialTab }_title`,
										editorValueToString(
											editorState.toJSON()
										)
									);
								} }
								placeholder={ '' }
							/>
							{ /* Hint text */ }
							<Text size={ 14 } weight={ 400 } color="help">
								{ __(
									'Type @ to view variable suggestions',
									'surerank'
								) }
							</Text>
						</div>

						{ /* Social description */ }
						<div className="space-y-1.5">
							{ /* Label & Limit */ }
							<div className="flex items-center justify-between gap-1">
								<Label
									tag="span"
									size="sm"
									className="space-x-0.5"
								>
									<span>
										{ __(
											'Social Description',
											'surerank'
										) }
									</span>
								</Label>
							</div>
							{ /* Input */ }
							<EditorInput
								ref={ descriptionEditor }
								className="[&+div]:items-start [&+div]:pt-1"
								by="label"
								trigger="@"
								defaultValue={ stringValueToFormatJSON(
									defaultDescriptionValue,
									variableSuggestions,
									'value'
								) }
								options={ variableSuggestions }
								onChange={ ( editorState ) => {
									handleUpdatePostMetaData(
										`home_page_${ activeSocialTab }_description`,
										editorValueToString(
											editorState.toJSON()
										)
									);
								} }
								placeholder={ '' }
								maxLength={ MAX_EDITOR_INPUT_LENGTH }
							/>
							{ /* Hint text */ }
							<Text size={ 14 } weight={ 400 } color="help">
								{ __(
									'Type @ to view variable suggestions',
									'surerank'
								) }
							</Text>
						</div>
						<div className="space-y-1.5">
							{ /* Label */ }
							<div className="flex items-center justify-start gap-1">
								<Label tag="span" size="sm">
									{ __( 'Social Image', 'surerank' ) }
								</Label>
								<InfoTooltip
									content={ __(
										'Set a default image that will be used for social sharing when no featured or social-specific image is available. This ensures your content always has a visual when shared on platforms like Facebook or X (Twitter).',
										'surerank'
									) }
								/>
							</div>
							{ /* Input */ }
							<Input
								className="m-0 [&>input]:m-0 [&>input]:transition-colors [&>input]:duration-150 [&>input]:ease-in-out"
								type="file"
								size="md"
								onClick={ handleClickInput }
							/>
							<MediaPreview
								imageUrl={ getPreviewData(
									`image_url`,
									siteSettings
								) }
								onRemove={ removeImage }
							/>
						</div>
					</>
				) }
				<div className="space-y-2">
					{ /* Label */ }
					<Label tag="span" size="sm">
						{ sprintf(
							// Translators: %s: Facebook or Twitter
							__( '%s Preview', 'surerank' ),
							activeSocialTab === 'facebook' ? 'Facebook' : 'X'
						) }
					</Label>
					{ /* Preview */ }
					<SocialPreview
						type={ activeSocialTab }
						title={ titlePreview }
						description={ descriptionPreview }
						twitterLargePreview={
							metaSettings?.twitter_card_type ===
							'summary_large_image'
						}
						imageURL={ getPreviewData( `image_url`, siteSettings ) }
						siteURL={ siteSettings?.site?.site_url?.replace(
							/(^\w+:|^)\/\//,
							''
						) }
						hideRemoveButton
					/>
				</div>
			</motion.div>
		</Container>
	);
};

const PAGE_CONTENT = [
	{
		container: null,
		content: [
			{
				id: 'homepage-social-settings',
				type: 'custom',
				component: <SocialSettings />,
			},
		],
	},
];

const SocialTab = () => {
	return <GeneratePageContent json={ PAGE_CONTENT } />;
};

export default SocialTab;
