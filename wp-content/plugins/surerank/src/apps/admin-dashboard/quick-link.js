import { Container, Label, Badge } from '@bsf/force-ui';
import { cn } from '@Functions/utils';

export const QuickLink = ( { link } ) => (
	<Container.Item
		className={ cn(
			'md:w-full lg:w-full flex cursor-pointer hover:shadow-md',
			link?.disabled && 'cursor-not-allowed'
		) }
		onClick={ () => {
			if ( ! link?.disabled && link?.link ) {
				window.open(
					surerank_globals.wp_dashboard_url +
						'?page=surerank_settings#' +
						link.link,
					'_self'
				);
			}
		} }
	>
		<Container
			className={ cn(
				'flex-1 gap-1 shadow-sm p-2 rounded-md bg-background-primary',
				link?.disabled && 'pointer-events-none shadow-none'
			) }
			containerType="flex"
			direction="column"
		>
			<Container.Item
				className={ cn(
					'inline-flex p-1 text-icon-primary',
					link?.disabled &&
						'text-icon-on-color-disabled justify-between'
				) }
			>
				<link.icon className="size-5" />

				{ link.badge && (
					<Badge size="xss" variant="green" label={ link.badge } />
				) }
			</Container.Item>
			<Container.Item className="space-y-0.5 p-1">
				<Label
					className={ cn(
						'text-sm font-medium cursor-pointer',
						link?.disabled && 'text-text-tertiary'
					) }
				>
					{ link.label }
				</Label>
				<Label className="text-sm font-normal text-text-tertiary cursor-pointer">
					{ link.description }
				</Label>
			</Container.Item>
		</Container>
	</Container.Item>
);
