<?php
function esValido($encabezado)
{
    session_start();
    if (isset($_SESSION['api_key'])) {
        if (isset($encabezado['x-api-key'])) {
            if ($_SESSION['api_key'] == $encabezado['x-api-key'] ){
                    return true;
            } else {
                    return false;    
        }
        } else {
            return false;
        }
    } else {
        return false;
    }
}

function sendResponse405()
{
    http_response_code(405);
    echo json_encode(["mensaje" => "Metodo No Permitido"]);
}

function encodeInt(int $number) {
    $alphabet = 'QYNLH3UMV24KJXBRGPDWCEA758Z9T';
    $base = strlen($alphabet);
    $encoded = '';
	if ($number < 0 || $number === null) { return null; }
    if ($number === 0) {return $alphabet[0];}
    while ($number > 0) {
        $encoded = $alphabet[$number % $base] . $encoded;
        $number = intdiv($number, $base);
    }
    return $encoded;
}

function decodeStr(string $str) {
    $alphabet = 'QYNLH3UMV24KJXBRGPDWCEA758Z9T';
    $base = strlen($alphabet);
    $str = strtoupper($str);
    $number = 0;
    for ($i = 0; $i < strlen($str); $i++) {
        $pos = strpos($alphabet, $str[$i]);
        if ($pos === false) { return null; }
        $number = $number * $base + $pos;
    }
    return $number;
}
?>