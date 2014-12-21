<?php
/**
 * phpwcms content management system
 *
 * @author Oliver Georgi <oliver@phpwcms.de>
 * @copyright Copyright (c) 2002-2014, Oliver Georgi
 * @license http://opensource.org/licenses/GPL-2.0 GNU GPL-2
 * @link http://www.phpwcms.de
 *
 **/

// set page processiong start time
list($usec, $sec) = explode(' ', microtime());
$phpwcms_rendering_start = $usec + $sec;

session_start();

//define used var names
$body_onload = '';
$forward_to_message_center = false;
$indexpage = array();
$phpwcms = array();
$BL = array();
$BE = array(
	'HTML' => '',
	'BODY_OPEN' => array(),
	'BODY_CLOSE' => array(),
	'HEADER' => array(),
	'LANG' => 'en',
	'MAINNAV' => array()
);

// check against user's language
if(!empty($_SESSION["wcs_user_lang"]) && preg_match('/[a-z]{2}/i', $_SESSION["wcs_user_lang"])) {
	$BE['LANG'] = $_SESSION["wcs_user_lang"];
}

require_once 'config/phpwcms/conf.inc.php';
require_once 'include/inc_lib/default.inc.php';
require_once PHPWCMS_ROOT.'/include/inc_lib/dbcon.inc.php';
require_once PHPWCMS_ROOT.'/include/inc_lib/general.inc.php';
checkLogin();
require_once PHPWCMS_ROOT.'/include/inc_lib/backend.functions.inc.php';
require_once PHPWCMS_ROOT.'/include/inc_lib/default.backend.inc.php';

//load default language EN
require_once PHPWCMS_ROOT.'/include/inc_lang/backend/en/lang.inc.php';
include_once PHPWCMS_ROOT."/include/inc_lang/code.lang.inc.php";
$BL['modules']				= array();

if(!empty($_SESSION["wcs_user_lang_custom"])) {
	//use custom lang if available -> was set in login.php
	$BL['merge_lang_array'][0]		= $BL['be_admin_optgroup_label'];
	$BL['merge_lang_array'][1]		= $BL['be_cnt_field'];
	include PHPWCMS_ROOT.'/include/inc_lang/backend/'. $BE['LANG'] .'/lang.inc.php';
	$BL['be_admin_optgroup_label']	= array_merge($BL['merge_lang_array'][0], $BL['be_admin_optgroup_label']);
	$BL['be_cnt_field']				= array_merge($BL['merge_lang_array'][1], $BL['be_cnt_field']);
	unset($BL['merge_lang_array']);
}

require_once PHPWCMS_ROOT.'/include/inc_lib/checkmessage.inc.php';
require_once PHPWCMS_ROOT.'/config/phpwcms/conf.template_default.inc.php';
require_once PHPWCMS_ROOT.'/config/phpwcms/conf.indexpage.inc.php';
require_once PHPWCMS_ROOT.'/include/inc_lib/imagick.convert.inc.php';

// check modules
require_once PHPWCMS_ROOT.'/include/inc_lib/modules.check.inc.php';

// load array with actual content types
include PHPWCMS_ROOT.'/include/inc_lib/article.contenttype.inc.php';

$BL['be_admin_struct_index'] = html_specialchars($indexpage['acat_name']);


$subnav								= ''; //Sub Navigation
$p									= isset($_GET["p"])  ? intval($_GET["p"]) : 0; //which page should be opened
$do									= isset($_GET["do"]) ? clean_slweg($_GET["do"]) : 'default'; //which backend section and which $do action
$module								= isset($_GET['module'])  ? clean_slweg($_GET['module']) : ''; //which module
$phpwcms['be_parse_lang_process']	= false; // limit parsing for BBCode/BraceCode languages only to some sections


// Build navbar
// =================================

