<?php
if (php_sapi_name() !== 'cli') {
    return;
}
require_once 'vendor/autoload.php';
use PHPoole\PHPoole;

$getopt = getopt('e::');

$options_dev = [
    'site' => [
        'title'   => "PHPoole",
        'baseurl' => 'http://localhost:8000/',
    ],
    'frontmatter' => [
        'format' => 'ini'
    ],
];
$options_prod = array_replace_recursive($options_dev, [
    'site' => [
        'baseurl' => 'http://narno.org/PHPoole-library',
    ],
]);

$prod = (isset($getopt['e']) && $getopt['e'] == 'prod') ? true : false;
$options = ($prod) ? $options_prod : $options_dev;
$phpoole = new PHPoole('./', null, $options);
$phpoole->build();

if (!$prod) {
    exec('php -S localhost:8000 -t _site');
}