<?php


/****************************************************************/
//EXPLICACIÓN
/****************************************************************/

/*

lAS FUNCIONES QUE DESARROLLE ABAJO SIMPLIFICAN LA TAREA DE REALIZAR OPERACIONES CON BASES DE DATOS MYSQL.
EL FUNCIONAMIENTO ES BASTANTE SIMPLE:

1 - SE CREA UN OBJETO DE CONEXIÓN SEGUN EL TIPO DE OPERACIÓN QUE TENGA QUE HACER: (CONFIGURAR DATOS DE ACCESO EN LA FUNCIÓN)
$conexion=conectarBD("w"); //CONEXIÓN PARA OPERACIONES DE ESCRITURA
$conexion=conectarBD("r"); //CONEXIÓN PARA OPERACIONES DE lECTURA

2 - SE CREA UN ARRAY FORMATEADO PARA RECIBIR LOS PARAMETROS DE CONSULTA PASANDO COMO PARAMETRO EL TIPO DE CONSULTA A REALIZAR:
$array_params=crearArrayParametros('select'); //OPCIONES: select,insert,update,delete

3 - SE AGREGAN AL ARRAY CREADO EN EL PASO ANTERIOR LOS PARAMETROS OBJETIVO (SON EL OBJETO DE LA OPERACIÓN) Y LOS PARAMETROS WHERE (SIRVEN PARA FILTRAR) PARA LA CONSULTA SEGUN CORRESPONDA.
//PARAMETROS OBJETIVO
$array_params=agregarParametroObjetivo($array_params,'nombre','valor modificado','string'); // (array formateado de params, nombre de la columna en la tabla, valor a definir (opcional), tipo de dato del valor (opcional))
//PARAMETROS WHERE
$array_params=agregarParametroWhere($array_params,'nombre','valor modificado','string',"igual"); // ES CASI LO MISMO QUE LA OTRA (PERO CONTEXTUALMENTE DIFERENTE). SE AGREGA UN ULTIMO PARAMETRO QUE ES LA CONDICION: (igual,diferente,mayor,menor,mayor_o_igual,menor_o_igual,parecido)

NOTA: ESTAS FUNCIONES SON CONTEXTUALES, LO QUE QUIERE DECIR ES QUE LOS PARAMETROS QUE LES PASEMOS DEPENDEN DEL TIPO DE CONSULTA QUE VAYAMOS A HACER
//EJEMPLO PARA UNA CONSULTA DE TIPO SELECT DONDE QUEREMOS SELECCIONAR TODAS LAS COLUMNAS DE UNA TABLA PODEMOS USAR LA FUNCION agregarParametroObjetivo DE ESTA FORMA:
//$array_params=agregarParametroObjetivo($array_params,'*');
//Y ASI DEPENDIENDO OTRO TIPO DE CONSULTAS (ESTUDIAR BIEN LAS FUNCIONES)

4 (OPCIONAL) - CREAR ARRAY CON FILTROS ESPECIALES PARA CONSULTAS DEL TIPO SELECT COMO SON LIMIT, OFFSET Y ORDER.
$select_especial_filters=crearArraySelectEspecialFilters(3,1,"ASC","id"); // LIMITE,OFFSET,ORDEN (ASC O DESC) Y PARAMETRO DE ORDEN, EJ: id

5 - EJECUTAR CONSULTA, SE ALMACENA EN UNA VARIABLE LA EJECUCIÓN DE LA FUNCION Y SE RECIBE UN ARRAY CON LOS DATOS SOLICITADOS EN EL CASO DE LAS CONSULTAS DE TIPO SELECT Y EN LAS OTRAS UN BOOL CON EL RESULTADO DE EJECUCIÓN
$resultado=ejecutarConsulta($conexion,"prueba",$array_params,false,false,$select_especial_filters); //OBJETO CONEXIÓN,NOMBRE DE LA TABLA,ARRAY CON PARAMETROS, KEYS ACEPTADAS DE PARAMETROS OBJ (ARRAY), KEYS ACEPTADAS DE PARAMETROS WHERE (ARRAY), FILTROS ESPECIALES PARA TIPO SELECT

6 - PARA HACER PRUEBAS Y VISUALIZAR LOS RESULTADOS PUEDEN USAR LA FUNCION var_dump($resultado);

*/