// Dashboard
$is_true = ($do === 'default') ? true : false;
$BE['MAINNAV'][] = '<li class="item-main' . ($is_true ? ' active' : '').'">';
$BE['MAINNAV'][] = '	<h4>' . $BL['be_nav_home'] . '</h4>';
$BE['MAINNAV'][] = '	<ul>';
$BE['MAINNAV'][] = phpwcms_subnav($BL['be_dashboard_overview'], 'phpwcms.php', $is_true);
$BE['MAINNAV'][] = '	</ul>';
$BE['MAINNAV'][] = '</li>';

// Content
$is_true = ($do === 'articles') ? true : false;
$BE['MAINNAV'][] = '<li class="item-main' . ($is_true ? ' active' : '').'">';
$BE['MAINNAV'][] = '	<h4>' . $BL['be_nav_articles'] . '</h4>';
$BE['MAINNAV'][] = '	<ul>';
$BE['MAINNAV'][] = phpwcms_subnav($BL['be_subnav_article_center'], 'phpwcms.php?do=articles', ($p === 0 && $is_true)); // 0
$BE['MAINNAV'][] = phpwcms_subnav($BL['be_subnav_article_new'], 'phpwcms.php?do=articles&amp;p=1&amp;struct=0', ($p === 1 && $is_true)); // 1
$BE['MAINNAV'][] = phpwcms_subnav($BL['be_news'], 'phpwcms.php?do=articles&amp;p=3', ($p === 3 && $is_true)); // 3
$BE['MAINNAV'][] = '	</ul>';
$BE['MAINNAV'][] = '</li>';

// Files
$is_true = ($do === 'files') ? true : false;
$BE['MAINNAV'][] = '<li class="item-main' . ($is_true ? ' active' : '').'">';
$BE['MAINNAV'][] = '	<h4>' . $BL['be_nav_files'] . '</h4>';
$BE['MAINNAV'][] = '	<ul>';
$BE['MAINNAV'][] = phpwcms_subnav($BL['be_subnav_file_center'], 'phpwcms.php?do=files', ($p === 0 && $is_true)); // 0
$BE['MAINNAV'][] = phpwcms_subnav($BL['be_subnav_file_actions'], 'phpwcms.php?do=files&amp;p=4', ($p === 4 && $is_true)); // 4
$BE['MAINNAV'][] = phpwcms_subnav($BL['be_file_multiple_upload'], 'phpwcms.php?do=files&amp;p=8', ($p === 8 && $is_true)); // 8
$BE['MAINNAV'][] = '	</ul>';
$BE['MAINNAV'][] = '</li>';

// Modules
$BE['MAINNAV'][] = '<li class="item-main' . ($do === 'modules' ? ' active' : '').'">';
$BE['MAINNAV'][] = '	<h4>' . $BL['be_nav_modules'] . '</h4>';
$BE['MAINNAV'][] = '	<ul>';
foreach($phpwcms['modules'] as $value) {
	if($value['type'] === 2) {
		continue;
	}
	$BE['MAINNAV'][] = phpwcms_subnav($BL['modules'][ $value['name'] ]['backend_menu'], 'phpwcms.php?do=modules&amp;module='.$value['name'], ($module === $value['name']));
}
$BE['MAINNAV'][] = '	</ul>';
$BE['MAINNAV'][] = '</li>';

// Messages
$is_true = ($do === 'messages') ? true : false;
$BE['MAINNAV'][] = '<li class="item-main' . ($is_true ? ' active' : '').'">';
$BE['MAINNAV'][] = '	<h4>' . $BL['be_nav_messages'] . '</h4>';
$BE['MAINNAV'][] = '	<ul>';
$BE['MAINNAV'][] = phpwcms_subnav($BL['be_subnav_msg_newslettersend'], 'phpwcms.php?do=messages&amp;p=3', ($p === 3 && $is_true)); // 3
$BE['MAINNAV'][] = phpwcms_subnav($BL['be_subnav_msg_subscribers'], 'phpwcms.php?do=messages&amp;p=4', ($p === 4 && $is_true)); // 4
$BE['MAINNAV'][] = phpwcms_subnav($BL['be_subnav_msg_newsletter'], 'phpwcms.php?do=messages&amp;p=2', ($p === 2 && $is_true)); // 2
if(!empty($phpwcms['enable_messages'])) {
	$BE['MAINNAV'][] = phpwcms_subnav($BL['be_subnav_msg_center'], 'phpwcms.php?do=messages', ($p === 0 && $is_true)); // 0
	$BE['MAINNAV'][] = phpwcms_subnav($BL['be_subnav_msg_new'], 'phpwcms.php?do=messages&amp;p=1', ($p === 1 && $is_true)); // 1
}
$BE['MAINNAV'][] = '	</ul>';
$BE['MAINNAV'][] = '</li>';

