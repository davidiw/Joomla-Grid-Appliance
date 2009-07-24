<?php
// no direct access
defined('_JEXEC') or die('Restricted access');

require_once(JPATH_COMPONENT.DS.'controller.php');
$controller = new P2PPoolController();

$controller->registerTask('create', 'create');
$controller->registerTask('stop', 'stop');
$controller->registerTask('upgrade', 'upgrade');
$controller->registerTask('cron', 'checkTasks');
$controller->registerTask('adminAction', 'adminAction');
$controller->registerTask('uninstall', 'uninstall');
$controller->registerDefaultTask('viewHandler');

$controller->execute(JRequest::getVar('task'));
$controller->redirect();
