<?php
App::uses('OfcmAppModel', 'Ofcm.Model');

class Contact extends OfcmAppModel
{

	public $belongsTo = array(
		'Ofum.User' ,
		'Course'=>array(
			'className'=>'Ofcm.Course',
			'counterCache'=>true
		) ,
		'Status'
	);
}