// Chat (deprecated)
if(!empty($phpwcms['enable_chat'])) {
	$is_true = ($do === 'chat') ? true : false;
	$BE['MAINNAV'][] = '<li class="item-main' . ($is_true ? ' active' : '').'">';
	$BE['MAINNAV'][] = '	<h4>' . $BL['be_nav_chat'] . '</h4>';
	$BE['MAINNAV'][] = '	<ul>';
	$BE['MAINNAV'][] = phpwcms_subnav($BL['be_subnav_chat_main'], 'phpwcms.php?do=chat', ($p === 0 && $is_true)); // 0
	$BE['MAINNAV'][] = phpwcms_subnav($BL['be_subnav_chat_internal'], 'phpwcms.php?do=chat&amp;p=1', ($p === 1 && $is_true)); // 1
	$BE['MAINNAV'][] = '	</ul>';
	$BE['MAINNAV'][] = '</li>';
}

// Profile
$is_true = ($do === 'profile') ? true : false;
$BE['MAINNAV'][] = '<li class="item-main' . ($is_true ? ' active' : '').'">';
$BE['MAINNAV'][] = '	<h4>' . $BL['be_nav_profile'] . '</h4>';
$BE['MAINNAV'][] = '	<ul>';
$BE['MAINNAV'][] = phpwcms_subnav($BL['be_subnav_profile_login'], 'phpwcms.php?do=profile', ($p === 0 && $is_true)); // 0
$BE['MAINNAV'][] = phpwcms_subnav($BL['be_subnav_profile_personal'], 'phpwcms.php?do=profile&amp;p=1', ($p === 1 && $is_true)); // 1
$BE['MAINNAV'][] = '	</ul>';
$BE['MAINNAV'][] = '</li>';

