<?php

Router::connect('/Course-Catalog',					array('plugin'=>'ofcm', 'controller'=>'CourseTypes', 'action'=>'catalog'));
Router::connect('/Course-Catalog/Level1',			array('plugin'=>'ofcm', 'controller'=>'CourseTypes', 'action'=>'catalog', 19));
Router::connect('/Course-Catalog/Level2',			array('plugin'=>'ofcm', 'controller'=>'CourseTypes', 'action'=>'catalog', 23));
Router::connect('/Course-Catalog/FORT',				array('plugin'=>'ofcm', 'controller'=>'CourseTypes', 'action'=>'catalog', 20));
Router::connect('/Course-Catalog/Breaching',		array('plugin'=>'ofcm', 'controller'=>'CourseTypes', 'action'=>'catalog', 21));
Router::connect('/Course-Catalog/LowLight',			array('plugin'=>'ofcm', 'controller'=>'CourseTypes', 'action'=>'catalog', 22));
Router::connect('/Course-Catalog/TrainTheTrainer',	array('plugin'=>'ofcm', 'controller'=>'CourseTypes', 'action'=>'catalog', 26));

Router::connect('/Certificates',					array('plugin'=>'ofcm', 'controller'=>'Certificates', 'action'=>'search'));

Router::connect('/CourseAdmin/*', array('admin'=>true,'plugin'=>'ofcm', 'controller'=>'Courses', 'action'=>'view'));



//oldstie

Router::connect('/Upcoming/*',				array('plugin'=>'ofcm', 'controller'=>'Courses', 'action'=>'upcoming'));
Router::connect('/Training',				array('plugin'=>'ofcm', 'controller'=>'Courses', 'action'=>'upcoming'));
Router::connect('/Training/Course-Catalog',	array('plugin'=>'ofcm', 'controller'=>'CourseTypes', 'action'=>'catalog'));