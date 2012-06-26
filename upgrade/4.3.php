<?php

if( ! defined( 'DATALIFEENGINE' ) ) {
	die( "Hacking attempt!" );
}


$config = <<<HTML
<?PHP

//System Configurations

\$config = array (

'version_id' => "4.5",
'home_title' => "{$config['home_title']}",
'http_home_url' => "{$config['http_home_url']}",
'charset' => "{$config['charset']}",
'admin_mail' => "{$config['admin_mail']}",
'description' => "{$config['description']}",
'keywords' => "{$config['keywords']}",
'date_adjust' => "{$config['date_adjust']}",
'site_offline' => "{$config['site_offline']}",
'allow_alt_url' => "{$config['allow_alt_url']}",
'langs' => "{$config['langs']}",
'skin' => "{$config['skin']}",
'allow_gzip' => "{$config['allow_gzip']}",
'allow_admin_wysiwyg' => "{$config['allow_admin_wysiwyg']}",
'allow_static_wysiwyg' => "{$config['allow_static_wysiwyg']}",
'news_number' => "{$config['news_number']}",
'meta_generator' => "{$config['meta_generator']}",
'smilies' => "{$config['smilies']}",
'timestamp_active' => "{$config['timestamp_active']}",
'news_sort' => "{$config['news_sort']}",
'news_msort' => "{$config['news_msort']}",
'hide_full_link' => "{$config['hide_full_link']}",
'allow_site_wysiwyg' => "{$config['allow_site_wysiwyg']}",
'allow_comments' => "{$config['allow_comments']}",
'allow_url_instead_mail' => "{$config['allow_url_instead_mail']}",
'comm_nummers' => "{$config['comm_nummers']}",
'comm_msort' => "{$config['comm_msort']}",
'smiles_nummer' => "{$config['smiles_nummer']}",
'flood_time' => "{$config['flood_time']}",
'auto_wrap' => "{$config['auto_wrap']}",
'timestamp_comment' => "{$config['timestamp_comment']}",
'allow_comments_wysiwyg' => "{$config['allow_comments_wysiwyg']}",
'allow_registration' => "{$config['allow_registration']}",
'allow_cache' => "{$config['allow_cache']}",
'allow_votes' => "{$config['allow_votes']}",
'allow_topnews' => "{$config['allow_topnews']}",
'allow_read_count' => "{$config['allow_read_count']}",
'allow_calendar' => "{$config['allow_calendar']}",
'allow_archives' => "{$config['allow_archives']}",
'files_allow' => "{$config['files_allow']}",
'files_type' => "{$config['files_type']}",
'files_count' => "{$config['files_count']}",
'registration_type' => "{$config['registration_type']}",
'allow_sec_code' => "{$config['allow_sec_code']}",
'allow_skin_change' => "{$config['allow_skin_change']}",
'max_users' => "{$config['max_users']}",
'max_users_day' => "{$config['max_users_day']}",
'allow_upload' => "{$config['allow_upload']}",
'max_up_size' => "{$config['max_up_size']}",
'max_image_days' => "{$config['max_image_days']}",
'allow_watermark' => "{$config['allow_watermark']}",
'max_watermark' => "{$config['max_watermark']}",
'max_image' => "{$config['max_image']}",
'jpeg_quality' => "{$config['jpeg_quality']}",
'reg_group' => "{$config['reg_group']}",
'files_antileech' => "{$config['files_antileech']}",

);

?>
HTML;

$con_file = fopen(ENGINE_DIR.'/data/config.php', "w") or die("Извините, но невозможно записать информацию в файл <b>.engine/data/config.php</b>.<br />Проверьте правильность проставленного CHMOD!");
fwrite($con_file, $config);
fclose($con_file);

		@unlink(ENGINE_DIR.'/cache/system/usergroup.php');

$tableSchema = array();

$tableSchema[] = "ALTER TABLE `" . PREFIX . "_usergroups` ADD `allow_feed` TINYINT( 1 ) DEFAULT '1' NOT NULL";
$tableSchema[] = "ALTER TABLE `" . PREFIX . "_usergroups` ADD `allow_search` TINYINT( 1 ) DEFAULT '1' NOT NULL";
$tableSchema[] = "ALTER TABLE `" . PREFIX . "_static` ADD `grouplevel` VARCHAR( 100 ) DEFAULT 'all' NOT NULL";
$tableSchema[] = "ALTER TABLE `" . PREFIX . "_post` ADD INDEX ( `approve` )";
$tableSchema[] = "ALTER TABLE `" . PREFIX . "_post` ADD INDEX ( `date` )";
$tableSchema[] = "ALTER TABLE `" . PREFIX . "_post` ADD INDEX ( `allow_main` )";

           foreach($tableSchema as $table) {
           $db->query ($table);
           }

msgbox("info","Информация", "<form action=\"index.php\" method=\"GET\">Обновление базы данных с версии <b>4.3</b> до версии <b>4.5</b> успешно завершено.<br />Нажмите далее для продолжения процессa обновления скрипта<br /><br /><input type=\"hidden\" name=\"next\" value=\"4.5\"><input class=\"edit\" type=\"submit\" value=\"Далее ...\"></form>");
?>