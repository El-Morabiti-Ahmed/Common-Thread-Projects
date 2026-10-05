<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/classes.php';
(new Api())->handle();
