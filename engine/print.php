<?php
/*
=====================================================
 DataLife Engine - by SoftNews Media Group 
-----------------------------------------------------
 http://dle-news.ru/
-----------------------------------------------------
 Copyright (c) 2004,2012 SoftNews Media Group
=====================================================
 ������ ��� ������� ���������� �������
=====================================================
 ����: print.php
-----------------------------------------------------
 ����������: ������ ��� ������
=====================================================
*/
@session_start ();
@ob_start ();
@ob_implicit_flush ( 0 );

define( 'DATALIFEENGINE', true );
define( 'ROOT_DIR', '..' );
define( 'ENGINE_DIR', dirname( __FILE__ ) );

@error_reporting ( E_ALL ^ E_WARNING ^ E_NOTICE );
@ini_set ( 'display_errors', true );
@ini_set ( 'html_errors', false );
@ini_set ( 'error_reporting', E_ALL ^ E_WARNING ^ E_NOTICE );

include ENGINE_DIR . '/data/config.php';

if( $config['http_home_url'] == "" ) {
	
	$config['http_home_url'] = explode( "engine/print.php", $_SERVER['PHP_SELF'] );
	$config['http_home_url'] = reset( $config['http_home_url'] );
	$config['http_home_url'] = "http://" . $_SERVER['HTTP_HOST'] . $config['http_home_url'];

}

require_once ENGINE_DIR . '/classes/mysql.php';
include_once ENGINE_DIR . '/data/dbconfig.php';
include_once ENGINE_DIR . '/modules/functions.php';
require_once ENGINE_DIR . '/classes/templates.class.php';

if( $config['site_offline'] == "yes" ) die( "The site in offline mode" );

check_xss();
$_TIME = time() + ($config['date_adjust'] * 60);

if (isset ( $_COOKIE['dle_skin'] ) ) {

	$_COOKIE['dle_skin'] = trim( totranslit($_COOKIE['dle_skin'], false, false) );

	if ($_COOKIE['dle_skin'] != '' AND @is_dir ( ROOT_DIR . '/templates/' . $_COOKIE['dle_skin'] )) {
		$config['skin'] = $_COOKIE['dle_skin'];
	}
}

if( $config["lang_" . $config['skin']] ) {
	if ( file_exists( ROOT_DIR . '/language/' . $config["lang_" . $config['skin']] . '/website.lng' ) ) {	
		include_once ROOT_DIR . '/language/' . $config["lang_" . $config['skin']] . '/website.lng';
	} else die("Language file not found");
} else {
	
	include_once ROOT_DIR . '/language/' . $config['langs'] . '/website.lng';

}

$config['charset'] = ($lang['charset'] != '') ? $lang['charset'] : $config['charset'];

if( $config['allow_registration'] == "yes" ) {
	
	include_once ENGINE_DIR . '/modules/sitelogin.php';

}

//####################################################################################################################
//                    ����������� ��������� � �� ���������
//####################################################################################################################
$cat_info = get_vars( "category" );

if( ! $cat_info ) {
	$cat_info = array ();
	
	$db->query( "SELECT * FROM " . PREFIX . "_category ORDER BY posi ASC" );
	while ( $row = $db->get_row() ) {
		
		$cat_info[$row['id']] = array ();
		
		foreach ( $row as $key => $value ) {
			$cat_info[$row['id']][$key] = $value;
		}
	
	}
	set_vars( "category", $cat_info );
	$db->free();
}

//################# ����������� ����� �������������
$user_group = get_vars( "usergroup" );

if( ! $user_group ) {
	$user_group = array ();
	
	$db->query( "SELECT * FROM " . USERPREFIX . "_usergroups ORDER BY id ASC" );
	
	while ( $row = $db->get_row() ) {
		
		$user_group[$row['id']] = array ();
		
		foreach ( $row as $key => $value ) {
			$user_group[$row['id']][$key] = $value;
		}
	
	}
	set_vars( "usergroup", $user_group );
	$db->free();
}

if( ! $is_logged ) {
	$member_id['user_group'] = 5;
}

$PHP_SELF = $config['http_home_url'] . "index.php";

if( isset( $_REQUEST['year'] ) ) $year = intval( $_GET['year'] );else $year = '';
if( isset( $_REQUEST['month'] ) ) $month = @$db->safesql( strip_tags( str_replace( '/', '', $_GET['month'] ) ) ); else $month = '';
if( isset( $_REQUEST['day'] ) ) $day = @$db->safesql( strip_tags( str_replace( '/', '', $_GET['day'] ) ) ); else $day = '';
if( isset( $_REQUEST['user'] ) ) $user = @$db->safesql( strip_tags( str_replace( '/', '', urldecode( $_GET['user'] ) ) ) ); else $user = '';
if( isset( $_REQUEST['news_name'] ) ) $news_name = @$db->safesql( strip_tags( str_replace( '/', '', $_GET['news_name'] ) ) ); else $news_name = '';
if( isset( $_REQUEST['newsid'] ) ) $newsid = intval( $_GET['newsid'] ); else $newsid = 0;
if( isset( $_REQUEST['cstart'] ) ) $cstart = intval( $_GET['cstart'] ); else $cstart = 0;
if( isset( $_REQUEST['news_page'] ) ) $news_page = intval( $_GET['news_page'] ); else $news_page = 0;
$category = '';

if (isset ($_REQUEST['do']) ) $do = totranslit ( $_REQUEST['do'] ); else $do = "";
if (isset ($_REQUEST['subaction']) ) $subaction = totranslit ($_REQUEST['subaction']); else $subaction = "";
if ( isset ($_REQUEST['doaction']) ) $doaction = totranslit ($_REQUEST['doaction']); else $doaction = "";
if ($do == "tags" AND !$_GET['tag']) $do = "alltags";

$dle_module = $do;
if ($do == "" and ! $subaction and $year) $dle_module = "date";
elseif ($do == "" and $catalog) $dle_module = "catalog";
elseif ($do == "") $dle_module = $subaction;
if ($subaction == '' AND $newsid) $dle_module = "showfull";
$dle_module = $dle_module ? $dle_module : "main";

$tpl = new dle_template();
$tpl->dir = ROOT_DIR . '/templates/' . $config['skin'];
define( 'TEMPLATE_DIR', $tpl->dir );

if ($config['rss_informer']) include_once ENGINE_DIR . '/modules/rssinform.php';

$config['allow_cache'] = false;
$view_template = "print";
include_once ENGINE_DIR . '/engine.php';

$tpl->result['content'] = str_replace ( '{THEME}', $config['http_home_url'] . 'templates/' . $config['skin'], $tpl->result['content'] );
$tpl->result['content'] = str_replace ( '{charset}', $config['charset'], $tpl->result['content'] );

echo $tpl->result['content'];
?>