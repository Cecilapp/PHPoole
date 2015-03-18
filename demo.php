<?php
require_once 'vendor/autoload.php';

use PHPoole\PHPoole;

$phpoole = new PHPoole('./demo', null, ['theme' => 'hyde']);
$phpoole->build();
