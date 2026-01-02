// NoFilteredLogs.js
import { Table } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';

const NoFilteredLogs = () => {
	return (
		<div className="bg-background-primary">
			<div className="p-6">
				{ /* Message indicating no logs match the filters */ }
				{ /* Table with No Logs Message */ }
				<Table>
					<Table.Head>
						<Table.HeadCell className="px-5.5">
							{ /* Empty checkbox */ }
						</Table.HeadCell>
						<Table.HeadCell className="w-1/4">
							{ __( 'Subject', 'suremails' ) }
						</Table.HeadCell>
						<Table.HeadCell className="w-1/8">
							{ __( 'Status', 'suremails' ) }
						</Table.HeadCell>
						<Table.HeadCell className="w-1/4">
							{ __( 'Email To', 'suremails' ) }
						</Table.HeadCell>
						<Table.HeadCell className="w-1/6">
							{ __( 'Date & Time', 'suremails' ) }
						</Table.HeadCell>
						<Table.HeadCell>
							{ __( 'Actions', 'suremails' ) }
						</Table.HeadCell>
					</Table.Head>
					<Table.Body>
						<Table.Row>
							<Table.Cell
								colSpan="6"
								className="px-4 py-4 text-center text-gray-500 border border-border-subtle"
							>
								{ __(
									'No Logs Available for the selected filters',
									'suremails'
								) }
							</Table.Cell>
						</Table.Row>
					</Table.Body>
				</Table>
			</div>
		</div>
	);
};

export default NoFilteredLogs;
