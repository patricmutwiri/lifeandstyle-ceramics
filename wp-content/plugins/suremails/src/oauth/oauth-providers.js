/**
 * OAuth Provider Configuration
 *
 * This configuration defines which providers support OAuth authentication
 * and their respective settings. Adding new OAuth providers only requires
 * updating this configuration instead of hardcoding conditions throughout the codebase.
 */

const OAUTH_PROVIDERS = {
	gmail: {
		state: 'gmail',
		type: 'GMAIL',
		displayName: 'Gmail',
	},
	zoho: {
		state: 'zoho',
		type: 'ZOHO',
		displayName: 'Zoho',
	},
};

/**
 * Get all supported OAuth provider states
 *
 * @return {string[]} Array of OAuth provider state values
 */
export const getOAuthProviderStates = () => {
	return Object.values( OAUTH_PROVIDERS ).map(
		( provider ) => provider.state
	);
};

/**
 * Check if a provider state is a valid OAuth provider
 *
 * @param {string} state - The provider state to check
 * @return {boolean} True if the state represents a valid OAuth provider
 */
export const isValidOAuthProvider = ( state ) => {
	return getOAuthProviderStates().includes( state );
};

/**
 * Get provider configuration by state
 *
 * @param {string} state - The provider state
 * @return {object|null} Provider configuration or null if not found
 */
export const getProviderByState = ( state ) => {
	return (
		Object.values( OAUTH_PROVIDERS ).find(
			( provider ) => provider.state === state
		) || null
	);
};

/**
 * Get provider type by state
 *
 * @param {string} state - The provider state
 * @return {string|null} Provider type or null if not found
 */
export const getProviderTypeByState = ( state ) => {
	const provider = getProviderByState( state );
	return provider ? provider.type : null;
};

/**
 * Check if a provider supports OAuth authentication
 *
 * @param {string} providerName - The provider name (lowercase)
 * @return {boolean} True if the provider supports OAuth
 */
export const isOAuthProvider = ( providerName ) => {
	const normalizedName = providerName.toLowerCase();
	return Object.hasOwnProperty.call( OAUTH_PROVIDERS, normalizedName );
};

export default OAUTH_PROVIDERS;