/****************************************************************/
//CONEXION POR METODO PDO
/****************************************************************/

//NOTA:
/*
Es recomendable tener diferentes usuarios para una misma BD con diferentes permisos segun sus funciones.
Ejemplo básico: Uno para solo escritura y otro para solo lectura.
*/

//FUNCION PARA CREAR OBJETO PDO PARA REALIZAR CONSULTAS A LA BD
//GENERALMENTE ESTA FUNCION DEBERIA IR EN UNA LIBRERIA QUE LA HEREDEN TODAS LAS DEMAS
function conectarBD($tipo=false,$bd=false,$host=false,$username=false,$password=false){
  //NOTA 1: $tipo, $bd, $host, $usernamey $password serian opcionales en este caso para que la funcion sea escalable
  //NOTA 2: si se fueran a especificar un $username y $password la variable $tipo no tendria efecto pero uno de los dos metodos debe ser declarado

  if (!$bd) { //SI NO SE PASO POR PARAMETRO TOMAR POR DEFECTO
    $bd="NOMBRE DE LA BD POR DEFECTO";
  }
  if (!$host) { //SI NO SE PASO POR PARAMETRO TOMAR POR DEFECTO
    $host="localhost"; //generalmente si la ejecuta dentro del mismo servidor va a ser localhost
  }

  if ($tipo && !$username && !$password) {
    //ASIGNAMOS EL USUARIO QUE VA A EJECUTAR LA CONSULTA DEPENDIENDO DE LAS NECESIDADES
    if ($tipo=="w") { //ESCRITURA
      $username="USUARIO POR DEFECTO CON ACCESO A LA BD PARA ESCRITURA";
      $password="PASS DEL USUARIO";
    }elseif ($tipo=="r") { //LECTURA
      $username="USUARIO POR DEFECTO CON ACCESO A LA BD PARA LECTURA";
      $password="PASS DEL USUARIO";
    }
  }elseif ($username && $password && !$tipo) {
    //AUTOMATICAMENTE SE VAN A TOMAR LAS VARIABLES $username Y $password QUE HAYAMOS PASADO
    //$username=$username;
    //$password=$password;
  }else {
    //HAY UN ERROR EN LA DISPOSICIÓN DE PARAMETROS HAY QUE USAR $tipo O EL METODO $username Y $password
    log_error("conectarBD","Error en la declaración de parametros. Se estan intentado usar 2 metodos simultaneamente");
    return false;
  }

  //CREAMOS LA CONEXION PDO
  try {
    $conexion = new PDO("mysql:dbname=$bd;host=$host;charset=utf8", $username, $password);
    return $conexion;
  } catch (PDOException $e) {
    //ERROR EN LA CONEXION
    log_error("conectarBD","Error en la conexión con la base de datos");
    return false;
  }
}







//FUNCION CREAR ARRAY PARA LOS PARAMETROS OBJETIVOS Y WHERE PARA CONSULTA
function crearArrayParametros($tipo_consulta){
  if($tipo_consulta!='select' && $tipo_consulta!='insert' && $tipo_consulta!='update' && $tipo_consulta!='delete') {
    //el tipo especificado no es valido
    return false;
  }else {
    $array=array();
    $array['tipo_consulta']=$tipo_consulta;
    $array['params_obj']=array();
    $array['params_where']=array();
    return $array;
  }
}


