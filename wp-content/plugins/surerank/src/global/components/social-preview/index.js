import { __, sprintf } from '@wordpress/i18n';
import { Image, BadgeCheck } from 'lucide-react';
import { Avatar, Text } from '@bsf/force-ui';
import { cn, decodeHtmlEntities } from '@Functions/utils';
import GlobalRemoveButton from '@AdminComponents/global-remove-image-button';
import { renderToString } from '@wordpress/element';

const getDateString = ( type ) => {
	const date = new Date();
	const month = date.toLocaleString( 'default', { month: 'long' } );
	const day = date.getDate();

	if ( 'twitter' === type ) {
		return `${ month?.slice( 0, 3 ) } ${ day }`;
	}

	return `${ month } ${ day }`;
};

const ImagePlaceholder = ( { className, ...props } ) => (
	<div
		className={ cn(
			'w-full h-full bg-background-secondary flex items-center justify-center',
			className
		) }
		{ ...props }
	>
		<Image className="size-6 m-auto" />
	</div>
);

const TwitterPreview = ( {
	displayName = 'Name',
	username = '@username',
	timeLabel = '28m',
	postText = 'Sample Post Title',
	title = __(
		'Modern WordPress SEO Plugin Without the Bloat - SureRank',
		'surerank'
	),
	description = '',
	imageURL = '',
	siteURL = 'surerank.com',
	onClickRemove,
	hideRemoveButton = true,
	forMetaBox = false,
	cardType = 'summary_large_image', // 'summary_large_image' or 'summary'
} ) => {
	const decodedTitle = decodeHtmlEntities( title );
	const decodedDescription = decodeHtmlEntities( description );
	const commonProps = {
		id: 'x-preview-large',
		className: '[#x-preview-large&_*]:font-twitter',
	};
	const avatar = (
		<Avatar
			className="flex-shrink-0"
			size={ forMetaBox ? 'sm' : 'md' }
			variant="primary"
			src="https://www.gravatar.com/avatar/?d=mp"
		>
			User
		</Avatar>
	);
	const userInfo = (
		<div className="inline-flex items-center gap-1">
			<Text
				as="p"
				className="m-0"
				size={ 16 }
				weight={ 500 }
				color="secondary"
			>
				{ displayName }
			</Text>
			<BadgeCheck
				className="size-5 text-white fill-[#1d9bf0]"
				aria-label={ __( 'Verified account', 'surerank' ) }
			/>
			<Text
				as="p"
				className="m-0"
				size={ 16 }
				weight={ 400 }
				color="secondary"
			>
				{ username } · { timeLabel }
			</Text>
		</div>
	);

	// For large card preview
	if ( cardType === 'summary_large_image' ) {
		const defaultPostText = sprintf(
			/* translators: %s: site URL */
			__( 'Check out my page: %s', 'surerank' ),
			renderToString(
				<Text
					className="inline text-[#1d9bf0] m-0 hover:underline"
					as="p"
				>
					{ siteURL.startsWith( 'http' )
						? siteURL
						: `https://${ siteURL }` }
				</Text>
			)
		);

		return (
			<div { ...commonProps }>
				<div className="p-3 flex items-start gap-3">
					{ avatar }
					<div className="flex-1">
						{ userInfo }
						{ postText ? (
							<Text
								as="p"
								className="mt-0.5 mb-0 line-clamp-2"
								size={ 14 }
								color="secondary"
							>
								{ postText }
							</Text>
						) : (
							<Text
								as="p"
								color="secondary"
								size={ 14 }
								className="mt-0.5 mb-0 line-clamp-2"
								dangerouslySetInnerHTML={ {
									__html: defaultPostText,
								} }
							/>
						) }

						<div className="relative mt-3 rounded-2xl border border-solid border-border-subtle overflow-hidden">
							{ imageURL ? (
								<div
									className={ cn(
										'relative w-full h-[16.8125rem]',
										forMetaBox && 'h-52'
									) }
								>
									<img
										src={ imageURL }
										alt="Shared link preview"
										className="w-full h-full object-cover"
									/>
									{ ! hideRemoveButton && (
										<GlobalRemoveButton
											onClick={ onClickRemove }
										/>
									) }
								</div>
							) : (
								<div className="h-[16.8125rem]">
									<ImagePlaceholder />
								</div>
							) }

							<div className="inline-block w-fit px-2 rounded absolute bottom-3 left-3 right-3 text-xs text-white bg-black/[0.77]">
								<Text
									as="p"
									className="m-0 line-clamp-1 text-inherit"
								>
									{ decodedTitle }
								</Text>
							</div>
						</div>

						<Text
							as="p"
							color="secondary"
							size={ 14 }
							weight={ 400 }
							className="mt-2 mb-1"
						>
							{ __( 'From', 'surerank' ) }{ ' ' }
							<span className="hover:underline">{ siteURL }</span>
						</Text>
					</div>
				</div>
			</div>
		);
	}

	// For summary (small) card preview
	return (
		<div
			id={ commonProps.id }
			className={ cn( 'p-3', commonProps.className ) }
		>
			{ /* Header */ }
			<div className="flex gap-3 justify-start items-start">
				{ avatar }
				<div className="space-y-4">
					{ userInfo }
					<div className="grid overflow-hidden min-h-[7.75rem] grid-cols-[7.5rem_1fr] rounded-2xl border border-solid border-border-subtle">
						{ imageURL ? (
							<div className="relative w-full h-full inline-flex">
								<img
									className="w-full h-full object-cover m-0 max-h-[7.625rem] border-y-0 border-l-0 border-r border-solid border-border-subtle"
									src={ imageURL }
									alt="thumbnail"
								/>
								{ ! hideRemoveButton && (
									<GlobalRemoveButton
										onClick={ onClickRemove }
									/>
								) }
							</div>
						) : (
							<ImagePlaceholder className="border-y-0 border-l-0 border-r border-solid border-border-subtle" />
						) }
						<div className="inline-grid items-center justify-start gap-0.5 px-3 py-5">
							<p className="m-0 font-normal text-text-secondary leading-4 text-xs">
								{ siteURL }
							</p>
							<div className="w-full overflow-hidden">
								<p className="m-0 text-[0.9375rem] font-semibold text-text-primary whitespace-nowrap leading-5">
									{ decodedTitle }
								</p>
							</div>
							<p className="m-0 font-normal text-text-secondary line-clamp-3 leading-5 text-sm">
								{ decodedDescription || '' }
							</p>
						</div>
					</div>
				</div>
			</div>
		</div>
	);
};

