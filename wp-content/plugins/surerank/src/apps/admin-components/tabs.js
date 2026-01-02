import { useState } from '@wordpress/element';
import { Tabs as FTabs } from '@bsf/force-ui';
import { renderField } from '@Functions/page-content-generator';

const Tabs = ( { field, formValues, setFormValues } ) => {
	const [ activeItem, setActiveItem ] = useState(
		field?.defaultValue ?? field?.tabs[ 0 ]?.slug
	);
	return (
		<FTabs activeItem={ activeItem }>
			<FTabs.Group
				variant="rounded"
				width="full"
				className="w-full"
				onChange={ ( { value } ) => {
					setActiveItem( value.slug );
				} }
			>
				{ field?.tabs?.map( ( tab ) => (
					<FTabs.Tab
						key={ tab.slug }
						slug={ tab.slug }
						text={ tab.label }
					/>
				) ) }
			</FTabs.Group>
			<div className="w-full">
				{ field?.tabs?.map( ( tab ) => (
					<FTabs.Panel key={ tab.slug } slug={ tab.slug }>
						<div className="w-full flex flex-col gap-6">
							{ tab.content?.map( ( contentField, index ) =>
								renderField(
									{
										...contentField,
										id:
											contentField.id ||
											`${ tab.slug }-field-${ index }`,
									},
									formValues,
									setFormValues
								)
							) }
						</div>
					</FTabs.Panel>
				) ) }
			</div>
		</FTabs>
	);
};

export default Tabs;
