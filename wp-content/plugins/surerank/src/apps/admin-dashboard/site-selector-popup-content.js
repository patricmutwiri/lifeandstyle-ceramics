import apiFetch from '@wordpress/api-fetch';
import { Button, toast, Loader, Skeleton, Text } from '@bsf/force-ui';
import { useDispatch, useSuspenseSelect, useSelect } from '@wordpress/data';
import { STORE_NAME } from '@/admin-store/constants';
import { __ } from '@wordpress/i18n';
import { useState, Suspense } from '@wordpress/element';
import { handleDisconnectConfirm } from '@AdminComponents/user-dropdown';
import { X } from 'lucide-react';
import useSiteVerificationStatus from '@AdminDashboard/use-site-verification-status';
import SiteSelector from '@AdminDashboard/site-selector';

// API Endpoints
const ENDPOINTS = {
	SELECT_SITE: '/surerank/v1/google-search-console/site',
	VERIFY_SITE: '/surerank/v1/google-search-console/verify-site',
	ADD_SITE: '/surerank/v1/google-search-console/add-site',
};

// UI Text Constants
const TEXTS = {
	HEADER: __( 'Search Console Account', 'surerank' ),
	NOT_CONNECTED: __(
		"This domain isn't yet connected to Google Search Console.",
		'surerank'
	),
	NOT_VERIFIED: __(
		"This domain isn't yet verified in Google Search Console.",
		'surerank'
	),
	VERIFIED_HELP: __(
		'Your domain is connected and verified in Google Search Console.',
		'surerank'
	),
	UNVERIFIED_HELP: __(
		'Click the button above to verify in Google Search Console and connect to SureRank.',
		'surerank'
	),
	CREATE_HELP: __(
		'Click the button above to create and connect a new Google Search Console property for this domain in your Google account.',
		'surerank'
	),
	SELECT_EXISTING: __(
		'Select an existing site from your Google Search Console account.',
		'surerank'
	),
	DISCONNECT_TITLE: __( 'Disconnect Search Console Account', 'surerank' ),
	DISCONNECT_DESC: __(
		'Are you sure you want to disconnect your Search Console account from SureRank?',
		'surerank'
	),
	DISCONNECT_BUTTON: __( 'Disconnect', 'surerank' ),
	SWITCH_ACCOUNTS: __( 'Need to switch accounts?', 'surerank' ),
	LINK_EXISTING: __( 'Prefer to link an existing property?', 'surerank' ),
	BACK: __( 'Back', 'surerank' ),
	SELECT_SITE: __( 'Select a site', 'surerank' ),
	CONNECT_SITE: __( 'Select site', 'surerank' ),
	CONNECT_VERIFY: __( 'Connect & Verify Property', 'surerank' ),
	CONNECT_CREATE: __( 'Connect & Create New Property', 'surerank' ),
	CONNECT_YOUR_SITE: __( 'Select site', 'surerank' ),
	CONNECTED: __( 'Connected', 'surerank' ),
	CONNECTING: __( 'Selecting…', 'surerank' ),
	VERIFYING: __( 'Verifying…', 'surerank' ),
	PLEASE_SELECT: __( 'Please select a site', 'surerank' ),
	CONNECTED_SUCCESS: __( 'Site selected successfully', 'surerank' ),
	VERIFICATION_STARTED: __(
		'Verification started successfully!',
		'surerank'
	),
	PROPERTY_CREATED: __( 'Property created successfully!', 'surerank' ),
	PROPERTY_VERIFIED: __( 'Property verified successfully!', 'surerank' ),
	PROPERTY_CREATED_VERIFIED: __(
		'Property created and verified successfully!',
		'surerank'
	),
	SITE_SELECTED: __( 'Site selected successfully', 'surerank' ),
	FAILED_REQUEST: __( 'Failed to process request', 'surerank' ),
	FAILED_PROCEED: __( 'Failed to proceed', 'surerank' ),
	RELOAD_DESC: __(
		'The changes will take effect after a page refresh. Reloading in 2 seconds…',
		'surerank'
	),
	PENDING_DESC: __(
		'Verification is pending and may take 1-2 hours or up to 2 days. Your site has been added to Search Console. Reloading in 2 seconds…',
		'surerank'
	),
};

const RELOAD_DELAY = 2000;

// Utility Functions
const getCurrentSiteUrl = () => window.location.origin;

const getHelpText = (
	currentSiteInList,
	isSelectedSiteVerified,
	isCurrentSiteAlreadySelected
) => {
	if (
		isCurrentSiteAlreadySelected &&
		currentSiteInList &&
		isSelectedSiteVerified
	) {
		return TEXTS.VERIFIED_HELP;
	}
	if ( isCurrentSiteAlreadySelected ) {
		return null;
	}
	if ( currentSiteInList && isSelectedSiteVerified ) {
		return TEXTS.VERIFIED_HELP;
	}
	if ( currentSiteInList && ! isSelectedSiteVerified ) {
		return TEXTS.UNVERIFIED_HELP;
	}
	return TEXTS.CREATE_HELP;
};

