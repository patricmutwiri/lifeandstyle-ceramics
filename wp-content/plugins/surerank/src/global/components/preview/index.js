import { EllipsisVertical } from 'lucide-react';
import { cn, decodeHtmlEntities } from '@Functions/utils';
import { SureRankLogo } from '@GlobalComponents/icons';

const Preview = ( {
	deviceType = 'desktop',
	faviconURL = '',
	title = '',
	description = '',
	permalink = '',
	siteTitle = '',
} ) => {
	const decoded_title = decodeHtmlEntities( title );
	const decoded_description = decodeHtmlEntities( description );

	return (
		<div className="p-2 rounded-lg bg-background-secondary">
			<div className="rounded-md border border-solid border-border-subtle bg-background-primary p-4 space-y-1.5 shadow-sm">
				{ /* Site logo, title, and URL */ }
				<div className="grid grid-cols-[1.75rem_1fr] items-center gap-3">
					{ faviconURL ? (
						<div className="relative w-7 h-7">
							<img
								className="w-full h-full object-cover m-0"
								src={ faviconURL }
								alt="favicon"
							/>
						</div>
					) : (
						<SureRankLogo className="size-7" />
					) }

					<div className="flex flex-col gap-0.5">
						<span className="text-text-primary font-semibold">
							{ siteTitle }
						</span>
						<div className="flex items-center justify-start gap-2">
							<span className="text-text-secondary line-clamp-1 break-all">
								{ /* Site URL */ }
								{ permalink }
							</span>
							<EllipsisVertical className="size-3.5 text-icon-secondary" />
						</div>
					</div>
				</div>
				{ /* Page title and description */ }
				<div className="space-y-1">
					<p className="text-xl leading-8 font-normal text-text-seo-title m-0 line-clamp-1 break-all">
						{ /* Page title */ }
						{ decoded_title }
					</p>
					<p
						className={ cn(
							'text-sm leading-5 font-medium text-text-secondary m-0 break-words',
							deviceType === 'mobile' &&
								'text-2xl leading-9.5 line-clamp-2'
						) }
					>
						{ /* Page description */ }
						{ decoded_description }
					</p>
				</div>
			</div>
		</div>
	);
};

export default Preview;
