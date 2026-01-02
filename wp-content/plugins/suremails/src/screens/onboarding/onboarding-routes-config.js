const ONBOARDING_ROUTES_CONFIG = [
	{
		url: '/onboarding/welcome',
		index: true,
	},
	{
		url: '/onboarding/connection',
	},
	{
		url: '/onboarding/reputation-shield',
		requires: {
			stateKeys: [ 'connectionSaved' ],
			redirectUrl: '/onboarding/connection',
		},
	},
	{
		url: '/onboarding/add-ons',
	},
	{
		url: '/onboarding/done',
	},
];

export default ONBOARDING_ROUTES_CONFIG;
