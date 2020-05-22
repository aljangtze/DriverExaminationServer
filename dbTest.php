<?php
require __DIR__ . '/db.php';
$db = new DB();
$db->__setup([
    'dsn'=>'sqlite:data.db',
    'username'=>null,
    'password'=>null,
    'charset'=>'utf8'
]);
print_r($db->fetch("select * from groups"));
print_r($db->query("select count(1) from groups;"));
//sqlite:php_sqlite_pdo.db

print_r($db->errorInfo());
?>