// Admin
if(!empty($_SESSION["wcs_user_admin"])) {
	$is_true = ($do === 'admin') ? true : false;
	$BE['MAINNAV'][] = '<li class="item-main' . ($is_true ? ' active' : '').'">';
	$BE['MAINNAV'][] = '	<h4>' . $BL['be_nav_admin'] . '</h4>';
	$BE['MAINNAV'][] = '	<ul>';
	$BE['MAINNAV'][] = phpwcms_subnav($BL['be_subnav_admin_sitestructure'], 'phpwcms.php?do=admin&amp;p=6', ($p === 6 && $is_true)); // 6
	$BE['MAINNAV'][] = phpwcms_subnav($BL['be_subnav_admin_pagelayout'], 'phpwcms.php?do=admin&amp;p=8', ($p === 8 && $is_true)); // 8
	$BE['MAINNAV'][] = phpwcms_subnav($BL['be_subnav_admin_templates'], 'phpwcms.php?do=admin&amp;p=11', ($p === 11 && $is_true)); // 11
	$BE['MAINNAV'][] = phpwcms_subnav($BL['be_subnav_admin_css'], 'phpwcms.php?do=admin&amp;p=10', ($p === 10 && $is_true)); // 10
	$BE['MAINNAV'][] = '	</ul>';
	$BE['MAINNAV'][] = '	<ul>';
	$BE['MAINNAV'][] = phpwcms_subnav($BL['be_subnav_admin_users'], 'phpwcms.php?do=admin', ($p === 0 && $is_true)); // 0
	if(!empty($phpwcms['usergroup_support'])) {
		$BE['MAINNAV'][] = phpwcms_subnav($BL['be_subnav_admin_groups'], 'phpwcms.php?do=admin&amp;p=1', ($p === 1 && $is_true)); // 1
	}
	$BE['MAINNAV'][] = '	</ul>';
	$BE['MAINNAV'][] = '	<ul>';
	//$BE['MAINNAV'][] = phpwcms_subnav($BL['be_admin_keywords'], 'phpwcms.php?do=admin&amp;p=5', ($p === 5 && $is_true)); // 5
	$BE['MAINNAV'][] = phpwcms_subnav($BL['be_subnav_admin_filecat'], 'phpwcms.php?do=admin&amp;p=7', ($p === 7 && $is_true)); // 7
	$BE['MAINNAV'][] = phpwcms_subnav($BL['be_subnav_admin_starttext'], 'phpwcms.php?do=admin&amp;p=12', ($p === 12 && $is_true)); // 12
	$BE['MAINNAV'][] = phpwcms_subnav($BL['be_alias'], 'phpwcms.php?do=admin&amp;p=13', ($p === 13 && $is_true)); // 13
	$BE['MAINNAV'][] = phpwcms_subnav($BL['be_link'] . ' &amp; ' . $BL['be_redirect'], 'phpwcms.php?do=admin&amp;p=14', ($p === 14 && $is_true)); // 14
	$BE['MAINNAV'][] = '	</ul>';
	$BE['MAINNAV'][] = '	<ul>';
	$BE['MAINNAV'][] = phpwcms_subnav($BL['be_flush_image_cache'], '#', false, 'onclick="return flush_image_cache(this,\'include/inc_act/ajax_connector.php?action=flush_image_cache&value=1\');"');
	$BE['MAINNAV'][] = phpwcms_subnav($BL['be_cnt_move_deleted'], 'include/inc_act/act_file.php?movedeletedfiles='. $_SESSION["wcs_user_id"], false, 'onclick="return confirm(\''.$BL['be_cnt_move_deleted_msg'].'\');"');
	$BE['MAINNAV'][] = '	</ul>';
	$BE['MAINNAV'][] = '	<ul>';
	$BE['MAINNAV'][] = phpwcms_subnav('phpinfo()', 'include/inc_act/act_phpinfo.php', false, 'target="_blank"');
	$BE['MAINNAV'][] = '	</ul>';
	$BE['MAINNAV'][] = '</li>';
}

switch ($do) {

	case "articles":
		include(PHPWCMS_ROOT.'/include/inc_lib/admin.functions.inc.php');
		include(PHPWCMS_ROOT.'/include/inc_lib/article.functions.inc.php');
		break;

	case "admin":
		if(!empty($_SESSION["wcs_user_admin"])) {
			include_once(PHPWCMS_ROOT.'/include/inc_lib/admin.functions.inc.php');
		}
		break;

	case "profile":
		if(!empty($_POST["form_aktion"])) {
			switch($_POST["form_aktion"]) {
				case "update_account":
					include(PHPWCMS_ROOT.'/include/inc_lib/profile.updateaccount.inc.php');
					break;
				case "update_detail":
					include(PHPWCMS_ROOT.'/include/inc_lib/profile.update.inc.php');
					break;
				case "create_detail":
					include(PHPWCMS_ROOT.'/include/inc_lib/profile.create.inc.php');
					break;
			}
		}
		break;

	case "logout":
		$sql  = "UPDATE ".DB_PREPEND."phpwcms_userlog SET logged_change="._dbEscape(time()).", logged_in=0 ";
		$sql .= "WHERE logged_user="._dbEscape($_SESSION["wcs_user"])." AND logged_in=1";
		_dbQuery($sql, 'UPDATE');
		session_destroy();
		headerRedirect(PHPWCMS_URL.get_login_file());
		break;
}


//script chaching to allow header redirect
ob_start(); //without Compression

// set correct content type for backend
header('Content-Type: text/html; charset='.PHPWCMS_CHARSET);

