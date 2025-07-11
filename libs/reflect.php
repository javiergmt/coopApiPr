<?php

class callServiceMethod
{

    public function execute($serviceName, $methodName, $payload)
    {

        $payload = ((is_array($payload)) ? $payload : array());

        $dirServices = realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "services") . DIRECTORY_SEPARATOR;

        // Cargo el archivo...
        if (!$serviceName || !preg_match("#^([A-Z0-9]+)$#i", $serviceName) || !@file_exists($dirServices . $serviceName . ".php")) {
            throw new Exception("Servicio Desconocido (" . $serviceName . ")");
        }
        include_once($dirServices . $serviceName . ".php");

        if (!class_exists($serviceName)) {
            throw new Exception("Servicio Desconocido (" . $serviceName . ")");
        }
        $Service = new $serviceName();

        if (!$methodName || !@method_exists($Service, $methodName) || !@is_callable(array($Service, $methodName))) {
            throw new Exception("Metodo Invalido (" . $methodName . ")");
        }

        $callParams = array();
        $Reflec = new ReflectionMethod($Service, $methodName);
        foreach ($Reflec->getParameters() as $param) {
            $callParams[] = $this->getParam($param, $payload);
        }
        
        return call_user_func_array(array($Service, $methodName), $callParams);
       
    }

    private function getParam($param, $data)
    {
        //echo "Validando el parametro: " . $param->getName() . "\n";

        try {
            $name = $param->getName();
            if ($name == "__payload__") {
                return $data;
            }
            if (!isset($data[$name])) {
                if ($param->isDefaultValueAvailable()) {
                    return $param->getDefaultValue();
                }
                throw new Exception("Missing");
            }

            return callServiceMethod::validateType($param->getType(), $data[$name], $param->isDefaultValueAvailable(), (($param->isDefaultValueAvailable()) ? $param->getDefaultValue() : null));
        } catch (Exception $ex) {
            throw new Exception('Param "' . $name . '": ' . $ex->getMessage());
        }
    }

    static function validateType($type, $value, $hasDefault, $default)
    {
        
        switch ($type) {
            case "string":
            case "?string":
                if ($value === null || $value === true || $value === false || is_array($value) || is_object($value)) {
                    $value = FALSE;
                } else {
                    $value = (string) $value;
                }
                break;

            case "int":
            case "?int":
                $value = filter_var($value, FILTER_VALIDATE_INT);
                break;

            case "float":
            case "?float":
                $value = filter_var($value, FILTER_VALIDATE_FLOAT);
                break;

            case "bool":
            case "?bool":
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                break;

            case "array":
            case "?array":
                $value = ((is_array($value)) ? $value : false);
                break;

            case "mixed":
            case "?mixed":
                return $value;
                break;

            default:
                throw new Exception("Tipo de dato, desconocido " . $type);
                break;
        }

        if (($value === false && $type != 'bool') || $value === NULL) {
            if ($hasDefault) {
                return $default;
            }
            throw new Exception("Valor invalido para ese tipo de datos" . $type . "-" . $value);
        }
      
        return $value;
    }
}
