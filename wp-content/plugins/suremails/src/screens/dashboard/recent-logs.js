import { Label, Badge, Button, Table } from '@bsf/force-ui';
import { useNavigate } from 'react-router-dom';
import Title from '@components/title/title';
import { __ } from '@wordpress/i18n';
import { ArrowUpRight, Mails, Plus } from 'lucide-react';
import { formatDate, getStatusLabel, getStatusVariant } from '@utils/utils';
const RecentLogs = ( { recentLogs, hasConnections = true } ) => {
	const navigate = useNavigate();

	// Helper function to extract emails using regex
	const extractEmails = ( str ) => {
		if ( typeof str !== 'string' ) {
			return [];
		}
		// Regular expression to match email addresses
		const emailRegex = /[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/gi;
		const matches = str.match( emailRegex );
		return matches || [];
	};

	// Helper functions: truncateText, formatEmailTo
	const truncateText = ( text, maxLength = 30 ) =>
		text?.length > maxLength
			? `${ text.substring( 0, maxLength ) }...`
			: text || '';

	const formatEmailTo = ( email_to ) => {
		const emails = extractEmails( email_to );

		if ( ! emails.length ) {
			return '';
		}

		const visibleEmails = emails
			.slice( 0, 2 )
			.map( ( email ) => truncateText( email ) );
		const remainingEmails = emails.length - visibleEmails.length;

		return remainingEmails > 0
			? `${ visibleEmails.join( ', ' ) }... (${ remainingEmails } more)`
			: visibleEmails.join( ', ' );
	};

	return (
		<>
			{ /* Header Section with Title and "Vieww All" Button */ }
			<div className="flex items-center justify-between p-1">
				<Title
					title={ __( 'Recent Email Logs', 'suremails' ) }
					tag="h3"
					size="xs"
				/>
				{ recentLogs.length > 0 && (
					<Button
						variant="ghost"
						icon={ <ArrowUpRight /> }
						iconPosition="right"
						size="sm"
						type="button"
						onClick={ () => navigate( '/logs' ) }
						className="text-xs font-medium"
					>
						{ __( 'View all', 'suremails' ) }
					</Button>
				) }
			</div>

			{ /* Table Section */ }
			<div className="overflow-hidden">
				<Table className="bg-background-primary">
					{ /* Table Header */ }
					<Table.Head className="bg-background-secondary">
						<Table.HeadCell className="px-3 py-2 text-sm font-medium text-left text-text-secondary">
							{ __( 'Email To', 'suremails' ) }
						</Table.HeadCell>
						<Table.HeadCell className="px-3 py-2 text-sm font-medium text-left text-text-secondary">
							{ __( 'Status', 'suremails' ) }
						</Table.HeadCell>
						<Table.HeadCell className="px-3 py-2 text-sm font-medium text-left text-text-secondary">
							{ __( 'Subject', 'suremails' ) }
						</Table.HeadCell>
						<Table.HeadCell className="px-3 py-2 text-sm font-medium text-left text-text-secondary">
							{ __( 'Date', 'suremails' ) }
						</Table.HeadCell>
					</Table.Head>

					{ /* Table Body */ }
					<Table.Body>
						{ recentLogs.length === 0 ? (
							<Table.Row className="bg-background-primary">
								<Table.Cell
									colSpan="4"
									className="py-10 bg-background-primary"
								>
									<div className="flex flex-col items-center justify-center h-full gap-3 bg-background-primary">
										<div className="flex flex-col items-center justify-center w-[29.375rem]">
											<Mails className="mb-3" />
											<div className="flex flex-col items-center space-y-1">
												<Label
													tag="p"
													className="text-sm font-medium text-center text-text-primary"
												>
													{ __(
														'No Email Logs Available',
														'suremails'
													) }
												</Label>
												<Label
													tag="p"
													className="text-sm font-normal text-center text-text-secondary"
												>
													{ __(
														'Once your emails start sending, you’ll see detailed stats here to help you monitor and manage your email activity.',
														'suremails'
													) }
												</Label>
											</div>
										</div>
										<div>
											{ ! hasConnections && (
												<Button
													variant="primary"
													size="sm"
													icon={ <Plus /> }
													iconPosition="left"
													onClick={ () =>
														navigate(
															'/connections',
															{
																state: {
																	openDrawer: true,
																},
															}
														)
													}
													className="font-medium"
												>
													{ __(
														'Add Connection',
														'suremails'
													) }
												</Button>
											) }
										</div>
									</div>
								</Table.Cell>
							</Table.Row>
						) : (
							recentLogs.map( ( log ) => (
								<Table.Row
									key={ log.id }
									className="border-b border-border-subtle"
								>
									<Table.Cell className="px-3 py-3 text-sm font-normal text-text-secondary">
										{ formatEmailTo( log.email_to ) }
									</Table.Cell>
									<Table.Cell className="px-3 py-3 text-text-secondary">
										<Badge
											className="inline-block"
											label={ getStatusLabel(
												log.status,
												log?.response
											) }
											variant={ getStatusVariant(
												log.status,
												log?.response
											) }
											size="sm"
											type="pill"
										/>
									</Table.Cell>
									<Table.Cell className="px-3 py-3 text-sm font-normal text-text-secondary">
										{ truncateText( log.subject, 30 ) }
									</Table.Cell>
									<Table.Cell className="px-3 py-3 text-sm font-normal text-text-secondary">
										{ formatDate( log.updated_at, {
											day: true,
											month: true,
										} ) }
									</Table.Cell>
								</Table.Row>
							) )
						) }
					</Table.Body>
				</Table>
			</div>
		</>
	);
};

export default RecentLogs;
