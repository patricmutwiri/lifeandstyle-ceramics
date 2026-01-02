import { __ } from '@wordpress/i18n';
import { memo } from '@wordpress/element';
import CollapsibleSection from '@components/collapsible-section';
import { Badge } from '@bsf/force-ui';
import { CheckIcon, XIcon } from 'lucide-react';
import { CONTENT_GUARD_CATEGORIES } from '@utils/constants';
import Title from '@components/title/title';

const transformCategoryLabel = ( key ) => {
	if ( ! key ) {
		return '';
	}

	let result = '';
	let capitalize = true;

	for ( let idx = 0; idx < key.length; idx++ ) {
		const char = key[ idx ];

		if ( char === '/' || char === '-' ) {
			result += ' ';
			capitalize = true;
			continue;
		}

		result += capitalize ? char.toUpperCase() : char;
		capitalize = false;
	}

	return result;
};

const ContentGuardChecks = ( { log } ) => {
	const contentGuardActivated =
		window?.suremails?.contentGuardActiveStatus === 'yes';

	const categories = log.meta?.content_guard?.categories
		? Object.entries( log.meta.content_guard.categories )
		: Object.entries( CONTENT_GUARD_CATEGORIES );
	const totalCategories = categories?.length;
	const totalPass = categories?.length
		? categories.filter( ( [ , value ] ) => ! value ).length
		: 0;

	return (
		contentGuardActivated && (
			<CollapsibleSection defaultOpen>
				<CollapsibleSection.Trigger className="flex items-center gap-1">
					<Title
						tag="h4"
						title={ __( 'Reputation Shield Checks', 'suremails' ) }
					/>
					<span className="ml-1 text-xs font-normal text-field-helper">
						{ totalPass }/{ totalCategories }
					</span>
				</CollapsibleSection.Trigger>
				<CollapsibleSection.Content>
					<div className="p-4 bg-background-secondary rounded overflow-hidden space-y-2">
						{ categories.map( ( [ key, value ] ) => {
							const isPassed = ! value;
							return (
								<div
									key={ key }
									className="p-3 flex items-center justify-between shadow-sm text-text-primary bg-background-primary rounded-md"
								>
									<p className="text-sm font-medium text-text-primary">
										{ transformCategoryLabel( key ) }
									</p>
									<Badge
										label={
											<span className="flex items-center gap-0.5">
												{ isPassed ? (
													<CheckIcon className="size-3 -ml-0.5" />
												) : (
													<XIcon className="size-3 -ml-0.5" />
												) }
												{ isPassed
													? __( 'Pass', 'suremails' )
													: __(
															'Fail',
															'suremails'
													  ) }
											</span>
										}
										variant={ isPassed ? 'green' : 'red' }
										disableHover
									/>
								</div>
							);
						} ) }
					</div>
				</CollapsibleSection.Content>
			</CollapsibleSection>
		)
	);
};

export default memo( ContentGuardChecks );
