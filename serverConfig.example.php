<?php
/*********************************************************************************
*Server Config Example
*
*Make sure this file is in the same folder as update.php and is called serverConfig.php
*The update script works with EITHER the cPanel password OR a cPanel API token
*If both password and API token are provided the API token is used
*The username is required for both methods of authentication
*********************************************************************************/
//cPanel API user, password, server, and port
$cpUser = 'cpanelusername';
$cpPassword = 'cpanelpassword';
$cpToken='cpanelapitoken';
$cpServer = 'server.example.com';
$cpPort = '2083';

//Authorized ddns users, passwords, and hostnames
$authUsers=array('user1','user2');
$authPasswords=array('user1password','user2password');
$authHostnames=array('user1host.example.net','user2host.example.net');
