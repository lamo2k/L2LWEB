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
 ����: pm.php
-----------------------------------------------------
 ����������: �������������� �������� ������������� ���������
=====================================================
*/
@session_start();
@error_reporting ( E_ALL ^ E_WARNING ^ E_NOTICE );
@ini_set ( 'display_errors', true );
@ini_set ( 'html_errors', false );
@ini_set ( 'error_reporting', E_ALL ^ E_WARNING ^ E_NOTICE );

define( 'DATALIFEENGINE', true );
define( 'ROOT_DIR', substr( dirname(  __FILE__ ), 0, -12 ) );
define( 'ENGINE_DIR', ROOT_DIR . '/engine' );

include ENGINE_DIR . '/data/config.php';

if( $config['http_home_url'] == "" ) {
	
	$config['http_home_url'] = explode( "engine/ajax/pm.php", $_SERVER['PHP_SELF'] );
	$config['http_home_url'] = reset( $config['http_home_url'] );
	$config['http_home_url'] = "http://" . $_SERVER['HTTP_HOST'] . $config['http_home_url'];

}

require_once ENGINE_DIR . '/classes/mysql.php';
require_once ENGINE_DIR . '/data/dbconfig.php';
require_once ENGINE_DIR . '/modules/functions.php';
require_once ENGINE_DIR . '/classes/templates.class.php';
require_once ENGINE_DIR . '/classes/parse.class.php';
require_once ENGINE_DIR . '/modules/sitelogin.php';

if( !$is_logged ) {
	die ( "Hacking attempt!" );
}

//################# ����������� ����� �������������
$user_group = get_vars( "usergroup" );

if( ! $user_group ) {
	$user_group = array ();
	
	$db->query( "SELECT * FROM " . USERPREFIX . "_usergroups ORDER BY id ASC" );
	
	while ( $row = $db->get_row() ) {
		
		$user_group[$row['id']] = array ();
		
		foreach ( $row as $key => $value ) {
			$user_group[$row['id']][$key] = stripslashes($value);
		}
	
	}
	set_vars( "usergroup", $user_group );
	$db->free();
}

$_REQUEST['skin'] = trim(totranslit($_REQUEST['skin'], false, false));
	
if( ! @is_dir( ROOT_DIR . '/templates/' . $_REQUEST['skin'] ) or $_REQUEST['skin'] == "" ) {
	die( "Hacking attempt!" );
}

if( $config["lang_" . $_REQUEST['skin']] ) {
	if ( file_exists( ROOT_DIR . '/language/' . $config["lang_" . $_REQUEST['skin']] . '/website.lng' ) ) {
		@include_once (ROOT_DIR . '/language/' . $config["lang_" . $_REQUEST['skin']] . '/website.lng');
	} else die("Language file not found");
} else {
	include_once ROOT_DIR . '/language/' . $config['langs'] . '/website.lng';
}
$config['charset'] = ($lang['charset'] != '') ? $lang['charset'] : $config['charset'];

@header( "Content-type: text/html; charset=" . $config['charset'] );

