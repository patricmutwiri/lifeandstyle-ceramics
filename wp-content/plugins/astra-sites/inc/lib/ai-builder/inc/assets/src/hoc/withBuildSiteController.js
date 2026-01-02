import { Fragment } from '@wordpress/element';
import useBuildSiteController from '../hooks/useBuildSiteController';
import PreBuildConfirmModal from '../components/pre-build-confirm-modal';
import PremiumConfirmModal from '../components/premium-confirm-modal';
import InformPrevErrorModal from '../components/inform-prev-error-modal';
import MultisitePermissionModal from '../components/multisite-permission-modal';

const withBuildSiteController = ( WrappedComponent ) => {
	const WithBuildSiteController = ( { ...props } ) => {
		const {
			preBuildModal,
			handleClosePreBuildModal,
			handleGenerateContent,
			premiumModal,
			setPremiumModal,
			prevErrorAlert,
			setPrevErrorAlertOpen,
			onConfirmErrorAlert,
			handleClickStartBuilding,
			isInProgress,
			multisitePermissionModal,
			setMultisitePermissionModalOpen,
			setMultisitePermissionModal,
		} = useBuildSiteController();

		return (
			<Fragment>
				<WrappedComponent
					{ ...{
						handleClickStartBuilding,
						isInProgress,
						setMultisitePermissionModal,
						...props,
					} }
				/>
				<PreBuildConfirmModal
					open={ preBuildModal.open }
					setOpen={ handleClosePreBuildModal }
					startBuilding={ handleGenerateContent(
						preBuildModal.skipFeature
					) }
				/>
				<PremiumConfirmModal
					open={ premiumModal }
					setOpen={ setPremiumModal }
				/>
				<InformPrevErrorModal
					open={ prevErrorAlert.open }
					setOpen={ setPrevErrorAlertOpen }
					onConfirm={ onConfirmErrorAlert }
					errorString={ JSON.stringify( prevErrorAlert.error ) }
				/>
				<MultisitePermissionModal
					open={ multisitePermissionModal.open }
					setOpen={ setMultisitePermissionModalOpen }
					missingThemes={
						multisitePermissionModal.missingThemes || []
					}
					missingPlugins={
						multisitePermissionModal.missingPlugins || []
					}
				/>
			</Fragment>
		);
	};

	return WithBuildSiteController;
};

export default withBuildSiteController;
