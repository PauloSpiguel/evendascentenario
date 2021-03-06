<?php

namespace Hcode;

class Model
{
    //Contem todos os dados dentro do objeto (idusuario etc)
    private $values = [];
    
    //Saber quando um metodo é chamado
    public function __call($name, $args)
    {

    $conf = parse_ini_file("DB/configuration.conf");

    //traz os tres primeiro caracteres
        $method = substr($name, 0, 3);
    //traz apartir da posição até o final
        $fieldName = substr($name, 3, strlen($name));

        //var_dump($method, $fieldName);

        switch ($method) {
            case "get":
                return (isset($this->values[$fieldName])) ? $this->values[$fieldName] : NULL;
                break;

            case "set":
                $this->values[$fieldName] = $args[0];
                break;
        }
    }

    public function setData($data = array())
    {

        foreach ($data as $key => $value) {
            $this->{"set" . $key}($value);
        }
    }

    public function getValues()
    {

        return $this->values;

    }

}
