<?php

$dir1 = scandir('/var/www/html/vendor/');
print_r($dir1, true);

$dir1 = scandir('/var/www/html/drupal/vendor/');
print_r($dir1, true);

$dir1 = scandir('/var/www/html/');
print_r($dir1, true);

