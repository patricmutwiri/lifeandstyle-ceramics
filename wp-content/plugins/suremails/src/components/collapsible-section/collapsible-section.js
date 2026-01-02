import { createContext, useContext, useState } from '@wordpress/element';
import { ChevronUp } from 'lucide-react';
import { cn } from '@utils/utils';

const CollapsibleSectionContext = createContext( {} );
const useCollapsibleSectionState = () =>
	useContext( CollapsibleSectionContext );

const CollapsibleSection = ( {
	children,
	className,
	defaultOpen = false,
	alwaysOpen = false,
} ) => {
	const [ isOpen, setIsOpen ] = useState( alwaysOpen || defaultOpen );

	const toggle = () => ! alwaysOpen && setIsOpen( ! isOpen );
	return (
		<CollapsibleSectionContext.Provider
			value={ { isOpen, toggle, alwaysOpen } }
		>
			<div
				className={ cn(
					'p-4 m-1 space-y-2 border rounded-md shadow-sm bg-background-primary',
					className
				) }
			>
				{ children }
			</div>
		</CollapsibleSectionContext.Provider>
	);
};

const Trigger = ( { children, className } ) => {
	const { isOpen, toggle, alwaysOpen } = useCollapsibleSectionState();

	const renderIcons = ( content ) => {
		if ( alwaysOpen ) {
			return false;
		}
		return content;
	};

	return (
		<div
			className="flex items-center justify-between cursor-pointer"
			onClick={ toggle }
		>
			{ /* Content Title */ }
			<div className={ cn( 'flex items-center gap-1', className ) }>
				{ children }
			</div>
			{ renderIcons(
				<ChevronUp
					className={ cn( 'size-5', isOpen ? '' : 'rotate-180' ) }
				/>
			) }
		</div>
	);
};

const Content = ( { children, className } ) => {
	const { isOpen } = useCollapsibleSectionState();

	if ( ! isOpen || ! children ) {
		return null;
	}

	return <div className={ cn( className ) }>{ children }</div>;
};

CollapsibleSection.Trigger = Trigger;
CollapsibleSection.Content = Content;

export default CollapsibleSection;
