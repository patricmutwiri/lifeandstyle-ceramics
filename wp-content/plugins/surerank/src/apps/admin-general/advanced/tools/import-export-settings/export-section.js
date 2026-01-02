import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Button, Container, Loader, Switch, Text, toast } from '@bsf/force-ui';
import ToggleCard from './toggle-card';

const OPTIONS_TO_EXPORT = Object.entries(
	window.surerank_globals?.exporter_options ?? {}
).map( ( [ id, label ] ) => ( { id, label } ) );

const ExportSection = () => {
	const [ selectedOptions, setSelectedOptions ] = useState(
		OPTIONS_TO_EXPORT.reduce(
			( acc, option ) => ( { ...acc, [ option.id ]: true } ),
			{}
		)
	);

	const [ toggleAll, setToggleAll ] = useState( true );
	const [ isExporting, setIsExporting ] = useState( false );

	const handleToggleAll = ( checked ) => {
		setToggleAll( checked );
		setSelectedOptions( () => {
			const newOptions = {};
			OPTIONS_TO_EXPORT.forEach( ( option ) => {
				newOptions[ option.id ] = checked;
			} );
			return newOptions;
		} );
	};

	const handleToggleOption = ( option, checked ) => {
		setSelectedOptions( ( prev ) => {
			const newOptions = { ...prev, [ option ]: checked };
			// Check if all options are selected to update toggleAll state
			const allSelected = Object.values( newOptions ).every(
				( value ) => value === true
			);
			setToggleAll( allSelected );
			return newOptions;
		} );
	};

	const handleExport = async () => {
		if ( isExporting ) {
			return;
		}
		setIsExporting( true );

		try {
			// Get selected categories
			const selectedCategories = Object.keys( selectedOptions ).filter(
				( key ) => selectedOptions[ key ]
			);

			// Call REST API to get export data
			const result = await apiFetch( {
				path: '/surerank/v1/export-settings',
				method: 'POST',
				data: {
					categories: selectedCategories,
				},
			} );

			if ( ! result.success ) {
				throw new Error(
					result.message || __( 'Export failed', 'surerank' )
				);
			}

			// The export data is merged directly with the success response
			const { success, ...exportData } = result;

			// Generate and download JSON file
			const jsonString = JSON.stringify( exportData, null, 2 );
			const blob = new Blob( [ jsonString ], {
				type: 'application/json',
			} );
			const url = URL.createObjectURL( blob );

			const link = document.createElement( 'a' );
			link.href = url;
			link.download = `surerank-settings-${
				new Date().toISOString().split( 'T' )[ 0 ]
			}.json`;
			document.body.appendChild( link );
			link.click();
			document.body.removeChild( link );
			URL.revokeObjectURL( url );
		} catch ( error ) {
			toast(
				error.message ||
					__( 'Export failed. Please try again.', 'surerank' )
			);
		} finally {
			setIsExporting( false );
		}
	};

	// Check if any option is enabled
	const isAnyOptionEnabled = Object.values( selectedOptions ).some(
		( value ) => value === true
	);
	const isDisabled = ! isAnyOptionEnabled && ! isExporting;

	return (
		<Container
			className="p-6 gap-2 w-full bg-background-primary rounded-lg shadow-sm"
			direction="column"
		>
			<div className="flex justify-between items-center p-1">
				<Text weight={ 600 } size={ 16 } color="primary">
					{ __( 'Export', 'surerank' ) }
				</Text>
				<div className="flex items-center gap-2">
					<Text weight={ 500 } size={ 12 } color="primary">
						{ __( 'Toggle all', 'surerank' ) }
					</Text>
					<Switch
						checked={ toggleAll }
						onChange={ handleToggleAll }
						size="xs"
					/>
				</div>
			</div>

			<div className="flex flex-col gap-1 p-1 bg-background-secondary rounded-lg">
				{ OPTIONS_TO_EXPORT.map( ( option ) => (
					<ToggleCard
						key={ option.id }
						label={ option.label }
						checked={ selectedOptions[ option.id ] }
						onChange={ ( checked ) =>
							handleToggleOption( option.id, checked )
						}
						id={ option.id }
					/>
				) ) }
			</div>

			<div className="flex justify-start p-1">
				<Button
					variant="primary"
					onClick={ handleExport }
					disabled={ isDisabled }
					icon={ isExporting ? <Loader variant="secondary" /> : null }
					iconPosition="left"
					className={
						isDisabled
							? 'disabled:opacity-70 disabled:bg-button-primary disabled:text-text-inverse disabled:outline-button-primary'
							: ''
					}
				>
					{ isExporting
						? __( 'Exporting…', 'surerank' )
						: __( 'Export Settings', 'surerank' ) }
				</Button>
			</div>
		</Container>
	);
};

export default ExportSection;
