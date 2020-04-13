<?php

namespace GasShaker\ZendModel;

/**
 * @Author: WenJun
 * @Date  :   16/4/28 20:59
 * @Email :  wenjun01@baidu.com
 * @File  :   Tool.php
 * @Desc  :   ...
 */
class Tool
{
    /**
     * Method  underline2Camel
     * @desc  下划线转驼峰命名方式
     *
     * @author  huangql <hql@GasShaker.com>
     * @static
     * @param $string
     * @param string $separator
     *
     * @return  string
     */
    public static function underline2Camel($string, $separator = '_')
    {
        $string = $separator . str_replace($separator, " ", strtolower($string));
        return ltrim(str_replace(" ", "", ucwords($string)), $separator);
    }

    /**
     * Method  camel2Underline
     * @desc  ......
     *
     * @author  huangql <hql@GasShaker.com>
     * @param $string
     * @param string $separator
     *
     * @return  string
     */
    public static function camel2Underline($string, $separator = '_')
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', "$1" . $separator . "$2", $string));
    }
}
