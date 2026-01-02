import { useState, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { useDispatch } from '@wordpress/data';
import {
	Button,
	Input,
	Text,
	toast,
	Container,
	Loader,
	FilePreview,
} from '@bsf/force-ui';
import { STORE_NAME } from '@/admin-store/constants';
import { useBlocker } from '@tanstack/react-router';

const ImportSection = () => {
	const [ selectedFile, setSelectedFile ] = useState( null );
	const [ isImporting, setIsImporting ] = useState( false );
	const { setConfirmationModal } = useDispatch( STORE_NAME );
	const inputKey = useRef( 0 );

	// Block navigation while importing
	useBlocker( {
		enableBeforeUnload: isImporting,
		shouldBlockFn: () => {
			if ( ! isImporting ) {
				return false;
			}
			const shouldLeave = window.confirm(
				__(
					'Are you sure you want to leave this page? The import process will be interrupted.',
					'surerank'
				)
			);
			return ! shouldLeave;
		},
	} );

	const handleFileChange = ( files ) => {
		const file = files[ 0 ];
		if ( file ) {
			setSelectedFile( file );
			return;
		}
		setSelectedFile( null );
	};

	const handleRemove = () => {
		setSelectedFile( null );
		inputKey.current++;
	};

	const handleImport = ( file ) => {
		setIsImporting( true );

		// Read file content and import
		const reader = new FileReader();
		reader.onload = async ( event ) => {
			try {
				const settingsData = JSON.parse( event.target.result );

				const result = await apiFetch( {
					path: '/surerank/v1/import-settings',
					method: 'POST',
					data: {
						settings_data: settingsData,
					},
				} );

				if ( ! result.success ) {
					throw new Error(
						result.message || __( 'Import failed', 'surerank' )
					);
				}
				toast.success(
					__( 'Settings imported successfully!', 'surerank' ),
					{
						description: __(
							'The imported settings will take effect after a page refresh. Reloading in 3 seconds…',
							'surerank'
						),
					}
				);
				setSelectedFile( null );
				// Reload the page to reflect the changes
				setTimeout( () => {
					window.location.reload();
				}, 3000 );
			} catch ( error ) {
				toast.error(
					error?.message ??
						__(
							'Import failed. Please check the file format.',
							'surerank'
						)
				);
			} finally {
				setIsImporting( false );
			}
		};

		reader.readAsText( file );
	};

	const onImportClick = () => {
		if ( selectedFile ) {
			setConfirmationModal( {
				open: true,
				title: __( 'Import SureRank Settings', 'surerank' ),
				description: __(
					'Are you sure you want to import these settings? This action will override all existing SureRank settings and cannot be undone.',
					'surerank'
				),
				onConfirm: () => handleImport( selectedFile ),
				confirmButtonText: __( 'Import Settings', 'surerank' ),
				cancelButtonText: __( 'Cancel', 'surerank' ),
				type: 'destructive',
			} );
		}
	};

	const isDisabled = ! selectedFile || isImporting;

	return (
		<Container
			className="p-6 gap-6 w-full bg-background-primary rounded-lg shadow-sm"
			direction="column"
		>
			<div className="flex flex-col gap-2 w-full">
				<Text weight={ 600 } size={ 16 } color="primary">
					{ __( 'Import', 'surerank' ) }
				</Text>
			</div>
			<div className="flex flex-col w-full gap-1.5">
				<div className="flex flex-col gap-1.5 w-full">
					<div className="flex gap-2 w-full [&>:first-child]:w-full">
						<Input
							key={ inputKey.current }
							className="w-full"
							type="file"
							accept=".json"
							onChange={ handleFileChange }
							size="md"
						/>
						<Button
							variant="primary"
							onClick={ onImportClick }
							disabled={ isDisabled }
							icon={
								isImporting ? (
									<Loader variant="secondary" />
								) : null
							}
							iconPosition="left"
							className={
								isDisabled
									? 'disabled:opacity-70 disabled:bg-button-primary disabled:text-text-inverse disabled:outline-button-primary'
									: ''
							}
						>
							{ isImporting
								? __( 'Importing…', 'surerank' )
								: __( 'Import', 'surerank' ) }
						</Button>
					</div>
					<Text size="xs" color="help">
						{ __(
							'This file can be obtained by exporting the settings on another site using the form below.',
							'surerank'
						) }
					</Text>
				</div>
				{ selectedFile && (
					<FilePreview
						file={ selectedFile }
						size="md"
						onRemove={ handleRemove }
					/>
				) }
			</div>
		</Container>
	);
};

export default ImportSection;
