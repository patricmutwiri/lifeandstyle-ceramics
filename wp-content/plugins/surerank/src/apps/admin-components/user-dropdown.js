import apiFetch from '@wordpress/api-fetch';
import { Avatar, DropdownMenu, toast } from '@bsf/force-ui';
import { Globe, LogOut, User } from 'lucide-react';
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import { STORE_NAME } from '@/admin-store/constants';

const UserDropdown = () => {
	const searchConsole = useSelect( ( select ) =>
		select( STORE_NAME ).getSearchConsole()
	);
	const { toggleSiteSelectorModal, setConfirmationModal } =
		useDispatch( STORE_NAME );

	const handleChangeSite = () => {
		toggleSiteSelectorModal();
	};

	const handleDisconnect = () => {
		setConfirmationModal( {
			open: true,
			title: __( 'Disconnect Search Console Account', 'surerank' ),
			description: __(
				'Are you sure you want to disconnect your Search Console account from SureRank?',
				'surerank'
			),
			onConfirm: handleDisconnectConfirm,
			confirmButtonText: __( 'Disconnect', 'surerank' ),
		} );
	};

	const options = [
		{
			label: __( 'Change Site', 'surerank' ),
			icon: Globe,
			onClick: handleChangeSite,
		},
		{
			label: __( 'Disconnect', 'surerank' ),
			icon: LogOut,
			onClick: handleDisconnect,
		},
	];

	if ( ! searchConsole.authenticated ) {
		return null;
	}

	return (
		<DropdownMenu placement="bottom-start">
			<DropdownMenu.Trigger className="cursor-pointer">
				<Avatar variant="primary-light" size="xs">
					<User />
				</Avatar>
			</DropdownMenu.Trigger>
			<DropdownMenu.ContentWrapper>
				<DropdownMenu.Content className="w-60 [&>div]:p-1.5">
					<DropdownMenu.List className="p-0">
						<div className="p-1 flex gap-2 items-center">
							<Avatar
								src={ searchConsole.profile.gravatar }
								className="rounded-full shrink-0"
							/>
							<div className="max-w-[78%]">
								<p className="text-base font-medium text-text-primary truncate">
									{ searchConsole.profile.name }
								</p>
								<p className="text-xs font-normal text-text-tertiary truncate">
									{ searchConsole.profile.email }
								</p>
							</div>
						</div>
						<DropdownMenu.Separator className="w-[calc(100%+1rem)] -translate-x-2 mb-0 my-1" />
						{ options.map( ( option, index ) => (
							<DropdownMenu.Item
								className="p-1"
								key={ index }
								onClick={ option.onClick }
							>
								<option.icon className="size-5" />
								<span>{ option.label }</span>
							</DropdownMenu.Item>
						) ) }
					</DropdownMenu.List>
				</DropdownMenu.Content>
			</DropdownMenu.ContentWrapper>
		</DropdownMenu>
	);
};

export const handleDisconnectConfirm = async () => {
	try {
		const response = await apiFetch( {
			path: '/surerank/v1/google-search-console/revoke-auth',
			method: 'DELETE',
		} );
		if ( ! response.success ) {
			throw new Error(
				response?.message ??
					__(
						'Failed to disconnect your account. Please try again.',
						'surerank'
					)
			);
		}
		toast.success(
			__(
				'Your account has been disconnected. The page will refresh in a few seconds.',
				'surerank'
			)
		);
		// Refresh the page after 3 seconds
		setTimeout( () => {
			window.location.reload();
		}, 3000 );
	} catch ( error ) {
		toast.error( error.message );
	}
};

export default UserDropdown;
