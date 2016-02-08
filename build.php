#!/usr/local/bin/php
<?php
if (php_sapi_name() !== 'cli') {
    return;
}
date_default_timezone_set('Europe/Paris');
require_once 'vendor/autoload.php';
use PHPoole\PHPoole;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use GitWrapper\GitWrapper;

$getopt = getopt('e::p::');

$options = Yaml::parse(file_get_contents('.phpoole'));
$options_dev = [
    'site' => [
        'baseurl' => 'http://localhost:8000',
    ],
];

$prod = (isset($getopt['e']) && $getopt['e'] == 'prod') ? true : false;
$options = (!$prod) ? array_replace_recursive($options, $options_dev) : $options;

$phpoole = new PHPoole('./', null, $options);
$phpoole->build();

// run server
if (!$prod) {
    echo "Start server http://localhost:8000\n";
    echo "Ctrl-C to stop it\n";
    exec('php -S localhost:8000 -t _site');

} else {
// publish?
    if (isset($getopt['p'])) {
        echo "Publishing...\n";
        //
        $branch = $options['github']['branch'];
        $tmpDirectory = tempnam(sys_get_temp_dir(), 'phpoole_publish_');
        //
        $filesystem = new Filesystem();
        $filesystem->remove($tmpDirectory);
        $filesystem->mkdir($tmpDirectory);
        //
        $wrapper = new GitWrapper();
        $git = $wrapper->clone("git@github.com:{$options['github']['username']}/{$options['github']['repository']}.git", $tmpDirectory);
        $git->config('user.name', $options['github']['username'])
            ->config('user.email', $options['github']['email']);
        $git->checkout($branch);
        //
        $finder = new Finder();
        $finder->files()
            ->in($tmpDirectory)
            ->ignoreVCS(true);
        $filesystem->remove($finder);
        $filesystem->mirror('_site', $tmpDirectory);
        //
        if ($git->hasChanges()) {
            $git->add('*')
                ->commit('Website generated with PHPoole.')
                ->push();
            echo "Done!\n";
        } else {
            echo "Nothing to do!\n";
        }
        //
        $filesystem->remove($tmpDirectory);
    }
}