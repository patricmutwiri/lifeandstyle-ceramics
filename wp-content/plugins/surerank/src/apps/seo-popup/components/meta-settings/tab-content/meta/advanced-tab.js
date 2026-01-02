import { __ } from '@wordpress/i18n';
import { Label, Checkbox, Input } from '@bsf/force-ui';
import { withSelect, withDispatch } from '@wordpress/data';
import { compose } from '@wordpress/compose';
import { STORE_NAME } from '@Store/constants';
import { ROBOTS_OPTIONS as robotOptions } from '@Global/constants';
import { SeoPopupInfoTooltip } from '@AdminComponents/tooltip';

const AdvancedTab = ( {
	postMetaData,
	setMetaData,
	canonicalURLPlaceholder,
	globalDefaults,
} ) => {
	const postType =
		surerank_seo_popup?.post_type ??
		wp?.data?.select( 'core/editor' )?.getCurrentPostType() ??
		'post';

	const noindex = postMetaData?.post_no_index;
	const nofollow = postMetaData?.post_no_follow;
	const noarchive = postMetaData?.post_no_archive;

	let defaultRobots = {};

	// Check if `robots.general` is empty and populate it with default values
	if ( noindex === '' && nofollow === '' && noarchive === '' ) {
		const no_index_array =
			globalDefaults?.no_index !== undefined
				? globalDefaults.no_index
				: [];

		const no_follow_array =
			globalDefaults?.no_follow !== undefined
				? globalDefaults.no_follow
				: [];

		const no_archive_array =
			globalDefaults?.no_archive !== undefined
				? globalDefaults.no_archive
				: [];

		if ( no_index_array?.includes( postType ) ) {
			defaultRobots = {
				...defaultRobots,
				post_no_index: 'yes',
			};
		}
		if ( no_follow_array?.includes( postType ) ) {
			defaultRobots = {
				...defaultRobots,
				post_no_follow: 'yes',
			};
		}
		if ( no_archive_array?.includes( postType ) ) {
			defaultRobots = {
				...defaultRobots,
				post_no_archive: 'yes',
			};
		}
	}

	const handleSelect = ( key, value ) => {
		defaultRobots = {
			...defaultRobots,
			[ key ]: value ? 'yes' : 'no',
		};
		setMetaData( defaultRobots );
	};

	const onChangeCanonical = ( value ) => {
		setMetaData( { canonical_url: value } );
	};

	if ( noindex === 'yes' ) {
		defaultRobots = {
			...defaultRobots,
			post_no_index: 'yes',
		};
	}
	if ( nofollow === 'yes' ) {
		defaultRobots = {
			...defaultRobots,
			post_no_follow: 'yes',
		};
	}
	if ( noarchive === 'yes' ) {
		defaultRobots = {
			...defaultRobots,
			post_no_archive: 'yes',
		};
	}

	return (
		<div className="flex flex-col gap-3 px-3 mt-1">
			{ /* Robot Instructions Section */ }
			<div className="flex flex-col gap-2">
				<div className="flex items-center gap-1">
					<Label as="p" size="sm">
						{ __( 'Robot Instructions', 'surerank' ) }
						<SeoPopupInfoTooltip
							content={ __(
								'These settings help search engines understand how to treat your page in search results. Enabling "No Index" will prevent the page from appearing in search results. "No Follow" tells search engines not to follow any links on the page, and "No Archive" prevents search engines from storing a cached version of the page.',
								'surerank'
							) }
						/>
					</Label>
				</div>
				<div className="flex flex-col gap-2 items-start">
					{ robotOptions.map( ( option ) => (
						<Checkbox
							key={ option.id }
							size="sm"
							label={ {
								heading: (
									<div className="flex items-center gap-1">
										{ option.label }
										<SeoPopupInfoTooltip
											content={ option.description }
										/>
									</div>
								),
							} }
							checked={ defaultRobots[ option.id ] === 'yes' }
							onChange={ ( checked ) =>
								handleSelect( option.id, checked )
							}
						/>
					) ) }
				</div>
			</div>

			{ /* Canonical URL Section */ }
			<div className="flex flex-col gap-1.5">
				<Label
					htmlFor="canonical-url"
					size="sm"
					className="flex items-center gap-1"
				>
					{ __( 'Canonical URL', 'surerank' ) }
					<SeoPopupInfoTooltip
						content={ __(
							'The Canonical URL tells search engines which version of a page should be indexed to avoid duplicate content issues. Leave blank to let SureRank set it automatically.',
							'surerank'
						) }
					/>
				</Label>
				<Input
					id="canonical-url"
					type="text"
					size="md"
					className="[&_input]:m-0"
					value={ postMetaData.canonical_url }
					onChange={ ( value ) => onChangeCanonical( value ) }
					placeholder={ canonicalURLPlaceholder }
				/>
			</div>
		</div>
	);
};

export default compose(
	withSelect( ( select ) => {
		const selectStore = select( STORE_NAME );
		const canonicalURLPlaceholder =
			select( 'core/editor' )?.getPermalink() ??
			surerank_seo_popup?.link ??
			'';
		return {
			postMetaData: selectStore.getPostSeoMeta(),
			canonicalURLPlaceholder,
			globalDefaults: selectStore.getGlobalDefaults(),
		};
	} ),
	withDispatch( ( dispatch ) => {
		const dispatchStore = dispatch( STORE_NAME );

		return {
			setMetaData: ( value ) => dispatchStore.updatePostMetaData( value ),
		};
	} )
)( AdvancedTab );
