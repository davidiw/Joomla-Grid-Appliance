<?php
// no direct access
defined('_JEXEC') or die('Restricted access');

require_once(JPATH_COMPONENT.DS.'controller.php');
$controller = new GroupAppliancesController();

$user =& JFactory::getUser();
if($user->guest) {
  $return = base64_encode("index.php?option=com_groupappliances");
  $login = "index.php?option=com_user&view=login";
  global $mainframe;
  $mainframe->redirect($login."&return=".$return);
}

$controller->registerTask('join', 'joinGroup');
$controller->registerTask('leave', 'leaveGroup');
$controller->registerTask('manage', 'manageGroup');
$controller->registerTask('delete', 'deleteGroup');
$controller->registerTask('create', 'createGroup');
$controller->registerTask('downloadFloppy', 'downloadFloppy');

$controller->registerDefaultTask('viewHandler');

$controller->execute(JRequest::getVar('task'));
$controller->redirect();
