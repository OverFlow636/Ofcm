<?php
App::uses('OfcmAppModel', 'Ofcm.Model');

class Contact extends OfcmAppModel
{

	public $belongsTo = array(
		'Ofum.User' ,
		'Ofcm.Course' ,
		'Status'
	);
}