// Component for handling site connection in connect mode
const ConnectSiteButton = () => {
	const searchConsole = useSelect(
		( select ) => select( STORE_NAME ).getSearchConsole(),
		[]
	);
	const [ isCreatingProperty, setIsCreatingProperty ] = useState( false );
	const currentSiteUrl = getCurrentSiteUrl();
	const {
		currentSiteInList,
		isSelectedSiteVerified,
		currentSiteInListButNotVerified,
		isCurrentSiteAlreadySelected,
	} = useSiteVerificationStatus(
		currentSiteUrl,
		currentSiteUrl,
		searchConsole
	);

	if (
		isCurrentSiteAlreadySelected &&
		currentSiteInList &&
		isSelectedSiteVerified
	) {
		return null;
	}

	const handleCreateProperty = async () => {
		if ( isCreatingProperty ) {
			return;
		}

		setIsCreatingProperty( true );

		try {
			// Use different endpoints based on site status
			let endpoint,
				method,
				data = {};

			if ( currentSiteInList && isSelectedSiteVerified ) {
				// Site is verified, just select it
				endpoint = ENDPOINTS.SELECT_SITE;
				method = 'PUT';
				data = { url: currentSiteUrl };
			} else if ( currentSiteInListButNotVerified ) {
				// Site exists but not verified
				endpoint = ENDPOINTS.VERIFY_SITE;
				method = 'POST';
			} else {
				// Site doesn't exist, create it
				endpoint = ENDPOINTS.ADD_SITE;
				method = 'POST';
			}

			const response = await apiFetch( {
				path: endpoint,
				method,
				...( Object.keys( data ).length > 0 && { data } ),
			} );

			if ( ! response.success ) {
				throw new Error( response.message ?? TEXTS.FAILED_REQUEST );
			}

			// Handle different response cases
			if ( currentSiteInList && isSelectedSiteVerified ) {
				// Site was already verified, just connected
				toast.success( TEXTS.CONNECTED_SUCCESS, {
					description: TEXTS.RELOAD_DESC,
				} );
			} else if ( response.pending ) {
				// Verification is pending
				toast.success(
					currentSiteInListButNotVerified
						? TEXTS.VERIFICATION_STARTED
						: TEXTS.PROPERTY_CREATED,
					{
						description: TEXTS.PENDING_DESC,
					}
				);
			} else {
				// Property created/verified successfully
				toast.success(
					currentSiteInListButNotVerified
						? TEXTS.PROPERTY_VERIFIED
						: TEXTS.PROPERTY_CREATED_VERIFIED,
					{
						description: TEXTS.RELOAD_DESC,
					}
				);
			}

			// Reload page after timeout
			setTimeout( () => {
				window.location.reload();
			}, RELOAD_DELAY );
		} catch ( error ) {
			toast.error( error.message );
		} finally {
			setIsCreatingProperty( false );
		}
	};

	// Determine button text based on site status
	const getButtonText = () => {
		if ( isCreatingProperty ) {
			if ( currentSiteInList && isSelectedSiteVerified ) {
				return TEXTS.CONNECTING;
			}
			return currentSiteInListButNotVerified
				? TEXTS.VERIFYING
				: TEXTS.CONNECTING;
		}

		if ( currentSiteInList && isSelectedSiteVerified ) {
			return TEXTS.CONNECT_SITE;
		}

		return currentSiteInListButNotVerified
			? TEXTS.CONNECT_VERIFY
			: TEXTS.CONNECT_CREATE;
	};

	return (
		<Button
			variant="primary"
			size="sm"
			onClick={ handleCreateProperty }
			disabled={ isCreatingProperty }
			icon={ isCreatingProperty && <Loader variant="secondary" /> }
			iconPosition="left"
		>
			{ getButtonText() }
		</Button>
	);
};

