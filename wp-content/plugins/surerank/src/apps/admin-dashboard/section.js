import { Container } from '@bsf/force-ui';
import { cn } from '@/functions/utils';

const Section = ( { children, className, ...props } ) => {
	return (
		<Container
			direction="column"
			gap="none"
			className={ cn(
				'p-4 border-0.5 border-solid border-border-subtle rounded-xl bg-background-primary space-y-2',
				className
			) }
			{ ...props }
		>
			{ children }
		</Container>
	);
};

export default Section;
