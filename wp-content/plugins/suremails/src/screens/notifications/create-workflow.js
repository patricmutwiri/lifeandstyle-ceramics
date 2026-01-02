import React from 'react';
import { Button, Input, toast } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';

const CreateWorkflow = () => {
	const handleCreate = () => {
		// Implement your workflow creation logic here
		toast.success( __( 'Workflow created successfully!', 'suremails' ) );
	};

	return (
		<div className="p-6 shadow-sm bg-background-primary rounded-xl">
			<h2 className="text-2xl font-semibold text-text-primary">
				{ __( 'Create a New Workflow', 'suremails' ) }
			</h2>
			<div className="mt-4">
				<Input
					label={ __( 'Workflow Name', 'suremails' ) }
					placeholder={ __( 'Enter workflow name', 'suremails' ) }
					className="mb-4"
				/>
				{ /* Add more form fields as necessary */ }
				<Button variant="primary" onClick={ handleCreate }>
					{ __( 'Create Workflow', 'suremails' ) }
				</Button>
			</div>
		</div>
	);
};

export default CreateWorkflow;
