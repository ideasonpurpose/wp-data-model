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

    public $Api;
    public $plugin;

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

        $this->assertObjectHasProperty('response', $actual);
        $this->assertArrayHasKey('key', $actual->response);
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

    //     $this->assertObjectHasProperty('response', $actual);
    // }

    public function testDetails()
    {
        $mockApi = $this->getMockBuilder(\IdeasOnPurpose\WP\Plugin\Api::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['pluginInfo', 'updateCheck'])
            ->getMock();

        $mockApi->expects($this->once())->method('pluginInfo');
        $mockApi->expects($this->once())->method('updateCheck');

        $slug = 'test_slug';
        $res = (object) [];
        $action = 'plugin_information';
        $args = (object) ['slug' => $slug];

        $mockApi->plugin_slug = $slug;
        $mockApi->response = (object) [
            'tested' => 'mock tested',
            'banners' => 'mock banners',
            'new_version' => 'mock new_version',
            'last_modified' => 'mock last_modified',
            'package' => 'mock package',
            'sections' => [],
        ];
        $mockApi->plugin_data = [
            'Name' => 'mock name',
            'Author' => 'mock author',
            'RequiresWP' => 'mock requiresWP',
            'PluginURI' => 'mock pluginURI',
            'Description' => 'mock description',
        ];
        $actual = $mockApi->details($res, $action, $args);
        $this->assertObjectHasProperty('slug', $actual);
    }

    /**
     * The method should return the $result argument unchanged
     * if $this->response is false
     */
    public function testDetails_skipped_false()
    {
        $mockApi = $this->getMockBuilder(\IdeasOnPurpose\WP\Plugin\Api::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['pluginInfo', 'updateCheck'])
            ->getMock();

        $mockApi->response = false;
        $expected = 'expected';
        $action = 'plugin_information';
        $args = (object) ['slug' => 'slug'];

        $actual = $mockApi->details($expected, $action, $args);
        $this->assertEquals($expected, $actual);
    }

    /**
     * The method should return the $result argument unchanged
     * if $action is anything but 'plugin_information'
     */
    public function testDetails_skipped_action()
    {
        $mockApi = $this->getMockBuilder(\IdeasOnPurpose\WP\Plugin\Api::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['pluginInfo', 'updateCheck'])
            ->getMock();

        $mockApi->response = 'not false';
        $expected = 'expected';
        $action = 'not plugin_information';
        $args = (object) ['slug' => 'slug'];

        $actual = $mockApi->details($expected, $action, $args);
        $this->assertEquals($expected, $actual);
    }

    /**
     * The method should return the $result argument unchanged
     * if $args->slug is not equal to $this->plugin_slug
     */
    public function testDetails_skipped_slug()
    {
        $mockApi = $this->getMockBuilder(\IdeasOnPurpose\WP\Plugin\Api::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['pluginInfo', 'updateCheck'])
            ->getMock();

        $mockApi->response = 'not false';
        $mockApi->plugin_slug = 'not slug';
        $expected = 'expected';
        $action = 'plugin_information';
        $args = (object) ['slug' => 'slug'];

        $actual = $mockApi->details($expected, $action, $args);
        $this->assertEquals($expected, $actual);
    }

    public function testUpdaterComplete()
    {
        global $transients;
        $transients = [];
        $mockApi = $this->getMockBuilder(\IdeasOnPurpose\WP\Plugin\Api::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['pluginInfo', 'updateCheck'])
            ->getMock();

        $mockApi->expects($this->exactly(2))->method('pluginInfo');

        $plugin_id = 'plugin_id';
        $mockApi->plugin_id = $plugin_id;
        $mockApi->transient = 'mock transient';
        $options = ['action' => 'update', 'type' => 'plugin', 'plugins' => [$plugin_id]];

        // success
        $mockApi->plugin_id = $plugin_id;
        $mockApi->updaterComplete(null, $options);

        /**
         * No delete_transient call when plugin_id doesn't match
         */
        $mockApi->plugin_id = 'not plugin_id yet';
        $mockApi->updaterComplete(null, $options);
        $mockApi->plugin_id = $plugin_id; // reset

        /**
         * Do nothing when action is not 'update'
         */
        $options['action'] = 'not update';
        $mockApi->updaterComplete(null, $options);

        $this->assertNotEmpty($transients);
        $this->assertEquals(1, count($transients));
        $this->assertContains($mockApi->transient, end($transients));
    }

    public function testPluginInfo()
    {
        $this->Api->pluginInfo();
        $this->assertObjectHasProperty('plugin_data', $this->Api);
        $this->assertObjectHasProperty('plugin_id', $this->Api);
        $this->assertObjectHasProperty('plugin_slug', $this->Api);
        $this->assertObjectHasProperty('transient', $this->Api);
    }

    public function testUpdateCheck_transientExists_debugTrue()
    {
        global $transients, $wp_remote_post, $error_log;

        $expected = 'Response for testing';
        // $expected = (object) ['response' => ['key' => 'value']];

        // TODO: This transient prefix is too buried, refactor it up into the constructor
        $transient_name = 'ideasonpurpose-update-check_mock-dir/plugin.php';
        $transients[$transient_name] = $expected;

        $this->Api->is_debug = true;
        $this->Api->updateCheck();

        // $this->assertFalse($this->Api->is_debug);
        $this->assertArrayHasKey($transient_name, $transients);
        // d($expected);
        // $this->assertEquals($expected, $this->Api->response);
        $this->assertStringContainsString('updateCheck', $error_log);
    }

    public function testUpdateCheck_transientExists_debugFalse()
    {
        $this->Api->is_debug = false;
        $this->Api->updateCheck();

        $this->assertIsObject($this->Api->response);
        $this->assertObjectHasProperty('url', $this->Api->response);
        $this->assertObjectHasProperty('args', $this->Api->response);
        $this->assertArrayHasKey('headers', $this->Api->response->args);
    }

    public function testUpdateCheck_transientExists_wpError()
    {
        global $wp_remote_post, $error_message, $error_log;

        $wp_remote_post = new \WP_Error();
        $error_message = 'mock Error';
        $this->Api->updateCheck();

        $this->assertFalse($this->Api->response);
        $this->assertStringContainsString('Something went wrong', $error_log);
    }

    public function testUpdateCheck_transientExists_responseCodeNot200()
    {
        global $wp_remote_post, $error_log;
        $wp_remote_post = false;
        $wp_remote_post = wp_remote_post();
        $wp_remote_post['response']['code'] = 418; // I'm a teapot 🫖
        $wp_remote_post['body'] = "I'm a teapot";

        $this->Api->updateCheck();

        $this->assertFalse($this->Api->response);
        $this->assertStringContainsString('Something went wrong', $error_log);
    }
}
