<?php

namespace IdeasOnPurpose\WP;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use IdeasOnPurpose\WP\Test;

Test\Stubs::init();

if (!function_exists(__NAMESPACE__ . '\error_log')) {
    function error_log($err)
    {
        Test\Stubs::error_log($err);
    }
}

#[CoversClass(\IdeasOnPurpose\WP\Error::class)]
final class ErrorTest extends TestCase
{
    public function testPrintError()
    {
        global $error_log;
        $error_log = 'aaa';
        $msg = 'Test Error Message';
        $Error = new Error($msg);
        $Error->is_debug = true;
        $actual = $Error->printInHead();
        $this->expectOutputRegex("/$msg/");
        $this->assertNull($actual);
        $this->assertStringContainsString($msg, $error_log);
    }

    public function testNoDebug()
    {
        global $error_log;
        $error_log = '';
        $msg = 'Test Error Message';
        $Error = new Error($msg);
        $Error->is_debug = false;
        $actual = $Error->printInHead();
        $Error->printInHead();
        $this->expectOutputString('');
        $this->assertNull($actual);
        $this->assertStringContainsString($msg, $error_log);
    }
}
