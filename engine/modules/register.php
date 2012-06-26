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
 ����: register.php
-----------------------------------------------------
 ����������: ����������� ����������
=====================================================
*/

if( ! defined( 'DATALIFEENGINE' ) ) {
	die( "Hacking attempt!" );
}

require_once ENGINE_DIR . '/classes/parse.class.php';

$parse = new ParseFilter( );
$parse->safe_mode = true;
$parse->allow_url = false;
$parse->allow_image = false;
$stopregistration = FALSE;

if( isset( $_REQUEST['doaction'] ) ) $doaction = $_REQUEST['doaction']; else $doaction = "";
$config['reg_group'] = intval( $config['reg_group'] ) ? intval( $config['reg_group'] ) : 4;

function check_reg($name, $email, $password1, $password2, $sec_code = 1, $sec_code_session = 1) {
	global $lang, $db, $banned_info, $relates_word;
	$stop = "";
	
	if( $sec_code != $sec_code_session OR !$sec_code_session ) $stop .= $lang['reg_err_19'];
	if( $password1 != $password2 ) $stop .= $lang['reg_err_1'];
	if( strlen( $password1 ) < 6 ) $stop .= $lang['reg_err_2'];
	if( strlen( $name ) > 20 ) $stop .= $lang['reg_err_3'];
	if( preg_match( "/[\||\'|\<|\>|\[|\]|\"|\!|\?|\$|\@|\/|\\\|\&\~\*\{\+]/", $name ) ) $stop .= $lang['reg_err_4'];
	if( empty( $email ) OR strlen( $email ) > 50 OR @count(explode("@", $email)) != 2) $stop .= $lang['reg_err_6'];
	if( $name == "" ) $stop .= $lang['reg_err_7'];
	if (strpos( strtolower ($name) , '.php' ) !== false) $stop .= $lang['reg_err_4'];

	
	if( count( $banned_info['name'] ) ) foreach ( $banned_info['name'] as $banned ) {
		
		$banned['name'] = str_replace( '\*', '.*', preg_quote( $banned['name'], "#" ) );
		
		if( $banned['name'] and preg_match( "#^{$banned['name']}$#i", $name ) ) {
			
			if( $banned['descr'] ) {
				$lang['reg_err_21'] = str_replace( "{descr}", $lang['reg_err_22'], $lang['reg_err_21'] );
				$lang['reg_err_21'] = str_replace( "{descr}", $banned['descr'], $lang['reg_err_21'] );
			} else
				$lang['reg_err_21'] = str_replace( "{descr}", "", $lang['reg_err_21'] );
			
			$stop .= $lang['reg_err_21'];
		}
	}
	
	if( count( $banned_info['email'] ) ) foreach ( $banned_info['email'] as $banned ) {
		
		$banned['email'] = str_replace( '\*', '.*', preg_quote( $banned['email'], "#" ) );
		
		if( $banned['email'] and preg_match( "#^{$banned['email']}$#i", $email ) ) {
			
			if( $banned['descr'] ) {
				$lang['reg_err_23'] = str_replace( "{descr}", $lang['reg_err_22'], $lang['reg_err_23'] );
				$lang['reg_err_23'] = str_replace( "{descr}", $banned['descr'], $lang['reg_err_23'] );
			} else
				$lang['reg_err_23'] = str_replace( "{descr}", "", $lang['reg_err_23'] );
			
			$stop .= $lang['reg_err_23'];
		}
	}
	
	if( $stop == "" ) {
		$name = strtolower( $name );
		$search_name = strtr( $name, $relates_word );
		
		$row = $db->super_query( "SELECT COUNT(*) as count FROM " . USERPREFIX . "_users WHERE email = '$email' OR LOWER(name) REGEXP '[[:<:]]{$search_name}[[:>:]]' OR name = '$name'" );
		
		if( $row['count'] ) $stop .= $lang['reg_err_8'];
	}
	
	return $stop;

}

