/**
 * MCP Server for Angie Demo Plugin
 * Demonstrates how to create external MCP tools for the Angie AI assistant using the official MCP SDK
 * and register with Angie using the new Angie MCP SDK
 */

/* eslint-disable */

import { AngieMcpSdk } from '@elementor/angie-sdk';
import { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { __ } from '@wordpress/i18n';
import { z } from 'zod';

async function makeApiRequest( endpoint, data, method = 'POST' ) {
	let url = window.wpApiSettings.root + endpoint;
	let requestOptions = {
		method,
		headers: {
			'X-WP-Nonce': window.wpApiSettings?.nonce || '',
		},
	};

	if ( method === 'GET' && data ) {
		const params = new URLSearchParams();
		Object.keys( data ).forEach( ( key ) => {
			params.append( key, data[ key ] );
		} );
		url += '?' + params.toString();
	} else if ( data ) {
		requestOptions.headers[ 'Content-Type' ] = 'application/json';
		requestOptions.body = JSON.stringify( data );
	}

	const response = await fetch( url, requestOptions );

	if ( ! response.ok ) {
		throw new Error( `HTTP error! status: ${ response.status }` );
	}

	return await response.json();
}

async function getAvailableTypes() {
	try {
		const response = await makeApiRequest(
			'surerank/v1/angie/get-available-types',
			null,
			'GET'
		);
		return response;
	} catch ( error ) {
		console.error( 'Failed to fetch available types:', error );
		return { post_types: [], taxonomies: [] };
	}
}

async function create_surerank_mcp_Server() {
	// Fetch available post types and taxonomies
	const availableTypes = await getAvailableTypes();
	const postTypeSlugs = availableTypes.post_type_keys || [];
	const taxonomySlugs = availableTypes.taxonomy_keys || [];

	const server = new McpServer(
		{
			name: 'surerank-tools',
			version: '1.0.1',
		},
		{
			capabilities: {
				tools: {},
			},
		}
	);

	server.tool(
		'enable-disable-sitemap',
		__( 'Enable/Disable the Sitemap from SureRank Settings', 'surerank' ),
		{
			enable: z.boolean(),
		},
		/**
		 * MCP tool handler to toggle the XML sitemap setting.
		 *
		 * @param {Object}  params        - Parameters passed by the MCP tool invocation.
		 * @param {boolean} params.enable - True to enable the sitemap, false to disable.
		 * @return {Promise<Object>} MCP response object with `content` array describing the result.
		 */
		async ( { enable } ) => {
			// Debug: log the incoming value and its type to diagnose false/"false" issues.
			console.log(
				'%cToggling sitemap, enable (raw):',
				'color: blue;',
				enable
			);
			console.log(
				'%cToggling sitemap, enable (type):',
				'color: blue;',
				typeof enable
			);

			// Coerce string boolean values (e.g. "false"/"true") to actual booleans.
			const parsedEnable =
				typeof enable === 'string'
					? enable === 'true' || enable === '1'
					: Boolean( enable );
			console.log(
				'%cToggling sitemap, enable (parsed):',
				'color: blue;',
				parsedEnable
			);

			// Make the API request to toggle the sitemap setting with a real boolean.
			const response = await makeApiRequest(
				'surerank/v1/angie/toggle-sitemap',
				{ enable: parsedEnable }
			);
			console.log(
				'%cResponse from toggle-sitemap:',
				'color: red;',
				response
			);
			return {
				content: [
					{
						type: 'text',
						text: JSON.stringify( response, null, 2 ),
					},
				],
			};
		}
	);

	server.tool(
		'page-seo-checks',
		__(
			"Page SEO Checks — SureRank helps you quickly evaluate and improve the SEO quality of your WordPress pages. It runs automated checks to ensure your content is optimized for both search engines and readers. Checks include: Image Optimization — identify images missing alt text and ensure accessibility and SEO best practices; URL Health — alert when a page URL is longer than 90 characters and confirm when it's short and SEO-friendly; Content Media — check whether the page includes images or videos to enhance engagement; Links — detect presence or absence of internal/external links and highlight missing links; Title & Meta Description — verify that the search engine title and description are present and within recommended length limits (title < 60 chars, description < 160 chars); Open Graph Tags — ensure Open Graph tags are present for better social sharing; Subheadings — confirm the page contains at least one subheading for better structure and readability; Canonical URL — check for a canonical tag to avoid duplicate content issues; Broken Links — detect broken links that harm UX and SEO. SureRank displays clear 'Good' and 'Needs Improvement' indicators for each check so you can spot issues and fix them quickly.",
			'surerank'
		),
		{
			post_id: z.number(),
		},
		/**
		 * MCP tool handler to check the Page SEO Check for the given post.
		 *
		 * @param {Object}  params         - Parameters passed by the MCP tool invocation.
		 * @param {number}  params.post_id - Post ID of the post, page or custom post type that the user wants to check the SEO Checks.
		 * @return {Promise<Object>} MCP response object with `content` array describing the result.
		 */
		async ( { post_id } ) => {
			// Make the API request to toggle the sitemap setting with a real boolean.
			const response = await makeApiRequest(
				'surerank/v1/checks/page',
				{ post_id: parseInt( post_id ) },
				'GET'
			);
			console.log(
				'%cResponse from page-seo-checks:',
				'color: red;',
				response
			);
			return {
				content: [
					{
						type: 'text',
						text: JSON.stringify( response, null, 2 ),
					},
				],
			};
		}
	);

	server.tool(
		'seo-robots-settings',
		__(
			'Apply SEO robots meta tags to WordPress content. Handles index/noindex (show/hide from search), follow/nofollow (follow/don\'t follow links), archive/noarchive (allow/prevent cached versions). Can target specific posts by ID or apply to all content of a type. Natural language examples: "make post id 25 noindex", "set post 26 and 28 to nofollow", "hide post id 30 from search", "make all pages noindex", "apply nofollow to all products", "set noarchive on all blog posts", "make all events indexable", "hide all pages from search engines", "allow all products to be indexed", "prevent all posts from being cached". Works with any post type: post, page, product, event, custom post types, etc.',
			'surerank'
		),
		{
			type: z
				.enum( [ 'cpt', 'taxonomy' ] )
				.describe(
					'Content type: "cpt" for posts/pages/products/events/custom post types, "taxonomy" for categories/tags/custom taxonomies. Use "cpt" for most content operations.'
				),
			action: z
				.enum( [
					'noindex',
					'index',
					'nofollow',
					'follow',
					'noarchive',
					'archive',
				] )
				.describe(
					'SEO action: "noindex" (hide from search), "index" (show in search), "nofollow" (don\'t follow links), "follow" (follow links), "noarchive" (no cached version), "archive" (allow cached version). Map user phrases like "hide from search" to "noindex", "show in search" to "index", etc.'
				),
			name: z
				.enum( [ ...postTypeSlugs, ...taxonomySlugs ] )
				.describe(
					`Post type or taxonomy slug. Available post types: ${ postTypeSlugs.join(
						', '
					) }. Available taxonomies: ${ taxonomySlugs.join( ', ' ) }.`
				),
			ids: z
				.array( z.number() )
				.optional()
				.describe(
					'Specific post/term IDs to target. Use when user mentions specific IDs like "post id 25" or "posts 26 and 28". Format as [25] for single ID, [26, 28] for multiple. If omitted, applies to ALL items of the specified type.'
				),
		},

		async ( { type, action, ids, name } ) => {
			// Make the API request to apply bulk SEO settings
			// console.log( 'type', type );
			// console.log( 'action', action );
			// console.log( 'name', name );
			// console.log( 'ids', ids );

			const response = await makeApiRequest(
				'surerank/v1/angie/bulk-robots-settings',
				{
					type,
					action,
					name,
					ids,
				}
			);
			// console.log(
			// 	'%cResponse from bulk-robots-settings:',
			// 	'color: red;',
			// 	response
			// );

			return {
				content: [
					{
						type: 'text',
						text: JSON.stringify( response, null, 2 ),
					},
				],
			};
		}
	);

	server.tool(
		'indexable-status',
		__(
			'Check if the page is indexable by search engines by checking surerank robots and other settings',
			'surerank'
		),
		{
			id: z.number().describe( 'Post ID to check the indexable status' ),
			type: z
				.enum( [ 'post', 'taxonomy' ] )
				.describe(
					'Type of the content to check the indexable status which can be post or taxonomy'
				),
		},
		async ( { id, type } ) => {
			// console.log( 'id', id );
			// console.log( 'type', type );

			const response = await makeApiRequest(
				'surerank/v1/angie/indexable-status',
				{ id, type },
				'GET'
			);
			return {
				content: [
					{
						type: 'text',
						text: JSON.stringify( response, null, 2 ),
					},
				],
			};
		}
	);

	server.tool(
		'update-title-and-meta-description',
		__(
			'Update/Enhance the title and/or meta description for a post or term using enhanced title and meta description in SureRank post meta',
			'surerank'
		),
		{
			id: z
				.number()
				.describe( 'Post ID to update the title and meta description' ),
			type: z
				.enum( [ 'post', 'taxonomy' ] )
				.describe(
					'Type of the content to update the title and meta description which can be post or taxonomy'
				),
			title: z
				.string()
				.describe(
					'Title to update for the post or term, maximum 60 characters long. Also relates to current meta title.'
				)
				.optional(),
			meta_description: z
				.string()
				.describe(
					'Meta description to update for the post or term, maximum 160 characters long.'
				)
				.optional(),
		},
		async ( { id, type, title, meta_description } ) => {
			console.log( 'id', id );
			console.log( 'type', type );
			console.log( 'title', title );
			console.log( 'meta_description', meta_description );

			const response = await makeApiRequest(
				'surerank/v1/angie/title-and-meta-description',
				{ id, type, title, meta_description },
				'PUT'
			);
			// console.log( 'response', response );
			return {
				content: [
					{ type: 'text', text: JSON.stringify( response, null, 2 ) },
				],
			};
		}
	);

	server.tool(
		'toggle-surerank-settings',
		__(
			'Toggle SureRank Settings — Control multiple SureRank SEO settings with a single command. Supports author archive (show/hide author post archives), date archive (show/hide date-based archives), no_index (strictly array of existing post types or taxonomies to hide from search), no_follow (strictly array of existing post types or taxonomies with nofollow), no_archive (strictly array of existing post types or taxonomies to prevent caching), enable_xml_sitemap (XML sitemap generation), enable_xml_image_sitemap (include images in sitemap), enable_automatic_indexing (auto-submit to search engines), auto_redirect_enabled (automatic redirects), enable_page_level_seo (per-page SEO controls), enable_google_console (Google Search Console integration), enable_schemas (structured data), enable_migration (import from other SEO plugins), enable_extended_meta_templates (advanced meta templates). Examples: "enable author archive and XML sitemap", "disable date archive and enable image sitemap", "set no_index for posts and pages", "enable all sitemaps and schemas", "turn on Google Console and page level SEO".',
			'surerank'
		),
		{
			settings: z
				.object( {
					author_archive: z
						.boolean()
						.optional()
						.describe(
							'Enable/disable author archive pages that show all posts by a specific author. True to enable, false to disable.'
						),
					date_archive: z
						.boolean()
						.optional()
						.describe(
							'Enable/disable date archive pages that group posts by month/year. True to enable, false to disable.'
						),
					no_index: z
						.array(
							z.enum( [ ...postTypeSlugs, ...taxonomySlugs ] )
						)
						.optional()
						.describe(
							`Array of post and taxonomy types to hide from search engines (noindex). Available post types: ${ postTypeSlugs.join(
								', '
							) }. Available taxonomies: ${ taxonomySlugs.join(
								', '
							) }. Empty array means no restrictions.`
						),
					no_follow: z
						.array(
							z.enum( [ ...postTypeSlugs, ...taxonomySlugs ] )
						)
						.optional()
						.describe(
							`Array of post and taxonomy types where search engines should not follow links (nofollow). Available post types: ${ postTypeSlugs.join(
								', '
							) }. Available taxonomies: ${ taxonomySlugs.join(
								', '
							) }. Empty array means no restrictions.`
						),
					no_archive: z
						.array(
							z.enum( [ ...postTypeSlugs, ...taxonomySlugs ] )
						)
						.optional()
						.describe(
							`Array of post and taxonomy types to prevent search engines from caching (noarchive). Available post types: ${ postTypeSlugs.join(
								', '
							) }. Available taxonomies: ${ taxonomySlugs.join(
								', '
							) }. Empty array means no restrictions.`
						),
					enable_xml_sitemap: z
						.boolean()
						.optional()
						.describe(
							'Enable/disable XML sitemap generation. True to generate sitemaps, false to disable.'
						),
					enable_xml_image_sitemap: z
						.boolean()
						.optional()
						.describe(
							'Enable/disable including images in XML sitemap. True to include images, false to exclude.'
						),
					enable_automatic_indexing: z
						.boolean()
						.optional()
						.describe(
							'Enable/disable automatic submission to search engines for faster indexing. True to enable, false to disable.'
						),
					auto_redirect_enabled: z
						.boolean()
						.optional()
						.describe(
							'Enable/disable automatic redirects for SEO purposes. True to enable, false to disable.'
						),
					enable_page_level_seo: z
						.boolean()
						.optional()
						.describe(
							'Enable/disable per-page SEO controls for individual posts/pages. True to enable, false to disable.'
						),
					enable_google_console: z
						.boolean()
						.optional()
						.describe(
							'Enable/disable Google Search Console integration. True to enable, false to disable.'
						),
					enable_schemas: z
						.boolean()
						.optional()
						.describe(
							'Enable/disable structured data/schema markup. True to enable, false to disable.'
						),
					enable_migration: z
						.boolean()
						.optional()
						.describe(
							'Enable/disable migration tools for importing from other SEO plugins. True to enable, false to disable.'
						),
					enable_extended_meta_templates: z
						.boolean()
						.optional()
						.describe(
							'Enable/disable advanced meta templates for dynamic SEO content. True to enable, false to disable.'
						),
				} )
				.describe(
					'Settings object containing the SureRank settings to toggle. Only include the settings you want to change.'
				),
		},
		/**
		 * MCP tool handler to toggle multiple SureRank settings.
		 *
		 * @param {Object} params - Parameters passed by the MCP tool invocation.
		 * @param {Object} params.settings - Object containing settings to toggle.
		 * @return {Promise<Object>} MCP response object with content array describing the result.
		 */
		async ( { settings } ) => {
			// Make the API request to toggle multiple settings
			const response = await makeApiRequest(
				'surerank/v1/angie/toggle-settings',
				{ settings }
			);
			console.log(
				'%cResponse from toggle-settings:',
				'color: red;',
				response
			);
			return {
				content: [
					{
						type: 'text',
						text: JSON.stringify( response, null, 2 ),
					},
				],
			};
		}
	);

	return server;
}

const init = async () => {
	try {
		const server = await create_surerank_mcp_Server();
		const sdk = new AngieMcpSdk();

		await sdk.registerServer( {
			name: 'surerank-angie-tools',
			version: '1.0.0',
			description: __(
				'SureRank Angie AI assistant, handles sitemap control, page or term visibility, robots meta tag management(search engine visibility), Site & Page SEO checks, Update post meta title and meta description, and comprehensive settings management for all SureRank SEO features',
				'surerank'
			),
			server,
		} );

		console.log(
			'SEO MCP Server registered with Angie successfully - SureRank'
		);
	} catch ( error ) {
		console.error(
			'Failed to register SEO MCP Server with SureRank Angie:',
			error
		);
	}
};

init();
