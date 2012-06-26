<?php

if( ! defined( 'DATALIFEENGINE' ) ) {
	die( "Hacking attempt!" );
}

$config['version_id'] = "8.3";
$config['allow_combine'] = "1";
$config['allow_subscribe'] = "0";

$tableSchema = array();

$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_subscribe";
$tableSchema[] = "CREATE TABLE " . PREFIX . "_subscribe (
  `id` int(11) NOT NULL auto_increment,
  `user_id` int(11) NOT NULL default '0',
  `name` varchar(40) NOT NULL default '',
  `email`  varchar(50) NOT NULL default '',
  `news_id` int(11) NOT NULL default '0',
  `hash` varchar(32) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `news_id` (`news_id`),
  KEY `user_id` (`user_id`) 
) TYPE=MyISAM /*!40101 DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci */";


$tableSchema[] = "UPDATE " . PREFIX . "_email set template='Уважаемый {%username_to%},\r\n\r\nуведомляем вас о том, что на сайт  {$config['http_home_url']} был добавлен комментарий к новости, на которую вы были подписаны.\r\n\r\n------------------------------------------------\r\nКраткая информация о комментарии\r\n------------------------------------------------\r\n\r\nАвтор: {%username%}\r\nДата добавления: {%date%}\r\nСсылка на новость: {%link%}\r\n\r\n------------------------------------------------\r\nТекст комментария\r\n------------------------------------------------\r\n\r\n{%text%}\r\n\r\n------------------------------------------------\r\n\r\nЕсли вы не хотите больше получать уведомлений о новых комментариях к данной новости, то проследуйте по данной ссылке: {%unsubscribe%}\r\n\r\nС уважением,\r\n\r\nАдминистрация {$config['http_home_url']}' WHERE name='comments'";
$tableSchema[] = "ALTER TABLE `" . PREFIX . "_usergroups` CHANGE `max_foto` `max_foto` VARCHAR( 10 ) NOT NULL DEFAULT ''";
$tableSchema[] = "ALTER TABLE `" . PREFIX . "_usergroups` ADD `allow_html` TINYINT( 1 ) NOT NULL DEFAULT '1'";
$tableSchema[] = "ALTER TABLE `" . PREFIX . "_usergroups` ADD `group_prefix` TEXT NOT NULL";
$tableSchema[] = "ALTER TABLE `" . PREFIX . "_usergroups` ADD `group_suffix` TEXT NOT NULL";
$tableSchema[] = "ALTER TABLE `" . PREFIX . "_usergroups` ADD `allow_subscribe` TINYINT( 1 ) NOT NULL DEFAULT '0'";
$tableSchema[] = "UPDATE " . PREFIX . "_usergroups SET allow_html='0' WHERE id='5'";
$tableSchema[] = "ALTER TABLE `" . PREFIX . "_vote` DROP INDEX `category`";
$tableSchema[] = "ALTER TABLE `" . PREFIX . "_vote` ADD INDEX ( `approve` )";
$tableSchema[] = "ALTER TABLE `" . PREFIX . "_vote` CHANGE `category` `category` TEXT NOT NULL";

foreach($tableSchema as $table) {
	$db->query ($table);
}


$handler = fopen(ENGINE_DIR.'/data/config.php', "w") or die("Извините, но невозможно записать информацию в файл <b>.engine/data/config.php</b>.<br />Проверьте правильность проставленного CHMOD!");
fwrite($handler, "<?PHP \n\n//System Configurations\n\n\$config = array (\n\n");
foreach($config as $name => $value)
{
	fwrite($handler, "'{$name}' => \"{$value}\",\n\n");
}
fwrite($handler, ");\n\n?>");
fclose($handler);


$video_config = <<<HTML
<?PHP

//Videoplayers Configurations

\$video_config = array (

'width' => "425",

'height' => "325",

'play' => "false",

'backgroundBarColor' => "0x1A1A1A",

'btnsColor' => "0xFFFFFF",

'outputTxtColor' => "0x999999",

'outputBkgColor' => "0x1A1A1A",

'loadingBarColor' => "0x666666",

'loadingBackgroundColor' => "0xCCCCCC",

'progressBarColor' => "0x000000",

'volumeStatusBarColor' => "0x000000",

'volumeBackgroundColor' => "0x666666",

);

?>
HTML;

$con_file = fopen(ENGINE_DIR.'/data/videoconfig.php', "w+") or die("Извините, но невозможно создать файл <b>.engine/data/videoconfig.php</b>.<br />Проверьте правильность проставленного CHMOD!");
fwrite($con_file, $video_config);
fclose($con_file);
@chmod("engine/data/videoconfig.php", 0666);

$fdir = opendir( ENGINE_DIR . '/cache/system/' );
while ( $file = readdir( $fdir ) ) {
	if( $file != '.' and $file != '..' and $file != '.htaccess' ) {
		@unlink( ENGINE_DIR . '/cache/system/' . $file );
		
	}
}

@unlink(ENGINE_DIR.'/data/snap.db');

clear_cache();

if ($db->error_count) $error_info = "Всего запланировано запросов: <b>".$db->query_num."</b> Неудалось выполнить запросов: <b>".$db->error_count."</b>. Возможно они уже выполнены ранее."; else $error_info = "";

msgbox("info","Информация", "<form action=\"index.php\" method=\"GET\">Обновление базы данных с версии <b>8.2</b> до версии <b>8.3</b> успешно завершено.<br />{$error_info}<br />Нажмите далее для продолжения процессa обновления скрипта<br /><br /><input type=\"hidden\" name=\"next\" value=\"8.3\"><input class=\"edit\" type=\"submit\" value=\"Далее ...\"></form>");
?>