if( $config['allow_registration'] != "yes" ) {
	
	msgbox( $lang['all_info'], $lang['reg_err_9'] );
	$stopregistration = TRUE;

} elseif( $config['max_users'] > 0 ) {

	$row = $db->super_query( "SELECT COUNT(*) as count FROM " . USERPREFIX . "_users" );

	if ( $row['count'] >= $config['max_users'] ) {	
		msgbox( $lang['all_info'], $lang['reg_err_10'] );
		$stopregistration = TRUE;
	}

}

if( isset( $_POST['submit_reg'] ) ) {
	
	if( $config['allow_sec_code'] == "yes" ) {

		if ($config['allow_recaptcha']) {

			require_once ENGINE_DIR . '/classes/recaptcha.php';
			$sec_code = 1;
			$sec_code_session = false;

			if ($_POST['recaptcha_response_field'] AND $_POST['recaptcha_challenge_field']) {
			
				$resp = recaptcha_check_answer ($config['recaptcha_private_key'],
			                                     $_SERVER["REMOTE_ADDR"],
			                                     $_POST['recaptcha_challenge_field'],
			                                     $_POST['recaptcha_response_field']);
			
			        if ($resp->is_valid) {

						$sec_code = 1;
						$sec_code_session = 1;

			        }
			}

		} else {
			$sec_code = $_POST['sec_code'];
			$sec_code_session = ($_SESSION['sec_code_session'] != '') ? $_SESSION['sec_code_session'] : false;
		}

	} else {
		$sec_code = 1;
		$sec_code_session = 1;
	}
	
	$password1 = $_POST['password1'];
	$password2 = $_POST['password2'];
	$name = $db->safesql( $parse->process( htmlspecialchars( trim( $_POST['name'] ) ) ) );
	$name = preg_replace('#\s+#i', ' ', $name);

	$not_allow_symbol = array ("\x22", "\x60", "\t", '\n', '\r', "\n", "\r", '\\', ",", "/", "�", "#", ";", ":", "~", "[", "]", "{", "}", ")", "(", "*", "^", "%", "$", "<", ">", "?", "!", '"', "'", " " );
	$email = $db->safesql(trim( str_replace( $not_allow_symbol, '', strip_tags( stripslashes( $_POST['email'] ) ) ) ) );

	$reg_error = check_reg( $name, $email, $password1, $password2, $sec_code, $sec_code_session );

	if( $config['reg_question'] ) {

		if ( intval($_SESSION['question']) ) {

			$answer = $db->super_query("SELECT id, answer FROM " . PREFIX . "_question WHERE id='".intval($_SESSION['question'])."'");

			$answers = explode( "\n", $answer['answer'] );

			$pass_answer = false;

			if( function_exists('mb_strtolower') ) {
				$question_answer = trim(mb_strtolower($_POST['question_answer'], $config['charset']));
			} else {
				$question_answer = trim(strtolower($_POST['question_answer']));
			}

			if( count($answers) AND $question_answer ) {
				foreach( $answers as $answer ){

					if( function_exists('mb_strtolower') ) {
						$answer = trim(mb_strtolower($answer, $config['charset']));
					} else {
						$answer = trim(strtolower($answer));
					}

					if( $answer AND $answer == $question_answer ) {
						$pass_answer	= true;
						break;
					}
				}
			}

			if( !$pass_answer ) $reg_error .= $lang['reg_err_25'];

		} else $reg_error .= $lang['reg_err_25'];
	
	}
	
	if( ! $reg_error ) {
		
		if( $config['registration_type'] ) {
			
			include_once ENGINE_DIR . '/classes/mail.class.php';
			$mail = new dle_mail( $config );
			
			$row = $db->super_query( "SELECT template FROM " . PREFIX . "_email where name='reg_mail' LIMIT 0,1" );
			
			$row['template'] = stripslashes( $row['template'] );
			
			$idlink = rawurlencode( base64_encode( $name . "||" . $email . "||" . md5( $password1 ) . "||" . md5( sha1( $name . $email . DBHOST . DBNAME . $config['key'] ) ) ) );
			
			$row['template'] = str_replace( "{%username%}", $name, $row['template'] );
			$row['template'] = str_replace( "{%validationlink%}", $config['http_home_url'] . "index.php?do=register&doaction=validating&id=" . $idlink, $row['template'] );
			$row['template'] = str_replace( "{%password%}", $password1, $row['template'] );
			
			$mail->send( $email, $lang['reg_subj'], $row['template'] );
			
			if( $mail->send_error ) msgbox( $lang['all_info'], $mail->smtp_msg );
			else msgbox( $lang['reg_vhead'], $lang['reg_vtext'] );
			
			$_SESSION['sec_code_session'] = false;
			$_SESSION['question'] = false;
			
			$stopregistration = TRUE;
		
		} else {
			
			$doaction = "validating";
			$_REQUEST['id'] = rawurlencode( base64_encode( $name . "||" . $email . "||" . md5( $password1 ) . "||" . md5( sha1( $name . $email . DBHOST . DBNAME . $config['key'] ) ) ) );
		}
	
	} else {
		msgbox( $lang['reg_err_11'], "<ul>" . $reg_error . "</ul>" );
	}

}

