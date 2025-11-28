<?php

use Doctum\Doctum;
use Doctum\RemoteRepository\GitHubRemoteRepository;
use Symfony\Component\Finder\Finder;

$dir = __DIR__;

$iterator = Finder::create()
    ->files()
    ->name('*.php')
    ->exclude('vendor')
    ->exclude('node_modules')
    ->exclude('build')
    ->exclude('dist')
    ->exclude('demo')
    ->exclude('examples')
    ->exclude('scripts')
    ->exclude('tests')
    ->in($dir);

return new Doctum($iterator, [
    'title'                => 'ConjureWP API Documentation',
    'language'             => 'en',
    'build_dir'            => __DIR__ . '/docs-api/build',
    'cache_dir'            => __DIR__ . '/docs-api/cache',
    'source_dir'           => $dir . '/',
    'remote_repository'    => new GitHubRemoteRepository('jakehenshall/ConjureWP', $dir),
    'default_opened_level' => 2,
    'footer_link'          => [
        'href'        => 'https://github.com/code-lts/doctum',
        'rel'         => 'noreferrer noopener',
        'target'      => '_blank',
        'before_text' => 'Documentation generated with',
        'link_text'   => 'Doctum',
        'after_text'  => '',
    ],
]);


