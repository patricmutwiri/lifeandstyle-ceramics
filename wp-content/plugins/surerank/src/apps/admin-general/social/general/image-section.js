import { useCallback, memo } from '@wordpress/element';
import { Container, Label, Input, Text } from '@bsf/force-ui';
import { Image } from 'lucide-react'; // Import Trash icon
import { __ } from '@wordpress/i18n';
import { useDispatch, useSuspenseSelect } from '@wordpress/data';
import { STORE_NAME } from '@AdminStore/constants';
import { cn } from '@Functions/utils';
import { InfoTooltip } from '@AdminComponents/tooltip';
import MediaPreview from '@/apps/admin-components/media-preview';
import { createMediaFrame } from '@/global/utils/utils';

const ImageTab = memo( () => {
	const { setMetaSettings } = useDispatch( STORE_NAME );
	const settings = useSuspenseSelect( ( select ) => {
		const { getMetaSettings } = select( STORE_NAME );
		return getMetaSettings();
	}, [] );

	const openMediaLibrary = useCallback(
		( e ) => {
			e.preventDefault();
			const frame = createMediaFrame( {
				title: __( 'Select Default Image', 'surerank' ),
				button: {
					text: __( 'Set Default Image', 'surerank' ),
				},
				multiple: false,
			} );

			frame.on( 'select', () => {
				const attachment = frame
					.state()
					.get( 'selection' )
					.first()
					.toJSON();
				setMetaSettings( {
					fallback_image: attachment.url,
					fallback_image_id: attachment.id,
				} );
			} );

			frame.open();
		},
		[ setMetaSettings ]
	);

	const removeImage = useCallback( () => {
		setMetaSettings( {
			fallback_image: '',
			fallback_image_id: '',
		} );
	}, [ setMetaSettings ] );

	const imagePreview = settings.fallback_image;

	return (
		<Container direction="column" className="w-full">
			<Container.Item className="md:w-full lg:w-full">
				<div className="flex flex-row items-center justify-between w-full">
					<div className="flex flex-col gap-1.5 size-full">
						<div className="flex items-center justify-start gap-1">
							<Label
								htmlFor="fallbackImage"
								size="sm"
								className="text-sm font-medium text-field-label"
								variant="neutral"
							>
								{ __( 'Default Image', 'surerank' ) }
							</Label>
							<InfoTooltip
								content={ __(
									'Set a default image that will be used for social sharing when no featured or social-specific image is available. This ensures your content always has a visual when shared on platforms like Facebook or X (Twitter).',
									'surerank'
								) }
							/>
						</div>
						<Input
							type="file"
							size="md"
							onClick={ openMediaLibrary }
						/>
						<Text color="help">
							{ __(
								'Recommended size: 1200 x 630 px. Use JPG or PNG format for best results.',
								'surerank'
							) }{ ' ' }
							<Text
								as="a"
								href="https://surerank.com/docs/general-settings/"
								target="_blank"
								rel="noopener noreferrer"
								color="help"
							>
								{ __( 'Learn more', 'surerank' ) }
							</Text>
						</Text>
						<MediaPreview
							imageUrl={ settings.fallback_image }
							onRemove={ removeImage }
						/>
					</div>
				</div>
			</Container.Item>
			<Container.Item className="md:w-full lg:w-full">
				<div className="flex flex-row items-center justify-between w-full">
					<div className="flex flex-col gap-2 size-full">
						<Label size="sm" variant="label">
							{ __( 'Preview', 'surerank' ) }
						</Label>
						<div
							className={ cn(
								'relative flex items-center justify-center bg-field-primary-background rounded-lg w-full',
								imagePreview ? 'h-auto' : 'h-[280px]'
							) }
						>
							{ imagePreview ? (
								<div className="relative w-full h-full">
									<img
										src={ imagePreview }
										alt={ __( 'Fallback', 'surerank' ) }
										className="object-cover rounded-lg max-h-[280px] w-full h-auto mx-auto"
									/>
								</div>
							) : (
								<div className="[&>*]:text-icon-secondary [&>*:svg]:h-8">
									<Image strokeWidth={ 1 } size={ 32 } />
								</div>
							) }
						</div>
					</div>
				</div>
			</Container.Item>
		</Container>
	);
} );

export default ImageTab;
