const replacement = ( str, data, postDynamicData = {} ) => {
	if ( ! str || ! data ) {
		return str;
	}
	const chunks = variables( str );

	const replacementArray = [];
	chunks.forEach( ( chunk ) => {
		if ( ! replacementArray[ chunk ] ) {
			// Chunk will be replaced dynamic keys like title, content, excerpt so we need to check if it is available in postDynamicData.
			const dynamicKeys = [ 'title', 'excerpt', 'content' ];

			// Verify if chunk is available in dynamicKeys and postDynamicData is not empty.
			if ( dynamicKeys.includes( chunk ) && postDynamicData?.[ chunk ] ) {
				replacementArray[ chunk ] = postDynamicData[ chunk ];
			} else {
				replacementArray[ chunk ] = data[ chunk ] || '';
			}
		}
	} );

	return str.replace( /%([^%\s]+)%/g, ( match, value ) => {
		return replacementArray[ value ] || '';
	} );
};

const variables = ( str ) => {
	const regex = /%([^%\s]+)%/g;
	const matches = str.match( regex );
	if ( ! matches ) {
		return [];
	}
	return matches.map( ( match ) => match.replace( /%/g, '' ) );
};

export default replacement;
