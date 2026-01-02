import { __ } from '@wordpress/i18n';
import { Button } from '@bsf/force-ui';
import Alert from '@/global/components/alert';

/**
 * MigrationError component displays an error message when migration fails
 *
 * @param {Object}   props            - Component props
 * @param {string}   props.error      - Error message to display
 * @param {Function} props.onRetry    - Function to call when retry button is clicked
 * @param {boolean}  props.isDisabled - Whether the retry button should be disabled
 * @return {JSX.Element} MigrationError component
 */
const MigrationError = ( { error, onRetry, isDisabled } ) => {
	return (
		<div className="flex flex-col gap-2">
			<Alert
				title={ __( 'Migration Error', 'surerank' ) }
				message={ error }
				showIcon={ true }
				color="error"
			/>
			{ typeof onRetry === 'function' && (
				<div className="flex mt-2">
					<Button
						size="md"
						variant="primary"
						onClick={ onRetry }
						disabled={ isDisabled }
					>
						{ __( 'Try Again', 'surerank' ) }
					</Button>
				</div>
			) }
		</div>
	);
};

export default MigrationError;
