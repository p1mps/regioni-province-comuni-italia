<?php
// Generatore schema e import del database comuni italiani (regioni, province, comuni)
// Usage: php generate_cities.php regioni_province.csv comuni.csv

function delete_all($db)
{
     $delete_regioni = "delete from regions";
     $delete_province = "delete from provinces";
     $delete_comuni = "delete from municipalities";

     $result = $db->query($delete_regioni);
     $result = $db->query($delete_province);
     $result = $db->query($delete_comuni);
}


function insert_states($db)
{
     $query_italy = "INSERT INTO states VALUES(1,'ITA','Italia')";
     $result = $db->query($query_italy);
}


function show_error_db($db){

     if($db){
          if($db->error){
               echo $db->error;
               exit(0);
          }
     }
}


function insert_provincia($db, $row_provincia, $row_regione, $provincia_sign, $provincia)
{
     $query_provincia = "INSERT INTO provinces VALUES (" . $row_provincia . "," .  "'". $provincia_sign . "'" . "," . "'" . $provincia . "'" .","  . $row_regione . ")";
     if($db->query($query_provincia))
          echo "inserted province " . $row_provincia . " " . $provincia_sign . " " . $provincia . "\n";
     else
          $db->error;
}


function insert_comuni($comuni_file, $db)
{
     $row_comune = 0;
     if ($comuni_file) {
         while (($line = fgets($comuni_file)) !== false) {
            $line_parsed = str_getcsv($line,';');
            $comune_name = $line_parsed[1];
            $provincia_sign =  $line_parsed[2];
            $comune_cap = $line_parsed[5];
            $result = $db->query("select * from provinces where sign=" . "'" . $provincia_sign . "'");

            while ($row = $result->fetch_assoc()) {
               $provincia_id = $row['id'];
               $regione_id = $row['regions_id'];
            }

            $query_comune = "INSERT INTO municipalities VALUES (" . $row_comune . "," .  '"'. $comune_name . '"' . "," . 1 .","  . $comune_cap . "," . $regione_id . "," . $provincia_id .")";
            if($db->query($query_comune))
               echo "inserted municipality " . $row_comune . " " . $comune_name . " " . $comune_cap . " " . $regione_id . "\n";
             else
               echo $db->error;
            $row_comune++;
       }
     }
}

function insert_province($regioni_province_file, $db)
{
     $row_provincia = 0;
     if ($regioni_province_file) {
         while (($line = fgets($regioni_province_file)) !== false) {
            $line_parsed = str_getcsv($line,';');
            $regione = $line_parsed[9];
            $regione_id = null;
            $provincia = $line_parsed[13];
            $provincia_sign = $line_parsed[14];
            $result = $db->query("select * from regions where name=" . '"' . $regione . '"');
            if($result){
                 while ($row = $result->fetch_assoc()) {
                    $regione_id = $row['id'];
                 }
            }
            else{
             echo "non ho trovato la regione per " . $provincia . "\n";
             exit(0);
            }
            insert_provincia($db, $row_provincia, $regione_id, $provincia_sign, $provincia);
            $row_provincia++;
       }
     }
}


function already_inserted_region($db, $regione)
{
     $result = $db->query('select * from regions where name=' . '"' . $regione . '"');
     if(!$result){
          echo $db->error;
     }
     if($result && $result->num_rows == 0)
          return true;
}


function insert_regioni($regioni_province_file, $db)
{
     $row_regione = 1;
     $row_provincia = 0;
     if ($regioni_province_file) {
         while (($line = fgets($regioni_province_file)) !== false) {
            $line_parsed = str_getcsv($line,';');
            $regione = $line_parsed[9];
            // check se ho già inserito la regione, altrimenti la inserisco
            if(already_inserted_region($db, $regione)){
               $result = $db->query("INSERT INTO regions VALUES (" . $row_regione . "," . '"' . $regione . '"' . ")");
               echo "inserted region " . $regione . " " . $row_regione . "\n";
               $row_regione++;
            }
         }
     } else {
         echo "file regioni e province non trovato! ERRORE";
         exit(0);
     }


}



if(count($argv) != 3){
     echo "Usage: php generate_cities.php regioni_province.csv comuni.csv";
     exit(0);
}


$db = new mysqli("localhost","root","","manager2");
if($db->connect_errno > 0){
    die('Unable to connect to database [' . $db->connect_error . ']');
}


$regioni_province_file = fopen($argv[1], 'r');
$comuni_file = fopen($argv[2], 'r');



$db->query("SET FOREIGN_KEY_CHECKS = 0");
delete_all($db);
insert_states($db);
insert_regioni($regioni_province_file, $db);
fseek($regioni_province_file, 0);
insert_province($regioni_province_file, $db);
insert_comuni($comuni_file, $db);
$db->query("SET FOREIGN_KEY_CHECKS = 1");

fclose($regioni_province_file);
fclose($comuni_file);
$db->close();

?>