// Connect Mode Component
export const ConnectMode = ( { setForceSelectMode } ) => {
	const { setConfirmationModal, toggleSiteSelectorModal } =
		useDispatch( STORE_NAME );
	const searchConsole = useSelect(
		( select ) => select( STORE_NAME ).getSearchConsole(),
		[]
	);

	// Handle disconnect function for connect mode
	const handleDisconnect = () => {
		setConfirmationModal( {
			open: true,
			title: TEXTS.DISCONNECT_TITLE,
			description: TEXTS.DISCONNECT_DESC,
			onConfirm: handleDisconnectConfirm,
			confirmButtonText: TEXTS.DISCONNECT_BUTTON,
		} );
	};

	const currentSiteUrl = getCurrentSiteUrl();
	const {
		currentSiteInList,
		isSelectedSiteVerified,
		currentSiteInListButNotVerified,
		isCurrentSiteAlreadySelected,
	} = useSiteVerificationStatus(
		currentSiteUrl,
		currentSiteUrl,
		searchConsole
	);

	const isSiteSelected = () => {
		return !! searchConsole?.hasSiteSelected;
	};

	const getStatusText = () => {
		if ( currentSiteInListButNotVerified ) {
			return TEXTS.NOT_VERIFIED;
		}
		if ( ! currentSiteInList ) {
			return TEXTS.NOT_CONNECTED;
		}
		return null;
	};

	return (
		<div className="relative bg-background-primary rounded-lg shadow-2xl w-full max-w-md mx-auto">
			<div className="p-5 space-y-4">
				{ /* Header */ }
				<div className="space-y-2">
					<div className="flex justify-between items-start w-full">
						<Text size={ 16 } weight={ 600 } lineHeight={ 24 }>
							{ TEXTS.HEADER }
						</Text>
						{ isSiteSelected() && (
							<Button
								icon={ <X /> }
								onClick={ toggleSiteSelectorModal }
								variant="ghost"
								className="p-0"
							/>
						) }
					</div>
					{ ! isCurrentSiteAlreadySelected && getStatusText() && (
						<Text
							size={ 14 }
							weight={ 400 }
							lineHeight={ 20 }
							color="secondary"
						>
							{ getStatusText() }
						</Text>
					) }
				</div>
				{ /* Current Site URL */ }
				<div className="p-2 rounded-[4px] bg-background-secondary">
					<Text
						size={ 14 }
						lineHeight={ 20 }
						weight={ 700 }
						color="secondary"
					>
						{ currentSiteUrl }
					</Text>
				</div>
				{ /* Main Action */ }
				<div className="space-y-4">
					<Suspense fallback={ <Skeleton className="h-12 w-full" /> }>
						<ConnectSiteButton />
					</Suspense>

					{ /* Help Text - only show when action is needed */ }
					{ ( () => {
						const helpText = getHelpText(
							currentSiteInList,
							isSelectedSiteVerified,
							isCurrentSiteAlreadySelected
						);
						const isVerified =
							currentSiteInList && isSelectedSiteVerified;
						return helpText ? (
							<div
								className={ `p-2 rounded-lg ring-1 ${
									isVerified
										? 'ring-alert-border-green bg-alert-background-green'
										: 'ring-alert-border-info bg-alert-background-info'
								}` }
							>
								<Text
									size={ 14 }
									weight={ 400 }
									lineHeight={ 20 }
									color="secondary"
								>
									{ helpText }
								</Text>
							</div>
						) : null;
					} )() }
				</div>
				{ /* Footer Actions */ }
				<div className="flex flex-wrap justify-between items-center gap-3 pt-4 border-t">
					<Button
						iconPosition="left"
						size="sm"
						variant="link"
						onClick={ handleDisconnect }
						className="whitespace-nowrap"
					>
						{ TEXTS.SWITCH_ACCOUNTS }
					</Button>

					<Button
						variant="outline"
						size="sm"
						onClick={ () => {
							setForceSelectMode( true );
						} }
						className="whitespace-nowrap"
					>
						{ TEXTS.LINK_EXISTING }
					</Button>
				</div>
			</div>
		</div>
	);
};

