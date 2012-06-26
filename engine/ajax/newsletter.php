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
 ����: newsletter.php
-----------------------------------------------------
 ����������: AJAX ��� �������� ���������
=====================================================
*/
@session_start();
@error_reporting ( E_ALL ^ E_WARNING ^ E_NOTICE );
@ini_set ( 'display_errors', true );
@ini_set ( 'html_errors', false );
@ini_set ( 'error_reporting', E_ALL ^ E_WARNING ^ E_NOTICE );

define('DATALIFEENGINE', true);
define( 'ROOT_DIR', substr( dirname(  __FILE__ ), 0, -12 ) );
define( 'ENGINE_DIR', ROOT_DIR . '/engine' );

include ENGINE_DIR.'/data/config.php';

if ($config['http_home_url'] == "") {

	$config['http_home_url'] = explode("engine/ajax/newsletter.php", $_SERVER['PHP_SELF']);
	$config['http_home_url'] = reset($config['http_home_url']);
	$config['http_home_url'] = "http://".$_SERVER['HTTP_HOST'].$config['http_home_url'];

}

require_once ENGINE_DIR.'/classes/mysql.php';
require_once ENGINE_DIR.'/data/dbconfig.php';

require_once ROOT_DIR.'/language/'.$config['langs'].'/website.lng';

$config['charset'] = ($lang['charset'] != '') ? $lang['charset'] : $config['charset'];

require_once ENGINE_DIR.'/modules/functions.php';

$user_group = get_vars ( "usergroup" );

if (! $user_group) {
	$user_group = array ();
	
	$db->query ( "SELECT * FROM " . USERPREFIX . "_usergroups ORDER BY id ASC" );
	
	while ( $row = $db->get_row () ) {
		
		$user_group[$row['id']] = array ();
		
		foreach ( $row as $key => $value ) {
			$user_group[$row['id']][$key] = $value;
		}
	
	}
	set_vars ( "usergroup", $user_group );
	$db->free ();
}

require_once ENGINE_DIR.'/modules/sitelogin.php';

if (!$is_logged OR !$user_group[$member_id['user_group']]['admin_newsletter']) die ("error");

$startfrom = intval($_POST['startfrom']);

if ($_POST['empfanger']) {

	$empfanger = array(); 

	$temp = explode(",", $_POST['empfanger']); 

	foreach ( $temp as $value ) {
		$empfanger[] = intval($value);
	}

	$empfanger = implode( "','", $empfanger );

	$empfanger = "user_group IN ('" . $empfanger . "')";

} else $empfanger = false;

$type = $_POST['type'];
$a_mail = intval($_POST['a_mail']);
$limit = intval($_POST['limit']);
$step = 0;

$title = convert_unicode($_POST['title'], $config['charset']);
$message = convert_unicode($_POST['message'], $config['charset']);

$find = array ('/data:/i', '/about:/i', '/vbscript:/i', '/onclick/i', '/onload/i', '/onunload/i', '/onabort/i', '/onerror/i', '/onblur/i', '/onchange/i', '/onfocus/i', '/onreset/i', '/onsubmit/i', '/ondblclick/i', '/onkeydown/i', '/onkeypress/i', '/onkeyup/i', '/onmousedown/i', '/onmouseup/i', '/onmouseover/i', '/onmouseout/i', '/onselect/i', '/javascript/i', '/javascript/i' );
$replace = array ("d&#097;ta:", "&#097;bout:", "vbscript<b></b>:", "&#111;nclick", "&#111;nload", "&#111;nunload", "&#111;nabort", "&#111;nerror", "&#111;nblur", "&#111;nchange", "&#111;nfocus", "&#111;nreset", "&#111;nsubmit", "&#111;ndblclick", "&#111;nkeydown", "&#111;nkeypress", "&#111;nkeyup", "&#111;nmousedown", "&#111;nmouseup", "&#111;nmouseover", "&#111;nmouseout", "&#111;nselect", "j&#097;vascript" );

