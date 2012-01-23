<?php
Router::connect('/Course-Catalog',					array('plugin'=>'ofcm', 'controller'=>'CourseTypes', 'action'=>'catalog'));
Router::connect('/Course-Catalog/Level1',			array('plugin'=>'ofcm', 'controller'=>'CourseTypes', 'action'=>'catalog', 19));
Router::connect('/Course-Catalog/Level2',			array('plugin'=>'ofcm', 'controller'=>'CourseTypes', 'action'=>'catalog', 23));
Router::connect('/Course-Catalog/FORT',				array('plugin'=>'ofcm', 'controller'=>'CourseTypes', 'action'=>'catalog', 20));
Router::connect('/Course-Catalog/Breaching',		array('plugin'=>'ofcm', 'controller'=>'CourseTypes', 'action'=>'catalog', 21));
Router::connect('/Course-Catalog/LowLight',			array('plugin'=>'ofcm', 'controller'=>'CourseTypes', 'action'=>'catalog', 22));
Router::connect('/Course-Catalog/TrainTheTrainer',	array('plugin'=>'ofcm', 'controller'=>'CourseTypes', 'action'=>'catalog', 26));