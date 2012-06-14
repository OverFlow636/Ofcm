<?php
App::uses('OfcmAppModel', 'Ofcm.Model');
/**
 * Course Model
 *
 */
class Course extends OfcmAppModel
{
	public $actsAs = array(
		'Containable'
	);

	public $belongsTo = array(
		'Ofcm.CourseType',
		'Conference'=>array(
			'counterCache'=>true
		),
		'Funding',
		'ShippingLocation' => array(
			'className' => 'Location',
			'foreignKey' => 'shipping_location_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		),
		'Location',
		'Status'
	);

	public $hasMany = array(
		'Attending'=>array(
			'className'=>'Ofcm.Attending',
			'dependent'=>true
		),
		'Contact'=>array(
			'className'=>'Ofcm.Contact',
			'dependent'=>true
		),
		'Hosting'=>array(
			'className'=>'Ofcm.Hosting',
			'dependent'=>true
		),
		'Instructing'=>array(
			'className'=>'Ofcm.Instructing',
			'dependent'=>true
		)
	);

	public $hasAndBelongsToMany = array(
		'Note'
	);

}
