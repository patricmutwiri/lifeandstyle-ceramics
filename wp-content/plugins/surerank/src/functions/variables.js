export const flat = ( variables, type = '' ) => {
	if ( ! variables ) {
		return;
	}

	let newVariables = {};
	if ( type === 'richSelect' ) {
		newVariables = [];
	}

	Object.values( variables ).forEach( ( variable ) => {
		Object.keys( variable ).forEach( ( key ) => {
			switch ( type ) {
				case 'richSelect':
					newVariables.push( {
						value: key,
						label: variable[ key ].label,
						title: '%' + key + '%',
						description: variable[ key ].description,
					} );
					break;
				case 'detailed':
					newVariables[ key ] = variable[ key ];
					break;
				default:
					newVariables[ key ] = variable[ key ].value;
			}
		} );
	} );

	return newVariables;
};
