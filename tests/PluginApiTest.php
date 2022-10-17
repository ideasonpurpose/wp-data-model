<?php

namespace IdeasOnPurpose\WP\Plugin;

use PHPUnit\Framework\TestCase;
use IdeasOnPurpose\WP\Test;

Test\Stubs::init();

if (!function_exists(__NAMESPACE__ . '\error_log')) {
    function error_log($err)
    {
        global $error_log;
        $error_log = $err;
    }
}

/**
 * @covers \IdeasOnPurpose\WP\Plugin\Api
 */
final class PluginApiTest extends TestCase
{
    protected function setUp(): void
    {
        global $flush_rewrite_rules, $error_log, $is_wp_error;
        $flush_rewrite_rules = null;
        $error_log = '';
        $is_wp_error = false;

        /** @var \IdeasOnPurpose\WP\CPT $this->Taxonomy */
        // $this->Api = $this->getMockBuilder('\IdeasOnPurpose\WP\Plugin\Api')
        //     ->disableOriginalConstructor()->onlyMethods([])
        //     // ->addMethods(['register'])
        //     ->getMock();

        $this->plugin = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['register', 'pluginInfo', 'updateCheck'])
            ->getMock();

        $this->plugin->method('register')->willReturn('registered');
        // $this->plugin->method('pluginInfo')->willReturn('plugin Info');
        // $this->plugin->method('updateCheck')->willReturn('update Check');
        $this->plugin->__FILE__ = 'file';

        $this->Api = new Api($this->plugin);
    }

    public function testActivate()
    {
        global $flush_rewrite_rules;
        $this->assertNull($flush_rewrite_rules);

        $this->Api->plugin->expects($this->once())->method('register');

        $this->Api->activate();
        $this->assertTrue($flush_rewrite_rules);
    }

    public function testDeactivate()
    {
        global $flush_rewrite_rules;
        $this->assertNull($flush_rewrite_rules);

        // $this->Api->plugin->expects($this->once())->method('register');

        $this->Api->deactivate();
        $this->assertTrue($flush_rewrite_rules);
    }

    public function testUpdate()
    {
        global $plugin_basename;
        $plugin_basename = 'fake_basename';
        $expected = (object) ['response' => ['key' => 'value']];

        // TODO: This transient prefix is too buried, refactor it up into the constructor
        $transient_name = 'ideasonpurpose-update-check_mock-dir/plugin.php';
        $transients[$transient_name] = $expected;

        $actual = $this->Api->update($expected, 'action');

        $this->assertObjectHasAttribute('response', $actual);
        $this->assertArrayHasKey('kidy', $actual->response);
    }

    // public function testUpdate_empty_response()
    // {
    //     global $plugin_basename;
    //     $plugin_basename = 'fake_basename';
    //     $expected = (object) ['response' => []];

    //     // TODO: This transient prefix is too buried, refactor it up into the constructor
    //     $transient_name = 'ideasonpurpose-update-check_mock-dir/plugin.php';
    //     $transients[$transient_name] = $expected;

    //     $actual = $this->Api->update($expected, 'action');

    //     $this->assertObjectHasAttribute('response', $actual);
    // }

    public function testDetails()
    {
    }

    public function testUpdaterComplete()
    {
    }

    public function testPluginInfo()
    {
        $this->Api->pluginInfo();
        $this->assertObjectHasAttribute('plugin_data', $this->Api);
        $this->assertObjectHasAttribute('plugin_id', $this->Api);
        $this->assertObjectHasAttribute('plugin_slug', $this->Api);
        $this->assertObjectHasAttribute('transient', $this->Api);
    }

    public function testUpdateCheck_transientExists_debugTrue()
    {
        global $transients, $is_wp_error, $wp_remote_post, $error_log;
        // $is_wp_error = false;

        $expected = 'Response for testing';
        // $expected = (object) ['response' => ['key' => 'value']];

        // TODO: This transient prefix is too buried, refactor it up into the constructor
        $transient_name = 'ideasonpurpose-update-check_mock-dir/plugin.php';
        $transients[$transient_name] = $expected;

        $this->Api->is_debug = true;
        $this->Api->updateCheck();

        $this->assertFalse($this->Api->is_debug);
        $this->assertArrayHasKey($transient_name, $transients);
        d($expected);
        $this->assertEquals($expected, $this->Api->response);
        $this->assertStringContainsString('updateCheck', $error_log);
    }

    public function testUpdateCheck_transientExists_debugFalse()
    {
        // global $transients, $wp_remote_post, $error_log;

        $this->Api->is_debug = false;
        $this->Api->updateCheck();

        $this->assertIsObject($this->Api->response);
        $this->assertObjectHasAttribute('url', $this->Api->response);
        $this->assertObjectHasAttribute('args', $this->Api->response);
        $this->assertArrayHasKey('headers', $this->Api->response->args);
    }

    public function testUpdateCheck_transientExists_wpError()
    {
        global $is_wp_error, $wp_remote_post, $error_message, $error_log;

        $is_wp_error = true;
        $wp_remote_post = new \WP_Error();
        $error_message = 'mock Error';
        $this->Api->updateCheck();

        $this->assertFalse($this->Api->response);
        $this->assertStringContainsString('Something went wrong', $error_log);
    }

    public function testUpdateCheck_transientExists_responseCodeNot200()
    {
        global $is_wp_error, $wp_remote_post, $error_log;
        $wp_remote_post = false;
        $wp_remote_post = wp_remote_post();
        $wp_remote_post['response']['code'] = 418; // I'm a teapot 🫖
        $wp_remote_post['body'] = "I'm a teapot";

        $this->Api->updateCheck();

        $this->assertFalse($this->Api->response);
        $this->assertStringContainsString('Something went wrong', $error_log);
    }
}
