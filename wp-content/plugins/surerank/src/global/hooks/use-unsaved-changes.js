/**
 * Hook to track unsaved changes in settings/forms
 *
 * Provides functionality to:
 * - Track initial settings and compare with current settings
 * - Show browser's beforeunload prompt when there are unsaved changes
 * - Block navigation with TanStack Router's useBlocker
 * - Return helper functions for UI (icon, reset, etc.)
 */

import { useRef, useCallback, useMemo, useState } from '@wordpress/element';
import { useBlocker } from '@tanstack/react-router';
import { __ } from '@wordpress/i18n';
import { LoaderCircle } from 'lucide-react';
import { DotIcon } from '@/global/components/icons';

/**
 * Custom hook to track unsaved changes and optionally block navigation
 *
 * @param {Object}  options                       - Hook options
 * @param {Object}  options.currentSettings       - Current settings object to compare against initial
 * @param {boolean} options.enableNavigationBlock - Whether to enable navigation blocking (default: false)
 * @param {boolean} options.enableBeforeUnload    - Whether to enable browser's beforeunload prompt (default: false)
 * @param {string}  options.blockMessage          - Custom message for the block prompt (optional)
 * @param {boolean} options.isUpdating            - Whether settings are currently being saved (for icon display)
 *
 * @return {Object} Hook return values
 */
const useUnsavedChanges = ( {
	currentSettings,
	enableNavigationBlock = false,
	enableBeforeUnload = false,
	blockMessage = __( 'You have unsaved changes. Are you sure you want to leave?', 'surerank' ),
	isUpdating = false,
} ) => {
	// Store initial settings when the hook first runs
	const initialSettingsRef = useRef( null );
	if ( initialSettingsRef.current === null && currentSettings ) {
		initialSettingsRef.current = JSON.parse( JSON.stringify( currentSettings ) );
	}

	// Keep a ref to the latest currentSettings so resetInitialSettings always has access to fresh value
	const currentSettingsRef = useRef( currentSettings );
	currentSettingsRef.current = currentSettings;

	// Force re-check of hasUnsavedChanges by updating a counter
	const [ resetCounter, setResetCounter ] = useState( 0 );

	// Check if there are unsaved changes by comparing current settings with initial
	const hasUnsavedChanges = useMemo( () => {
		if ( ! initialSettingsRef.current || ! currentSettings ) {
			return false;
		}

		// Deep comparison using JSON.stringify for nested objects
		return JSON.stringify( initialSettingsRef.current ) !== JSON.stringify( currentSettings );
	}, [ currentSettings, resetCounter ] );

	// Reset initial settings (call after successful save)
	// Uses ref to always get the latest currentSettings value
	const resetInitialSettings = useCallback( () => {
		if ( currentSettingsRef.current ) {
			initialSettingsRef.current = JSON.parse( JSON.stringify( currentSettingsRef.current ) );
			// Trigger re-computation of hasUnsavedChanges
			setResetCounter( ( n ) => n + 1 );
		}
	}, [] );

	// Use TanStack Router's useBlocker to block navigation when there are unsaved changes
	const blocker = useBlocker( {
		shouldBlockFn: () => {
			if ( ! enableNavigationBlock || ! hasUnsavedChanges ) {
				return false;
			}

			const shouldLeave = window.confirm( blockMessage );
			return ! shouldLeave;
		},
		enableBeforeUnload: enableBeforeUnload && hasUnsavedChanges,
	} );

	// Function to determine button icon based on state
	const getButtonIcon = useCallback( () => {
		if ( isUpdating ) {
			return <LoaderCircle className="animate-spin" />;
		}
		if ( hasUnsavedChanges ) {
			return <DotIcon />;
		}
		return null;
	}, [ isUpdating, hasUnsavedChanges ] );

	// Generate className for save button
	const getSaveButtonClassName = useCallback( ( additionalClasses = '' ) => {
		const disabledClasses = isUpdating || ! hasUnsavedChanges
			? 'bg-background-brand cursor-not-allowed pointer-events-none'
			: '';
        const opacity = ! hasUnsavedChanges ? 'opacity-60' : '';

		return [ disabledClasses, opacity, additionalClasses ].filter( Boolean ).join( ' ' );
	}, [ isUpdating, hasUnsavedChanges ] );

	return {
		hasUnsavedChanges,
		resetInitialSettings,
		getButtonIcon,
		getSaveButtonClassName,
		blocker,
	};
};

export default useUnsavedChanges;