//FUNCION CREAR ARRAY PARA FILTROS ESPECIALES PARA CONSULTAS DE TIPO SELECT
function crearArraySelectEspecialFilters($limit=false,$offset=false,$order=false,$order_param=false){

  $select_especial_filters=array();

  if ($limit) {
    $limit=(int)$limit;
    $limit=sanitizarValor($limit,'int');
    $select_especial_filters['limit']=$limit;
  }
  if ($offset) {
    $offset=(int)$offset;
    $offset=sanitizarValor($offset,'int');
    $select_especial_filters['offset']=$offset;
  }
  if ($order && ($order=="ASC" || $order=="DESC")) {
    $select_especial_filters['order']=$order;
  }
  if ($order_param) {
    $order_param=sanitizarValor($order_param,'string');
    $select_especial_filters['order_param']=$order_param;
  }

  return $select_especial_filters;

}



//FUNCIÓN PARA AGREGAR PARAMETRO OBJETIVO AL ARRAY DE PARAMETROS DE CONSULTA
function agregarParametroObjetivo($array_params,$var_key,$var_value=false,$var_value_tipo=false,$optional=false){

  //VALIDAR PARAMETROS
  if(!is_array($array_params) || empty($array_params) || !isset($array_params['tipo_consulta']) || !isset($array_params['params_obj']) || !isset($array_params['params_where'])) {
    //el elemento $array_params no tiene el formato necesario
    return false;
  }
  if ($array_params['tipo_consulta']!='select' && $array_params['tipo_consulta']!='delete' && !$var_value && !$optional) {
    //error se debe introducir un var_value para el array especificado
    log_error("agregarParametroObjetivo","Error se debe introducir un var_value para el array especificado");
    return false;
  }
  if (!$var_key || $var_key=="") {
    //error es necesario una var_key para el elemento
    log_error("agregarParametroObjetivo","Error se debe introducir un var_key para el array especificado");
    return false;
  }
  if ($var_value && !$var_value_tipo) {
    log_error("agregarParametroObjetivo","Error se debe introducir el tipo para el var_value especificado");
    //error se debe introducir el tipo para el $var_value especificado
    return false;
  }


  //SANITIZAR $var_value SI ES QUE ESTA SETEADO SEGUN EL TIPO
  if ($var_value) {
    $var_value=sanitizarValor($var_value,$var_value_tipo);
    if (!$var_value && !$optional) {
      //error al sanitizar el valor: se devolvio false y este no es opcional
      log_error("agregarParametroObjetivo","Error al sanitizar el valor: se devolvio false y este no es opcional");
      return false;
    }elseif (!$var_value && $optional) {
      $var_value="";
    }
  }

  //SANITIZAR $var_key POR SI LO HAN TRAIDO DIRECTAMENTE DE $_POST[...]
  $var_key=sanitizarValor($var_key,'string');
  if (!$var_key) {
    //error al sanitizar el key: se devolvio false
    log_error("agregarParametroObjetivo","Error al sanitizar el key: se devolvio false");
    return false;
  }

  $array_index=array();
  $array_index['var_key']=$var_key;
  if ($var_value!==false) {
    $array_index['var_value']=$var_value;
    $array_index['var_value_tipo']=$var_value_tipo;
    $array_index['var_value_optional']=$optional;
  }
  array_push($array_params['params_obj'],$array_index);
  return $array_params;

}


