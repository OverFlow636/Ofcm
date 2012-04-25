<?php
App::uses('OfcmAppModel', 'Ofcm.Model');
/**
 * Hosting Model
 *
 * @property Agency $Agency
 * @property Course $Course
 * @property Status $Status
 */
class Hosting extends OfcmAppModel
{

	public $belongsTo = array(
		'Agency',
		'Ofcm.Course',
		'Status'
	);
}
