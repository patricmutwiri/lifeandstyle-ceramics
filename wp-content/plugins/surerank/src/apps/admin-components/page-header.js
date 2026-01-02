import { Container, Text, Title } from '@bsf/force-ui';
import { useSelect } from '@wordpress/data';
import { STORE_NAME } from '@AdminStore/constants';
import { InfoTooltip } from './tooltip';
import { useCallback, useEffect, isValidElement } from '@wordpress/element';

const PageHeader = ( {
	title = '',
	description = '',
	icon: Icon = null,
	secondaryButton = null,
	info_tooltip = null,
	afterDescription = null,
} ) => {
	const { unsavedSettings } = useSelect( ( select ) => {
		const { getUnsavedSettings } = select( STORE_NAME );
		return {
			unsavedSettings: getUnsavedSettings() || {},
		};
	}, [] );

	const hasUnsavedSettings = Object.keys( unsavedSettings || {} ).length > 0;

	// Handle navigation warning for unsaved changes
	const handleBeforeUnload = useCallback(
		( event ) => {
			event.preventDefault();
			event.returnValue = '';
		},
		[ unsavedSettings ]
	);

	useEffect( () => {
		if ( ! hasUnsavedSettings ) {
			return;
		}
		window.addEventListener( 'beforeunload', handleBeforeUnload );
		return () => {
			window.removeEventListener( 'beforeunload', handleBeforeUnload );
		};
	}, [ handleBeforeUnload, hasUnsavedSettings ] );

	return (
		<Container direction="column" className="gap-3">
			<div className="flex items-center justify-between gap-3 flex-1">
				<Container direction="column" className="gap-0.5">
					<Container direction="row" className="gap-2">
						<Title
							className="[&_h2]:text-text-primary [&_h2]:leading-[1.875rem]"
							title={ title }
							icon={
								!! Icon && (
									<Icon className="size-5 text-text-primary" />
								)
							}
							iconPosition="left"
							size="md"
						/>
						{ /* Added the InfoTooltip next to the Title */ }
						{ info_tooltip && (
							<div className="mt-[7px]">
								<InfoTooltip
									content={ info_tooltip }
									interactive={ true }
								/>
							</div>
						) }
					</Container>
					<Text size={ 14 } weight={ 400 } color="secondary">
						{ description }
					</Text>
				</Container>

				{ !! secondaryButton && secondaryButton }
			</div>
			{ isValidElement( afterDescription ) && afterDescription }
		</Container>
	);
};

export default PageHeader;
