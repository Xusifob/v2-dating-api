<?php

namespace App\Entity;


abstract class Entity
{


    /**
     * @param array $data
     */
    public function __construct($data = array())
    {
        $this->setData($data);

    }




    /**
     * @param $elem
     * @return bool
     */
    public function get($elem)
    {

        $getter = "get" . $this->dashesToCamelCase($elem,true);

        if(method_exists($this,$getter)) {
            return $this->$getter($elem);
        }

        $vars = get_object_vars($this);

        if(isset($vars[$elem])) {
            return $vars[$elem];
        }
        return false;

    }


    /**
     * @param $key
     * @param $elem
     * @return bool
     */
    public function set($key,$elem)
    {

        $vars = get_object_vars($this);

        $setter = "set" . $this->dashesToCamelCase($key,true);

        if(method_exists($this,$setter)) {
            $this->$setter($elem);
            return false;
        }


        if(array_key_exists($key,$vars)) {
            $this->$key = $elem ;
            return true;
        }

        return false;

    }




    /**
     * @param $data
     */
    public function setData($data)
    {
        foreach ($data as $key => $datum) {
            $this->set($key,$datum);
        }
    }


    /**
     * @param $string
     * @param bool $capitalizeFirstCharacter
     * @return mixed|string
     */
    protected function dashesToCamelCase($string, $capitalizeFirstCharacter = false)
    {

        $str = str_replace('_', '', ucwords($string, '-'));

        if (!$capitalizeFirstCharacter) {
            $str = lcfirst($str);
        }

        return $str;
    }


}