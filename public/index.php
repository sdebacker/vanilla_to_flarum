<?php

$timestamp_start = microtime(true);

require '../vendor/autoload.php';
require 'function.php';

$dotenv = Dotenv\Dotenv::createImmutable('../');
$dotenv->load();

// INI CONFIG
// ------------------------------

set_time_limit(0);
ini_set('memory_limit', -1);
ini_set('display_errors', 'On');
ini_set('error_reporting', 'E_ALL');
ini_set('log_errors', 'On');
ini_set('error_log', '/scripts/logs/migrate.log');

error_reporting(E_ALL & ~E_NOTICE);

// ENVIRONMENT VARIABLES
// ------------------------------

$dbHost = getenv('DB_HOST');
$dbVanillaUser = getenv('DB_VANILLA_USER');
$dbVanillaName = getenv('DB_VANILLA_NAME');
$dbVanillaPass = getenv('DB_VANILLA_PASS');
$dbFlarumUser = getenv('DB_FLARUM_USER');
$dbFlarumName = getenv('DB_FLARUM_NAME');
$dbFlarumPass = getenv('DB_FLARUM_PASS');
$dbVanillaPrefix = getenv('DB_VANILLA_PREFIX');
$dbFlarumPrefix = getenv('DB_FLARUM_PREFIX');

$mailFrom = getenv('MAILFROM');
$mailHost = getenv('MAILHOST');
$mailPort = getenv('MAILPORT');
$mailEncr = getenv('MAILENCR');
$mailUser = getenv('MAILUSER');
$mailPass = getenv('MAILPASS');

WriteInLog('------------------- STARTING MIGRATION PROCESS -------------------');

try {
    $dbVanilla = new PDO("mysql:host=$dbHost;dbname=$dbVanillaName;charset=utf8", $dbVanillaUser, $dbVanillaPass);
    $dbFlarum = new PDO("mysql:host=$dbHost;dbname=$dbFlarumName;charset=utf8", $dbFlarumUser, $dbFlarumPass);
    // Enabling PDO exceptions
    $dbVanilla->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $dbFlarum->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    WriteInLog($e, 'ERROR');
    die('/!\ An error occurred while connecting to the databases');
}

WriteInLog('Connected successfully to the databases !');

RunQuery($dbFlarum, 'SET FOREIGN_KEY_CHECKS = 0');
RunQuery($dbFlarum, "TRUNCATE TABLE `${dbFlarumPrefix}users`");
RunQuery($dbFlarum, "TRUNCATE TABLE `${dbFlarumPrefix}tags`");
RunQuery($dbFlarum, "TRUNCATE TABLE `${dbFlarumPrefix}discussions`");
RunQuery($dbFlarum, "TRUNCATE TABLE `${dbFlarumPrefix}discussion_tag`");
RunQuery($dbFlarum, "TRUNCATE TABLE `${dbFlarumPrefix}discussion_user`");
RunQuery($dbFlarum, "TRUNCATE TABLE `${dbFlarumPrefix}posts`");
RunQuery($dbFlarum, "TRUNCATE TABLE `${dbFlarumPrefix}groups`");
RunQuery($dbFlarum, "TRUNCATE TABLE `${dbFlarumPrefix}group_user`");
RunQuery($dbFlarum, 'SET FOREIGN_KEY_CHECKS = 1');

require 'importer/users.php';
require 'importer/categories.php';
require 'importer/discussions-posts.php';
require 'importer/groups.php';
// require 'importer/misc.php';

$timestamp_end = microtime(true);
$diff = $timestamp_end - $timestamp_start;
$min = floor($diff / 60);
$sec = floor($diff - $min * 60);

WriteInLog("---------------------- END OF MIGRATION (time : $min min $sec sec) ----------------------");