const SiteSelectorInputs = ( { forceSelectMode, setForceSelectMode } ) => {
	const searchConsole = useSuspenseSelect(
		( select ) => select( STORE_NAME ).getSearchConsole(),
		[]
	);
	const { toggleSiteSelectorModal, setSearchConsole, setConfirmationModal } =
		useDispatch( STORE_NAME );
	const [ isLoading, setIsLoading ] = useState( false );
	const currentSiteUrl = getCurrentSiteUrl();

	// Set default selected site
	const [ selectedSite, setSelectedSite ] = useState(
		searchConsole?.selectedSite || searchConsole?.tempSelectedSite || ''
	);

	// Use the custom hook for site verification status
	const { isSelectedSiteVerified } = useSiteVerificationStatus(
		selectedSite,
		currentSiteUrl,
		searchConsole
	);

	const handleSelectSite = ( site ) => {
		setSelectedSite( site );
	};

	const handleDisconnect = () => {
		setConfirmationModal( {
			open: true,
			title: TEXTS.DISCONNECT_TITLE,
			description: TEXTS.DISCONNECT_DESC,
			onConfirm: handleDisconnectConfirm,
			confirmButtonText: TEXTS.DISCONNECT_BUTTON,
		} );
	};

	const handleProceed = async () => {
		if ( isLoading ) {
			return;
		}

		// Proceed with site selection only
		if ( ! selectedSite ) {
			toast.error( TEXTS.PLEASE_SELECT );
			return;
		}

		setIsLoading( true );
		try {
			const response = await apiFetch( {
				path: ENDPOINTS.SELECT_SITE,
				method: 'PUT',
				data: { url: selectedSite },
			} );
			if ( ! response.success ) {
				throw new Error( response.message ?? TEXTS.FAILED_PROCEED );
			}
			toast.success( TEXTS.SITE_SELECTED );
			toggleSiteSelectorModal();
			setSearchConsole( {
				selectedSite,
				hasSiteSelected: true,
			} );
		} catch ( error ) {
			toast.error( error.message );
		} finally {
			setIsLoading( false );
		}
	};

	return (
		<>
			<div className="p-5 pt-2 pb-3 space-y-4">
				<SiteSelector
					sites={ searchConsole?.sites || [] }
					selectedSite={ selectedSite }
					onSiteSelect={ handleSelectSite }
					placeholder={ TEXTS.SELECT_SITE }
				/>
			</div>
			{ /* Footer */ }
			<SiteSelectorFooter
				isLoading={ isLoading }
				selectedSite={ selectedSite }
				isSelectedSiteVerified={ isSelectedSiteVerified }
				handleDisconnect={ handleDisconnect }
				handleProceed={ handleProceed }
				forceSelectMode={ forceSelectMode }
				setForceSelectMode={ setForceSelectMode }
			/>
		</>
	);
};

const SiteSelectorFooter = ( {
	isLoading,
	selectedSite,
	isSelectedSiteVerified,
	handleDisconnect,
	handleProceed,
	forceSelectMode,
	setForceSelectMode,
} ) => {
	return (
		<div className="flex flex-wrap justify-between items-center border-t bg-background-secondary p-4 gap-3 rounded-b-lg">
			<Button
				iconPosition="left"
				size="sm"
				variant="link"
				onClick={ handleDisconnect }
				className="whitespace-nowrap"
			>
				{ TEXTS.SWITCH_ACCOUNTS }
			</Button>

			<div className="flex gap-3">
				{ forceSelectMode && (
					<Button
						variant="outline"
						size="sm"
						onClick={ () => setForceSelectMode( false ) }
					>
						{ TEXTS.BACK }
					</Button>
				) }

				<Button
					variant="primary"
					size="md"
					onClick={ handleProceed }
					icon={ isLoading && <Loader variant="secondary" /> }
					iconPosition="left"
					disabled={
						isLoading || ! selectedSite || ! isSelectedSiteVerified
					}
				>
					{ TEXTS.CONNECT_YOUR_SITE }
				</Button>
			</div>
		</div>
	);
};

// Select Mode Component
export const SelectMode = ( { forceSelectMode, setForceSelectMode } ) => {
	const { toggleSiteSelectorModal } = useDispatch( STORE_NAME );
	const searchConsole = useSelect(
		( select ) => select( STORE_NAME ).getSearchConsole(),
		[]
	);

	const isSiteSelected = () => {
		return !! searchConsole?.hasSiteSelected;
	};

	return (
		<div className="relative bg-background-primary rounded-lg shadow-lg max-w-md w-full">
			{ /* Header */ }
			<div className="border-b rounded-t-lg bg-background-secondary p-5 pb-2 space-y-3">
				<div className="flex justify-between items-start w-full">
					<Text size={ 16 } weight={ 600 } lineHeight={ 24 }>
						{ TEXTS.HEADER }
					</Text>
					{ isSiteSelected() && (
						<Button
							icon={ <X /> }
							onClick={ toggleSiteSelectorModal }
							variant="ghost"
							className="p-0"
						/>
					) }
				</div>
				<div className="space-y-1">
					<Text
						size={ 14 }
						weight={ 400 }
						lineHeight={ 20 }
						color="secondary"
					>
						{ TEXTS.SELECT_EXISTING }
					</Text>
				</div>
			</div>

			{ /* Body */ }
			<Suspense
				fallback={
					<div className="flex flex-col gap-5">
						<div className="flex flex-col gap-1.5 px-5 pt-2">
							<Skeleton className="h-5 w-1/4" />
							<Skeleton className="h-10 w-full" />
						</div>
						<div className="flex justify-end p-4 gap-3">
							<Skeleton className="h-10 w-20" />
							<Skeleton className="h-10 w-20" />
						</div>
					</div>
				}
			>
				<SiteSelectorInputs
					forceSelectMode={ forceSelectMode }
					setForceSelectMode={ setForceSelectMode }
				/>
			</Suspense>
		</div>
	);
};
