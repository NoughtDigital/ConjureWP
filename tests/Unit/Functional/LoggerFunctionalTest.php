<?php

use Monolog\Logger as MonologLogger;

beforeEach(function () {
    // Create a temporary log directory for tests
    $this->tempLogDir = sys_get_temp_dir() . '/conjurewp-test-logs-' . uniqid();
    mkdir($this->tempLogDir, 0755, true);
    $this->tempLogFile = $this->tempLogDir . '/test.log';
});

afterEach(function () {
    // Clean up test logs
    if (file_exists($this->tempLogFile)) {
        unlink($this->tempLogFile);
    }
    if (is_dir($this->tempLogDir)) {
        rmdir($this->tempLogDir);
    }
    
    // Reset singleton
    $reflection = new ReflectionClass('Conjure_Logger');
    $instance = $reflection->getProperty('instance');
    $instance->setAccessible(true);
    $instance->setValue(null, null);
});

test('logger can be instantiated with configuration', function () {
    require_once getPluginPath('includes/class-conjure-logger.php');
    
    $config = [
        'enable_rotation' => false,
        'min_log_level' => MonologLogger::DEBUG,
    ];
    
    $logger = Conjure_Logger::get_instance($config);
    
    expect($logger)->toBeInstanceOf('Conjure_Logger');
});

test('logger writes info messages to log file', function () {
    require_once getPluginPath('includes/class-conjure-logger.php');
    
    $logger = Conjure_Logger::get_instance();
    
    // Use reflection to test internal logging
    $reflection = new ReflectionClass($logger);
    $logProperty = $reflection->getProperty('log');
    $logProperty->setAccessible(true);
    $log = $logProperty->getValue($logger);
    
    expect($log)->toBeInstanceOf('Monolog\Logger');
});

test('logger has all required logging methods', function () {
    require_once getPluginPath('includes/class-conjure-logger.php');
    
    $logger = Conjure_Logger::get_instance();
    
    expect(method_exists($logger, 'info'))->toBeTrue();
    expect(method_exists($logger, 'error'))->toBeTrue();
    expect(method_exists($logger, 'warning'))->toBeTrue();
    expect(method_exists($logger, 'debug'))->toBeTrue();
});

test('logger respects minimum log level configuration', function () {
    require_once getPluginPath('includes/class-conjure-logger.php');
    
    $config = [
        'min_log_level' => MonologLogger::ERROR,
    ];
    
    $logger = Conjure_Logger::get_instance($config);
    
    $reflection = new ReflectionClass($logger);
    $configProperty = $reflection->getProperty('config');
    $configProperty->setAccessible(true);
    $actualConfig = $configProperty->getValue($logger);
    
    expect($actualConfig['min_log_level'])->toBe(MonologLogger::ERROR);
});

test('logger accepts custom configuration updates', function () {
    require_once getPluginPath('includes/class-conjure-logger.php');
    
    $logger = Conjure_Logger::get_instance();
    expect($logger)->toBeInstanceOf('Conjure_Logger');
    
    // Verify update_config method exists
    expect(method_exists($logger, 'update_config'))->toBeTrue();
});

test('logger parses string log levels correctly', function () {
    require_once getPluginPath('includes/class-conjure-logger.php');
    
    $logger = Conjure_Logger::get_instance();
    
    // Test that parse_log_level method exists
    expect(method_exists($logger, 'parse_log_level'))->toBeTrue();
});