?><!DOCTYPE>
<html>
<head><?php printf(PHPWCMS_HEADER_COMMENT, ''); ?>
	<meta http-equiv="X-UA-Compatible" content="IE=Edge,chrome=1">
	<meta charset="<?php echo PHPWCMS_CHARSET ?>">
	<title><?php echo $BL['be_page_title'].' - '.PHPWCMS_HOST ?></title>
	<link href="include/inc_css/phpwcms.css" rel="stylesheet" type="text/css">
	<meta name="robots" content="noindex, nofollow">
<?php

$BE['HEADER']['alias_slah_var'] = '	<script type="text/javascript"> ' . LF . '		var aliasAllowSlashes = ' . (PHPWCMS_ALIAS_WSLASH ? 'true' : 'false') . ';' . LF . '	</script>';

initMootools();
$BE['HEADER']['phpwcms.js'] = getJavaScriptSourceLink('include/inc_js/phpwcms.js');

if($do == "messages" && $p == 1) {

	include(PHPWCMS_ROOT.'/include/inc_lib/message.sendjs.inc.php');

} elseif($do == "articles") {

	if($p == 2 && isset($_GET["aktion"]) && intval($_GET["aktion"]) == 2) {
		initJsOptionSelect();
	}
	if(($p == 1) || ($p == 2 && isset($_GET["aktion"]) && intval($_GET["aktion"]) == 1)) {
		initJsCalendar();
	}

} elseif($do == 'admin' && ($p == 6 || $p == 11)) {

	// struct editor
	initJsOptionSelect();

}

if($BE['LANG'] == 'ar') {
	$BE['HEADER'][] = '<style type="text/css">' . LF . '<!--' . LF . '* {direction: rtl;}' . LF . '// -->' . LF . '</style>';
}

?>
<!-- phpwcms HEADER -->
</head>
<body<?php echo $body_onload ?>>
	<div class="outer">
	<!-- phpwcms BODY_OPEN -->

		<header>
			<h2 class="phpwcms-logo">
				<a href="phpwcms.php" target="_top"><img src="img/backend/phpwcms-signet-be-small.png" alt="phpwcms v<?php echo  html_specialchars(PHPWCMS_VERSION); ?>" width="104" height="26" border="0" /></a>
			</h2>

			<a href="<?php echo PHPWCMS_URL ?>" class="open-home pull-right" target="_blank"><?php echo PHPWCMS_HOST ?></a>

			<div class="pull-right">

				<form action="phpwcms.php" method="POST" class="backend-search">

					<h3><?php echo $BL['be_ctype_search'] ?></h3>
					<input type="text" name="backend_search_input" value="<?php

						if(isset($_POST['backend_search_input'])) {
							$_SESSION['phpwcms_backend_search'] = clean_slweg($_POST['backend_search_input']);
						}

						if(!empty($_SESSION['phpwcms_backend_search'])) {
							echo html_specialchars($_SESSION['phpwcms_backend_search']);
						}
					?>" class="backend-search-input v11" /><input type="image" src="img/famfamfam/magnifier.png" class="backend-search-button" />

				</form>

				<a href="phpwcms.php?do=logout" target="_top"><?php echo $BL['be_nav_logout'] ?></a>

			</div>

		</header>

		<div class="inner">

			<nav>
				<ul class="nav-main">
					<?php echo implode(LF, $BE['MAINNAV']); ?>
				</ul>

<?php
		// User logged in
		$result = _dbGet('phpwcms_userlog', 'logged_user, logged_username', 'logged_in=1');
		if(isset($result[0]['logged_user'])): ?>
				<dl class="users-logged-in">
					<dt><?php echo $BL['usr_online'] ?></dt>
<?php		foreach($result as $value): ?>
					<dd><?php echo html($value['logged_username'] . ' ('.$value['logged_user'].')'); ?></dd>

<?php		endforeach; ?>
				</dl>
<?php 	endif; ?>

			</nav>

			<section class="main">

			{STATUS_MESSAGE}
			{BE_PARSE_LANG}
			<!--BE_MAIN_CONTENT_START//-->

