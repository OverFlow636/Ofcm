<?php
App::uses('OfcmAppModel', 'Ofcm.Model');
/**
 * Instructor Model
 *
 * @property User $User
 * @property Tier $Tier
 * @property Status $Status
 * @property Instructing $Instructing
 * @property InstructorTierRequirement $InstructorTierRequirement
 */
class Instructor extends OfcmAppModel
{
	public $actsAs = array(
		'Containable'
	);

	public $belongsTo = array(
		'User' => array(
			'className' => 'User',
			'plugin'=>'ofum',
			'foreignKey' => 'user_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		),
		'Tier' => array(
			'className' => 'Tier',
			'foreignKey' => 'tier_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		),
		'Status'
	);


	public $hasMany = array(
		'Ofcm.Instructing',
		'InstructorTierRequirement' => array(
			'className' => 'InstructorTierRequirement',
			'foreignKey' => 'instructor_id',
			'dependent' => false,
			'conditions' => '',
			'fields' => '',
			'order' => '',
			'limit' => '',
			'offset' => '',
			'exclusive' => '',
			'finderQuery' => '',
			'counterQuery' => ''
		)
	);

}
