<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected string $pluginPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pluginPath = dirname(__DIR__);
    }

    protected function getPluginFile(string $file = ''): string
    {
        return $file ? $this->pluginPath . '/' . ltrim($file, '/') : $this->pluginPath;
    }
}