if( $doaction != "validating" AND !$stopregistration ) {
	
	if( $_POST['dle_rules_accept'] == "yes" ) {
		
		$_SESSION['dle_rules_accept'] = "1";
	
	}
	
	if( $config['registration_rules'] and ! $_SESSION['dle_rules_accept'] ) {
		
		$_GET['page'] = "dle-rules-page";
		include ENGINE_DIR . '/modules/static.php';
	
	} else {
		
		$tpl->load_template( 'registration.tpl' );
		
		$tpl->set( '[registration]', "" );
		$tpl->set( '[/registration]', "" );
		$tpl->set_block( "'\\[validation\\](.*?)\\[/validation\\]'si", "" );
		$path = parse_url( $config['http_home_url'] );

		if( $config['reg_question'] ) {

			$tpl->set( '[question]', "" );
			$tpl->set( '[/question]', "" );

			$question = $db->super_query("SELECT id, question FROM " . PREFIX . "_question ORDER BY RAND() LIMIT 1");
			$tpl->set( '{question}', htmlspecialchars( stripslashes( $question['question'] ), ENT_QUOTES ) );

			$_SESSION['question'] = $question['id'];

		} else {

			$tpl->set_block( "'\\[question\\](.*?)\\[/question\\]'si", "" );
			$tpl->set( '{question}', "" );

		}
		
		if( $config['allow_sec_code'] == "yes" ) {

			if ( $config['allow_recaptcha'] ) {

				$tpl->set( '[recaptcha]', "" );
				$tpl->set( '[/recaptcha]', "" );

				$tpl->set( '{recaptcha}', '
<script language="javascript" type="text/javascript">
<!--
	var RecaptchaOptions = {
        theme: \''.$config['recaptcha_theme'].'\',
        lang: \''.$lang['wysiwyg_language'].'\'
	};

//-->
</script>
<script type="text/javascript" src="http://www.google.com/recaptcha/api/challenge?k='.$config['recaptcha_public_key'].'"></script>' );

				$tpl->set_block( "'\\[sec_code\\](.*?)\\[/sec_code\\]'si", "" );
				$tpl->set( '{reg_code}', "" );

			} else {

				$tpl->set( '[sec_code]', "" );
				$tpl->set( '[/sec_code]', "" );
				$tpl->set( '{reg_code}', "<span id=\"dle-captcha\"><img src=\"" . $path['path'] . "engine/modules/antibot.php\" alt=\"{$lang['sec_image']}\" /><br /><a onclick=\"reload(); return false;\" href=\"#\">{$lang['reload_code']}</a></span>" );
				$tpl->set_block( "'\\[recaptcha\\](.*?)\\[/recaptcha\\]'si", "" );
				$tpl->set( '{recaptcha}', "" );
			}

		} else {

			$tpl->set( '{reg_code}', "" );
			$tpl->set( '{recaptcha}', "" );
			$tpl->set_block( "'\\[sec_code\\](.*?)\\[/sec_code\\]'si", "" );
			$tpl->set_block( "'\\[recaptcha\\](.*?)\\[/recaptcha\\]'si", "" );
		}
		
		$tpl->copy_template = "<form  method=\"post\" name=\"registration\" onsubmit=\"if (!check_reg_daten()) {return false;};\" id=\"registration\" action=\"\">\n" . $tpl->copy_template . "
<input name=\"submit_reg\" type=\"hidden\" id=\"submit_reg\" value=\"submit_reg\" />
<input name=\"do\" type=\"hidden\" id=\"do\" value=\"register\" />
</form>";
		
		$tpl->copy_template .= <<<HTML
<script language='javascript' type="text/javascript">
<!--
function reload () {

	var rndval = new Date().getTime(); 

	document.getElementById('dle-captcha').innerHTML = '<img src="{$path['path']}engine/modules/antibot.php?rndval=' + rndval + '" width="120" height="50" alt="" /><br /><a onclick="reload(); return false;" href="#">{$lang['reload_code']}</a>';

};
function check_reg_daten () {

	if(document.forms.registration.name.value == '') {

		DLEalert('{$lang['reg_err_30']}', dle_info);return false;

	}

	if(document.forms.registration.password1.value.length < 6) {

		DLEalert('{$lang['reg_err_31']}', dle_info);return false;

	}

	if(document.forms.registration.password1.value != document.forms.registration.password2.value) {

		DLEalert('{$lang['reg_err_32']}', dle_info);return false;

	}

	if(document.forms.registration.email.value == '') {

		DLEalert('{$lang['reg_err_33']}', dle_info);return false;

	}

return true;

};
//-->
</script>
HTML;
		$tpl->compile( 'content' );
		$tpl->clear();
	
	}

}

if( isset( $_POST['submit_val'] ) ) {
	
	$fullname = $db->safesql( $parse->process( $_POST['fullname'] ) );
	$land = $db->safesql( $parse->process( $_POST['land'] ) );
	$icq = intval( str_replace("-", "", $_POST['icq'] ) );
	if( $icq < 1 ) $icq = "";
	$info = $db->safesql( $parse->BB_Parse( $parse->process( $_POST['info'] ), false ) );
	
	$image = $_FILES['image']['tmp_name'];
	$image_name = $_FILES['image']['name'];
	$image_size = $_FILES['image']['size'];
	$image_name = str_replace( " ", "_", $image_name );
	$img_name_arr = explode( ".", $image_name );
	$type = totranslit( end( $img_name_arr ) );

	if( stripos ( $image_name, "php" ) !== false ) die("Hacking attempt!");	

	$user_arr = explode( "||", base64_decode( @rawurldecode( $_POST['id'] ) ) );

	if( $user_arr[0] == "" OR  $user_arr[2]== "" ) die("Hacking attempt!");

	$user = $db->safesql( trim( $user_arr[0] ) );
	$email = $db->safesql( trim( $user_arr[1] ) );
	$pass = md5( $user_arr[2] );

	if( md5( sha1( $user . $email . DBHOST . DBNAME . $config['key'] ) ) != $user_arr[3] ) die( 'ID not valid!' );

	if( preg_match( "/[\||\'|\<|\>|\[|\]|\"|\!|\?|\$|\@|\/|\\\|\&\~\*\{\+]/", $user ) ) die( 'USER not valid!' );

	$row = $db->super_query( "SELECT * FROM " . USERPREFIX . "_users WHERE name = '$user' AND password='$pass'" );
	
	if( !$row['user_id'] ) die("Access Denied!");
	
	$db->free();
	
	if( is_uploaded_file( $image ) and ! $stop ) {

		if( intval( $user_group[$member_id['user_group']]['max_foto'] ) > 0 ) {
		
			if( $image_size < 100000 ) {
				
				$allowed_extensions = array ("jpg", "png", "jpe", "jpeg", "gif" );
				
				if( in_array( $type, $allowed_extensions ) AND $image_name ) {
					
					include_once ENGINE_DIR . '/classes/thumb.class.php';
					
					$res = @move_uploaded_file( $image, ROOT_DIR . "/uploads/fotos/" . $row['user_id'] . "." . $type );
					
					if( $res ) {

						@chmod( ROOT_DIR . "/uploads/fotos/" . $row['user_id'] . "." . $type, 0666 );
						$thumb = new thumbnail( ROOT_DIR . "/uploads/fotos/" . $row['user_id'] . "." . $type );

						if( $thumb->size_auto( $user_group[$member_id['reg_group']]['max_foto'] ) ) {
								$thumb->jpeg_quality( $config['jpeg_quality'] );
								$thumb->save( ROOT_DIR . "/uploads/fotos/foto_" . $row['user_id'] . "." . $type );
						} else {
							if($type == "gif" ) {
								@rename( ROOT_DIR . "/uploads/fotos/" . $row['user_id'] . "." . $type, ROOT_DIR . "/uploads/fotos/foto_" . $row['user_id'] . "." . $type );
							} else {
								$thumb->jpeg_quality( $config['jpeg_quality'] );
								$thumb->save( ROOT_DIR . "/uploads/fotos/foto_" . $row['user_id'] . "." . $type );
							}
						}
						
						@unlink( ROOT_DIR . "/uploads/fotos/" . $row['user_id'] . "." . $type );
						$foto_name = "foto_" . $row['user_id'] . "." . $type;
						
						$db->query( "UPDATE " . USERPREFIX . "_users SET foto='$foto_name' WHERE user_id='{$row['user_id']}'" );
					
					} else
						$stop = $lang['reg_err_12'];
				} else
					$stop = $lang['reg_err_13'];
			} else
				$stop = $lang['news_err_16'];
		} else
			$stop .= $lang['news_err_32'];

	}
	
	if( intval( $user_group[$member_id['user_group']]['max_info'] ) > 0 and dle_strlen( $info, $config['charset'] ) > $user_group[$member_id['user_group']]['max_info'] ) $stop .= $lang['reg_err_14'];
	if( dle_strlen( $fullname, $config['charset'] ) > 100 ) $stop .= $lang['reg_err_15'];
	if( dle_strlen( $land, $config['charset'] ) > 100 ) $stop .= $lang['reg_err_16'];
	if( strlen( $icq ) > 20 ) $stop .= $lang['reg_err_17'];
	if( $parse->not_allowed_tags ) $stop .= $lang['news_err_34'];

	if ( preg_match( "/[\||\'|\<|\>|\"|\!|\]|\?|\$|\@|\/|\\\|\&\~\*\+]/", $fullname ) ) {

		$stop .= $lang['news_err_35'];
	}

	if ( preg_match( "/[\||\'|\<|\>|\"|\!|\]|\?|\$|\@|\/|\\\|\&\~\*\+]/", $land ) ) {

		$stop .= $lang['news_err_36'];
	}
	
	if( $stop ) {
		msgbox( $lang['reg_err_18'], $stop );
	} else {
		
		$xfieldsaction = "init";
		$xfieldsadd = true;
		$xfieldsid = "";
		include (ENGINE_DIR . '/inc/userfields.php');
		$filecontents = array ();
		
		if( ! empty( $postedxfields ) ) {
			foreach ( $postedxfields as $xfielddataname => $xfielddatavalue ) {
				if( ! $xfielddatavalue ) {
					continue;
				}
				
				$xfielddatavalue = $db->safesql( $parse->BB_Parse( $parse->process( $xfielddatavalue ), false ) );
				
				$xfielddataname = $db->safesql( $xfielddataname );
				
				$xfielddataname = str_replace( "|", "&#124;", $xfielddataname );
				$xfielddatavalue = str_replace( "|", "&#124;", $xfielddatavalue );
				$filecontents[] = "$xfielddataname|$xfielddatavalue";
			}
			
			$filecontents = implode( "||", $filecontents );
		} else
			$filecontents = '';
		
		$db->query( "UPDATE " . USERPREFIX . "_users SET fullname='$fullname', info='$info', land='$land', icq='$icq', xfields='$filecontents' WHERE user_id='{$row['user_id']}'" );
		
		msgbox( $lang['reg_ok'], $lang['reg_ok_1'] );
		
		$stopregistration = TRUE;
	}
}

if( $doaction == "validating" AND !$stopregistration AND !$_POST['submit_val'] ) {
	
	$user_arr = explode( "||", base64_decode( @rawurldecode( $_REQUEST['id'] ) ) );
	
	$regpassword = md5( $user_arr[2] );
	$name = trim( $db->safesql( htmlspecialchars( $parse->process( $user_arr[0] ) ) ) );

	$not_allow_symbol = array ("\x22", "\x60", "\t", '\n', '\r', "\n", "\r", '\\', ",", "/", "�", "#", ";", ":", "~", "[", "]", "{", "}", ")", "(", "*", "^", "%", "$", "<", ">", "?", "!", '"', "'", " " );
	$email = $db->safesql(trim( str_replace( $not_allow_symbol, '', strip_tags( stripslashes( $user_arr[1] ) ) ) ) );
	
	if( md5( sha1( $name . $email . DBHOST . DBNAME . $config['key'] ) ) != $user_arr[3] ) die( 'ID not valid!' );

	$name = preg_replace('#\s+#i', ' ', $name);	
	$reg_error = check_reg( $name, $email, $regpassword, $regpassword );
	
	if( $reg_error != "" ) {
		msgbox( $lang['reg_err_11'], $reg_error );
		$stopregistration = TRUE;
	} else {
		
		if( ($_REQUEST['step'] != 2) and $config['registration_type'] ) {
			$stopregistration = TRUE;
			$lang['confirm_ok'] = str_replace( '{email}', $email, $lang['confirm_ok'] );
			$lang['confirm_ok'] = str_replace( '{login}', $name, $lang['confirm_ok'] );
			msgbox( $lang['all_info'], $lang['confirm_ok'] . "<br /><br /><a href=\"" . $config['http_home_url'] . "index.php?do=register&doaction=validating&step=2&id=" . rawurlencode( $_REQUEST['id'] ) . "\">" . $lang['reg_next'] . "</a>" );
		} else {
			
			$add_time = time() + ($config['date_adjust'] * 60);
			$_IP = $db->safesql( $_SERVER['REMOTE_ADDR'] );
			if( intval( $config['reg_group'] ) < 3 ) $config['reg_group'] = 4;
			
			$db->query( "INSERT INTO " . USERPREFIX . "_users (name, password, email, reg_date, lastdate, user_group, info, signature, favorites, xfields, logged_ip) VALUES ('$name', '$regpassword', '$email', '$add_time', '$add_time', '" . $config['reg_group'] . "', '', '', '', '', '" . $_IP . "')" );
			$id = $db->insert_id();
			
			set_cookie( "dle_user_id", $id, 365 );
			set_cookie( "dle_password", $user_arr[2], 365 );
			
			$_SESSION['dle_user_id'] = $id;
			$_SESSION['dle_password'] = $user_arr[2];
		
		}
	
	}

}

if( $doaction == "validating" and ! $stopregistration ) {
	
	$tpl->load_template( 'registration.tpl' );
	
	$tpl->set( '[validation]', "" );
	$tpl->set( '[/validation]', "" );
	$tpl->set_block( "'\\[registration\\].*?\\[/registration\\]'si", "" );
	
	$xfieldsaction = "list";
	$xfieldsadd = true;
	include (ENGINE_DIR . '/inc/userfields.php');
	$tpl->set( '{xfields}', $output );

	$_REQUEST['id'] = htmlspecialchars( $_REQUEST['id'], ENT_QUOTES );

	$tpl->copy_template = "<form  method=\"post\" name=\"registration\" enctype=\"multipart/form-data\" action=\"\">\n" . $tpl->copy_template . "
<input name=\"submit_val\" type=\"hidden\" id=\"submit_val\" value=\"submit_val\" />
<input name=\"do\" type=\"hidden\" id=\"do\" value=\"register\" />
<input name=\"doaction\" type=\"hidden\" id=\"doaction\" value=\"validating\" />
<input name=\"id\" type=\"hidden\" id=\"id\" value=\"{$_REQUEST['id']}\" />
</form>";
	
	$tpl->compile( 'content' );
	$tpl->clear();
}

?>