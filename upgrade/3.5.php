<?php

if( ! defined( 'DATALIFEENGINE' ) ) {
	die( "Hacking attempt!" );
}


function banned (){
global $db;

$result_banned = $db->query("SELECT user_id FROM " . PREFIX . "_users WHERE banned = 'yes'");

	while($row = $db->get_array($result_banned)){
	$db->query("INSERT INTO " . PREFIX . "_banned (users_id, descr, date, days) values ('$row[user_id]', '', '0', '0')");
	}
}

$config = <<<HTML
<?PHP

//System Configurations

\$config_version_name = "$config_version_name";

\$config_version_id = "3.7";

\$config_home_title = "$config_home_title";

\$config_http_home_url = "$config_http_home_url";

\$config_admin_mail = "$config_admin_mail";

\$config_langs = "$config_langs";

\$config_skin = "$config_skin";

\$config_auto_wrap = "$config_auto_wrap";

\$config_flood_time = "$config_flood_time";

\$config_smilies = "$config_smilies";

\$config_date_adjust = "$config_date_adjust";

\$config_timestamp_active = "$config_timestamp_active";

\$config_allow_gzip = "$config_allow_gzip";

\$config_hide_full_link = "$config_hide_full_link";

\$config_only_registered_comment = "$config_only_registered_comment";

\$config_allow_edit_comment = "$config_allow_edit_comment";

\$config_allow_delete_comment = "$config_allow_delete_comment";

\$config_allow_url_instead_mail = "$config_allow_url_instead_mail";

\$config_timestamp_comment = "$config_timestamp_comment";

\$config_allow_alt_url = "$config_allow_alt_url";

\$config_allow_fixed_news = "$config_allow_fixed_news";

\$config_allow_calendar = "$config_allow_calendar";

\$config_allow_archives = "$config_allow_archives";

\$config_allow_registration = "$config_allow_registration";

\$config_registration_type = "$config_registration_type";

\$config_max_users = "$config_max_users";

\$config_max_users_day = "$config_max_users_day";

\$config_max_foto = "$config_max_foto";

\$config_allow_watermark = "$config_allow_watermark";

\$config_max_watermark = "$config_max_watermark";

\$config_max_image = "$config_max_image";

\$config_jpeg_quality = "$config_jpeg_quality";

\$config_allow_admin_wysiwyg = "$config_allow_admin_wysiwyg";

\$config_allow_site_wysiwyg = "$config_allow_site_wysiwyg";

\$config_allow_comments_wysiwyg = "$config_allow_comments_wysiwyg";

\$config_smiles_nummer = "$config_smiles_nummer";

\$config_allow_read_count = "$config_allow_read_count";

\$config_allow_upload = "$config_allow_upload";

\$config_max_up_size = "$config_max_up_size";

\$config_max_image_days = "$config_max_image_days";

\$config_allow_cache = "$config_allow_cache";

\$config_news_number = "$config_news_number";

\$config_allow_votes = "$config_allow_votes";

\$config_allow_topnews = "$config_allow_topnews";

\$config_allow_skin_change = "$config_allow_skin_change";

\$config_site_offline = "$config_site_offline";

\$config_news_sort = "$config_news_sort";

\$config_news_msort = "$config_news_msort";

\$config_allow_sec_code = "$config_allow_sec_code";

\$config_comm_nummers = "$config_comm_nummers";

\$config_comm_msort = "$config_comm_msort";

\$config_charset = "$config_charset";

?>
HTML;


$con_file = fopen(ENGINE_DIR.'/data/config.php', "w") or die("Извините, но невозможно записать информацию в файл <b>.engine/data/config.php</b>.<br />Проверьте правильность проставленного CHMOD!");
fwrite($con_file, $config);
fclose($con_file);

$tableSchema = array();

$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_banned";
$tableSchema[] = "CREATE TABLE " . PREFIX . "_banned (
  `id` smallint(5) NOT NULL auto_increment,
  `users_id` mediumint(8) NOT NULL default '0',
  `descr` text NOT NULL,
  `date` varchar(20) NOT NULL default '',
  `days` smallint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `user_id` (`users_id`)
) TYPE=MyISAM /*!40101 DEFAULT CHARACTER SET cp1251 COLLATE cp1251_general_ci */";

           foreach($tableSchema as $table) {
           $db->query ($table);
           }

banned ();

msgbox("info","Информация", "<form action=\"index.php\" method=\"GET\">Обновление базы данных с версии <b>3.5</b> до версии <b>3.7</b> успешно завершено.<br />Нажмите далее для продолжения процессa обновления скрипта<br /><br /><input type=\"hidden\" name=\"next\" value=\"3.7\"><input class=\"edit\" type=\"submit\" value=\"Далее ...\"></form>");
?>