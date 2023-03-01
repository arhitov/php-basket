<?php

use App\Config\Repository as ConfigRepository;

$config_repository = new ConfigRepository();

var_dump($config_repository);echo '<br>';
var_dump($config_repository->get('acquire.list.main'));echo '<br>';
var_dump($config_repository);echo '<br>';