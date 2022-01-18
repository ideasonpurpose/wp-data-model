<?php

namespace IdeasOnPurpose\WP;

// use IdeasOnPurpose\WP\Test;
// use IdeasOnPurpose\WP\DataModel;
use PHPUnit\Framework\TestCase;

Test\Stubs::init();

// class DM extends DataModel {

//   public function register() {

//     $this->taxonomyMap = [
//       'category' => ['help', 'page', 'policy', 'post'],
//       'audience' => ['calendar', 'help', 'policy', 'post'],
//       'fellowship' => ['calendar'],
//       'post_tag' => ['help'],
//   ];

//   /**
//    * Set separators for the WordPress admin
//    */
//   new WP\Admin\Separators(20, 24, 27);

//   }

// }
/**
 * @covers \IdeasOnPurpose\WP\DataModel
 */
final class DataModelTest extends TestCase

{
    public function setUp(): void
    {
        global $actions;
        global $wp_post_types;

        $actions = [];
        $wp_post_types = [];
    }

    public function testParseTaxonomyMap()
    {
        /**
         * WIP: Check that register_taxonomy_for_object_type is called
         */
        $stub = $this->getMockBuilder(DataModel::class)
            ->disableOriginalConstructor()
            ->getMock(['parseTaxonomyMap']);

        $setter = function () {
            $this->taxonomyMap = [
                'category' => ['help', 'page', 'policy', 'post'],
                'audience' => ['calendar', 'help', 'policy', 'post'],
                'fellowship' => ['calendar'],
                'post_tag' => ['help'],
            ];
        };
        $setMap = $setter->bindTo($stub, get_class($stub));
        $setMap();
        $stub->parseTaxonomyMap();

        // \Kint::$mode_default = \Kint::MODE_CLI;
        // error_log(@d($stub));
        // \Kint::$mode_default = \Kint::MODE_RICH;
    }
}
