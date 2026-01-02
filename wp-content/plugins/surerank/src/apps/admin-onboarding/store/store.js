import {
	createContext,
	useContext,
	useReducer,
	useEffect,
	useCallback,
} from '@wordpress/element';
import reducer, { initialState } from '@Onboarding/store/reducer';

const STORAGE_KEY = 'surerank_onboarding_state';

/* global sessionStorage */

// Helper functions for session storage
const saveToSessionStorage = ( state ) => {
	try {
		sessionStorage.setItem( STORAGE_KEY, JSON.stringify( state ) );
	} catch ( error ) {
		// eslint-disable-next-line no-console
		console.error( 'Failed to save state to session storage:', error );
	}
};

const loadFromSessionStorage = () => {
	try {
		const savedState = sessionStorage.getItem( STORAGE_KEY );
		return savedState ? JSON.parse( savedState ) : initialState;
	} catch ( error ) {
		// eslint-disable-next-line no-console
		console.error( 'Failed to load state from session storage:', error );
		return initialState;
	}
};

const clearSessionStorage = () => {
	try {
		sessionStorage.removeItem( STORAGE_KEY );
	} catch ( error ) {
		// eslint-disable-next-line no-console
		console.error( 'Failed to clear session storage:', error );
	}
};

const OnboardingContext = createContext();
export const useOnboardingState = () => useContext( OnboardingContext );

export const OnboardingProvider = ( { children } ) => {
	const [ state, dispatch ] = useReducer( reducer, loadFromSessionStorage() );

	useEffect( () => {
		// Save state to session storage whenever it changes
		saveToSessionStorage( state );
	}, [ state ] );

	// Enhanced dispatch function that handles state updates and storage clearing
	const enhancedDispatch = useCallback(
		( action ) => {
			// If we're on the success step or exiting, clear storage
			if ( action?.currentStep === '/finish' || action?.isExiting ) {
				return clearSessionStorage();
			}
			dispatch( action );
		},
		[ dispatch ]
	);

	return (
		<OnboardingContext.Provider value={ [ state, enhancedDispatch ] }>
			{ children }
		</OnboardingContext.Provider>
	);
};
