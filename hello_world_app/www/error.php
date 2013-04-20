<?php
/*Hello World
  ----Copyright (c) 2013, Sauder Software, LLC
  --------------------------------------------------------------------
  ----Author: Andrew Mark Sauder, andrew@saudersoftware.com, 240.321.9009
  ----Creation Date: April 2013
  ---->=PHP 5.4.5 REQUIRED
  --------------------------------------------------------------------*/

$_GET['R1'] = 'error';
include_once($_SERVER['DOCUMENT_ROOT']."/../AS/bin/loader.php");

$AS = new ASloader();

$AS->startApp();

$c = $AS->renderApp();

echo $c;
die();