<?php

	      switch($do) {

	      	case "profile":	//Profile
					      	if($p === 1) {
					      		include(PHPWCMS_ROOT.'/include/inc_tmpl/profile.data.tmpl.php');
					      	} else {
					      		include(PHPWCMS_ROOT.'/include/inc_tmpl/profile.account.tmpl.php');
					      	}
	      					break;

	      	case "files":	// File manager
					      	if($p === 8) { //FTP File upload

							include(PHPWCMS_ROOT.'/include/inc_tmpl/files.ftptakeover.tmpl.php');

					      	// based on pwmod by pagewerkstatt.ch 12/2012
					      	} elseif ($p === 4) {

							include(PHPWCMS_ROOT.'/include/inc_tmpl/files.actions.tmpl.php');

					      	} else {

					      		include(PHPWCMS_ROOT.'/include/inc_tmpl/files.reiter.tmpl.php'); //Files Navigation/Reiter
					      		switch($files_folder) {
					      			case 0:	//Listing der Privaten Dateien
							      			if(isset($_GET["mkdir"]) || (isset($_POST["dir_aktion"]) && intval($_POST["dir_aktion"]) == 1) ) {
												include(PHPWCMS_ROOT.'/include/inc_tmpl/files.private.newdir.tmpl.php');
											}
							      			if(isset($_GET["editdir"]) || (isset($_POST["dir_aktion"]) && intval($_POST["dir_aktion"]) == 2) ) {
												include(PHPWCMS_ROOT.'/include/inc_tmpl/files.private.editdir.tmpl.php');
											}
							      			if(isset($_GET["upload"]) || (isset($_POST["file_aktion"]) && intval($_POST["file_aktion"]) == 1) ) {
							      				include(PHPWCMS_ROOT.'/include/inc_tmpl/files.private.upload.tmpl.php');
							      			}
							      			if(isset($_GET["editfile"]) || (isset($_POST["file_aktion"]) && intval($_POST["file_aktion"]) == 2) ) {
							      				include(PHPWCMS_ROOT.'/include/inc_tmpl/files.private.editfile.tmpl.php');
							      			}
							      			include(PHPWCMS_ROOT.'/include/inc_lib/files.private-functions.inc.php'); //Listing-Funktionen einfügen
							      			include(PHPWCMS_ROOT.'/include/inc_lib/files.private.additions.inc.php'); //Zusätzliche Private Funktionen
							      			break;

					      			case 1: //Funktionen zum Listen von Public Files
							      			include(PHPWCMS_ROOT.'/include/inc_lib/files.public-functions.inc.php'); //Public Listing-Funktionen einfügen
							      			include(PHPWCMS_ROOT.'/include/inc_tmpl/files.public.list.tmpl.php'); //Elemetares für Public Listing
							      			break;

					      			case 2:	//Dateien im Papierkorb
							      			include(PHPWCMS_ROOT.'/include/inc_tmpl/files.private.trash.tmpl.php');
							      			break;

					      			case 3:	//Dateisuche
							      			include(PHPWCMS_ROOT.'/include/inc_tmpl/files.search.tmpl.php');
							      			break;
					      		}
		      					include(PHPWCMS_ROOT.'/include/inc_tmpl/files.abschluss.tmpl.php'); //Abschließende Tabellenzeile = dicke Linie
					      	}
	      					break;

	      	case "chat":	//Chat
					      	if($p === 1) {
					      		include(PHPWCMS_ROOT.'/include/inc_tmpl/chat.list.tmpl.php'); break; //Chat/Listing
					      	} else {
					      		include(PHPWCMS_ROOT.'/include/inc_tmpl/chat.main.tmpl.php'); break; //Chat Startseite
					      	}
	      					break;

			case "messages":	//Messages
					      	switch($p) {
					      		case 0: include(PHPWCMS_ROOT.'/include/inc_tmpl/message.center.tmpl.php'); break; //Messages Overview
					      		case 1: include(PHPWCMS_ROOT.'/include/inc_tmpl/message.send.tmpl.php');   break;	//New Message
					      		case 2: //Newsletter subscription
							      		if($_SESSION["wcs_user_admin"] == 1) include(PHPWCMS_ROOT.'/include/inc_tmpl/message.subscription.tmpl.php');
							      		break;
					      		case 3: //Newsletter
							      		if($_SESSION["wcs_user_admin"] == 1) include(PHPWCMS_ROOT.'/include/inc_tmpl/newsletter.list.tmpl.php');
							      		break;
					      		case 4: //Newsletter subscribers
							      		if($_SESSION["wcs_user_admin"] == 1) {
											include(PHPWCMS_ROOT.'/include/inc_tmpl/message.subscribers.tmpl.php');
										}
					      				break;
					      	}
	      					break;

	      	case "modules":	//Modules

				// if a module is selected
				if(isset($phpwcms['modules'][$module])) {

					include($phpwcms['modules'][$module]['path'].'backend.default.php');

				}

				break;

	      	case "admin":	//Administration
	      	if($_SESSION["wcs_user_admin"] == 1) {
	      		switch($p) {
	      			case 0: //User Administration
	      			switch(!empty($_GET['s']) ? intval($_GET["s"]) : 0) {
	      				case 1: include(PHPWCMS_ROOT.'/include/inc_tmpl/admin.newuser.tmpl.php');  break; //New User
	      				case 2: include(PHPWCMS_ROOT.'/include/inc_tmpl/admin.edituser.tmpl.php'); break; //Edit User
	      			}
	      			include(PHPWCMS_ROOT.'/include/inc_tmpl/admin.listuser.tmpl.php');
	      			break;

					case 1: //Users and Groups
						//enym new group management tool
					include(PHPWCMS_ROOT.'/include/inc_tmpl/admin.groups.tmpl.php');
					break;

					case 2: //Settings
					include(PHPWCMS_ROOT.'/include/inc_tmpl/admin.settings.tmpl.php');
					break;

					case 5: //Keywords
					include(PHPWCMS_ROOT.'/include/inc_tmpl/admin.keyword.tmpl.php');
					break;

	      			case 6: //article structure

	      			include(PHPWCMS_ROOT.'/include/inc_lib/admin.structure.inc.php');
	      			if(isset($_GET["struct"])) {
						//include(PHPWCMS_ROOT.'/include/inc_lib/article.contenttype.inc.php'); //loading array with actual content types
	      				include(PHPWCMS_ROOT.'/include/inc_tmpl/admin.structform.tmpl.php');
	      			} else {
	      				include(PHPWCMS_ROOT.'/include/inc_tmpl/admin.structlist.tmpl.php');
						$phpwcms['be_parse_lang_process'] = true;
	      			}
	      			break;

					case 7:	//File Categories
	      			include(PHPWCMS_ROOT.'/include/inc_tmpl/admin.filecat.tmpl.php');
	      			break;

	      			case 8:	//Page Layout
	      			include(PHPWCMS_ROOT.'/include/inc_tmpl/admin.pagelayout.tmpl.php');
	      			break;

					case 10:	//Frontend CSS
	      			include(PHPWCMS_ROOT.'/include/inc_tmpl/admin.frontendcss.tmpl.php');
	      			break;

					case 11:	//Templates
	      			include(PHPWCMS_ROOT.'/include/inc_tmpl/admin.templates.tmpl.php');
	      			break;

	      			case 12:	//Default backend starup HTML
	      			include(PHPWCMS_ROOT.'/include/inc_tmpl/admin.startup.tmpl.php');
	      			break;

					//Default backend sitemap HTML
					case 13:
					include(PHPWCMS_ROOT.'/include/inc_tmpl/admin.aliaslist.tmpl.php');
	        		break;

					//Default backend sitemap HTML
					case 14:
					include(PHPWCMS_ROOT.'/include/inc_tmpl/admin.redirect.tmpl.php');
	        		break;

	      		}
	      	}
	      	break;

			// articles
	      	case "articles":
				$_SESSION['image_browser_article'] = 0; //set how image file browser should work
				switch($p) {

					// List articles
					case 0:
						include(PHPWCMS_ROOT.'/include/inc_tmpl/article.structlist.tmpl.php');
						$phpwcms['be_parse_lang_process'] = true;
						break;

					// Edit/create article
					case 1:
					case 2:
						include(PHPWCMS_ROOT.'/include/inc_lib/article.editcontent.inc.php');
						break;

					// News
					case 3:
						include(PHPWCMS_ROOT.'/include/inc_lib/news.inc.php');
						include(PHPWCMS_ROOT.'/include/inc_tmpl/news.tmpl.php');
						break;
				}
				break;

			// about phpwcms
			case "about":
				include(PHPWCMS_ROOT.'/include/inc_tmpl/about.tmpl.php');
				break;

			// start
			default:
				include(PHPWCMS_ROOT.'/include/inc_tmpl/be_start.tmpl.php');
				include(PHPWCMS_TEMPLATE.'inc_default/startup.php');
				$phpwcms['be_parse_lang_process'] = true;

		}

