<?php
namespace ZF\Console\Filter;

/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

use Zend\Filter\FilterInterface;

class Explode implements FilterInterface
{
    private $delimiter;

    public function __construct($delimiter=',')
    {
        $this->delimiter = $delimiter;
    }

    /* (non-PHPdoc)
     * @see \Zend\Filter\FilterInterface::filter()
     */
    public function filter($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        return explode($this->delimiter, $value);
    }
}
