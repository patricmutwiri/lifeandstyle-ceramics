import { __ } from '@wordpress/i18n';
import { useState, useMemo, useCallback } from '@wordpress/element';
import { useSuspenseSelect, useDispatch } from '@wordpress/data';
import { STORE_NAME } from '@AdminStore/constants';
import { Label, Button } from '@bsf/force-ui';
import { Plus } from 'lucide-react';
import apiFetch from '@wordpress/api-fetch';
import ConditionSelect from './condition-select';
import { GET_POSTS_BY_QUERY_URL } from '@Global/constants/api';

const DisplayConditions = ( { schemaId } ) => {
	const { setMetaSetting } = useDispatch( STORE_NAME );

	const { metaSettings } = useSuspenseSelect( ( select ) => {
		const { getMetaSettings } = select( STORE_NAME );
		return { metaSettings: getMetaSettings() };
	}, [] );

	const displayRules = surerank_globals?.schema_rules || {};
	const schemaSettings = metaSettings.schemas?.[ schemaId ] || {};

	const groupedOptions = useMemo( () => {
		return Object.entries( displayRules ).map( ( [ key, rule ] ) => ( {
			label: rule.label,
			options: Object.entries( rule.value ).map(
				( [ valueKey, label ] ) => ( {
					value: valueKey,
					label,
					key,
				} )
			),
		} ) );
	}, [ displayRules ] );

	const initialShowOn = schemaSettings.show_on?.rules?.length
		? Array.from( new Set( schemaSettings.show_on?.rules ) ).map(
				( rule ) => ( {
					condition: rule,
					specificPosts:
						rule === 'specifics'
							? schemaSettings?.show_on?.specificText || []
							: [],
					searchOptions: [],
				} )
		  )
		: [ { condition: '', specificPosts: [], searchOptions: [] } ];

	const initialNotShowOn = schemaSettings.not_show_on?.rules?.length
		? Array.from( new Set( schemaSettings.not_show_on?.rules ) ).map(
				( rule ) => ( {
					condition: rule,
					specificPosts:
						rule === 'specifics'
							? schemaSettings?.not_show_on?.specificText || []
							: [],
					searchOptions: [],
				} )
		  )
		: [ { condition: '', specificPosts: [], searchOptions: [] } ];

	// Flattened state
	const [ conditionsState, setConditionsState ] = useState( {
		show_on: initialShowOn,
		not_show_on: initialNotShowOn,
	} );

	// Function to update specific conditions
	const setConditionsByType = ( type, updatedConditions ) => {
		setConditionsState( ( prevState ) => ( {
			...prevState,
			[ type ]: updatedConditions,
		} ) );
	};

	const fetchSpecificPosts = useCallback(
		async ( query, index, type ) => {
			try {
				const response = await apiFetch( {
					path: GET_POSTS_BY_QUERY_URL,
					method: 'POST',
					data: { q: query },
				} );

				if ( response?.success && Array.isArray( response?.results ) ) {
					const groupedOptionsData = response.results.map(
						( group ) => ( {
							label: group.text,
							options: group.children.map( ( child ) => ( {
								value: child.id,
								label: child.text,
							} ) ),
						} )
					);

					setConditionsState( ( prevState ) => {
						const updatedConditions = [ ...prevState[ type ] ];
						updatedConditions[ index ].searchOptions =
							groupedOptionsData;
						return {
							...prevState,
							[ type ]: updatedConditions,
						};
					} );
				}
			} catch ( error ) {
				// return error;
			}
		},
		[] // No dependencies because it doesn't rely on external state or props
	);

	const updateSchema = useCallback(
		( key, updatedRules, updatedSpecific = null ) => {
			const deduplicatedSpecific = Array.from(
				new Set( updatedSpecific?.map( ( item ) => item.value ) )
			);
			const updatedSpecificText =
				updatedSpecific?.filter( ( item ) =>
					deduplicatedSpecific.includes( item.value )
				) || [];

			const updatedSchema = {
				...schemaSettings,
				[ key ]: {
					rules: updatedRules,
					specific: deduplicatedSpecific,
					specificText: updatedSpecificText,
				},
			};

			setMetaSetting( 'schemas', {
				...metaSettings.schemas,
				[ schemaId ]: updatedSchema,
			} );
		},
		[ schemaSettings, schemaId, metaSettings.schemas, setMetaSetting ]
	);

	const addCondition = ( type ) => {
		setConditionsState( ( prevState ) => {
			const updatedConditions = [ ...prevState[ type ] ];

			updatedConditions.push( {
				condition: '',
				specificPosts: [],
				searchOptions: [],
			} );

			updateSchema(
				type,
				updatedConditions.map( ( cond ) => cond.condition ),
				updatedConditions
					.filter( ( cond ) => cond.condition === 'specifics' )
					.flatMap( ( cond ) => cond.specificPosts )
			);

			return {
				...prevState,
				[ type ]: updatedConditions,
			};
		} );
	};

	return (
		<>
			<div className="space-y-2 p-2">
				<Label required>{ __( 'Display On', 'surerank' ) }</Label>
				<ConditionSelect
					conditionsList={ conditionsState.show_on }
					setConditionsList={ ( updatedConditions ) =>
						setConditionsByType( 'show_on', updatedConditions )
					}
					groupedOptions={ groupedOptions }
					viewKey="show_on"
					updateSchema={ updateSchema }
					fetchSpecificPosts={ fetchSpecificPosts }
					isClearable={ true }
				/>
				<Button
					variant="ghost"
					size="xs"
					onClick={ () => addCondition( 'show_on' ) }
					className="flex items-center gap-1"
					icon={ <Plus className="size-4" /> }
				>
					{ __( 'Add Condition', 'surerank' ) }
				</Button>
			</div>

			<div className="space-y-2 p-2">
				<Label>{ __( 'Do Not Display On', 'surerank' ) }</Label>
				<ConditionSelect
					conditionsList={ conditionsState.not_show_on }
					setConditionsList={ ( updatedConditions ) =>
						setConditionsByType( 'not_show_on', updatedConditions )
					}
					groupedOptions={ groupedOptions }
					viewKey="not_show_on"
					updateSchema={ updateSchema }
					fetchSpecificPosts={ fetchSpecificPosts }
					isClearable={ true }
				/>
				<Button
					variant="ghost"
					size="xs"
					onClick={ () => addCondition( 'not_show_on' ) }
					className="flex items-center gap-1"
					icon={ <Plus className="size-4" /> }
				>
					{ __( 'Add Condition', 'surerank' ) }
				</Button>
			</div>
		</>
	);
};

export default DisplayConditions;
