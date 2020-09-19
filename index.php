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
$caller->sort('id', 'DESC');

echo '<pre>';
print_r("----- Whole Response -----\n");
print_r($caller->get());

print_r("----- Only login and node_id fields -----\n");
print_r($caller->only(['login', 'node_id']));
?>