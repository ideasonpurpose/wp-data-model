<?php

namespace IdeasOnPurpose\WP;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

// use Labels;
use IdeasOnPurpose\WP\Test;
// use PhpParser\Node\Stmt\Label;
// use WP_Taxonomy;
// use WP_Post_Type;

Test\Stubs::init();

if (!function_exists(__NAMESPACE__ . '\error_log')) {
    function error_log($err)
    {
        global $error_log;
        $error_log = $err;
    }
}

#[CoversClass(\IdeasOnPurpose\WP\DataModel\Labels::class)]
final class LabelsTest extends TestCase
{
    public function testLabels()
    {
        global $i18n;

        $i18n = [
            'toad' => 'sapo',
            'toads' => 'sapos',
        ];
        $actual = DataModel\Labels::labels('toad', 'toads', true, false);
        $this->assertArrayHasKey('name', $actual);
    }

    public function testUpdateLabels()
    {
        global $i18n;

        $i18n = [
            'bird' => 'pájaro',
            'birds' => 'pájaros',
        ];

        $labels = DataModel\Labels::labels('toad', 'toads', true, false);
        $actual = DataModel\Labels::updateLabels(__('bird'), __('birds'), $labels);
        $this->assertArrayHasKey('name', $actual);
        $this->assertEqualsIgnoringCase(__('birds'), $actual['name']);
    }

    public function testPostTypeLabels()
    {
        $actual = DataModel\Labels::post_type('dog', 'Dogs');
        $this->assertEqualsIgnoringCase('dog', $actual['singular_name']);
    }

    public function testTaxonomyLabels()
    {
        $actual = DataModel\Labels::taxonomy('color', 'colors');
        $this->assertEqualsIgnoringCase('Colors', $actual['name']);
    }
}
