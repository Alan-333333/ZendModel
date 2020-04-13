<?php

namespace Btctrade\ZendModel;

abstract class AbstractEntity
{

    /**
     * Method  __set
     * @desc  ......
     *
     * @author  huangql <hql@btctrade.com>
     * @param $name
     * @param $value
     *
     * @return  $this
     */
    public function __set($name, $value)
    {
        $field = $name;
        if (!property_exists($this, $name)) {
            throw new \InvalidArgumentException("Setting the field '$field' is not valid for this entity.");
        }

        $mutator = "set" . ucfirst(strtolower($name));
        if (method_exists($this, $mutator) && is_callable(array($this, $mutator))) {
            $this->$mutator($value);
        } else {
            $this->$field = $value;
        }

        return $this;
    }

    /**
     * Method  __get
     * @desc  ......
     *
     * @author  huangql <hql@btctrade.com>
     * @param $name
     *
     * @return  mixed
     */
    public function __get($name)
    {
        $field = $name;
        if (!property_exists($this, $field)) {
            throw new \InvalidArgumentException("Getting the field '$field' is not valid for this entity.");
        }

        $accessor = "get" . ucfirst(strtolower($name));
        return (method_exists($this, $accessor) &&
            is_callable(array($this, $accessor))) ? $this->$accessor() : $this->$field;
    }

    /**
     * Method  toArray
     * @desc  get the entity properties
     *
     * @author  huangql <hql@btctrade.com>
     *
     * @return  array
     */
    public function toArray()
    {
        return get_object_vars($this);
    }

    /**
     * Method  toDBArray
     * @desc  将类属性转成数据表字段【下划线类型】
     *
     * @author  huangql <hql@btctrade.com>
     *
     * @return  array
     */
    public function toDBArray()
    {
        $array = [];
        foreach (get_object_vars($this) as $property => $value) {
            $columnName = Tool::camel2Underline($property);
            $array[$columnName] = $value;
        }
        return $array;
    }

    /**
     * Method  reset
     * @desc  reset the entity properties
     *
     * @author  huangql <hql@btctrade.com>
     *
     * @return  void
     */
    public function reset()
    {
        foreach (get_object_vars($this) as $property => $value) {
            if (is_null($value)) {
                continue;
            }
            $this->$property = null;
        }
    }
}
