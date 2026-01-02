import { useRef, useState } from '@wordpress/element';
import { Button, toast } from '@bsf/force-ui';
import { LoaderCircle, Check } from 'lucide-react';
import { __ } from '@wordpress/i18n';
import { ADMIN_SETTINGS_URL } from '@/global/constants/api';
import { STORE_NAME } from '@/admin-store/constants';
import { useSelect, useDispatch } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';
import { DotIcon } from '@/global/components/icons';

const GlobalSaveButton = ( {
	onClick,
	onSuccess, // New prop for handling success callback
	buttonTextInitial = __( 'Save', 'surerank' ),
	icon,
	disabled,
	...props
} ) => {
	const [ buttonText, setButtonText ] = useState( buttonTextInitial );
	const [ saving, setSaving ] = useState( false );
	const saveCompleted = useRef( true );

	const handleSaveClick = async () => {
		if ( saving || disabled || ! saveCompleted.current ) {
			return;
		}
		saveCompleted.current = false; // Reset for next save
		setSaving( true );
		setButtonText( __( 'Saving..', 'surerank' ) );

		try {
			const response = await onClick();
			if ( ! response.success ) {
				throw new Error( response.message );
			}
			setButtonText( __( 'Saved', 'surerank' ) );

			if ( onSuccess && typeof onSuccess === 'function' ) {
				await onSuccess( response );
			} else {
				toast.success(
					__( 'Settings saved successfully.', 'surerank' )
				);
			}

			// Return a promise that resolves after the button text reset
			return new Promise( ( resolve ) => {
				setTimeout( () => {
					setButtonText( buttonTextInitial );
					saveCompleted.current = true;
					resolve( response );
				}, 1000 );
			} );
		} catch ( error ) {
			toast.error( error.message );
			setButtonText( buttonTextInitial );
			saveCompleted.current = true;
		} finally {
			setSaving( false );
		}
	};

	const getIcon = () => {
		if ( saving ) {
			return <LoaderCircle className="animate-spin" />;
		}
		if ( buttonText === __( 'Saved', 'surerank' ) ) {
			return <Check />;
		}
		if ( icon ) {
			return icon;
		}
		return null;
	};

	return (
		<Button onClick={ handleSaveClick } icon={ getIcon() } { ...props }>
			{ buttonText }
		</Button>
	);
};

export const SaveSettingsButton = ( { onSuccess } ) => {
	const { unsavedSettings } = useSelect( ( select ) => {
		const { getUnsavedSettings } = select( STORE_NAME );
		return {
			unsavedSettings: getUnsavedSettings() || {},
		};
	}, [] );
	const { resetUnsavedSettings } = useDispatch( STORE_NAME );

	const handleSave = async () => {
		const queryParams = { data: unsavedSettings };

		const response = await apiFetch( {
			path: ADMIN_SETTINGS_URL,
			method: 'POST',
			data: queryParams,
		} );

		if ( response.success ) {
			resetUnsavedSettings();
		}

		return response;
	};

	const hasUnsavedSettings = Object.keys( unsavedSettings || {} ).length > 0;

	return (
		<GlobalSaveButton
			onClick={ handleSave }
			onSuccess={ onSuccess } // Pass the onSuccess callback
			className={
				! hasUnsavedSettings
					? 'opacity-60 bg-background-brand cursor-not-allowed pointer-events-none'
					: ''
			}
			icon={ hasUnsavedSettings ? <DotIcon /> : null }
		>
			{ __( 'Save', 'surerank' ) }
		</GlobalSaveButton>
	);
};

export default GlobalSaveButton;
