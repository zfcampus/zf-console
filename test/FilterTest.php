<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Console;

use PHPUnit_Framework_TestCase as TestCase;
use ZF\Console\Filter;

class FilterTest extends TestCase
{
    public function testExplode()
    {
        $string = "foo,bar,baz";
        $expected = array('foo','bar','baz');
        $filter = new Filter\Explode();
        $this->assertEquals($expected, $filter->filter($string));
    }

    public function testExplodePipeDelimiter()
    {
        $string = "foo|bar|baz";
        $expected = array('foo','bar','baz');
        $filter = new Filter\Explode('|');
        $this->assertEquals($expected, $filter->filter($string));
    }

    public function testJson()
    {
        $string = '{"session.save_handler": "cluster", "something": "else"}';
        $expected = array('session.save_handler'=> 'cluster', 'something'=> 'else');
        $filter = new Filter\Json();
        $this->assertEquals($expected, $filter->filter($string));
    }

    public function testQueryString()
    {
        $string = 'session.save_handler=cluster&something=else';
        $expected = array('session.save_handler'=> 'cluster', 'something'=> 'else');
        $filter = new Filter\QueryString();
        $this->assertEquals($expected, $filter->filter($string));
    }
}
