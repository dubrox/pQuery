<?php
$source = isset($_POST['source']) ? $_POST['source'] : 'test/Agencia_Express.htm';
require 'classes/pQuery.php';
pQuery($source);

p(".seccion.inmuebles")->attr('title','Luke')->clon()->appendTo('#lista_secciones')->attr('title', 'I\'m your father!')->html('<h1>Robin</h1>');
p(".seccion.vehiculos")->title = 'Luke';
p('#header')->append('<h1>Batman</h1>');

echo p()->outerHtml();