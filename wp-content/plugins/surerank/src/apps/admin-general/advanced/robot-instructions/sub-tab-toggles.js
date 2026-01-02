import { memo } from '@wordpress/element';
import { Container, Switch, Label } from '@bsf/force-ui';
import { __, sprintf } from '@wordpress/i18n';

const SubTabToggles = memo(
	( { type, keyWord, listData, settings, handleSwitchChange } ) => {
		return (
			<Container direction="column" className="w-full gap-2">
				{ Object.entries( listData ).map( ( [ item, heading ] ) => (
					<div
						key={ item }
						className="flex flex-row items-start gap-2"
					>
						<div className="p-2">
							<Switch
								id={ `toggle_${ item }` }
								size="sm"
								aria-label={ heading }
								checked={ settings.includes( item ) }
								onChange={ ( checked ) => {
									let updatedSettings;
									if ( checked ) {
										updatedSettings = [ ...settings, item ];
									} else {
										updatedSettings = settings.filter(
											( val ) => val !== item
										);
									}
									handleSwitchChange( type, updatedSettings );
								} }
								label={
									<div className="flex flex-col gap-0.5">
										<Label
											htmlFor={ `toggle_${ item }` }
											variant="neutral"
											className="gap-1"
										>
											{ heading }
										</Label>
										<Label
											htmlFor={ `toggle_${ item }` }
											size="xs"
											className="font-normal gap-1"
											variant="help"
										>
											{ sprintf(
												/* translators: %1$s: keyword, %2$s: heading */
												__(
													'Apply %1$s to all the %2$s',
													'surerank'
												),
												keyWord,
												heading
											) }
										</Label>
									</div>
								}
							/>
						</div>
					</div>
				) ) }
			</Container>
		);
	}
);

export default SubTabToggles;
