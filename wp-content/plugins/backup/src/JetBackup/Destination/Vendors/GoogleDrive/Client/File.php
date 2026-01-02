<?php
/*
*
* JetBackup @ package
* Created By Idan Ben-Ezra
*
* Copyrights @ JetApps
* https://www.jetapps.com
*
**/
namespace JetBackup\Destination\Vendors\GoogleDrive\Client;

defined( '__JETBACKUP__' ) or die( 'Restricted access' );

/*
[kind] => drive#file
[fileExtension] => 
[copyRequiresWriterPermission] => 
[md5Checksum] => e18a1f2b838b67701aa407714d68ac23
[writersCanShare] => 1
[viewedByMe] => 
[mimeType] => application/json
[parents] => Array
    (
        [0] => 15gD8cvTKHP76PzixColW52B8c9bsDSLT
    )

[iconLink] => https://drive-thirdparty.googleusercontent.com/16/type/application/json
[shared] => 
[lastModifyingUser] => stdClass Object
    (
        [displayName] => Idan Ben-Ezra
        [kind] => drive#user
        [me] => 1
        [permissionId] => 06770184627288947036
        [emailAddress] => idan@smartix.co.il
        [photoLink] => https://lh3.googleusercontent.com/a/ACg8ocIHgfURDqMvzCRyKNcpkHBJ0FliQDu1Ky5ebpwbvGd4Lt609rw=s64
    )

[owners] => Array
    (
        [0] => stdClass Object
            (
                [displayName] => Idan Ben-Ezra
                [kind] => drive#user
                [me] => 1
                [permissionId] => 06770184627288947036
                [emailAddress] => idan@smartix.co.il
                [photoLink] => https://lh3.googleusercontent.com/a/ACg8ocIHgfURDqMvzCRyKNcpkHBJ0FliQDu1Ky5ebpwbvGd4Lt609rw=s64
            )

    )

[headRevisionId] => 0B9RKqTSQa-PwS3pqMnBsZjhMc0pPek01YmdKUjVxQ1ZxNFFVPQ
[webViewLink] => https://drive.google.com/file/d/1kx7fUourLWcXoA4uLY7iuZOHDFQ9U653/view?usp=drivesdk
[webContentLink] => https://drive.google.com/uc?id=1kx7fUourLWcXoA4uLY7iuZOHDFQ9U653&export=download
[size] => 91
[viewersCanCopyContent] => 1
[permissions] => Array
    (
        [0] => stdClass Object
            (
                [id] => 06770184627288947036
                [displayName] => Idan Ben-Ezra
                [type] => user
                [kind] => drive#permission
                [photoLink] => https://lh3.googleusercontent.com/a/ACg8ocIHgfURDqMvzCRyKNcpkHBJ0FliQDu1Ky5ebpwbvGd4Lt609rw=s64
                [emailAddress] => idan@smartix.co.il
                [role] => owner
                [deleted] => 
                [pendingOwner] => 
            )

    )

[hasThumbnail] => 
[spaces] => Array
    (
        [0] => drive
    )

[id] => 1kx7fUourLWcXoA4uLY7iuZOHDFQ9U653
[name] => .jetbackup
[starred] => 
[trashed] => 
[explicitlyTrashed] => 
[createdTime] => 2024-08-13T23:03:03.761Z
[modifiedTime] => 2024-08-13T23:03:03.761Z
[modifiedByMeTime] => 2024-08-13T23:03:03.761Z
[quotaBytesUsed] => 91
[version] => 2
[originalFilename] => .jetbackup
[ownedByMe] => 1
[fullFileExtension] => 
[isAppAuthorized] => 1
[capabilities] => stdClass Object
    (
        [canChangeViewersCanCopyContent] => 1
        [canEdit] => 1
        [canCopy] => 1
        [canComment] => 1
        [canAddChildren] => 
        [canDelete] => 1
        [canDownload] => 1
        [canListChildren] => 
        [canRemoveChildren] => 
        [canRename] => 1
        [canTrash] => 1
        [canReadRevisions] => 1
        [canChangeCopyRequiresWriterPermission] => 1
        [canMoveItemIntoTeamDrive] => 1
        [canUntrash] => 1
        [canModifyContent] => 1
        [canMoveItemOutOfDrive] => 1
        [canAddMyDriveParent] => 
        [canRemoveMyDriveParent] => 1
        [canMoveItemWithinDrive] => 1
        [canShare] => 1
        [canMoveChildrenWithinDrive] => 
        [canModifyContentRestriction] => 1
        [canChangeSecurityUpdateEnabled] => 
        [canAcceptOwnership] => 
        [canReadLabels] => 
        [canModifyLabels] => 
        [canModifyEditorContentRestriction] => 1
        [canModifyOwnerContentRestriction] => 1
        [canRemoveContentRestriction] => 
    )

[thumbnailVersion] => 0
[modifiedByMe] => 1
[permissionIds] => Array
    (
        [0] => 06770184627288947036
    )

[linkShareMetadata] => stdClass Object
    (
        [securityUpdateEligible] => 
        [securityUpdateEnabled] => 1
    )

[sha1Checksum] => 9561293e73ccf5c5d5f18089c68594730b50c257
[sha256Checksum] => 4324ac6b7837c138c23e3d42346494a7a704bbe1be79e64b64dc02bfcdb76102
*/

class File {
	
	private string $_id='';
	private string $_name='';
	private int $_size=0;
	private string $_mimeType='';
	private int $_mtime=0;
	private int $_ctime=0;
	private string $_md5Checksum='';
	private string $_sha1Checksum='';
	private string $_sha256Checksum='';

	public function setId(string $id):void { $this->_id = $id; }
	public function getId():string { return $this->_id; }

	public function setName(string $name):void { $this->_name = $name; }
	public function getName():string { return $this->_name; }

	public function setSize(int $size):void { $this->_size = $size; }
	public function getSize():int { return $this->_size; }

	public function setMimeType(string $type):void { $this->_mimeType = $type; }
	public function getMimeType():string { return $this->_mimeType; }

	public function setModificationTime(int $time):void { $this->_mtime = $time; }
	public function getModificationTime():int { return $this->_mtime; }

	public function setCreationTime(int $time):void { $this->_ctime = $time; }
	public function getCreationTime():int { return $this->_ctime; }

	public function setMD5Checksum(string $checksum):void { $this->_md5Checksum = $checksum; }
	public function getMD5Checksum():string { return $this->_md5Checksum; }

	public function setSHA1Checksum(string $checksum):void { $this->_sha1Checksum = $checksum; }
	public function getSHA1Checksum():string { return $this->_sha1Checksum; }

	public function setSHA256Checksum(string $checksum):void { $this->_sha256Checksum = $checksum; }
	public function getSHA256Checksum():string { return $this->_sha256Checksum; }
}