?>

			<!--BE_MAIN_CONTENT_END//-->
			</section>
		</div>

		<footer>
			<p>
				<a href="http://www.phpwcms.org" title="phpwcms">phpwcms <?php echo PHPWCMS_VERSION ?></a>
				&copy; 2002&#8212;<?php echo date('Y'); ?>
				<a href="mailto:oliver@phpwcms.de?subject=phpwcms">Oliver Georgi</a>.
				<a href="phpwcms.php?do=about" title="<?php echo $BL['be_aboutlink_title'] ?>">Licensed under GPL. Extensions are copyright	of their respective owners.</a>
			</p>
		</footer>

	<?php

	//Set Focus for chat insert filed
	set_chat_focus($do, $p);

	//If new message was sent -> automatic forwarding to message center
	forward_to($forward_to_message_center, PHPWCMS_URL."phpwcms.php?do=messages", 2500);


	?>
	<!-- phpwcms BODY_CLOSE -->
	</div>
</body>
</html>
<?php

// retrieve complete processing time
list($usec, $sec) = explode(' ', microtime());
header('X-phpwcms-Page-Processed-In: ' . number_format(1000*($usec + $sec - $phpwcms_rendering_start), 3) .' ms');

$BE['HTML'] = ob_get_clean();

// Load ToolTip JS only when necessary
if(strpos($BE['HTML'], 'Tip(')) {
	$BE['BODY_CLOSE']['wz_tooltip.js'] = getJavaScriptSourceLink('include/inc_js/wz_tooltip.js', '');
}

