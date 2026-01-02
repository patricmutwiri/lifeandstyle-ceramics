import PageHeader from './page-header';
import { cn } from '@Functions/utils';

const PageContentWrapper = ( {
	children,
	title,
	description,
	icon,
	secondaryButton,
	info_tooltip = null,
	className,
	afterDescription = null,
} ) => {
	if ( ! children ) {
		return null;
	}

	return (
		<div
			className={ cn(
				'flex flex-col justify-start-start gap-7 w-full h-full',
				className
			) }
		>
			<PageHeader
				title={ title }
				description={ description }
				icon={ icon }
				secondaryButton={ secondaryButton }
				info_tooltip={ info_tooltip }
				afterDescription={ afterDescription }
			/>
			{ children }
		</div>
	);
};

export default PageContentWrapper;