//FUNCIÓN PARA AGREGAR PARAMETRO WHERE AL ARRAY DE PARAMETROS DE CONSULTA
function agregarParametroWhere($array_params,$var_key,$var_value,$var_value_tipo,$condicion=false,$optional=false){
  //VALIDAR PARAMETROS
  if(!is_array($array_params) || empty($array_params) || !isset($array_params['tipo_consulta']) || !isset($array_params['params_obj']) || !isset($array_params['params_where'])) {
    //el elemento $array_params no tiene el formato necesario
    log_error("agregarParametroWhere","El elemento array_params no tiene el formato necesario");
    return false;
  }

  $var_key=sanitizarValor($var_key,'string');
  if (!$var_key) {
    //error al sanitizar el key: se devolvio false
    log_error("agregarParametroWhere","Error al sanitizar el key: se devolvio false");
    return false;
  }

  $var_value=sanitizarValor($var_value,$var_value_tipo);
  if (!$var_value && !$optional) {
    //error al sanitizar el valor: se devolvio false y este no es opcional
    log_error("agregarParametroWhere","Error al sanitizar el valor: se devolvio false y este no es opcional");
    return false;
  }elseif (!$var_value && $optional) {
    $var_value="";
  }

  if (!$condicion) {
    //condicion por defecto
    $condicion="igual";
  }

  $condicion=traducirCondicion($condicion);

  if (!$condicion) {
    //error al obtener el valor de la condición especificada para el parametro
    log_error("agregarParametroWhere","Error al obtener el valor de la condición especificada para el parametro");
    return false;
  }

  if ($condicion=="LIKE") { //condicion ya formateada. Se le da formato especial al valor en caso de la condición LIKE
    $var_value="%".$var_value."%";
  }


  $array_index=array();
  $array_index['var_key']=$var_key;
  $array_index['var_value']=$var_value;
  $array_index['var_value_tipo']=$var_value_tipo;
  $array_index['condicion']=$condicion;

  array_push($array_params['params_where'],$array_index);
  return $array_params;

}


//FUNCION GENERAL PARA EJECUTAR CONSULTAS NORMALES (SELECT,INSERT,UPDATE,DELETE)
//EN ESTA FUNCION NO ESTAN CONTEMPLADAS TIPOS DE CONSULTAS ESPECIALES COMO LAS QUE USAN JOIN

