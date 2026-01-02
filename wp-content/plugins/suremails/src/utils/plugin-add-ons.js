/**
 * Recommended Plugins Data
 *
 * Contains an array of recommended plugin objects.
 *
 * @package
 */

import { __ } from '@wordpress/i18n';
import {
	SureFormsLogo,
	SpectraLogo,
	SureCartLogo,
	SureTriggersLogo,
	PrestoPlayerLogo,
	SureFeedbackLogo,
	StartersTemplatesLogo,
	CartFlowsLogo,
	SureDashLogo,
	AstraThemeLogo,
	AbandonedCartsLogo,
	SureRankLogo,
	UAE,
} from 'assets/icons';

export const recommendedPluginsData = {
	sequence: window.suremails?.recommendedPluginsData || [
		'sureforms',
		'ultimate-addons-for-gutenberg',
		'surecart',
		'suretriggers',
	],
	description: 'short_description',
};

export const AddOnsPlugin = {
	sequence: [
		'sureforms',
		'ultimate-addons-for-gutenberg',
		'suredash',
		'cartflows',
		'surecart',
		'suretriggers',
		'astra-sites',
		'presto-player',
	],
	description: 'long_description',
};

export const AddOnsTheme = {
	sequence: [ 'astra', 'spectra-one' ],
	description: 'long_description',
};

