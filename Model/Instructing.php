<?php
App::uses('OfcmAppModel', 'Ofcm.Model');
/**
 * Instructing Model
 *
 * @property User $User
 * @property Course $Course
 * @property Status $Status
 */
class Instructing extends OfcmAppModel
{
	public $actsAs = array(
		'Containable'
	);

	public $belongsTo = array(
		'Ofcm.Instructor',
		'Ofcm.Course',
		'Status',
		'Tier',
		'ConfirmationEmail'=>array(
			'className'=>'Message',
			'foreignKey'=>'confirmation_message_id'
		),
		'AfterActionEmail'=>array(
			'className'=>'Message',
			'foreignKey'=>'afteraction_message_id'
		)
	);
}
