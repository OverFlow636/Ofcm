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
		'Ofum.User',
		'Tier',
		'Status',
		'Ofcm.InstructorHistory',
		'Location'
	);

	public $hasMany = array(
		'Ofcm.Instructing',
		'InstructorTierRequirement',
		'TierReview'
	);


}
