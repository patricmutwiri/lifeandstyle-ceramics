import PageContentWrapper from '@AdminComponents/page-content-wrapper';
import { __ } from '@wordpress/i18n';
import withSuspense from '@AdminComponents/hoc/with-suspense';
import GeneratePageContent from '@Functions/page-content-generator';

export const PAGE_CONTENT = [
	{
		container: {
			direction: 'column',
			gap: 6,
		},
		content: [
			{
				id: 'author_archive',
				type: 'switch',
				storeKey: 'author_archive',
				label: __(
					'Remove Author Pages & Redirect to Home',
					'surerank'
				),
				description: __(
					'Author pages list all posts written by a specific author. On single-author sites, these pages duplicate your main blog and can confuse search engines. Enable this to redirect them to your homepage.',
					'surerank'
				),
				transform: ( value ) => ! value,
			},
			{
				id: 'date_archive',
				type: 'switch',
				storeKey: 'date_archive',
				label: __(
					'Remove Date Archive Pages & Redirect to Home',
					'surerank'
				),
				description: __(
					'WordPress creates archive pages for each month or year of your posts. These pages often add no real value and can lead to duplicate content. Enable this to redirect them to your homepage.',
					'surerank'
				),
				transform: ( value ) => ! value,
			},
		],
	},
];

const ArchivePages = () => {
	return (
		<PageContentWrapper
			title={ __( 'Archive Pages', 'surerank' ) }
			description={ __(
				'Archive Pages let visitors access links to view posts by author or by date. This makes it easier for people to find content based on who wrote it or when it was published.',
				'surerank'
			) }
		>
			<GeneratePageContent json={ PAGE_CONTENT } />
		</PageContentWrapper>
	);
};

export default withSuspense( ArchivePages );
