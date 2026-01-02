export const parseContent = ( content ) => {
	const parser = new DOMParser();
	return parser.parseFromString( content, 'text/html' );
};