function ejecutarConsulta($conexion,$tabla,$params,$allowed_params=false,$allowed_where_params=false,$select_especial_filters=false){
  //EXPLICACIÓN DE LOS PARAMETROS:
  //$conexion (OBJETO PDO): SE LE DEBE PASAR UN OBJECTO DE CONEXIÓN PDO YA ARMADO PARA QUE ESTA FUNCIÓN NO SEA TAN ENGORROSA CON LOS PARAMETROS
  //$tabla (STRING): NOMBRE DE LA TABLA DONDE SE VA A LLEVAR A CABO LA CONSULTA
  //$params (ARRAY): ARRAY CON PARAMETROS (FORMATEADOS) QUE VAN A SER EL OBJETO DE LA CONSULTA
  //EL OBJETO PARAMS DEBE TENER EL SIGUIENTE FORMATO:
  //$params['tipo_consulta']
  //$params['params_obj']
  //$params['params_where']
  //$allowed_params (ARRAY) - OPCIONAL/RECOMENDADO: ARRAY CON NOMBRES DE COLUMNAS ACEPTADOS PARA $params
  //$allowed_where_params (ARRAY) - OPCIONAL/RECOMENDADO: ARRAY CON NOMBRES DE COLUMNAS ACEPTADOS PARA $where_params
  //$select_especial_filters (ARRAY) - OPCIONAL: PARAMETROS ESPECIALES PARA CONSULTAS DE TIPO SELECT
  //$select_especial_filters['limit'] (INT) - OPCIONAL: LIMITE PARA CONSULTAS DE TIPO SELECT
  //$select_especial_filters['offset'] (INT) - OPCIONAL: OFFSET PARA CONSULTAS DE TIPO SELECT
  //$select_especial_filters['order'] (STRING) - OPCIONAL: ORDEN PARA CONSULTAS DE TIPO SELECT - "ASC" O "DESC"
  //$select_especial_filters['order_param'] (STRING) - OPCIONAL: PARAMETRO PARA CONSIDERAR EL ORDEN

  //1ER PASO: VALIDACIÓN DE PARAMETROS

  $error_validacion=false;
  $error_validacion_arr=array();

  if(($conexion instanceof PDO)==false) {
    $error_validacion=true;
    $error_validacion_arr['$conexion']='No es PDO';
  }
  if($params['tipo_consulta']!='select' && $params['tipo_consulta']!='insert' && $params['tipo_consulta']!='update' && $params['tipo_consulta']!='delete') {
    $error_validacion=true;
    $error_validacion_arr['$params']='El tipo de consulta no esta contemplado';
  }
  if(!$tabla || !is_string($tabla)) {
    $error_validacion=true;
    $error_validacion_arr['$tabla']='No especificado';
  }
  if(!$params || !is_array($params) || empty($params) || !isset($params['tipo_consulta']) || !isset($params['params_obj']) || !isset($params['params_where'])) {
    $error_validacion=true;
    $error_validacion_arr['$params']='Formato incorrecto';
  }
  if($params['tipo_consulta']=="") {
    $error_validacion=true;
    $error_validacion_arr['$params']='Sin tipo de consulta';
  }
  if (empty($params['params_where']) && $params['tipo_consulta']!="insert" && $params['tipo_consulta']!="select") {
    $error_validacion=true;
    $error_validacion_arr['$params']='Faltan params_where para el tipo de consulta especificado';
  }
  if(empty($params['params_obj']) && $params['tipo_consulta']!="delete") {
    $error_validacion=true;
    $error_validacion_arr['$params']='Faltan params_obj para el tipo de consulta especificado';
  }
  if($allowed_params && (!is_array($allowed_params) || empty($allowed_params))) {
    $error_validacion=true;
    $error_validacion_arr['$allowed_params']='Se especifico un array vacio o incorrecto';
  }
  if($allowed_where_params && (!is_array($allowed_where_params) || empty($allowed_where_params))) {
    $error_validacion=true;
    $error_validacion_arr['$allowed_where_params']='Se especifico un array vacio o incorrecto';
  }
  if($select_especial_filters && (!is_array($select_especial_filters) || empty($select_especial_filters))) {
    $error_validacion=true;
    $error_validacion_arr['$select_especial_filters']='Se especifico un array vacio o incorrecto';
  }

  if ($error_validacion) {
    log_error("ejecutarConsulta","Error de validación",$error_validacion_arr);
    return false;
  }else {

    //2DO PASO: VERIFICAR SI SE PASARON LISTAS DE PARAMETROS PERMITIDOS Y COMPROBAR

    if ($allowed_params) {
      foreach ($params['params_obj'] as $key => $value) {
        if (!in_array($value['var_key'], $allowed_params)) {
          log_error("ejecutarConsulta","Error el var_key especificado no se encuentra dentro de los parametros permitidos en allowed_params");
          //error el var_key especificado no se encuentra dentro de los parametros permitidos
          return false;
        }
      }
    }

    if ($allowed_where_params) {
      foreach ($params['params_where'] as $key => $value) {
        if (!in_array($value['var_key'], $allowed_params)) {
          log_error("ejecutarConsulta","Error el var_key especificado no se encuentra dentro de los parametros permitidos en allowed_where_params");
          //error el var_key especificado no se encuentra dentro de los parametros permitidos
          return false;
        }
      }
    }


    $tipo_consulta=$params['tipo_consulta'];

    //3ER PASO: ARMAR STRING QUERY DE PARAMETROS OBJETIVO Y WHERE
    $str_query_obj="";
    $str_query_obj_aux="";
    $first_value_obj=true;
    $str_query_where="";
    $first_value_where=true;
    $str_query_full;
    $var_keys_test_duplicated=array();
    $bind_params=array();

    switch ($tipo_consulta) {
      case 'select':

      foreach ($params['params_obj'] as $key => $value) {
        if ($first_value_obj) {
          $str_query_obj.=$value['var_key'];
          $first_value_obj=false;
        }else {
          $str_query_obj.=",".$value['var_key'];
        }
        array_push($var_keys_test_duplicated,$value['var_key']);
        //$bind_params[":".$value['var_key']]=$value['var_value'];
      }

      foreach ($params['params_where'] as $key => $value) {
        if (in_array($value['var_key'], $var_keys_test_duplicated)) {
          $value['var_key_fix']="2".$value['var_key'];
        }else {
          $value['var_key_fix']=$value['var_key'];
        }
        if ($first_value_where) {
          $str_query_where.=" WHERE ".$value['var_key']." ".$value['condicion']." :".$value['var_key_fix'];
          $first_value_where=false;
        }else {
          $str_query_where.=" AND ".$value['var_key']." ".$value['condicion']." :".$value['var_key_fix'];
        }
        $bind_params[":".$value['var_key_fix']]=$value['var_value'];
      }

      //extra filters select
      $select_especial_filters_string="";
      if ($select_especial_filters) {
        if (isset($select_especial_filters['order']) && isset($select_especial_filters['order_param'])) {
          $select_especial_filters['order_param']=sanitizarValor($select_especial_filters['order_param'],'string'); //se puede mejorar dicha sanitizacion
          if ($select_especial_filters['order_param'] && ($select_especial_filters['order']=="ASC" || $select_especial_filters['order']=="DESC")) {
            $select_especial_filters_string.=" ORDER BY ".$select_especial_filters['order_param']." ".$select_especial_filters['order'];
          }
        }

        if (isset($select_especial_filters['limit'])) {
          $select_especial_filters['limit']=(int)$select_especial_filters['limit'];
          $select_especial_filters['limit']=sanitizarValor($select_especial_filters['limit'],'int');
          if ($select_especial_filters['limit']) {
            $select_especial_filters_string.=" LIMIT ".$select_especial_filters['limit'];
          }
        }
        if (isset($select_especial_filters['offset'])) {
          $select_especial_filters['offset']=(int)$select_especial_filters['offset'];
          $select_especial_filters['offset']=sanitizarValor($select_especial_filters['offset'],'int');
          if ($select_especial_filters['offset']) {
            $select_especial_filters_string.=" OFFSET ".$select_especial_filters['offset'];
          }
        }
      }

      $str_query_full="SELECT ".$str_query_obj." FROM ".$tabla.$str_query_where.$select_especial_filters_string.";";

      break;
      case 'insert':

      foreach ($params['params_obj'] as $key => $value) {
        if ($first_value_obj) {
          $str_query_obj.=$value['var_key'];
          $str_query_obj_aux.=":".$value['var_key'];
          $first_value_obj=false;
        }else {
          $str_query_obj.=", ".$value['var_key'];
          $str_query_obj_aux.=", :".$value['var_key'];
        }
        $bind_params[":".$value['var_key']]=$value['var_value'];
      }
      $str_query_obj="(".$str_query_obj.") VALUES (".$str_query_obj_aux.")";

      $str_query_full="INSERT INTO ".$tabla." ".$str_query_obj.";";

      break;
      case 'update':

      foreach ($params['params_obj'] as $key => $value) {
        if ($first_value_obj) {
          $str_query_obj.=$value['var_key']." = :".$value['var_key'];
          $first_value_obj=false;
        }else {
          $str_query_obj.=", ".$value['var_key']." = :".$value['var_key'];
        }
        array_push($var_keys_test_duplicated,$value['var_key']);
        $bind_params[":".$value['var_key']]=$value['var_value'];
      }

      foreach ($params['params_where'] as $key => $value) {
        if (in_array($value['var_key'], $var_keys_test_duplicated)) {
          $value['var_key_fix']="2".$value['var_key'];
        }else {
          $value['var_key_fix']=$value['var_key'];
        }
        if ($first_value_where) {
          $str_query_where.=" WHERE ".$value['var_key']." ".$value['condicion']." :".$value['var_key_fix'];
          $first_value_where=false;
        }else {
          $str_query_where.=" AND ".$value['var_key']." ".$value['condicion']." :".$value['var_key_fix'];
        }
        $bind_params[":".$value['var_key_fix']]=$value['var_value'];
      }

      $str_query_full="UPDATE ".$tabla." SET ".$str_query_obj.$str_query_where.";";

      break;
      case 'delete':

      foreach ($params['params_where'] as $key => $value) {
        if ($first_value_where) {
          $str_query_where.=" WHERE ".$value['var_key']." ".$value['condicion']." :".$value['var_key'];
          $first_value_where=false;
        }else {
          $str_query_where.=" AND ".$value['var_key']." ".$value['condicion']." :".$value['var_key'];
        }
        $bind_params[":".$value['var_key']]=$value['var_value'];
      }

      $str_query_full="DELETE FROM ".$tabla.$str_query_where.";";

      break;
      default:
      log_error("ejecutarConsulta","Error en armado de consulta no se encontro rutina para el tipo de consulta introducido");
      return false;
      break;
    }


    //4TO PASO: PREPARAR SENTENCIA, APUNTAR VALORES DE PARAMETROS Y EJECUTAR CONSULTA
    //var_dump($str_query_full);

    $consulta=$conexion->prepare("$str_query_full");
    foreach ($bind_params as $key => $value) {

      $consulta->bindValue($key, $value);

    }

    if ($consulta->execute()) {
      if ($tipo_consulta=="select") {
        if ($consulta->rowCount() > 0) {
          $array_resultado=array();
          while ($resultado=$consulta->fetch(PDO::FETCH_ASSOC)) {
            array_push($array_resultado,$resultado);
          }
          return $array_resultado;
        }else {
          return 0;
        }
      }else {
        return true;
      }


    }else {
      //error al ejecutar la consulta
      log_error("ejecutarConsulta","Error al ejecutar la consulta");
      return false;
    }


  }

}








