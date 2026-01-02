/**
 * Get the pricing link for the SureRank plugin.
 *
 * @param {string} utm_medium - The UTM parameter to include in the link.
 * @return {string} The pricing link URL.
 */
export const getPricingLink = ( utm_medium = 'surerank_plugin' ) => {
	return (
		window?.surerank_globals?.pricing_link + `?utm_medium=${ utm_medium }`
	);
};

/**
 * Redirect to the pricing page for the SureRank plugin.
 *
 * @param {string} utm_medium - The UTM parameter to include in the link.
 */
export const redirectToPricingPage = ( utm_medium ) => {
	const pricingLink = getPricingLink( utm_medium );
	window.open( pricingLink, '_blank', 'noopener,noreferrer' );
};

/**
 * Get the current active plan.
 *
 * @return {string|null} The active plan slug (e.g., 'surerank-starter', 'surerank-pro', 'surerank-business') or null if no plan is active.
 */
export const getActivePlan = () => {
	return window?.surerank_globals?.active_plan || null;
};

/**
 * Plan hierarchy mapping - higher number means higher tier
 */
const PLAN_HIERARCHY = {
	'surerank-starter': 1,
	'surerank-pro': 2,
	'surerank-business': 3,
};

/**
 * Normalize plan name to standard format
 *
 * @param {string} plan - The plan name (e.g., 'pro', 'surerank-pro', 'business')
 * @return {string} Normalized plan name (e.g., 'surerank-pro')
 */
const normalizePlanName = ( plan ) => {
	if ( ! plan ) {
		return '';
	}

	if ( plan.startsWith( 'surerank-' ) ) {
		return plan;
	}

	return `surerank-${ plan }`;
};

/**
 * Check if the current plan should see an upgrade nudge for a specific feature.
 *
 * @param {string} requiredPlan - The minimum plan required for the feature (e.g., 'pro', 'surerank-pro', 'business')
 * @return {boolean} True if the nudge should be shown, false otherwise.
 */
export const isProActive = ( requiredPlan = null ) => {
	if ( ! requiredPlan ) {
		return window?.surerank_globals?.is_pro_active;
	}

	const activePlan = getActivePlan();

	if ( ! activePlan ) {
		return false;
	}

	const normalizedRequired = normalizePlanName( requiredPlan );
	const normalizedActive = normalizePlanName( activePlan );

	const requiredLevel = PLAN_HIERARCHY[ normalizedRequired ] || 0;
	const activeLevel = PLAN_HIERARCHY[ normalizedActive ] || 0;
	return activeLevel >= requiredLevel;
};
