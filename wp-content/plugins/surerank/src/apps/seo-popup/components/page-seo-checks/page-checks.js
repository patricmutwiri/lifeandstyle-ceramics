import { useMemo, memo } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { Loader, Text } from '@bsf/force-ui';
import { motion } from 'framer-motion';
import { CheckCard } from '@GlobalComponents/check-card';
import {
	PRO_PAGE_CHECKS_CONTENT_GENERATION_MAPPING,
	isFixItForMeButton,
	IS_HELP_ME_FIX_PRO_ACTIVE,
	shouldHideFixHelpButtons,
	helpMeFixRedirect,
} from '@/global/constants';
import { STORE_NAME } from '@Store/constants';

const PageChecks = ( {
	pageSeoChecks = {},
	onIgnore,
	onRestore,
	onFix,
	type = 'page',
} ) => {
	const {
		badChecks = [],
		fairChecks = [],
		passedChecks = [],
		ignoredChecks = [],
		suggestionChecks = [],
		isCheckingLinks = false,
		linkCheckProgress = { current: 0, total: 0 },
	} = pageSeoChecks;

	const hideFixHelpButtons = shouldHideFixHelpButtons( STORE_NAME );

	const hasBadOrFairChecks = useMemo(
		() =>
			badChecks.length > 0 ||
			fairChecks.length > 0 ||
			suggestionChecks.length > 0,
		[ badChecks.length, fairChecks.length, suggestionChecks.length ]
	);

	const handleIgnoreCheck = ( checkId ) => () => {
		if ( ! checkId || typeof onIgnore !== 'function' ) {
			return;
		}
		onIgnore( checkId );
	};

	const handleFixCheck = ( checkId ) => () => {
		if ( ! checkId ) {
			return;
		}

		if ( isFixItForMeButton( checkId ) && typeof onFix === 'function' ) {
			onFix( checkId );
		} else {
			helpMeFixRedirect( checkId );
		}
	};

	const getFixItButtonProps = ( checkId ) => {
		if ( hideFixHelpButtons ) {
			return { show: false };
		}

		const isFixButton = isFixItForMeButton( checkId );
		const locked = isFixButton
			? PRO_PAGE_CHECKS_CONTENT_GENERATION_MAPPING.includes( checkId )
			: ! IS_HELP_ME_FIX_PRO_ACTIVE;

		return {
			show: true,
			locked,
			...( ! isFixButton && {
				buttonLabel: __( 'Help Me Fix', 'surerank' ),
			} ),
		};
	};

	return (
		<motion.div
			className="space-y-3 p-1"
			initial={ { opacity: 0 } }
			animate={ { opacity: 1 } }
			exit={ { opacity: 0 } }
			transition={ { duration: 0.3 } }
		>
			{ /* Critical and Warning Checks Container */ }
			{ hasBadOrFairChecks && (
				<div className="space-y-3">
					{ /* Broken links check progress will render here - only for page checks */ }
					{ type === 'page' && isCheckingLinks && (
						<div className="flex items-center gap-2 p-2 bg-white rounded-lg shadow-sm border-0.5 border-solid border-border-subtle">
							<Loader size="sm" />
							<Text size={ 14 } weight={ 500 } color="tertiary">
								{ sprintf(
									/* translators: %1$d: number of links */
									__(
										'%1$d out of %2$d checks are done.',
										'surerank'
									),
									linkCheckProgress.current,
									linkCheckProgress.total
								) }
							</Text>
						</div>
					) }
					{ badChecks.map( ( check ) => (
						<CheckCard
							key={ check.id }
							variant="red"
							label={ __( 'Critical', 'surerank' ) }
							title={ check.title }
							data={ check?.data }
							showImages={ check?.showImages }
							onIgnore={ handleIgnoreCheck( check.id ) }
							showIgnoreButton={ true }
							onFix={ handleFixCheck( check.id ) }
							fixItButtonProps={ getFixItButtonProps( check.id ) }
						/>
					) ) }
					{ fairChecks.map( ( check ) => (
						<CheckCard
							key={ check.id }
							variant="yellow"
							label={ __( 'Warning', 'surerank' ) }
							title={ check.title }
							data={ check?.data }
							showImages={ check?.showImages }
							onIgnore={ handleIgnoreCheck( check.id ) }
							onFix={ handleFixCheck( check.id ) }
							showIgnoreButton={ true }
							fixItButtonProps={ getFixItButtonProps( check.id ) }
						/>
					) ) }
					{ suggestionChecks.map( ( check ) => (
						<CheckCard
							key={ check.id }
							variant="blue"
							label={ __( 'Suggestion', 'surerank' ) }
							title={ check.title }
							data={ check?.data }
							showImages={ check?.showImages }
							onIgnore={ handleIgnoreCheck( check.id ) }
							showIgnoreButton={ true }
							onFix={ handleFixCheck( check.id ) }
							fixItButtonProps={ getFixItButtonProps( check.id ) }
						/>
					) ) }
				</div>
			) }
			{ /* Ignored Checks Container */ }
			{ ignoredChecks.length > 0 && (
				<div className="space-y-3">
					{ ignoredChecks.map( ( check ) => (
						<CheckCard
							key={ check.id }
							variant="neutral"
							label={ __( 'Ignore', 'surerank' ) }
							title={ check.title }
							showRestoreButton={ true }
							onRestore={ () => onRestore( check.id ) }
						/>
					) ) }
				</div>
			) }

			{ passedChecks.length > 0 && (
				<div className="space-y-3">
					{ passedChecks.map( ( check ) => (
						<CheckCard
							key={ check.id }
							variant="green"
							label={ __( 'Passed', 'surerank' ) }
							title={ check.title }
							onIgnore={ () => onIgnore( check.id ) }
						/>
					) ) }
				</div>
			) }
		</motion.div>
	);
};

export default memo( PageChecks );