/****************************************************************/
//FUNCIONES AUXILIARES
/****************************************************************/


//FUNCION PARA VERIFICAR CARACTERES PERMITIDOS PARA CADA VARIABLE (OPCIONAL):
//ej: $variable = '"Hola Ami'gos"';
//ej: $permitidos = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@-_.';
//ej: $devolver_variable = true o false dependiendo el contexto y necesidades
function verificarCaracteresPermitidos($variable,$permitidos,$devolver_variable=true){
  for ($i=0; $i<strlen($variable); $i++){
    if (strpos($permitidos, substr($variable,$i,1))===false){
      return false; //Hay algun caracter no permitido
    }
  }
  //opcional devolver la variable ingresada:
  if ($devolver_variable) {
    return $variable;
  }else {
    return true; //esta bien
  }
}




//FUNCION PARA SANITIZAR VALORES SEGUN EL TIPO
function sanitizarValor($var_value,$var_value_tipo,$estricto=true){

  switch ($var_value_tipo) {
    case 'string':
    $var_value=htmlspecialchars($var_value);
    $var_value = filter_var($var_value, FILTER_SANITIZE_STRING);
    break;
    case 'url':
    $var_value=htmlspecialchars($var_value);
    $var_value = filter_var($var_value, FILTER_SANITIZE_URL);
    break;
    case 'int':
    $var_value=htmlspecialchars($var_value);
    $var_value = filter_var($var_value, FILTER_SANITIZE_NUMBER_INT);
    break;
    case 'float':
    $var_value=htmlspecialchars($var_value);
    $var_value = filter_var($var_value, FILTER_SANITIZE_NUMBER_FLOAT);
    break;
    case 'ip':
    $var_value=htmlspecialchars($var_value);
    $var_value=filter_var($var_value, FILTER_VALIDATE_IP);
    break;
    case 'email':
    $var_value=htmlspecialchars($var_value);
    $var_value = filter_var($var_value, FILTER_SANITIZE_EMAIL);
    break;
    case 'date':
    //POR AHORA PARA LAS FECHAS LAS VALIDAMOS COMO STRINGS
    $var_value=htmlspecialchars($var_value);
    $var_value = filter_var($var_value, FILTER_SANITIZE_STRING);
    break;
    case 'html':
    //EL HTML NO LO SANITIZAMOS POR AHORA (TENER CUIDADO)
    break;
    default:
    //error el tipo especificado no coincide con ninguno de los contemplados
    return false;
    break;
  }

  if ($var_value && $estricto) { //agregar mas opciones
    $var_value=str_replace ( "`" , "``" , $var_value );
  }

  if ($var_value) {
    return $var_value;
  }else {
    return false;
  }

}


