import { Tabs, Skeleton } from '@bsf/force-ui';
import { useState, useCallback } from '@wordpress/element';
import { motion } from 'framer-motion';
import { STORE_NAME } from '@AdminStore/constants';
import { useDispatch, useSuspenseSelect } from '@wordpress/data';
import SubTabToggles from './sub-tab-toggles';
import { __ } from '@wordpress/i18n';

const FollowingTab = ( { postContent, keyWord } ) => {
	const subTabOption = [
		{
			label: __( 'Post Types', 'surerank' ),
			tabSlug: 'postTypes',
		},
		{
			label: __( 'Taxonomies', 'surerank' ),
			tabSlug: 'taxonomies',
		},
		{
			label: __( 'Archives', 'surerank' ),
			tabSlug: 'archives',
		},
	];
	const [ postTypesData, setPostTypesData ] = useState( [] );
	const [ taxonomiesData, setTaxonomiesData ] = useState( [] );
	const [ archivesData, setArchivesData ] = useState( [] );
	const { setMetaSetting } = useDispatch( STORE_NAME );
	const { metaSettings } = useSuspenseSelect( ( select ) => {
		const { getMetaSettings } = select( STORE_NAME );
		return {
			metaSettings: getMetaSettings(),
		};
	}, [] );

	const [ activeSocialTab, setActiveSocialTab ] = useState( 'postTypes' );
	const handleTabChange = useCallback( ( { value } ) => {
		setActiveSocialTab( value.slug );
	}, [] );

	const handleSettingsChange = useCallback(
		( key, value ) => {
			setMetaSetting( key, value ); //store in redux
		},
		[ setMetaSetting ]
	);

	const handleSwitchChange = ( key, value ) => {
		const updateAndCombineData = ( setStateFunction, updatedValue ) => {
			setStateFunction( updatedValue );
			const combinedData = Array.from(
				new Set( [
					...( key === 'postTypes' ? updatedValue : postTypesData ),
					...( key === 'taxonomies' ? updatedValue : taxonomiesData ),
					...( key === 'archives' ? updatedValue : archivesData ),
				] )
			);

			// Store in Redux with filtered data
			handleSettingsChange( 'no_follow', combinedData );
		};

		// Update the state based on the key
		switch ( key ) {
			case 'postTypes':
				updateAndCombineData( setPostTypesData, value );
				break;
			case 'taxonomies':
				updateAndCombineData( setTaxonomiesData, value );
				break;
			case 'archives':
				updateAndCombineData( setArchivesData, value );
				break;
			default:
				break;
		}
	};

	const renderTabComponent = () => {
		if ( ! postContent ) {
			return (
				<div className="space-y-4 pt-2">
					<Skeleton
						variant="rectangular"
						className="w-full max-w-72 h-5"
					/>
					<Skeleton
						variant="rectangular"
						className="w-full max-w-72 h-5"
					/>
					<Skeleton
						variant="rectangular"
						className="w-full max-w-72 h-5"
					/>
				</div>
			);
		}
		switch ( activeSocialTab ) {
			case 'taxonomies':
				return (
					<SubTabToggles
						type="taxonomies"
						keyWord={ keyWord }
						listData={ postContent.taxonomies }
						settings={ metaSettings.no_follow }
						handleSwitchChange={ handleSwitchChange }
					/>
				);
			case 'archives':
				return (
					<SubTabToggles
						type="archives"
						keyWord={ keyWord }
						listData={ postContent.archives }
						settings={ metaSettings.no_follow }
						handleSwitchChange={ handleSwitchChange }
					/>
				);
			default:
				return (
					<SubTabToggles
						type="postTypes"
						keyWord={ keyWord }
						listData={ postContent.post_types }
						settings={ metaSettings.no_follow }
						handleSwitchChange={ handleSwitchChange }
					/>
				);
		}
	};

	return (
		<div className="space-y-4">
			<Tabs.Group
				activeItem={ activeSocialTab }
				variant="rounded"
				width="full"
				onChange={ handleTabChange }
				className="m-2"
			>
				{ subTabOption.map( ( { label, tabSlug } ) => (
					<Tabs.Tab key={ tabSlug } slug={ tabSlug } text={ label } />
				) ) }
			</Tabs.Group>
			<motion.div
				key={ activeSocialTab }
				initial={ { opacity: 0, x: 0 } }
				animate={ { opacity: 1, y: 0 } }
				exit={ { opacity: 0, x: -10 } }
				transition={ { duration: 0.3 } }
				className="w-full"
			>
				{ renderTabComponent() }
			</motion.div>
		</div>
	);
};
export default FollowingTab;
