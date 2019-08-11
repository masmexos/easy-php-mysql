<?php

require_once("lib_epms.php");

/****************************************************************/
//TESTING ZONE
/****************************************************************/

//ejecutarConsulta($conexion,$tabla,$params,$allowed_params=false,$allowed_where_params=false,$select_especial_filters=false)
//agregarParametroObjetivo($array_params,$var_key,$var_value=false,$var_value_tipo=false,$optional=false)
//agregarParametroWhere($array_params,$var_key,$var_value,$var_value_tipo,$condicion=false,$optional=false)
//crearArraySelectEspecialFilters($limit=false,$offset=false,$order=false,$order_param=false)


//DESCOMENTAR Y CONFIGURAR CON LOS DATOS QUE CORRESPONDA O EDITAR LA FUNCION CON LOS DATOS PARA USAR EL MODO FACIL.
//$conexion=conectarBD(false,"NOMBRE BD",false,"USER BD","PASS USER");

//echo "<br><br>------PROBANDO SELECT-------<br><br>";

$array_params=crearArrayParametros('select');
$array_params=agregarParametroObjetivo($array_params,'*');
$select_especial_filters=crearArraySelectEspecialFilters(3,1,"ASC","id");
//$resultado=ejecutarConsulta($conexion,"prueba",$array_params,false,false,$select_especial_filters); var_dump($resultado);

//echo "<br><br>------PROBANDO INSERT-------<br><br>";

$array_params=crearArrayParametros('insert');
$array_params=agregarParametroObjetivo($array_params,'nombre','var de prueba prueba','string');
$array_params=agregarParametroObjetivo($array_params,'descripcion','probando','string');
//$resultado=ejecutarConsulta($conexion,"prueba",$array_params); var_dump($resultado);


//echo "<br><br>------PROBANDO DELETE-------<br><br>";

$array_params=crearArrayParametros('delete');
$array_params=agregarParametroWhere($array_params,'nombre','gaga','string',"igual");
//$resultado=ejecutarConsulta($conexion,"prueba",$array_params); var_dump($resultado);


//echo "<br><br>------PROBANDO UPDATE-------<br><br>";

$array_params=crearArrayParametros('update');
$array_params=agregarParametroObjetivo($array_params,'nombre','modificado!','string');
$array_params=agregarParametroWhere($array_params,'id',2,'int',"igual");
//$resultado=ejecutarConsulta($conexion,"prueba",$array_params); var_dump($resultado);

/****************************************************************/
//FIN TESTING ZONE
/****************************************************************/


 ?>
