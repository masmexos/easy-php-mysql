

<?php

require_once("lib_epms.php");
//en el caso de querer ver un registro de otro dia cambiar $date en el formato correspondiente, ej: 20-07-2019
$date = date('d-m-Y');
$date_encrypt_end = encrypt_decrypt('encrypt', $date, $date);

$fileName='lib_epms_log/log_' . $date .'_'.$date_encrypt_end.'.log';

if ( file_exists($fileName) && ($fn = fopen($fileName, "r"))!==false ) {

  while(! feof($fn))  {
  $result = fgets($fn);
  $decrypted_msg = encrypt_decrypt('decrypt', $result,$date);
  echo $decrypted_msg;
  echo "<br>";
  }

  fclose($fn);
}
else
{
  echo "No se encontro un archivo con la fecha especificada, pruebe otra";
}
 ?>