//	parse for backend languages
backend_language_parser();

//	replace special backend sections -> good for additional code like custom JavaScript, CSS and so on
//	<!-- phpwcms BODY_CLOSE -->
//	<!-- phpwcms BODY_OPEN -->
//	<!-- phpwcms HEADER -->

// special body onload JavaScript
if($body_onload) {
	$BE['HTML'] = str_replace('<body>', '<body '.$body_onload.'>', $BE['HTML']);
}

$BE['HEADER'][] = '  <!--[if lte IE 7]><style type="text/css">body{behavior:url("'.TEMPLATE_PATH.'inc_css/specific/csshover3.htc");}</style><![endif]-->';

// html head section
$BE['HTML'] = str_replace('<!-- phpwcms HEADER -->', implode(LF, $BE['HEADER']), $BE['HTML']);

// body open area
$BE['HTML'] = str_replace('<!-- phpwcms BODY_OPEN -->', implode(LF, $BE['BODY_OPEN']), $BE['HTML']);

// body close area
$BE['HTML'] = str_replace('<!-- phpwcms BODY_CLOSE -->', implode(LF, $BE['BODY_CLOSE']), $BE['HTML']);

// Show global system status message
$BE['HTML'] = str_replace('{STATUS_MESSAGE}', show_status_message(true), $BE['HTML']);

// return all
echo $BE['HTML'];

?>