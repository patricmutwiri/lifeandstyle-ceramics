export const initialState = {};

const reducer = ( state = initialState, newState ) => {
	return { ...state, ...newState };
};

export default reducer;
