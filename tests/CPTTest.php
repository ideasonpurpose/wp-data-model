<?php

namespace IdeasOnPurpose\WP;

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
 * @covers \IdeasOnPurpose\WP\CPT
 * @covers \IdeasOnPurpose\WP\Error
 */
final class CPTTest extends TestCase
{
    protected function setUp(): void
    {
        /** @var \IdeasOnPurpose\WP\CPT $this->Taxonomy */
        $this->CPT = $this->getMockForAbstractClass(CPT::class);
        $this->CPT->type = 'test_post_type';
        $this->CPT->args = [];

        /**
         * Workaround the protected $css property.
         */
        $this->Ref = new \ReflectionClass($this->CPT);
        $this->css = $this->Ref->getProperty('css');
        $this->css->setAccessible(true);
        $this->css->setValue($this->CPT, '');

        $this->adminCSS = $this->Ref->getProperty('adminCSS');
        $this->adminCSS->setAccessible(true);
        $this->adminCSS->setValue($this->CPT, '');


    }

    public function testRegister()
    {
        global $post_types;
        $post_types = [];
        $this->assertNull($this->CPT->register());
        // d($post_types);
        $this->assertContains($this->CPT->type, $post_types);
    }

    public function testAdminStyles()
    {
        global $inline_styles;
        $inline_styles = [];

        $this->css->setValue($this->CPT, 'Test CSS String');
        // $this->adminCSS->setValue($this->CPT, '');


        $this->CPT->adminStyles();
        $this->assertStringContainsString('wp-admin', $inline_styles[0]['handle']);
        $this->assertStringContainsString('/* START', $inline_styles[0]['data']);
    }

    public function testAdminStylesDeprecated()
    {
        global $inline_styles, $error_log;
        $inline_styles = [];

        // $this->expectOutputRegex('/deprecated/');
        // $this->expectError();
        // $this->css = '';
        $msg = 'Deprecated Admin CSS';
        $this->adminCSS->setValue($this->CPT, $msg);

        $this->CPT->adminStyles();
        // $this->expectErrorMessageMatches('/\$this->adminCSS/');
        // d($error_log);
        $this->assertStringContainsString('$this->adminCSS', $error_log);
        $this->assertStringContainsString('wp-admin', $inline_styles[0]['handle']);
        $this->assertStringContainsString($msg, $inline_styles[0]['data']);

        // d($inline_styles);
    }
}
