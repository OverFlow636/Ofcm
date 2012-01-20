<?php

App::uses('OfcmAppModel', 'Ofcm.Model');
class CourseType extends OfcmAppModel
{

	public function __construct()
	{
		$modelVars = Configure::read('Ofcm.CourseTypeModel');
		foreach($modelVars as $var => $value)
			$this->$var = $value;

		$this->actsAs[] = 'Containable';
		$this->hasMany[] = 'Ofcm.Course';
		parent::__construct();
	}

}
