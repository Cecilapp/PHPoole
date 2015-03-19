<?php
require_once 'vendor/autoload.php';

use PHPoole\PHPoole;

$phpoole = new PHPoole('./demo', null, [
    'site' => [
        'title'       => "Narno's blog",
        'baseline'    => 'Mon super site !',
        'baseurl'     => 'http://localhost:63342/PHPoole-library/demo/_site/',
        'description' => "Mon super site qu'il est trop bien, wahou !\n Mangez des pommes.",
    ],
    'theme' => 'hyde'
]);
$phpoole->build();
