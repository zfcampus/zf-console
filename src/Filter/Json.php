<?php

/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Console\Filter;

use Zend\Filter\FilterInterface;

class Json implements FilterInterface
{

    /* (non-PHPdoc)
     * @see \Zend\Filter\FilterInterface::filter()
     */
    public function filter($value)
    {
        if(!is_string($value)) {
            return $value;
        }

        @$data = json_decode($value, true);
        if($data === false) {
            return $value;
        }

        return $data;
    }

}
