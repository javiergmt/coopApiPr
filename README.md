Api para Comercios y Promotores

================================================================================
METODOS

POST
http://dominio/coopApi/api
Todos los Request son iguales, se diferencian a traves del BODY
================================================================================
DESCONEXION
Body:
{
    "service": "Comercios",
    "method": "Logout"
}
================================================================================
PROMOTORES
================================================================================
LOGIN o VALIDACION DE UN USUARIO EN PROMOTERES URBANOS 
Body: 
{ "service": "promotores", "method": "validarPrUsuario", "params": { "usuario": "root", "clave": "root1234" } } Respuesta: { "idUsuario": 1, "nombre": "ROOT", "usuario": "root", "clave": "root1234", } Si No Lo encuentra: { "error": "Error Call Service Base." } Md5 (root1234) : aabb2100033f035
================================================================================
ENVIAR MENSAJE POR WHATSAPP Body: { "service": "promotores", "method": "enviarMsg", "params": { "id":"SY", "dni": 17620426, "telefono":5492215231902 } }
================================================================================
GENERAR LINK Body: { "service": "promotores", "method": "getLink", "params": { "tipo":"S", (P,S,D) "id": 1 } }
================================================================================
LISTA PROMOTRES, SPONSORS O DELEGADOS Body: { "service": "promotores", "method": "getPSD", "params": { "tipo":"S", (P,S,D) } } Devuelve: { "nombre": "juan", "id": 1 }
================================================================================
LISTA LIDERES 
Body:
 { "service": "promotores", "method": "getLideres", "params": {}"
 } 
Devuelve: { "nombre": "Lider 1", "id": 1 }
================================================================================
LISTA ZONAS 
Body:
{ "service": "promotores", "method": "getZonas", "params": {}"
} 
Devuelve: { "nombre": "Zona 1", "id": 1 }
================================================================================
AGREGAR o ACTUALIZAR o BORRAR UN PROMOTOR 
Body: 
{ "service": "promotores", "method": "promotorAdd", "params": { "idPromotor" : 0, "nombre" : "promo1", ( nombre = '--DEL--' para borrar ) "idLider" : 1, "idzona" : 1, "uidWapp" : "uidWapp", "activo" : 1 } }
================================================================================
AGREGAR o ACTUALIZAR o BORRAR UN SPONSOR 
Body: 
{ "service": "promotores", "method": "sponsorAdd", "params": { "idSponsor" : 0, "nombre" : "Sp1", ( nombre = '--DEL--' para borrar ) "idPromotor" : 1, "activo" : 1 } }
================================================================================
AGREGAR o ACTUALIZAR o BORRAR UN DELEGADO 
Body: 
{ "service": "promotores", "method": "delegadoAdd", "params": { "idDelegado" : 0, "nombre" : "Deleg1", ( nombre = '--DEL--' para borrar ) "idPromotor" : 1, "activo" : 1 } }
================================================================================
AGREGAR o ACTUALIZAR o BORRAR UN CONTACTO 
Body: 
{ "service": "promotores", "method": "contactoAdd", "params": { "idContacto" : 0, "dni" : "17666266", ( --DEL-- para borrar ) "telefono" : "5492215231902", "idPromotor" : 1, "idSponsor" : 1, "idDelegado" : 1, "fecha" : "04/07/2025", "hora" : "17:15", "idEstado" : 0 } }
================================================================================
DEVUELVE LOS CONTACTOS 
Body: 
{ "service": "promotores", "method": "getContactos", "params": { "idPromotor" : 3, "idSponsor" : -1, "idDelegado" : -1, "idLider": -1, "idZona" : -1, "desde" : "2025/01/01", "hasta" : "2045/01/01", "estado" : -1 } }

