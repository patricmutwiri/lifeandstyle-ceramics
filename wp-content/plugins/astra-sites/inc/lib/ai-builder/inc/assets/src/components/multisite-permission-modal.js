import { memo } from '@wordpress/element';
import Modal from './modal';
import ModalTitle from './modal-title';
import { __ } from '@wordpress/i18n';
import Button from './button';
import { ExclamationCircleIcon } from '@heroicons/react/24/outline';

const MultisitePermissionModal = ( {
	open,
	setOpen,
	missingThemes = [],
	missingPlugins = [],
} ) => {
	return (
		<Modal
			open={ open }
			setOpen={ setOpen }
			className="sm:w-full sm:max-w-2xl"
		>
			<ModalTitle>
				<ExclamationCircleIcon className="w-8 h-8 text-alert-error" />
				<h5 className="text-lg text-zip-app-heading">
					{ __( 'Multisite Requirements Not Met', 'ai-builder' ) }
				</h5>
			</ModalTitle>

			<p className="!mt-3 text-sm leading-5 font-normal text-zip-body-text">
				{ __(
					'In a multisite environment, you need to have all required plugins and themes already installed and activated by your network administrator to import this template.',
					'ai-builder'
				) }
			</p>

			<div className="!my-4 space-y-6">
				{ missingThemes.length > 0 && (
					<div>
						<h3 className="text-base font-semibold text-zip-app-heading mb-3">
							{ __( 'Missing Theme:', 'ai-builder' ) }
						</h3>
						<ul className="list-disc pl-5 space-y-1">
							{ missingThemes.map( ( theme, index ) => (
								<li
									key={ index }
									className="text-zip-body-text"
								>
									{ theme.name || theme }
								</li>
							) ) }
						</ul>
					</div>
				) }

				{ missingPlugins.length > 0 && (
					<div>
						<h3 className="text-base font-semibold text-zip-app-heading mb-3">
							{ __( 'Missing Plugins:', 'ai-builder' ) }
						</h3>
						<ul className="list-disc pl-5 space-y-1">
							{ missingPlugins.map( ( plugin, index ) => (
								<li
									key={ index }
									className="text-zip-body-text"
								>
									{ plugin.name || plugin }
								</li>
							) ) }
						</ul>
					</div>
				) }

				<div className="p-4 bg-blue-50 rounded-lg">
					<p className="text-blue-800 text-sm">
						{ __(
							'Please contact your network administrator to install and activate these plugins and themes.',
							'ai-builder'
						) }
					</p>
				</div>
			</div>

			<div className="flex items-center gap-3 justify-center mt-4">
				<Button
					variant="primary"
					size="base"
					className="w-full"
					onClick={ () => {
						setOpen( false );
						window.location.href = aiBuilderVars?.adminUrl || '/';
					} }
				>
					{ __( 'Go To Main Screen', 'ai-builder' ) }
				</Button>
			</div>
		</Modal>
	);
};

export default memo( MultisitePermissionModal );
