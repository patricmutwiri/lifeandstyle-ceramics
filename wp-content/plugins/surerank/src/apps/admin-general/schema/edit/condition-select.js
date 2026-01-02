import { memo, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, Select as RuleSelectInput } from '@bsf/force-ui';
import { Trash, X } from 'lucide-react';
import Select from 'react-select';

const ConditionSelect = ( {
	conditionsList,
	setConditionsList,
	groupedOptions,
	viewKey,
	updateSchema,
	fetchSpecificPosts,
	isClearable = false,
} ) => {
	const formattedOptions = useMemo( () => {
		return groupedOptions.flatMap( ( group ) => group.options );
	}, [ groupedOptions ] );

	const handleConditionUpdate = ( updatedConditions ) => {
		setConditionsList( updatedConditions );
		updateSchema(
			viewKey,
			updatedConditions.map( ( cond ) => cond.condition ),
			updatedConditions
				.filter( ( cond ) => cond.condition === 'specifics' )
				.flatMap( ( cond ) => cond.specificPosts )
		);
	};

	return conditionsList.map( ( item, index ) => (
		<div key={ index } className="flex flex-col gap-2 mt-2">
			<div className="flex flex-row gap-2 items-center">
				<div className="w-full">
					<RuleSelectInput
						combobox
						onChange={ ( value ) => {
							const updated = [ ...conditionsList ];
							updated[ index ].condition = value;
							handleConditionUpdate( updated );
						} }
						size="md"
						value={ item.condition ?? '' }
					>
						<RuleSelectInput.Button
							className="[&>div>span]:inline-block [&>div>span]:w-full"
							placeholder={ __( 'Select an option', 'surerank' ) }
							render={ ( value ) => {
								const selectedOption = value
									? formattedOptions.find(
											( option ) => option.value === value
									  )
									: null;
								const displayText = selectedOption
									? selectedOption.label
									: __( 'Select an option', 'surerank' );

								return (
									<span className="relative flex items-center justify-between w-full">
										<span className="truncate pr-7">
											{ displayText }
										</span>
										{ isClearable && value && (
											<>
												<span
													role="button"
													tabIndex="0"
													onMouseDown={ ( event ) => {
														event.preventDefault();
														event.stopPropagation();
														const updated = [
															...conditionsList,
														];
														updated[
															index
														].condition = '';
														updated[
															index
														].specificPosts = [];
														handleConditionUpdate(
															updated
														);
													} }
													className="absolute p-1 right-4 inline-flex rounded transition-colors text-icon-secondary hover:text-icon-primary"
													aria-label={ __(
														'Clear selection',
														'surerank'
													) }
												>
													<X className="size-5 transition-colors" />
												</span>
												<hr className="h-4 border-l border-y-0 border-r-0 border-solid border-border-subtle mx-2 my-0" />
											</>
										) }
									</span>
								);
							} }
						/>
						<RuleSelectInput.Portal id="surerank-root">
							<RuleSelectInput.Options>
								{ groupedOptions.map( ( group ) => (
									<RuleSelectInput.OptionGroup
										key={ group.label }
										label={ group.label }
									>
										{ group.options.map( ( option ) => (
											<RuleSelectInput.Option
												key={ option.value }
												value={ option.value }
												name={ option.label }
												id={ option.value }
											>
												{ option.label }
											</RuleSelectInput.Option>
										) ) }
									</RuleSelectInput.OptionGroup>
								) ) }
							</RuleSelectInput.Options>
						</RuleSelectInput.Portal>
					</RuleSelectInput>
				</div>

				{ conditionsList.length !== 1 && (
					<Button
						variant="ghost"
						size="sm"
						onClick={ () => {
							const updated = conditionsList.filter(
								( _, i ) => i !== index
							);
							handleConditionUpdate( updated );
						} }
						className="text-icon-secondary"
						icon={ <Trash className="size-4" /> }
					/>
				) }
			</div>

			{ item.condition === 'specifics' && (
				<Select
					isMulti
					options={ item.searchOptions }
					value={ item.specificPosts }
					onInputChange={ ( query ) => {
						if ( query.length > 2 ) {
							fetchSpecificPosts( query, index, viewKey );
						}
					} }
					onChange={ ( values ) => {
						const updated = [ ...conditionsList ];
						updated[ index ].specificPosts = values;
						handleConditionUpdate( updated );
					} }
					placeholder={ __(
						'Search posts/pages/taxonomies, etc',
						'surerank'
					) }
					classNamePrefix="surerank"
					classNames={ {
						control: () =>
							`border border-solid border-field-border rounded-md outline outline-1 outline-field-border focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-focus focus-within:outline-none hover:border-field-border focus:border-field-border [&:hover:not(:focus):not(:focus-within)]:border-border-strong transition-[outline,border,background-color,color,box-shadow] duration-200 ease-in-out`,
						multiValue: () =>
							`font-medium items-center justify-center border border-solid box-border max-w-full transition-colors duration-150 ease-in-out py-1 px-1.5 text-xs h-6 rounded gap-0.5 bg-badge-background-gray text-badge-color-gray border-badge-border-gray hover:bg-badge-hover-gray`,
						multiValueLabel: () => `font-medium text-xs`,
						multiValueRemove: () =>
							`font-medium text-xs bg-transparent text-icon-secondary cursor-pointer`,
						menu: () =>
							`outline outline-1 outline-field-border rounded-md z-10`,
						noOptionsMessage: () =>
							`text-field-placeholder text-sm font-normal`,
						placeholder: () =>
							`text-field-placeholder text-sm font-normal`,
					} }
					styles={ {
						option: ( provided, state ) => ( {
							...provided,
							backgroundColor: state.isFocused
								? '#f9fafb'
								: 'transparent',
							padding: '8px 12px',
						} ),
					} }
				/>
			) }
		</div>
	) );
};

export default memo( ConditionSelect );
