<?php  
require_once($_SERVER['DOCUMENT_ROOT'].'/classes/common.php');
load_common_include_files();
require_once($_SERVER['DOCUMENT_ROOT'].'/priv/imagearchive/include.inc.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/classes/Article.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/classes/Image.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/classes/Log.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/priv/CampsiteInterface.php');

list($access, $User) = check_basic_access($_REQUEST);
if (!$access) {
	header('Location: /priv/logout.php');
	exit;
}

// check input
$ImageId = isset($_REQUEST['image_id'])?$_REQUEST['image_id']:0;
if (!is_numeric($ImageId) || ($ImageId <= 0)) {
	header('Location: '.CAMPSITE_IMAGEARCHIVE_DIR.'index.php?'.Image_GetSearchUrl($_REQUEST));
	exit;	
}

// Check input
if (!isset($_REQUEST['cDescription']) || !isset($_REQUEST['cPhotographer'])
	|| !isset($_REQUEST['cPlace']) || !isset($_REQUEST['cDate'])) {
	header('Location: '.CAMPSITE_IMAGEARCHIVE_DIR.'index.php?'.Image_GetSearchUrl($_REQUEST));
	exit;		
}

$imageObj =& new Image($ImageId);

// This file can only be accessed if the user has the right to delete images.
if (!$User->hasPermission('ChangeImage')) {
	header('Location: /priv/logout.php');
	exit;		
}

$updateArray = array('Description' => $_REQUEST['cDescription'],
					'Photographer' => $_REQUEST['cPhotographer'],
					'Place' => $_REQUEST['cPlace'],
					'Date' => $_REQUEST['cDate']);
if (isset($_REQUEST['cURL'])) {
	$updateArray['URL'] = $_REQUEST['cURL'];
}
$imageObj->update($updateArray);

$logtext = getGS('Changed image properties of $1', $imageObj->getImageId()); 
Log::Message($logtext, $User->getUserName(), 43);

// Go back to article image list.
header('Location: '.CAMPSITE_IMAGEARCHIVE_DIR.'index.php?'.Image_GetSearchUrl($_REQUEST));
exit;
?>