$message = preg_replace( $find, $replace, $message );
$message = preg_replace( "#<iframe#i", "&lt;iframe", $message );
$message = preg_replace( "#<script#i", "&lt;script", $message );
$message = str_replace( "<?", "&lt;?", $message );
$message = str_replace( "?>", "?&gt;", $message );

if (!$title OR !$message OR !$limit) die ("error");

if ($type == "pm") {

$time = time()+($config['date_adjust']*60);
$title = $db->safesql($title);
$message = $db->safesql($message);

if ($empfanger)
$result = $db->query("SELECT user_id, name, fullname FROM " . USERPREFIX . "_users WHERE {$empfanger} LIMIT ".$startfrom.",".$limit);
else
$result = $db->query("SELECT user_id, name, fullname FROM " . USERPREFIX . "_users LIMIT ".$startfrom.",".$limit);



while($row = $db->get_row($result))
  {

	if ( $row['fullname'] )
		$message_send = str_replace("{%user%}", $row['fullname'], $message);
	else
		$message_send = str_replace("{%user%}", $row['name'], $message);

	$db->query("INSERT INTO " . USERPREFIX . "_pm (subj, text, user, user_from, date, pm_read, folder) values ('$title', '$message_send', '$row[user_id]', '$member_id[name]', '$time', 'no', 'inbox')");
	$db->query("UPDATE " . USERPREFIX . "_users set pm_all=pm_all+1, pm_unread=pm_unread+1  where user_id='$row[user_id]'");
    $step++;
  }
$db->free($result);
}
elseif ($type == "email") {

$message = stripslashes( $message );
$title = stripslashes( $title );

$message = <<<HTML
<html><title>{$title}</title>
<meta content="text/html; charset={$config['charset']}" http-equiv=Content-Type>
<style type="text/css">
html,body{
    font-family: Verdana;
    word-spacing: 0.1em;
    letter-spacing: 0;
    line-height: 1.5em;
    font-size: 11px;
}

p {
	margin:0px;
	padding: 0px;
}

a:active,
a:visited,
a:link {
	color: #4b719e;
	text-decoration:none;
}

a:hover {
	color: #4b719e;
	text-decoration: underline;
}
</style>
<body>
{$message}
</body>
</html>
HTML;


include_once ENGINE_DIR.'/classes/mail.class.php';
$mail = new dle_mail ($config, true);

$where = array();

if ($empfanger) $where[] = $empfanger;
if ($a_mail) $where[] = "allow_mail = '1'";

if (count($where)) $where = " WHERE ".implode (" AND ", $where);
else $where = "";

	if ($config['mail_bcc']) {
		$limit = $limit * 6;
		$i = 0;
		$h_mail = array();
		$bcc = array();

		$db->query("SELECT email FROM " . USERPREFIX . "_users".$where." LIMIT ".$startfrom.",".$limit);

		$db->close();

		  while($row = $db->get_row())
		  {
				if ($i == 0) { $h_mail[$t] = $row['email'];}
				else {$bcc[$t][] = $row['email'];}

				$i++;

				if ($i == 6) {
					$i=0;
					$t++;
				}

			$step++;
          }

		$db->free();

		foreach ($h_mail as $key => $email) {
			$mail->bcc = $bcc[$key];
			$message_send = str_replace("{%user%}", $lang['nl_info_2'], $message);

			$mail->send ($email, $title, $message_send);
		}

	}
	else 
	{

		$db->query("SELECT email, name, fullname FROM " . USERPREFIX . "_users".$where." LIMIT ".$startfrom.",".$limit);

		$db->close();

		  while($row = $db->get_row())
		  {

			if ( $row['fullname'] )
				$message_send = str_replace("{%user%}", $row['fullname'], $message);
			else
				$message_send = str_replace("{%user%}", $row['name'], $message);

		   $mail->send ($row['email'], $title, $message_send);
	
		   $step++;
		  }

		$db->free();
	}

}
else
{
die ("error");
}

$count = $startfrom + $step;
$buffer = "{\"status\": \"ok\",\"count\": {$count}}";

@header("Content-type: text/html; charset=".$config['charset']);
echo $buffer;
?>