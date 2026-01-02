import {
	Type,
	House,
	Share2,
	Network,
	Bot,
	Globe,
	Rss,
	GitFork,
	ArrowLeftRight,
} from 'lucide-react';
import { __ } from '@wordpress/i18n';
import {
	AstraLogo,
	SpectraLogo,
	SureMailIcon,
	SureFormsIcon,
	UAE,
	OttoKitLogo,
} from '@GlobalComponents/icons';

export const FETCH_STATUS = {
	IDLE: 'idle',
	LOADING: 'loading',
	INSTALLING: 'installing',
	ACTIVATING: 'activating',
	SUCCESS: 'success',
	ERROR: 'error',
};

export const quickLinks = [
	{
		label: __( 'Title & Description', 'surerank' ),
		description: __(
			'Manage titles and meta descriptions to boost your search rankings.',
			'surerank'
		),
		icon: Type,
		link: '/',
	},
	{
		label: __( 'Home Page', 'surerank' ),
		description: __(
			'Set SEO options for your homepage to improve its search visibility.',
			'surerank'
		),
		icon: House,
		link: '/homepage',
	},
	{
		label: __( 'Social Network', 'surerank' ),
		description: __(
			'Configure how your content appears when shared on social media platforms.',
			'surerank'
		),
		icon: Share2,
		link: '/social',
	},
	{
		label: __( 'Sitemaps', 'surerank' ),
		description: __(
			'Generate XML sitemaps to help search engines crawl your site.',
			'surerank'
		),
		icon: Network,
		link: '/advanced/sitemaps',
	},
	{
		label: __( 'Robot Instructions', 'surerank' ),
		description: __(
			'Configure robots.txt to guide search engines on crawling.',
			'surerank'
		),
		icon: Bot,
		link: '/advanced/robot_instructions',
	},
	{
		label: __( 'Crawl Optimization', 'surerank' ),
		description: __(
			'Adjust crawl settings to improve search engine indexing.',
			'surerank'
		),
		icon: Globe,
		link: '/advanced/crawl_optimization',
	},
	{
		label: __( 'Feeds', 'surerank' ),
		description: __(
			'Manage RSS feeds to keep search engines updated with your latest content.',
			'surerank'
		),
		icon: Rss,
		link: '/advanced/feeds',
	},
	{
		label: __( 'Schema', 'surerank' ),
		description: __(
			'Add structured data to enhance search engine understanding.',
			'surerank'
		),
		icon: GitFork,
		link: '/advanced/schema',
	},
	{
		label: __( 'Import/Export', 'surerank' ),
		description: __(
			'Transfer SEO settings or backup configurations with ease.',
			'surerank'
		),
		icon: ArrowLeftRight,
		disabled: true,
		badge: __( 'Planned', 'surerank' ),
	},
];

export const themesAndPlugins = [
	{
		name: __( 'Astra Theme', 'surerank' ),
		title: __( 'Build Lightning-Fast Websites!', 'surerank' ),
		description: __(
			'Fast, lightweight and customizable WordPress theme.',
			'surerank'
		),
		long_description: __(
			'Fast, lightweight & customizable WordPress theme that combines speed optimization with extensive customization capabilities. Perfect for any website without compromising on design possibilities.',
			'surerank'
		),
		icon: AstraLogo,
		slug: 'astra',
		type: 'theme',
	},
	{
		name: __( 'Spectra', 'surerank' ),
		title: __( 'Create Lightning-Fast WordPress Websites!', 'surerank' ),
		description: __( 'Website builder for Gutenberg.', 'surerank' ),
		long_description: __(
			'Create lightning-fast WordPress websites with this powerful website builder. Supercharge your Gutenberg editor with advanced blocks and page builder capabilities for building stunning websites.',
			'surerank'
		),
		icon: SpectraLogo,
		slug: 'ultimate-addons-for-gutenberg',
		type: 'plugin',
	},
	{
		name: __( 'SureMail', 'surerank' ),
		title: __( 'Supercharge Your Email Deliverability!', 'surerank' ),
		description: __(
			'Connect and send emails via SMTP connections.',
			'surerank'
		),
		long_description: __(
			'Powerful, easy-to-use email delivery service that ensures your emails land in inboxes, not spam folders. Automate your WordPress email workflows confidently with reliable SMTP connections.',
			'surerank'
		),
		icon: SureMailIcon,
		slug: 'suremails',
		type: 'plugin',
	},
	{
		name: __( 'SureForms', 'surerank' ),
		title: __( 'Build Beautiful Forms Effortlessly!', 'surerank' ),
		description: __( 'AI-powered form builder for WordPress.', 'surerank' ),
		long_description: __(
			'Creating beautiful, functional forms has never been easier with this AI-powered form builder. Build contact forms, surveys, and more with an intuitive drag-and-drop interface designed for WordPress.',
			'surerank'
		),
		icon: SureFormsIcon,
		slug: 'sureforms',
		type: 'plugin',
	},
	{
		name: __( 'Ultimate Addons for Elementor', 'surerank' ),
		title: __( 'Create Modern Elementor Websites!', 'surerank' ),
		description: __(
			'Powerful Elementor addons for modern websites.',
			'surerank'
		),
		long_description: __(
			'Build modern, professional websites with powerful Elementor addons. Create stunning headers, footers, and page layouts with advanced widgets and design capabilities.',
			'surerank'
		),
		icon: UAE,
		slug: 'header-footer-elementor',
		type: 'plugin',
	},
	{
		name: __( 'OttoKit', 'surerank' ),
		title: __( 'Automate Your WordPress Workflows!', 'surerank' ),
		description: __( 'No-code AI automation tool.', 'surerank' ),
		long_description: __(
			'No-code AI automation tool for creating automated workflows without technical skills. Connect your favorite apps and automate business processes with ease.',
			'surerank'
		),
		icon: OttoKitLogo,
		slug: 'suretriggers',
		type: 'plugin',
	},
];
