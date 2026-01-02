/**
 * WordPress dependencies
 */
import { createReduxStore, register } from '@wordpress/data';

/**
 * Internal dependencies
 */
import reducer from './reducer';
import * as actions from './actions';
import * as selectors from './selectors';
import controls from './controls';
import resolvers from './resolvers';
import { STORE_NAME as storeName } from './constants';
import { applyFilters } from '@wordpress/hooks';

const extendedActions = applyFilters(
	'surerank-pro.admin-store-actions',
	actions
);
const extendedSelectors = applyFilters(
	'surerank-pro.admin-store-selectors',
	selectors
);
const extendedControls = applyFilters(
	'surerank-pro.admin-store-controls',
	controls
);
const extendedResolvers = applyFilters(
	'surerank-pro.admin-store-resolvers',
	resolvers
);

/**
 * Store definition for the viewport namespace.
 *
 * @see https://github.com/WordPress/gutenberg/blob/HEAD/packages/data/README.md#createReduxStore
 *
 * @type {Object}
 */
const store = createReduxStore( storeName, {
	reducer,
	actions: extendedActions,
	selectors: extendedSelectors,
	controls: extendedControls,
	resolvers: extendedResolvers,
} );

register( store );