export const pluginAddons = [
	{
		id: '1',
		badgeText: __( 'Free', 'suremails' ),
		svg: <SureFormsLogo />,
		title: __( 'SureForms', 'suremails' ),
		long_description: __(
			'A powerful no-code form builder for WordPress, enabling users to create custom forms easily.',
			'suremails'
		),
		short_description: __(
			'Best no code WordPress form builder.',
			'suremails'
		),
		slug: 'sureforms',
		name: __( 'SureForms', 'suremails' ),
		type: 'plugin',
		init: 'sureforms/sureforms.php',
	},
	{
		id: '2',
		badgeText: __( 'Free', 'suremails' ),
		svg: <SpectraLogo />,
		title: __( 'Spectra', 'suremails' ),
		long_description: __(
			'A feature-rich Gutenberg block editor plugin that adds advanced design tools to WordPress.',
			'suremails'
		),
		short_description: __( 'Free WordPress Page Builder.', 'suremails' ),
		slug: 'ultimate-addons-for-gutenberg',
		name: __( 'Spectra', 'suremails' ),
		type: 'plugin',
		init: 'ultimate-addons-for-gutenberg/ultimate-addons-for-gutenberg.php',
	},
	{
		id: '3',
		badgeText: __( 'Free', 'suremails' ),
		svg: <SureDashLogo />,
		title: __( 'SureDash', 'suremails' ),
		long_description: __(
			'An all-in-one business dashboard for WordPress to manage customers, communities, and courses.',
			'suremails'
		),
		short_description: __(
			'Manage your business with SureDash.',
			'suremails'
		),
		slug: 'suredash',
		name: __( 'SureDash', 'suremails' ),
		type: 'plugin',
		init: 'suredash/suredash.php',
	},
	{
		id: '4',
		badgeText: __( 'Free', 'suremails' ),
		svg: <SureFeedbackLogo />,
		title: __( 'SureFeedback', 'suremails' ),
		long_description: __(
			'Collect user feedback directly on your site to improve design, content, and user experience.',
			'suremails'
		),
		short_description: __(
			'Control user access with SureMembers.',
			'suremails'
		),
		slug: 'projecthuddle-child-site',
		name: __( 'SureFeedback', 'suremails' ),
		type: 'plugin',
		init: 'projecthuddle-child-site/ph-child.php',
	},
	{
		id: '5',
		badgeText: __( 'Free', 'suremails' ),
		svg: <CartFlowsLogo />,
		title: __( 'CartFlows', 'suremails' ),
		long_description: __(
			'A sales funnel builder for WordPress to boost conversions and optimize checkout flows.',
			'suremails'
		),
		short_description: __(
			'Boost conversions with CartFlows.',
			'suremails'
		),
		slug: 'cartflows',
		name: __( 'CartFlows', 'suremails' ),
		type: 'plugin',
		init: 'cartflows/cartflows.php',
	},
	{
		id: '6',
		badgeText: __( 'Free', 'suremails' ),
		svg: <SureCartLogo />,
		title: __( 'SureCart', 'suremails' ),
		long_description: __(
			'A modern eCommerce plugin for WordPress, offering a flexible and smooth checkout system.',
			'suremails'
		),
		short_description: __(
			'The new way to sell on WordPress.',
			'suremails'
		),
		slug: 'surecart',
		name: __( 'SureCart', 'suremails' ),
		type: 'plugin',
		init: 'surecart/surecart.php',
	},
	{
		id: '7',
		badgeText: __( 'Free', 'suremails' ),
		svg: <SureTriggersLogo />,
		title: __( 'OttoKit', 'suremails' ),
		long_description: __(
			'A no-code automation platform for WordPress to build workflows and connect your tools.',
			'suremails'
		),
		short_description: __( 'Automate your WordPress setup.', 'suremails' ),
		slug: 'suretriggers',
		name: __( 'OttoKit', 'suremails' ),
		type: 'plugin',
		init: 'suretriggers/suretriggers.php',
	},
	{
		id: '8',
		badgeText: __( 'Free', 'suremails' ),
		svg: <StartersTemplatesLogo />,
		title: __( 'Starter Templates', 'suremails' ),
		long_description: __(
			'A collection of ready-to-use website templates for WordPress to help launch sites quickly.',
			'suremails'
		),
		short_description: __(
			'Launch sites quickly with Starter Templates.',
			'suremails'
		),
		slug: 'astra-sites',
		name: __( 'Starter Templates', 'suremails' ),
		type: 'plugin',
		init: 'astra-sites/astra-sites.php',
	},
	{
		id: '9',
		badgeText: __( 'Free', 'suremails' ),
		svg: <PrestoPlayerLogo />,
		title: __( 'Presto Player', 'suremails' ),
		long_description: __(
			'An advanced media player plugin that improves video delivery with customization and analytics.',
			'suremails'
		),
		short_description: __(
			'Enhance video delivery with Presto Player.',
			'suremails'
		),
		slug: 'presto-player',
		name: __( 'Presto Player', 'suremails' ),
		type: 'plugin',
		init: 'presto-player/presto-player.php',
	},

	{
		id: '10',
		badgeText: __( 'Free', 'suremails' ),
		svg: <AstraThemeLogo />,
		title: __( 'Astra', 'suremails' ),
		long_description: __(
			'A fast, lightweight, and customizable WordPress theme,built for performance and flexibility.',
			'suremails'
		),
		short_description: __(
			'A fast and customizable WordPress theme.',
			'suremails'
		),
		slug: 'astra',
		name: __( 'Astra', 'suremails' ),
		type: 'theme',
		init: 'astra',
	},

	{
		id: '11',
		badgeText: __( 'Free', 'suremails' ),
		svg: <SpectraLogo />,
		title: __( 'Spectra One', 'suremails' ),
		long_description: __(
			'A modern block-based WordPress theme, designed for speed, style, and full- site editing.',
			'suremails'
		),
		short_description: __(
			'A modern block-based WordPress theme.',
			'suremails'
		),
		slug: 'spectra-one',
		name: __( 'Spectra One', 'suremails' ),
		type: 'theme',
		init: 'spectra-one',
	},

	{
		id: '12',
		badgeText: __( 'Free', 'suremails' ),
		svg: <AbandonedCartsLogo />,
		title: __( 'Cart Abandonment Recovery', 'suremails' ),
		long_description: __(
			'Recover lost sales by automatically emailing customers who abandon their carts. Set up takes less than 10 minutes - no coding required.',
			'suremails'
		),
		short_description: __(
			'Recover lost sales with automated abandoned cart emails - no coding needed.',
			'suremails'
		),
		slug: 'woo-cart-abandonment-recovery',
		name: __( 'Cart Abandonment Recovery', 'suremails' ),
		type: 'plugin',
		init: 'woo-cart-abandonment-recovery/woo-cart-abandonment-recovery.php',
	},
	{
		id: '13',
		badgeText: __( 'Free', 'suremails' ),
		svg: <UAE />,
		title: __( 'Ultimate Addons for Elementor', 'suremails' ),
		long_description: __(
			'Build modern websites with elementor addons.',
			'suremails'
		),
		short_description: __(
			'Build modern websites with elementor addons.',
			'suremails'
		),
		slug: 'header-footer-elementor',
		type: 'plugin',
		init: 'header-footer-elementor/header-footer-elementor.php',
	},

	{
		id: '14',
		badgeText: __( 'Free', 'suremails' ),
		svg: <SureRankLogo />,
		title: __( 'SureRank', 'suremails' ),
		long_description: __(
			'Optimize your website for search engines with SureRank, an all-in-one SEO plugin for WordPress.',
			'suremails'
		),
		short_description: __(
			'Optimize your website for search engines with SureRank.',
			'suremails'
		),
		slug: 'surerank',
		type: 'plugin',
		init: 'surerank/surerank.php',
	},
];
