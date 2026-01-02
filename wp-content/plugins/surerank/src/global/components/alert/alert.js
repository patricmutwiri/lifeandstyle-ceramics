import { Text } from '@bsf/force-ui';
import { AlertTriangleIcon, Check, InfoIcon } from 'lucide-react';
import { cn } from '@/functions/utils';

/**
 * Alert component displays a success message with title and description
 * Based on the Figma design: Status=Success, High contrast=False, Type=Inline long
 *
 * @param {Object}                             props            - Component props
 * @param {string}                             props.title      - The title/heading of the alert
 * @param {string}                             props.message    - The message/description content
 * @param {boolean}                            props.showIcon   - Whether to show the success icon (default: false)
 * @param {string}                             props.className  - Additional CSS classes
 * @param {'success'|'error'|'warning'|'info'} props.color      - Color variant for the alert (default: 'primary')
 * @param {JSX.Element}                        props.footer     - Footer content
 * @param {JSX.Element}                        props.renderIcon - Custom icon element
 * @param {JSX.Element}                        props.action     - Action element
 * @return {JSX.Element} Alert component
 */
const Alert = ( {
	title = '',
	message = '',
	showIcon = false,
	className = '',
	color = 'success',
	footer = null,
	renderIcon = null,
	action = null,
	...props
} ) => {
	const borderColorClass =
		{
			success: 'border-alert-border-green',
			error: 'border-alert-border-danger',
			warning: 'border-alert-border-warning',
			info: 'border-alert-border-info',
		}[ color ] || 'border-alert-border-green';

	const backgroundColorClass =
		{
			success: 'bg-alert-background-green',
			error: 'bg-alert-background-danger',
			warning: 'bg-alert-background-warning',
			info: 'bg-alert-background-info',
		}[ color ] || 'bg-alert-background-green';

	const iconColorClass =
		{
			success: 'text-support-success',
			error: 'text-support-error',
			warning: 'text-support-warning',
			info: 'text-support-info',
		}[ color ] || 'text-support-success';

	const Icon =
		{
			success: Check,
			error: AlertTriangleIcon,
			warning: AlertTriangleIcon,
			info: InfoIcon,
		}[ color ] || Check;

	return (
		<div
			className={ cn(
				'flex flex-row self-stretch gap-2 p-3 border border-solid rounded-lg',
				borderColorClass,
				backgroundColorClass,
				className
			) }
			role="alert"
			{ ...props }
		>
			{ !! renderIcon && renderIcon }
			{ showIcon && ! renderIcon && (
				<Icon
					className={ cn( iconColorClass, 'shrink-0' ) }
					size={ 20 }
				/>
			) }
			<div className="flex flex-col gap-3 pl-1 pr-5 py-0 flex-1">
				<div className="flex flex-col justify-center self-stretch gap-0.5">
					{ title && (
						<Text
							size={ 14 }
							weight={ 600 }
							color="primary"
							className="leading-[1.43]"
						>
							{ title }
						</Text>
					) }
					{ message && (
						<Text
							size={ 14 }
							weight={ 400 }
							color="primary"
							className="self-stretch"
						>
							{ message }
						</Text>
					) }
				</div>
				{ footer }
			</div>
			{ action }
		</div>
	);
};

export default Alert;
