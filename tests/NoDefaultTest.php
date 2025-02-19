<?php
/**
 * Contains the NoDefaultTest class.
 *
 * @copyright   Copyright (c) 2016-2017 Attila Fulop
 * @author      Attila Fulop
 * @license     MIT
 * @since       2016-05-30
 *
 */

namespace Konekt\Enum\Tests;

use Konekt\Enum\Tests\Fixture\SampleFooBarNoDefault;
use PHPUnit\Framework\TestCase;

class NoDefaultTest extends TestCase
{
    /**
     * @test
     */
    public function magic_constructor_results_the_same_as_passing_to_normal_constructor_if_enum_has_no_default()
    {
        $foo = new SampleFooBarNoDefault(SampleFooBarNoDefault::FOO);

        $this->assertTrue($foo->equals(SampleFooBarNoDefault::FOO()));
    }

    /**
     * @test
     */
    public function string_cast_works_even_if_no_default_is_present()
    {
        $this->assertSame('foo', (string)SampleFooBarNoDefault::FOO());
        $this->assertSame('bar', (string)SampleFooBarNoDefault::BAR());
    }

    /**
     * @test
     */
    public function value_can_be_retrieved()
    {
        $foo = SampleFooBarNoDefault::FOO();
        $this->assertSame('foo', $foo->value());

        $bar = SampleFooBarNoDefault::create(SampleFooBarNoDefault::BAR);
        $this->assertSame('bar', $bar->value());

        $foo2 = new SampleFooBarNoDefault(SampleFooBarNoDefault::FOO);
        $this->assertSame('foo', $foo2->value());
    }

    /**
     * @test
     */
    public function cant_create_without_explicit_value()
    {
        $this->expectException(\UnexpectedValueException::class);

        new SampleFooBarNoDefault();
    }
}
