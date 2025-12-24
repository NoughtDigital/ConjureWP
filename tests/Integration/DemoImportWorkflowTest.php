<?php

test('demo content files exist in demo directory', function () {
    $demoDir = getPluginPath('demo');
    
    expect(is_dir($demoDir))->toBeTrue();
    
    // Check for demo content files
    $expectedFiles = [
        'content.xml',
        'widgets.json',
        'customizer.dat',
        'redux-options.json',
    ];
    
    foreach ($expectedFiles as $file) {
        $filePath = $demoDir . '/' . $file;
        expect(file_exists($filePath))->toBeTrue("Demo file {$file} should exist");
    }
});

test('demo content xml is valid xml', function () {
    $contentFile = getPluginPath('demo/content.xml');
    
    if (!file_exists($contentFile)) {
        $this->markTestSkipped('Demo content.xml not found');
    }
    
    $xml = simplexml_load_file($contentFile);
    expect($xml)->not->toBeFalse();
});

test('demo widgets json is valid json', function () {
    $widgetsFile = getPluginPath('demo/widgets.json');
    
    if (!file_exists($widgetsFile)) {
        $this->markTestSkipped('Demo widgets.json not found');
    }
    
    $json = json_decode(file_get_contents($widgetsFile), true);
    expect(json_last_error())->toBe(JSON_ERROR_NONE);
});

test('demo redux options is valid json', function () {
    $reduxFile = getPluginPath('demo/redux-options.json');
    
    if (!file_exists($reduxFile)) {
        $this->markTestSkipped('Demo redux-options.json not found');
    }
    
    $json = json_decode(file_get_contents($reduxFile), true);
    expect(json_last_error())->toBe(JSON_ERROR_NONE);
});

test('conjure class has import workflow methods', function () {
    require_once getPluginPath('class-conjure.php');
    
    $workflowMethods = [
        'get_import_data_info',
        'get_import_files_paths',
        'get_import_steps_html',
    ];
    
    foreach ($workflowMethods as $method) {
        expect(method_exists('Conjure', $method))->toBeTrue();
    }
});

test('conjure class tracks import files', function () {
    require_once getPluginPath('class-conjure.php');
    
    $reflection = new ReflectionClass('Conjure');
    expect($reflection->hasProperty('import_files'))->toBeTrue();
});

test('conjure class has step completion tracking', function () {
    require_once getPluginPath('class-conjure.php');
    
    expect(method_exists('Conjure', 'get_step_completion_state'))->toBeTrue();
});



