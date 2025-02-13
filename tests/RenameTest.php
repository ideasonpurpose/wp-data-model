<?php

namespace IdeasOnPurpose\WP;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use IdeasOnPurpose\WP\Test;
use ReflectionException;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\Exception;

Test\Stubs::init();

if (!function_exists(__NAMESPACE__ . '\error_log')) {
    function error_log($err)
    {
        Test\Stubs::error_log($err);
    }
}

/**
 * Empty class for mocking the abstract class
 * TODO: WHY is that an abstract not a plain class?
 */
class RenameMock extends Rename
{
    //     public function update($object, $singular, $plural, $overrides = []) {}
}

#[CoversClass(\IdeasOnPurpose\WP\Error::class)]
#[CoversClass(\IdeasOnPurpose\WP\Rename::class)]
#[CoversClass(\IdeasOnPurpose\WP\DataModel\Labels::class)]
final class RenameTest extends TestCase
{
    protected function setUp(): void
    {
        global $wp_post_types, $wp_taxonomies;

        $wp_post_types = [
            'post' => (object) ['hierarchical' => false],
            'page' => (object) ['hierarchical' => true],
        ];

        $wp_taxonomies = [
            'category' => (object) ['hierarchical' => true],
            'post_tag' => (object) ['hierarchical' => false],
        ];
    }

    public function testMagicHandler()
    {
        $mockRename = $this->getMockBuilder(Rename::class)
            ->onlyMethods(['update'])
            ->getMock();

        $name = 'page';
        $args = ['singlular', 'plural', ['override' => 'label']];

        $mockRename
            ->expects($this->once())
            ->method('update')
            ->with(
                $this->identicalTo($name),
                $this->identicalTo($args[0]),
                $this->identicalTo($args[1]),
                $this->identicalTo($args[2])
            );

        $mockRename->magicHandler($name, $args);
    }

    public function testMagicHandler_noPlural()
    {
        $mockRename = $this->getMockBuilder(Rename::class)
            ->onlyMethods(['update'])
            ->getMock();

        $name = 'page';
        $args = ['singlular'];

        $mockRename
            ->expects($this->once())
            ->method('update')
            ->with(
                $this->identicalTo($name),
                $this->identicalTo($args[0]),
                $this->identicalTo($args[0]),
                $this->isArray()
            );

        $mockRename->magicHandler($name, $args);
    }

    public function testMagicHandler_overridesNotArray()
    {
        $mockRename = $this->getMockBuilder(Rename::class)
            ->onlyMethods(['update'])
            ->getMock();

        $name = 'page';
        $args = ['singlular', 'plural', 123];

        $actual = $mockRename->magicHandler($name, $args);
        $this->assertInstanceOf(Error::class, $actual);
    }

    /**
     * Send a bad arg to the __callStatic method to
     * @return void
     * @throws ReflectionException
     * @throws ExpectationFailedException
     * @throws Exception
     */
    public function test__callStatic()
    {
        $actual = Rename::page();
        $this->assertInstanceOf(Error::class, $actual);
    }

    public function testUpdatePostType()
    {
        global $wp_post_types;

        $reflection = new \ReflectionClass(Rename::class);
        $renameMock = $reflection->newInstanceWithoutConstructor();

        $renameMock->update('post', 'dog', 'dogs', ['override' => 'DOGS']);
        $this->assertEquals('Dogs', $wp_post_types['post']->label);
        $this->assertObjectHasProperty('override', $wp_post_types['post']->labels);
    }

    public function testUpdateTaxonomy()
    {
        global $wp_taxonomies;
        $reflection = new \ReflectionClass(RenameMock::class);
        $renameMock = $reflection->newInstanceWithoutConstructor();

        $renameMock->update('post_tag', 'color', 'Colors', ['override' => 'red']);
        $this->assertEquals('Colors', $wp_taxonomies['post_tag']->label);
        $this->assertObjectHasProperty('override', $wp_taxonomies['post_tag']->labels);
    }

    public function testUpdateError()
    {
        global $error_log;

        $error_log = 'aaaa';
        $reflection = new \ReflectionClass(RenameMock::class);
        $renameMock = $reflection->newInstanceWithoutConstructor();

        error_log('hi there');
        $renameMock->update('nope', 'a', 'b');
        $this->assertStringContainsString('nope', $error_log);
        $this->assertStringContainsString('Unable to rename.', $error_log);
    }
}
