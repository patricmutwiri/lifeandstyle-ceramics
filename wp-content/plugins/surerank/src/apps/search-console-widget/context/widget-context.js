import {
	createContext,
	useContext,
	useReducer,
	useMemo,
} from '@wordpress/element';

// Action types
export const ACTIONS = {
	SET_LOADING: 'SET_LOADING',
	SET_CLICKS_DATA: 'SET_CLICKS_DATA',
	SET_SITE_TRAFFIC: 'SET_SITE_TRAFFIC',
	SET_ERROR: 'SET_ERROR',
};

// Initial state
const initialState = {
	isLoading: false,
	clicksData: [
		{
			label: 'Clicks',
			value: null,
			previous: null,
			percentage: null,
			percentageType: 'success',
			color: 'bg-sky-500',
		},
		{
			label: 'Impressions',
			value: null,
			previous: null,
			percentage: null,
			percentageType: 'success',
			color: 'bg-background-brand',
		},
	],
	siteTraffic: [],
	error: null,
};

// Reducer function
const widgetReducer = ( state, action ) => {
	switch ( action.type ) {
		case ACTIONS.SET_LOADING:
			return { ...state, isLoading: action.payload };
		case ACTIONS.SET_CLICKS_DATA:
			return { ...state, clicksData: action.payload };
		case ACTIONS.SET_SITE_TRAFFIC:
			return { ...state, siteTraffic: action.payload };
		case ACTIONS.SET_ERROR:
			return { ...state, error: action.payload };
		default:
			return state;
	}
};

// Create separate contexts for state and dispatch to avoid unnecessary re-renders
const WidgetStateContext = createContext( undefined );
const WidgetDispatchContext = createContext( undefined );

/**
 * Widget Context Provider
 *
 * @param {Object}                          props          Component props
 * @param {import('react').React.ReactNode} props.children Child components
 * @return {JSX.Element} Provider component
 */
export const WidgetProvider = ( { children } ) => {
	const [ state, dispatch ] = useReducer( widgetReducer, initialState );

	// Memoize state and dispatch to prevent unnecessary re-renders
	const memoizedState = useMemo( () => state, [ state ] );
	const memoizedDispatch = useMemo( () => dispatch, [ dispatch ] );

	return (
		<WidgetStateContext.Provider value={ memoizedState }>
			<WidgetDispatchContext.Provider value={ memoizedDispatch }>
				{ children }
			</WidgetDispatchContext.Provider>
		</WidgetStateContext.Provider>
	);
};

/**
 * Hook to use widget state
 *
 * @return {Object} Widget state
 */
export const useWidgetState = () => {
	const context = useContext( WidgetStateContext );
	if ( context === undefined ) {
		throw new Error(
			'useWidgetState must be used within a WidgetProvider'
		);
	}
	return context;
};

/**
 * Hook to use widget dispatch
 *
 * @return {Function} Dispatch function
 */
export const useWidgetDispatch = () => {
	const context = useContext( WidgetDispatchContext );
	if ( context === undefined ) {
		throw new Error(
			'useWidgetDispatch must be used within a WidgetProvider'
		);
	}
	return context;
};
