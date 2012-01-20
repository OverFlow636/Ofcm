<?php

App::uses('AppController', 'Controller');
class OfcmAppController extends AppController
{

    public function beforeFilter()
	{
        Configure::load('Ofcm.ofcm');

		parent::beforeFilter();
    }

}

