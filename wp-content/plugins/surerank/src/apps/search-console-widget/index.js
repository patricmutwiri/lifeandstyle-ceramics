import { mountComponent } from '@Functions/utils';
import { WidgetProvider } from './context/widget-context';
import Widget from './widget';
import './style.scss';

/**
 * App component with context provider
 *
 * @return {JSX.Element} App component
 */
const App = () => {
	return (
		<WidgetProvider>
			<div className="[&_*]:box-border [*_::after]:box-border [*_::before]:box-border space-y-3">
				<Widget />
			</div>
		</WidgetProvider>
	);
};

// Mount the widget to the DOM
mountComponent( '#surerank-widget-root', <App /> );
