import { Container, Text } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';
import { cn } from '@Functions/utils';

/**
 * Empty State Component
 * @param {Object}                    props           Component props
 * @param {import('react').ReactNode} props.message   Optional message to display in the empty state
 * @param {import('react').ReactNode} [props.title]   Optional title for the empty state
 * @param {import('react').ReactNode} [props.icon]    Optional icon to display in the empty state
 * @param {string}                    props.className Optional additional class names for styling
 * @return {JSX.Element} Empty State component
 */
const EmptyState = ( { message = '', title = '', icon = null, className } ) => {
	if ( title && icon && message ) {
		return (
			<div className={ cn( 'p-2 rounded-lg bg-background-secondary', className ) }>
				<div className="flex flex-col items-center text-center gap-2 p-4 bg-background-primary shadow-sm rounded-md">
					{ icon }
					<div className="space-y-1">
						{ title && (
							<Text as="h5" size={ 18 } weight={ 600 } color="primary">
								{ title }
							</Text>
						) }
						<Text size={ 14 } weight={ 400 } color="secondary">
							{ message ||
									__( 'No data available', 'surerank' ) }
						</Text>
					</div>
				</div>
			</div>
		);
	}
	return <Container
		align="center"
		justify="center"
		className={ cn(
			'p-8 bg-background-primary border border-solid border-border-subtle rounded-lg',
			className
		) }
	>
		<Text size={ 14 } weight={ 400 } color="secondary">
			{ message || __( 'No data available', 'surerank' ) }
		</Text>
	</Container>;
};

export default EmptyState;
