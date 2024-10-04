<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$active_group = 'default';
$query_builder = TRUE;

$hostName = 'localhost';
$userName = 'alpha';
$password = '1lh5nz7uwqtkjutn8akd';
$dbName = 'gcap_api';

$db['default'] = array(
    'dsn'   => '',
    'hostname' => $hostName,
    'username' => $userName,
    'password' => $password,
    'database' => $dbName,
    'dbdriver' => 'mysqli',
    'dbprefix' => '',
    'pconnect' => FALSE,
    'db_debug' => (ENVIRONMENT !== 'production'),
    'cache_on' => FALSE,
    'cachedir' => '',
    'char_set' => 'utf8',
    'dbcollat' => 'utf8_general_ci',
    'swap_pre' => '',
    'encrypt' => FALSE,
    'compress' => FALSE,
    'stricton' => FALSE,
    'failover' => array(),
    'save_queries' => TRUE
);
