import { Button } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';
import SaveButton from '../save-button';

const Footer = ( { onClose } ) => {
	return (
		<div className="flex items-center justify-start gap-3 px-5 py-2.5">
			<SaveButton />
			<Button onClick={ onClose } variant="outline">
				{ __( 'Close', 'surerank' ) }
			</Button>
		</div>
	);
};

export default Footer;
