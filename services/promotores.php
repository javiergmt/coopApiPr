<?php

error_reporting(0);

class promotores
{

    public function enviarMsg(string $id, string $dni, string $telefono)
    {
        if (!$id || !$dni || !$telefono) {
            throw new Exception("Invalid Data"); // esto llega en la respuesta de la api como {"error": "Invalid Data"}
        }
        $hora = date("H:i");
        //$telefono = '5492215231902';
       
        $telefono = str_replace("-", "", $telefono); // se eliminan los guiones del telefono
        $telefono = "549".$telefono;       
        $uid = 'paolab';
        $template = 'error';
        $tipo = substr($id, 0, 1); // P, S o D
        $id = substr($id, 1); // el resto del id codificado

        // Se obtiene el template de la base de datos, en este caso se usa un SP que devuelve el template segun el tipo y hora
        $R = dbExecSP("dbo.sp_pr_getTemplate", [
            "tipo" => $tipo,
            "hora" => $hora
        ]);

        If (!$R) {
            throw new Exception("No se encontró el template para el tipo $tipo y hora $hora"); // si no se encuentra el template, se lanza una excepción
        } else {
            // Si se encuentra el template, se asigna a la variable $template
            if (isset($R['Template'])) {
                $template = $R['Template']; // Se obtiene el template del resultado del SP
            } else {
                throw new Exception("Template no encontrado en el resultado del SP"); // si no se encuentra el template, se lanza una excepción
            }
        }
        /*
        echo "Tipo: $tipo\n";
        echo "ID: $id\n";
        echo "DNI: $dni\n";
        echo "Teléfono: $telefono\n";   
        echo "Template: $template\n";
        */
        $idEstado = 1;
        if ($template == 'error') {
            $idEstado = 0; // Si el template es error, se asigna un estado de error
        }
        
        $idPromotor = 0; // Se debe definir el id del promotor
        $idSponsor = 0; // Se debe definir el id del sponsor
        $idDelegado = 0; // Se debe definir el id del delegado

        if ($tipo == 'P') {
            $idPromotor = decodeStr($id);
        } elseif ($tipo == 'S') {
            $idSponsor = decodeStr($id);
        } elseif ($tipo == 'D') {
            $idDelegado = decodeStr($id);
        } else {
            throw new Exception("Tipo de ID no valido: $tipo"); // si el tipo no es P, S o D, se lanza una excepción
        }   

        /*
        echo "Pr: $idPromotor\n";
        echo "Sp: $idSponsor\n";
        echo "De: $idDelegado\n";
        */
        
        $R = dbExecSP("dbo.sp_pr_contactoAdd", [
            "idContacto" => 0, // 0 para agregar un nuevo contacto
            "dni" => $dni,
            "telefono" => $telefono,
            "idPromotor" => $idPromotor, // Se debe definir el id del promotor
            "idSponsor" => $idSponsor, // Se debe definir el id del sponsor
            "idDelegado" => $idDelegado, // Se debe definir el id del delegado
            //"fecha" => date("m/d/Y"), // Fecha actual
            "fecha" => "07/11/2025", // Fecha actual
            "hora" => $hora,
            "idEstado" => $idEstado // Estado inicial del contacto, se debe definir
        ]);
        //echo "template: $template\n";
        Return $R;

        /*
        Esta funcion podria ser mas amplia y recibir, Dni,Telefono de la persona interesada
        y los datos que vienen en el encabezado del link que son el tipo P,S,o D y el id codificado
        Se decodifica el id y se busca en la base de datos el usuario con ese id
        y se le envia el mensaje al telefono del usuario con el template que corresponda segun el horario.
        Para determinar si esta o no en horario se debe buscar el dia y el rango horario en la tabla PR_HORARIO
        Estos datos se deben grabar en la tabla PR_CONTACTOS, en esta tabla aun falta definir el estado del contacto
        */

        $idEstado = 0;

        If ($idEstado <> 0) {
            $url = 'https://us-central1-wabu-a7bc1.cloudfunctions.net/apiCrmCall';
            $curl = curl_init();
            $fields = array(
                'apiKey' => 'ESKORJQEkXe5qUQ6E6c4Dbduhzjqkt1Axz9ITtpwOi11PGm3dJ8ZEJpd6BZOTDimG9Gkvyla6u7rKuCgHUwFy2cRTGS7PBo25zoyhShMp2QJjaw2zqsCoPW08OOrydmw',
                'action' => 'sendMessage',
                'uid' => $uid,
                'isNotify' => true,
                'payload' => array (
                    'serverID' => array('616824958187829'),
                    'to' => $telefono,
                    'type' => 'template',
                    'from_me' => true,
                    'uid' => $uid,
                    'complete_template_components' =>'',
                    'template' => array(
                        'name' => $template,
                        'language' => array('code' => 'es')
                    ),
                    'transfer_to_me' => true
                )
            );
            $json_string = json_encode($fields);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_POST, TRUE);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $json_string);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true );
            $R = curl_exec($curl);
            curl_close($curl);
            
            if (!$R) {
                throw new Exception("Error al enviar el mensaje"); // si la llamada a la API falla
            } else {
                return $R;
            }
        } else {
            // Si el estado es 0, no se envía el mensaje
            return [
                "mensaje" => "No se envió el mensaje, estado de contacto es error."
            ];
        }
    
    }

    public function getLink(string $tipo, int $id)
    {
        if (!$id || !$tipo) {
            throw new Exception("Invalid Data"); // esto llega en la respuesta de la api como {"error": "Invalid Data"}
        }
        $idlink =  encodeInt($id);
    
        return "https://example.com/link?&id=$tipo$idlink";
    }

    public function validarPrUsuario(string $usuario, string $clave)
    {
        if (!$usuario || !$clave) {
            throw new Exception("Invalid Data"); // esto llega en la respuesta de la api como {"error": "Invalid Data"}
        }
        $R = dbExecSP("dbo.sp_pr_validarUsuario", [
            "usuario" => $usuario,
            "clave" => md5($clave)
        ]);


        if (!$R) {
            throw new Exception("Invalid Data"); // si el SP no devuelve nada, se lanza una excepción generica
             $_SESSION['logged'] = FALSE; // Si la validación falla, marcar al usuario como no logueado
        } else {
            // Si la validación es exitosa, se inicia la sesión y se guardan los datos del usuario}
            session_regenerate_id(true); // Regenerar ID de sesión para prevenir ataques de fijación de sesión
            $_SESSION['usuario'] = $usuario; // Guardar el nombre de usuario en la sesión
            $_SESSION['permisos'] = ['TODOS']; // Ejemplo de permisos asignados al usuario
            $_SESSION['logged'] = TRUE; // Marcar al usuario como logueado
         }
            
        // DEVUELVO el resultado del SP, esto se convierte a JSON automáticamente
        return $R;
    }

    public function logout()
    {
        // Limpiar la sesión actual
        $_SESSION = [];
        // Destruir la sesión para cerrar sesión
        session_destroy();
        
        // Aquí se podría agregar la lógica de cierre de sesión, como invalidar un token o limpiar la sesión
        return [
            "memsaje" => "Logout ok"
        ];
    }  

    public function promotorAdd(int $idPromotor, string $nombre, int $idLider, int $idzona,
                                string $uidWapp, int $activo = 1)
    {
    
        if (!$idPromotor || !$nombre || !$idLider || !$idzona || !$uidWapp) {
            throw new Exception("Invalid Data"); // esto llega en la respuesta de la api como {"error": "Invalid Data"}
        }

        $R = dbExecSP("dbo.sp_pr_promotorAdd", [
            "idPromotor" => $idPromotor,
            "nombre" => $nombre,
            "idLider" => $idLider,
            "idzona" => $idzona,
            "uidWapp" => $uidWapp,
            "activo" => $activo
       ]);


        if (!$R) {
            //throw new Exception("Invalid Data"); // si el SP no devuelve nada, se lanza una excepción generica
            $R = [
                "mensaje" => "Promotor agregado/actualizado correctamente."
            ];
        }

        // DEVUELVO el resultado del SP, esto se convierte a JSON automáticamente
        return $R;
    }
  
    public function sponsorAdd(int $idSponsor, string $nombre, int $idPromotor, int $activo = 1)
    {
    
        if (!$idSponsor || !$nombre || !$idPromotor) {
            throw new Exception("Invalid Data"); // esto llega en la respuesta de la api como {"error": "Invalid Data"}
        }

        $R = dbExecSP("dbo.sp_pr_sponsorAdd", [
            "idSponsor" => $idSponsor,
            "nombre" => $nombre,
            "idPromotor" => $idPromotor,
            "activo" => $activo
       ]);


        if (!$R) {
            //throw new Exception("Invalid Data"); // si el SP no devuelve nada, se lanza una excepción generica
            $R = [
                "mensaje" => "Sponsor agregado/actualizado correctamente."
            ];
        }

        // DEVUELVO el resultado del SP, esto se convierte a JSON automáticamente
        return $R;
    }

    public function delegadoAdd(int $idDelegado, string $nombre, int $idPromotor, int $activo = 1)
    {
    
        if (!$idDelegado || !$nombre || !$idPromotor) {
            throw new Exception("Invalid Data"); // esto llega en la respuesta de la api como {"error": "Invalid Data"}
        }

        $R = dbExecSP("dbo.sp_pr_delegadoAdd", [
            "idDelegado" => $idDelegado,
            "nombre" => $nombre,
            "idPromotor" => $idPromotor,
            "activo" => $activo
       ]);


        if (!$R) {
            //throw new Exception("Invalid Data"); // si el SP no devuelve nada, se lanza una excepción generica
            $R = [
                "mensaje" => "Delegado agregado/actualizado correctamente."
            ];
        }

        // DEVUELVO el resultado del SP, esto se convierte a JSON automáticamente
        return $R;
    }

    public function contactoAdd(int $idContacto,string $dni, string $telefono, int $idPromotor, int $idSponsor,
                                int $idDelegado, string $fecha, string $hora, int $idEstado)
    {
        if (!$dni || !$telefono || !$idPromotor || !$idSponsor || !$idDelegado || !$fecha || !$hora || !$idEstado) {
            throw new Exception("Invalid Data"); // esto llega en la respuesta de la api como {"error": "Invalid Data"}
        }
    
        $R = dbExecSP("dbo.sp_pr_contactoAdd", [
            "idContacto" => $idContacto,
            "dni" => $dni,
            "telefono" => $telefono,
            "idPromotor" => $idPromotor,
            "idSponsor" => $idSponsor,
            "idDelegado" => $idDelegado,
            "fecha" => $fecha,
            "hora" => $hora,
            "idEstado" => $idEstado
       ]);


        if (!$R) {
            //throw new Exception("Invalid Data"); // si el SP no devuelve nada, se lanza una excepción generica
            $R = [
                "mensaje" => "Contacto agregado/actualizado correctamente."
            ];
        }

        // DEVUELVO el resultado del SP, esto se convierte a JSON automáticamente
        return $R;
    }

    public function getContactos(int $idPromotor, int $idSponsor,int $idDelegado,int $idLider, int $idZona, 
                                 string $desde , string $hasta, int $estado)
    {
        if (!$idPromotor || !$idSponsor || !$idDelegado || !$idLider || !$idZona || !$desde || !$hasta) {
            throw new Exception("Invalid Data"); // esto llega en la respuesta de la api como {"error": "Invalid Data"}
        }
    
        $R = dbExecSP("dbo.sp_pr_getContactos", [
            "idPromotor" => $idPromotor,
            "idSponsor" => $idSponsor,
            "idDelegado" => $idDelegado,
            "idLider" => $idLider,
            "idZona" => $idZona,
            "desde" => $desde,
            "hasta" => $hasta,
            "estado" => $estado
       ],TRUE);


        if (!$R) {
            throw new Exception("Invalid Data"); // si el SP no devuelve nada, se lanza una excepción generica
        }

        // DEVUELVO el resultado del SP, esto se convierte a JSON automáticamente
        return $R;
    }

    public function getPSD(string $tipo)
    {
    
        if (!$tipo) {
            throw new Exception("Invalid Data"); // esto llega en la respuesta de la api como {"error": "Invalid Data"}
        }
        $R = dbExecSP("dbo.sp_pr_getPSD", [
            "tipo" => $tipo
       ],TRUE);


        if (!$R) {
            throw new Exception("Invalid Data"); // si el SP no devuelve nada, se lanza una excepción generica
        }

        // DEVUELVO el resultado del SP, esto se convierte a JSON automáticamente
        return $R;
    }

    public function getLideres()
    {
    
        $R = dbExecSP("dbo.sp_pr_getLideres", [] ,TRUE);


        if (!$R) {
            throw new Exception("Invalid Data"); // si el SP no devuelve nada, se lanza una excepción generica
        }

        // DEVUELVO el resultado del SP, esto se convierte a JSON automáticamente
        return $R;
    }

    public function getZonas()
    {
    
        $R = dbExecSP("dbo.sp_pr_getZonas", [],TRUE);


        if (!$R) {
            throw new Exception("Invalid Data"); // si el SP no devuelve nada, se lanza una excepción generica
        }

        // DEVUELVO el resultado del SP, esto se convierte a JSON automáticamente
        return $R;
    }
}   