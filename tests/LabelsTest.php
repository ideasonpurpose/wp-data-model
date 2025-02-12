<?php

namespace IdeasOnPurpose\WP;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

use IdeasOnPurpose\WP\Test;

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
        $actual = DataModel\Labels::labels('toad', 'toads', true, false);
        $this->assertObjectHasProperty('name', $actual);
    }

    public function testLabels_isPost_post()
    {
        $actual = DataModel\Labels::labels('place', 'places', 'post', false);
        $this->assertObjectHasProperty('name', $actual);
        $this->assertObjectHasProperty('featured_image', $actual);
        $this->assertObjectNotHasProperty('most_used', $actual);
    }
    public function testLabels_isPost_page()
    {
        $actual = DataModel\Labels::labels('place', 'places', 'page', false);
        $this->assertObjectHasProperty('name', $actual);
        $this->assertObjectHasProperty('featured_image', $actual);
        $this->assertObjectNotHasProperty('most_used', $actual);
    }
    public function testLabels_isPost_post_type()
    {
        $actual = DataModel\Labels::labels('place', 'places', 'post_type', false);
        $this->assertObjectHasProperty('name', $actual);
        $this->assertObjectHasProperty('featured_image', $actual);
        $this->assertObjectNotHasProperty('most_used', $actual);
    }
    public function testLabels_isPost_category()
    {
        $actual = DataModel\Labels::labels('place', 'places', 'category', false);
        $this->assertObjectHasProperty('name', $actual);
        $this->assertObjectNotHasProperty('featured_image', $actual);
        $this->assertObjectHasProperty('most_used', $actual);
    }
    public function testLabels_isPost_tag()
    {
        $actual = DataModel\Labels::labels('place', 'places', 'tag', false);
        $this->assertObjectHasProperty('name', $actual);
        $this->assertObjectNotHasProperty('featured_image', $actual);
        $this->assertObjectHasProperty('most_used', $actual);
    }
    public function testLabels_isPost_taxonomy()
    {
        $actual = DataModel\Labels::labels('place', 'places', 'taxonomy', false);
        $this->assertObjectHasProperty('name', $actual);
        $this->assertObjectNotHasProperty('featured_image', $actual);
        $this->assertObjectHasProperty('most_used', $actual);
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
        $this->assertObjectHasProperty('name', $actual);
        $this->assertEqualsIgnoringCase(__('birds'), $actual->name);
    }

    public function testPostTypeLabels()
    {
        $actual = DataModel\Labels::post_type('dog', 'Dogs');
        $this->assertEqualsIgnoringCase('dog', $actual->singular_name);
    }

    public function testTaxonomyLabels()
    {
        $actual = DataModel\Labels::taxonomy('color', 'colors');
        $this->assertEqualsIgnoringCase('Colors', $actual->name);
    }

    public function testPostTypeLabelsHasMenuName()
    {
        $actual = DataModel\Labels::post_type('dog', 'Dogs');
        $this->assertObjectHasProperty('menu_name', $actual);
        $this->assertEqualsIgnoringCase('Dogs', $actual->menu_name);
    }

    public function testTaxonomyLabelsHasMenuName()
    {
        $actual = DataModel\Labels::taxonomy('color', 'colors');
        $this->assertObjectHasProperty('menu_name', $actual);
        $this->assertEqualsIgnoringCase('Colors', $actual->menu_name);
    }

    public function testLabelsUnicode()
    {
        global $i18n;

        $i18n = [
            'パンダ' => '🐼',
            '蜂' => '🐝🐝',
        ];
        $labels = DataModel\Labels::labels('toad', 'toads', true, false);
        $actual = DataModel\Labels::updateLabels(__('パンダ'), __('蜂'), $labels);
        $this->assertObjectHasProperty('name', $actual);
        $this->assertEqualsIgnoringCase('🐝🐝', $actual->name);
        $this->assertEqualsIgnoringCase('🐼', $actual->singular_name);
    }

    public function testLabelsJapanese()
    {
        global $i18n;
        $i18n = [
            'person' => '人',
            // 'people' => '人々',  // not actually plural?
            'people' => '人',
        ];
        $labels = DataModel\Labels::labels('固定ページ', '固定ページ', 'page');
        $labels->view_item = '固定ページを表示';
        $labels->view_items = '固定ページ一覧を表示';
        $labels->featured_image = 'アイキャッチ画像';
        $actual = DataModel\Labels::updateLabels(__('person'), __('people'), $labels);
        $this->assertObjectHasProperty('name', $actual);
        $this->assertEqualsIgnoringCase('人', $actual->name);
        $this->assertEqualsIgnoringCase('人', $actual->singular_name);
        $this->assertEqualsIgnoringCase('人を表示', $actual->view_item);
        $this->assertEqualsIgnoringCase('人一覧を表示', $actual->view_items);
    }
}
