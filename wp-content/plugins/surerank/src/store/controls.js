import apiFetch from '@wordpress/api-fetch';

const controls = {
	FETCH_FROM_API( action ) {
		return apiFetch( action.payload );
	},
};

export default controls;
