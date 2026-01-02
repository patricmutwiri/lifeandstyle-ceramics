import { Component } from '@wordpress/element';

class ErrorBoundary extends Component {
	constructor( props ) {
		super( props );
		this.state = { hasError: false, error: null };
	}

	static getDerivedStateFromError( error ) {
		return { hasError: true, error };
	}

	render() {
		if ( this.state.hasError ) {
			return (
				<div className="text-base font-medium text-text-error p-4 border border-solid border-border-error rounded-lg">
					Error: { this.state.error.message || 'Unknown error' }
				</div>
			);
		}

		return this.props.children;
	}
}

export default ErrorBoundary;
