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
		'Ofcm.Attending',
		'Ofcm.Contact',
		'Ofcm.Hosting',
		'Ofcm.Instructing'
	);

	public $hasAndBelongsToMany = array(
		'Note'
	);

}