if ($_GET['action'] == "add_ignore") {

	$id = intval($_GET['id']);

	$row = $db->super_query( "SELECT id, user, user_from FROM " . USERPREFIX . "_pm WHERE id='{$id}'" );

	$row['user_from'] = $db->safesql( $row['user_from'] );

	if( $row['user'] != $member_id['user_id'] OR !$row['id']) die("Operation not Allowed");

	if ($row['user_from'] == $member_id['name']) { echo $lang['ignore_error']; die(); }

	$db->query( "SELECT id FROM " . USERPREFIX . "_ignore_list WHERE user_from='{$row['user_from']}'" );

	if ($db->num_rows()) { echo $lang['ignore_error_1']; die(); }

	$row_group = $db->super_query( "SELECT user_group FROM " . USERPREFIX . "_users WHERE name='{$row['user_from']}'" );

	if ($user_group[$row_group['user_group']]['admin_editusers']) { echo $lang['ignore_error_2']; die(); }

	$db->query( "INSERT INTO " . USERPREFIX . "_ignore_list (user, user_from) values ('{$row['user']}', '{$row['user_from']}')" );

	echo $lang['ignore_ok'];

} elseif ($_GET['action'] == "del_ignore") {

	$id = intval($_GET['id']);

	$row = $db->super_query( "SELECT * FROM " . USERPREFIX . "_ignore_list WHERE id='{$id}'" );

	if ($row['id'] AND ($row['user'] == $member_id['user_id'] OR $member_id['user_group'] == 1) ) { $db->query( "DELETE FROM " . USERPREFIX . "_ignore_list WHERE id = '{$row['id']}'" ); echo $lang['ignore_del_ok']; die(); }

	die("Operation not Allowed");

} else {

	$parse = new ParseFilter( );
	$parse->safe_mode = true;
	
	function del_tpl($read) {
		global $tpl;
		$read = str_replace( '\"', '"', str_replace( "&amp;", "&", $read ) );
		$tpl->copy_template = $read;
	}
	
	$tpl = new dle_template( );
	$tpl->dir = ROOT_DIR . '/templates/' . $_REQUEST['skin'];
	define( 'TEMPLATE_DIR', $tpl->dir );
	
	$_POST['name'] = convert_unicode( $_POST['name'], $config['charset'] );
	$_POST['subj'] = convert_unicode( $_POST['subj'], $config['charset'] );
	$_POST['text'] = convert_unicode( $_POST['text'], $config['charset'] );
	
	$name = $parse->process( trim( $_POST['name'] ) );
	$subj = $parse->process( trim( $_POST['subj'] ) );
	
	if( $config['allow_comments_wysiwyg'] != "yes" ) $text = $parse->BB_Parse( $parse->process( $_POST['text'] ), false );
	else {
		$parse->wysiwyg = true;
		$parse->ParseFilter( Array ('div', 'span', 'p', 'br', 'strong', 'em', 'ul', 'li', 'ol' ), Array (), 0, 1 );
		$text = $parse->BB_Parse( $parse->process( $_POST['text'] ) );
	}
	
	$tpl->load_template( 'pm.tpl' );
	
	preg_replace( "'\\[readpm\\](.*?)\\[/readpm\\]'ies", "del_tpl('\\1')", $tpl->copy_template );
	
			if( strpos( $tpl->copy_template, "[xfvalue_" ) !== false ) $xfound = true;
			else $xfound = false;
			
			if( $xfound ) { 
	
				$xfields = xfieldsload( true );
	
				$xfieldsdata = xfieldsdataload( $member_id['xfields'] );
					
				foreach ( $xfields as $value ) {
					$preg_safe_name = preg_quote( $value[0], "'" );
						
					if( $value[5] != 1 OR $member_id['user_group'] == 1 OR ($is_logged AND $member_id['name'] == $row['user_from']) ) {
						if( empty( $xfieldsdata[$value[0]] ) ) {
							$tpl->copy_template = preg_replace( "'\\[xfgiven_{$preg_safe_name}\\](.*?)\\[/xfgiven_{$preg_safe_name}\\]'is", "", $tpl->copy_template );
						} else {
							$tpl->copy_template = preg_replace( "'\\[xfgiven_{$preg_safe_name}\\](.*?)\\[/xfgiven_{$preg_safe_name}\\]'is", "\\1", $tpl->copy_template );
						}
						$tpl->copy_template = preg_replace( "'\\[xfvalue_{$preg_safe_name}\\]'i", stripslashes( $xfieldsdata[$value[0]] ), $tpl->copy_template );
					} else {
						$tpl->copy_template = preg_replace( "'\\[xfgiven_{$preg_safe_name}\\](.*?)\\[/xfgiven_{$preg_safe_name}\\]'is", "", $tpl->copy_template );
						$tpl->copy_template = preg_replace( "'\\[xfvalue_{$preg_safe_name}\\]'i", "", $tpl->copy_template );
					}
				}
			}
	
			$tpl->set( '{subj}', $subj );
			$tpl->set( '{text}', stripslashes($text) );
			$tpl->set( '{author}', $member_id['name'] );
			$tpl->set( '[reply]', "<a href=\"#\">" );
			$tpl->set( '[/reply]', "</a>" );
			$tpl->set( '[del]', "<a href=\"#\">" );
			$tpl->set( '[/del]', "</a>" );
			$tpl->set( '[ignore]', "<a href=\"#\">" );
			$tpl->set( '[/ignore]', "</a>" );
			$tpl->set( '[complaint]', "<a href=\"#\">" );
			$tpl->set( '[/complaint]', "</a>" );
	
			if( $member_id['signature'] and $user_group[$member_id['user_group']]['allow_signature'] ) {
					
				$tpl->set_block( "'\\[signature\\](.*?)\\[/signature\\]'si", "\\1" );
				$tpl->set( '{signature}', stripslashes( $member_id['signature'] ) );
				
			} else {
				$tpl->set_block( "'\\[signature\\](.*?)\\[/signature\\]'si", "" );
			}
	
			if( $member_id['icq'] ) $tpl->set( '{icq}', stripslashes( $member_id['icq'] ) );
			else $tpl->set( '{icq}', '--' );
	
			if( $user_group[$member_id['user_group']]['icon'] ) $tpl->set( '{group-icon}', "<img src=\"" . $user_group[$member_id['user_group']]['icon'] . "\" border=\"0\" alt=\"\" />" );
			else $tpl->set( '{group-icon}', "" );
	
			$tpl->set( '{group-name}', $user_group[$member_id['user_group']]['group_prefix'].$user_group[$member_id['user_group']]['group_name'].$user_group[$member_id['user_group']]['group_suffix'] );
			$tpl->set( '{news-num}', intval( $member_id['news_num'] ) );
			$tpl->set( '{comm-num}', intval( $member_id['comm_num'] ) );
	
			if( $member_id['foto'] ) $tpl->set( '{foto}', $config['http_home_url'] . "uploads/fotos/" . $member_id['foto'] );
			else $tpl->set( '{foto}', "{THEME}/images/noavatar.png" );
	
			$tpl->set( '{date}', "--" );
	
			if($member_id['reg_date'] ) $tpl->set( '{registration}', langdate( "j.m.Y", $member_id['reg_date'] ) );
			else $tpl->set( '{registration}', '--' );
	
	$tpl->compile( 'content' );
	$tpl->clear();
	
	$tpl->result['content'] = str_replace( '{THEME}', $config['http_home_url'] . 'templates/' . $_REQUEST['skin'], $tpl->result['content'] );
	$tpl->result['content'] = str_ireplace( "[hide]", "", str_ireplace( "[/hide]", "", $tpl->result['content']) );
	
	$tpl->result['content'] = "<div id=\"blind-animation\" style=\"display:none\">".$tpl->result['content']."<div>";
	
	echo $tpl->result['content'];
}

?>