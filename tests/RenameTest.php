<?php

namespace IdeasOnPurpose\WP\Admin;

use IdeasOnPurpose\WP\Test;
use PHPUnit\Framework\TestCase;

Test\Stubs::init();

/**
 * @covers \IdeasOnPurpose\WP\Rename
 */
final class RenameTest extends TestCase
{
    public function setUp(): void
    {
        global $actions, $menu;

        $actions = [];
        $menu = [];
    }

    public function testInsertOneSeparator()
    {
        global $actions, $menu;
        new Separators(23);
        $expected = [23];

        $this->assertEquals($expected, $actions[0]['action'][0]->seps);
    }
}
