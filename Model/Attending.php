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

	public $belongsTo = array(
		'Ofum.User',
		'Course'=>array(
			'className'=>'Ofcm.Course',
			'counterCache'=>true
		),
		'Conference',
		'Status',
		'Payment',
		'TeleformData',
		'RegisteredBy'=>array(
			'plugin'=>'Ofum',
			'className'=>'User',
			'foreignKey'=>'registered_by_id'
		),
		'ConfirmationEmail'=>array(
			'className'=>'Message',
			'foreignKey'=>'confirmation_message_id'
		),
		'CertStatusEmail'=>array(
			'className'=>'Message',
			'foreignKey'=>'certificate_message_id'
		)
	);

	public $hasMany = array(
		'Studentlist',
		'TeleformData'
	);

}
