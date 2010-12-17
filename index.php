<?php
$source = isset($_POST['source']) ? $_POST['source'] : 'http://127.0.0.1/pQuery/test/Agencia_Express.htm';
require 'classes/pQuery.php';
pQuery($source);

p(".seccion.inmuebles")->attr('title','Luca')->clon()->appendTo('#lista_secciones')->attr('title', "Alex")->html('<h1>Hola</h1>');
p(".seccion.vehiculos")->title = 'Luca 2';
p('#header')->clon()->appendTo('#content');

echo p()->outerHtml();