import { Alert } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';

export const noFieldsAlert = (
	<Alert
		content={ __(
			'This schema does not have any configurable fields',
			'surerank'
		) }
		className="shadow-none"
		variant="info"
	/>
);

export const generateUUID = ( length = 16 ) => {
	return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(
		/[xy]/g,
		function ( c ) {
			const r = Math.floor( Math.random() * length );
			const v = c === 'x' ? r : ( r % 4 ) + 8; // Replace bitwise operations
			return v.toString( 16 );
		}
	);
};

/**
 * Checks if a schema type exists and is valid in schemaTypeData
 * @param {string} schemaTitle - The schema title/name to check
 * @return {boolean} - True if schema exists and has valid data
 */
export const isSchemaTypeValid = ( schemaTitle ) => {
	const schemaTypeData = surerank_globals?.schema_type_data || {};
	return (
		schemaTypeData[ schemaTitle ] &&
		Array.isArray( schemaTypeData[ schemaTitle ] ) &&
		schemaTypeData[ schemaTitle ].length > 0
	);
};