const SocialPreview = ( {
	displayName = 'Name',
	username = '@username',
	type = 'facebook',
	title = __( 'Sample Post - Testing Site', 'surerank' ),
	description = '',
	imageURL = '',
	siteURL = 'surerank.com',
	twitterLargePreview = false,
	onClickRemove,
	hideRemoveButton = true,
	forMetaBox = false,
} ) => {
	let designContent = null;
	const decoded_description = decodeHtmlEntities( description );
	const decoded_title = decodeHtmlEntities( title );
	const descriptionContent = decoded_description || '';

	switch ( type ) {
		case 'twitter':
			designContent = (
				<TwitterPreview
					displayName={ displayName }
					username={ username }
					timeLabel={ getDateString( 'twitter' ) }
					title={ decoded_title }
					description={ decoded_description }
					imageURL={ imageURL }
					siteURL={ siteURL }
					onClickRemove={ onClickRemove }
					hideRemoveButton={ hideRemoveButton }
					forMetaBox={ forMetaBox }
					cardType={
						twitterLargePreview ? 'summary_large_image' : 'summary'
					}
				/>
			);
			break;
		case 'facebook':
			designContent = (
				<>
					{ /* Image */ }
					<div className="w-full h-[16.8125rem] overflow-clip">
						{ imageURL ? (
							<div className="relative w-full h-full">
								<img
									src={ imageURL }
									alt="Social Post"
									className="w-full h-full object-cover"
								/>
								{ ! hideRemoveButton && (
									<GlobalRemoveButton
										onClick={ onClickRemove }
									/>
								) }
							</div>
						) : (
							<ImagePlaceholder />
						) }
					</div>

					{ /* Footer */ }
					<div className="p-3 w-full">
						<p className="m-0 text-xs leading-4 font-normal text-text-secondary">
							{ siteURL }
						</p>
						<p className="mt-1.5 mb-1 text-base leading-6 font-semibold text-text-primary line-clamp-2">
							{ decoded_title }
						</p>
						<p className="m-0 text-sm leading-5 font-normal line-clamp-2">
							{ descriptionContent }
						</p>
					</div>
				</>
			);
			break;
		default:
			designContent = null;
			break;
	}

	return (
		<div className="p-2 rounded-lg bg-background-secondary">
			<div className="flex flex-col rounded-md border border-solid border-border-subtle shadow-sm overflow-hidden bg-background-primary">
				{ designContent }
			</div>
		</div>
	);
};

export default SocialPreview;