//FUNCION PARA TRADUCIR CONDICIONES DESDE SINTAXIS LITERAL A MATEMATICA
function traducirCondicion($condicion){
  switch ($condicion) {
    case 'igual':
    $resultado="=";
    break;
    case 'mayor':
    $resultado=">";
    break;
    case 'menor':
    $resultado="<";
    break;
    case 'diferente':
      $resultado="!=";
      break;
      case 'mayor_o_igual':
      $resultado=">=";
      break;
      case 'menor_o_igual':
      $resultado="<=";
      break;
      case 'parecido':
      $resultado="LIKE";
      break;
      default:
      //ERROR EL TIPO DE CONDICIÓN INTRODUCIDA NO ESTA CONTAMPLADO
      return false;
      break;
    }
    return $resultado;
  }


//CREAR LOG DIARIO DE ERRORES BASICO EN TXT ENCRIPTADO
function log_error($codigo,$descripcion,$args=false){
  $log_msg="";
  $log_msg.="%reg%";
  $log_msg.="%date%".date('d-m-Y H:i:s')."%date%";
  $log_msg.="%cod%".$codigo."%cod%";
  $log_msg.="%desc%".$descripcion."%desc%";
if ($args && is_array($args) && !empty($args)) {
  $log_msg.="%args%";
  foreach ($args as $key => $value) {
    $log_msg.="%el%%k%".$key."%k%%v%".$value."%v%%el%";
  }
  $log_msg.="%args%";
}
$log_msg.="%reg%";
  $log_directorio = "lib_epms_log";
  if (!file_exists($log_directorio)){
      mkdir($log_directorio, 0777, true);
  }
  $date = date('d-m-Y');
  $date_encrypt_end = encrypt_decrypt('encrypt', $date, $date);
  $log_file_data = $log_directorio.'/log_' . $date ."_".$date_encrypt_end.'.log';
  //escribir regisro encriptado en log diario
$encrypted_msg = encrypt_decrypt('encrypt', $log_msg, $date);
  file_put_contents($log_file_data, $encrypted_msg . "\n", FILE_APPEND);
}


//FUNCION PARA ENCRIPTAR/DESENCRIPTAR CONTENIDO
function encrypt_decrypt($action, $string, $date_param) {
    $output = false;
    $metodo_enc = "AES-256-CBC";
    $secret_key = 'b95e7db5ca10d0a7059984'; //KEY SECRETA TIENEN QUE CAMBIARLA
    if (!$date_param) {
      $iv_param = hash('sha256', date('d-m-Y'));
    }else {
      $iv_param = hash('sha256', $date_param);
    }
    $secret_iv = $iv_param;
    $key = hash('sha256', $secret_key);
    $iv = substr(hash('sha256', $secret_iv), 0, 16);
    if ( $action == 'encrypt' ) {
        $output = openssl_encrypt($string, $metodo_enc, $key, 0, $iv);
        $output = base64_encode($output);
    } else if( $action == 'decrypt' ) {
        $output = openssl_decrypt(base64_decode($string), $metodo_enc, $key, 0, $iv);
    }
    return $output;
}

  ?>
