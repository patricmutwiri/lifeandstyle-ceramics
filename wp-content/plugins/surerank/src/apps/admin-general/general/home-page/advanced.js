import { __ } from '@wordpress/i18n';
import GeneratePageContent from '@Functions/page-content-generator';

export const PAGE_CONTENT = [
	// This means one section with multiple fields
	{
		// Container for the section it can be null
		container: {
			id: 'advanced',
			direction: 'column',
			gap: 6,
		},
		content: [
			{
				container: {
					id: 'robot-instructions',
					direction: 'column',
					gap: 2,
				},
				content: [
					{
						id: 'robot-instructions-label',
						type: 'label',
						label: __( 'Robot Instructions', 'surerank' ),
						size: 'sm',
						tag: 'p',
						tooltip: __(
							'These settings help search engines understand how to treat your home page in search results. Enabling ‘No Index’ will prevent the home page from appearing in search results. ‘No Follow’ tells search engines not to follow any links on the page, and ‘No Archive’ prevents search engines from storing a cached version of the page. These settings apply only to your website’s home page.',
							'surerank'
						),
					},
					{
						id: 'noindex',
						type: 'checkbox',
						label: __( 'No Index', 'surerank' ),
						description: __(
							'Prevents search engines from listing your home page in search results.',
							'surerank'
						),
						size: 'sm',
						storeKey: 'home_page_robots.general',
						value: 'noindex',
						dataType: 'array',
					},
					{
						id: 'nofollow',
						type: 'checkbox',
						label: __( 'No Follow', 'surerank' ),
						description: __(
							'Tells search engines not to follow any links on your home page.',
							'surerank'
						),
						size: 'sm',
						storeKey: 'home_page_robots.general',
						value: 'nofollow',
						dataType: 'array',
					},
					{
						id: 'noarchive',
						type: 'checkbox',
						label: __( 'No Archive', 'surerank' ),
						description: __(
							'Blocks search engines from storing a cached version of your home page.',
							'surerank'
						),
						size: 'sm',
						storeKey: 'home_page_robots.general',
						value: 'noarchive',
						dataType: 'array',
					},
					{
						id: 'robot-instructions-label-description',
						type: 'label',
						variant: 'help',
						tag: 'p',
						label: __(
							'This is only for Home Page. These settings will not affect any other pages.',
							'surerank'
						),
						size: 'sm',
						searchable: false,
					},
				],
			},
			// {
			// 	container: null,
			// 	content: [
			// 		{
			// 			type: 'switch',
			// 			id: 'index_home_page_paginated_pages',
			// 			name: 'index_home_page_paginated_pages',
			// 			label: __(
			// 				'Index Home Page Paginated Pages',
			// 				'surerank'
			// 			),
			// 			size: 'sm',
			// 			storeKey: 'index_home_page_paginated_pages',
			// 			value: true,
			// 			dataType: 'boolean',
			// 		},
			// 	],
			// },
		],
	},
];

const AdvancedTab = () => {
	return <GeneratePageContent json={ PAGE_CONTENT } />;
};

export default AdvancedTab;
