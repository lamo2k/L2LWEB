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
 ����: mysql.php
-----------------------------------------------------
 ����������: ����� ��� ������ � ����� ������
=====================================================
*/

if(!defined('DATALIFEENGINE'))
{
  die("Hacking attempt!");
}

if ( extension_loaded('mysqli') )
{
	include_once( ENGINE_DIR."/classes/mysqli.class.php" );
}
else
{
	include_once( ENGINE_DIR."/classes/mysql.class.php" );
}

?>