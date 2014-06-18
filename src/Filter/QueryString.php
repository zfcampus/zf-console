<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Console\Filter;

use Zend\Filter\FilterInterface;
use Zend\Stdlib\ArrayUtils;

class QueryString implements FilterInterface
{

    /* (non-PHPdoc)
     * @see \Zend\Filter\FilterInterface::filter()
     */
    public function filter($value)
    {
        if(!is_string($value)) {
            return $value;
        }

        // check if the values is provided like a query string
        $pairs = explode('&', $value);
        foreach ($pairs as $pair) {
            list($k, $v) = explode('=', $pair);

            if(preg_match("/^(.*?)((\[(.*?)\])+)$/m",$k, $m)) {
                $parts = explode('][',rtrim(ltrim($m[2],'['),']'));
                $json = '{"'.implode('":{"', $parts).'": '.json_encode($v).str_pad('', count($parts),'}');
                if(!isset($data[$m[1]])) {
                    $data[$m[1]] = json_decode($json, true);
                } else {
                    $data[$m[1]] = ArrayUtils::merge($data[$m[1]], json_decode($json, true));
                }
            } else {
                $data[$k] = $v;
            }
        }

        return $data;
    }
}
