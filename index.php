<?php

namespace App;
require_once('./vendor/autoload.php');

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$caller = new Caller;
$caller->make('https://api.github.com/users', 'get');
$caller->root();
$caller->where('login', '=', 'mojombo');


?>