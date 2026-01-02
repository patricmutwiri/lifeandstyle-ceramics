import {
	createContext,
	useCallback,
	useContext,
	useMemo,
	useReducer,
	useEffect,
} from '@wordpress/element';
import { useLocation } from 'react-router-dom';

/* global sessionStorage */

// Session storage
export const ONBOARDING_SESSION_STORAGE_KEY = 'SUREMAIL_ONBOARDING';

// Context provider for the onboarding layout
const OnboardingContext = createContext( {} );

export const useOnboardingState = ( selector ) => {
	const [ state, dispatch ] = useContext( OnboardingContext );
	const selectedState = useMemo(
		() => ( selector ? selector( state ) : state ),
		[ state, selector ]
	);
	const improvedDispatch = useCallback(
		( action ) => {
			dispatch( typeof action === 'function' ? action( state ) : action );
		},
		[ dispatch, state ]
	);
	return [ selectedState, improvedDispatch ];
};

const reducer = ( state, action ) => {
	return {
		...state,
		...action,
	};
};

export const OnboardingProvider = ( { children } ) => {
	const location = useLocation();
	const { connection = '', auth_code = '' } = location.state ?? {};

	const getSavedState = useCallback( () => {
		try {
			const savedState = sessionStorage.getItem(
				ONBOARDING_SESSION_STORAGE_KEY
			);
			if ( savedState ) {
				return JSON.parse( savedState );
			}
		} catch ( error ) {
			// Do nothing
		}
	}, [] );
	const savedState = getSavedState();

	const initialState = savedState
		? {
				...savedState,
				connectionFormData: {
					...( savedState.connectionFormData ?? {} ),
					...( connection && auth_code
						? { connection, auth_code }
						: {} ),
				},
		  }
		: {
				safeGuard: {
					activation: suremails?.contentGuardActiveStatus === 'yes',
					showLeadForm: Boolean( suremails?.contentGuardPopupStatus ),
				},
		  };

	const [ state, dispatch ] = useReducer( reducer, initialState );

	const handleSaveState = useCallback( () => {
		try {
			// Deep clone the state to avoid mutating the original state.
			const newState = JSON.parse( JSON.stringify( state ) );

			// Exclude Error states
			const excludedKeys = [ 'connectionErrors' ];
			if ( excludedKeys.some( ( key ) => newState[ key ] ) ) {
				excludedKeys.forEach( ( key ) => {
					delete newState[ key ];
				} );
			}

			// Save the state to session storage
			sessionStorage.setItem(
				ONBOARDING_SESSION_STORAGE_KEY,
				JSON.stringify( newState )
			);
		} catch ( error ) {
			// Do nothing
		}
	}, [ state ] );

	// Save the state to session storage
	useEffect( () => {
		handleSaveState();
	}, [ handleSaveState ] );

	return (
		<OnboardingContext.Provider value={ [ state, dispatch ] }>
			{ children }
		</OnboardingContext.Provider>
	);
};

export default OnboardingProvider;
