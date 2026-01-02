import PageContentWrapper from '@AdminComponents/page-content-wrapper';
import { __ } from '@wordpress/i18n';
import withSuspense from '@AdminComponents/hoc/with-suspense';
import GeneratePageContent from '@Functions/page-content-generator';
import { createLazyRoute } from '@tanstack/react-router';

const getPageContent = () => {
	const socialProfiles = surerank_admin_common?.social_profiles ?? [];

	return socialProfiles
		.filter( ( profile ) => profile.show_in_dashboard !== false )
		.map( ( profile ) => ( {
			id: profile.id,
			name: profile.id,
			label: profile.label,
			placeholder: profile.placeholder,
			type: 'text',
			storeKey: 'social_profiles',
			dataType: 'object',
		} ) );
};

export const PAGE_CONTENT = [
	{
		type: 'container',
		content: [ ...getPageContent() ],
	},
];

const SocialAccounts = () => {
	return (
		<PageContentWrapper
			title={ __( 'Other Accounts', 'surerank' ) }
			description={ __(
				'Enter the full URLs of your official profiles. These will be added to the Organization schema under SameAs to help search engines verify your online presence.',
				'surerank'
			) }
		>
			<GeneratePageContent json={ PAGE_CONTENT } />
		</PageContentWrapper>
	);
};

export const LazyRoute = createLazyRoute( '/social/accounts' )( {
	component: withSuspense( SocialAccounts ),
} );

export default withSuspense( SocialAccounts );
