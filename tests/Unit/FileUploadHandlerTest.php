<?php

test('conjure file upload handler class file exists', function () {
    $classFile = getPluginPath('includes/class-conjure-file-upload-handler.php');
    expect(file_exists($classFile))->toBeTrue();
});

test('conjure file upload handler class can be loaded', function () {
    require_once getPluginPath('includes/class-conjure-file-upload-handler.php');
    expect(class_exists('Conjure_File_Upload_Handler'))->toBeTrue();
});

test('conjure file upload handler class has required methods', function () {
    require_once getPluginPath('includes/class-conjure-file-upload-handler.php');
    
    $requiredMethods = [
        '__construct',
        'get_upload_dir',
        'ajax_upload_file',
        'ajax_upload_from_media',
        'ajax_delete_uploaded_file',
        'cleanup_uploaded_files',
        'is_manual_upload_mode',
        'allow_import_file_types',
        'get_manual_upload_html',
    ];
    
    foreach ($requiredMethods as $method) {
        expect(method_exists('Conjure_File_Upload_Handler', $method))->toBeTrue();
    }
});

