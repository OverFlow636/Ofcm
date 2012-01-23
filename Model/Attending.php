<?php
App::uses('OfcmAppModel', 'Ofcm.Model');
/**
 * Attending Model
 *
 * @property User $User
 * @property Course $Course
 * @property Conference $Conference
 * @property Status $Status
 * @property Payment $Payment
 * @property TeleformData $TeleformData
 * @property Studentlist $Studentlist
 * @property TeleformDatum $TeleformDatum
 */
class Attending extends OfcmAppModel
{

	public $actsAs = array(
		'Containable'
	);

	public $validate = array(
		'user_id' => array(
			'numeric' => array(
				'rule' => array('numeric'),
				//'message' => 'Your custom message here',
				//'allowEmpty' => false,
				//'required' => false,
				//'last' => false, // Stop validation after this rule
				//'on' => 'create', // Limit validation to 'create' or 'update' operations
			),
		),
		'course_id' => array(
			'numeric' => array(
				'rule' => array('numeric'),
				//'message' => 'Your custom message here',
				//'allowEmpty' => false,
				//'required' => false,
				//'last' => false, // Stop validation after this rule
				//'on' => 'create', // Limit validation to 'create' or 'update' operations
			),
		),
		'conference_id' => array(
			'numeric' => array(
				'rule' => array('numeric'),
				//'message' => 'Your custom message here',
				//'allowEmpty' => false,
				//'required' => false,
				//'last' => false, // Stop validation after this rule
				//'on' => 'create', // Limit validation to 'create' or 'update' operations
			),
		),
		'status_id' => array(
			'numeric' => array(
				'rule' => array('numeric'),
				//'message' => 'Your custom message here',
				//'allowEmpty' => false,
				//'required' => false,
				//'last' => false, // Stop validation after this rule
				//'on' => 'create', // Limit validation to 'create' or 'update' operations
			),
		),
	);

	//The Associations below have been created with all possible keys, those that are not needed can be removed

/**
 * belongsTo associations
 *
 * @var array
 */
	public $belongsTo = array(
		'Ofum.User',
		'Ofcm.Course',
		'Conference',
		'Status',
		'Payment',
		'TeleformData'
	);

/**
 * hasMany associations
 *
 * @var array
 */
	public $hasMany = array(
		'Studentlist' => array(
			'className' => 'Studentlist',
			'foreignKey' => 'attending_id',
			'dependent' => false,
			'conditions' => '',
			'fields' => '',
			'order' => '',
			'limit' => '',
			'offset' => '',
			'exclusive' => '',
			'finderQuery' => '',
			'counterQuery' => ''
		),
		'TeleformDatum' => array(
			'className' => 'TeleformDatum',
			'foreignKey' => 'attending_id',
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
