<?php
App::uses('OfcmAppModel', 'Ofcm.Model');
/**
 * Certificate Model
 *
 * @property Survey $Survey
 */
class Certificate extends OfcmAppModel
{
	public $useTable=false;

	public $hasAndBelongsToMany = array(
		'Survey' => array(
			'className' => 'Survey',
			'joinTable' => 'survey_certificates',
			'foreignKey' => 'certificate_id',
			'associationForeignKey' => 'survey_id',
			'unique' => true,
			'conditions' => '',
			'fields' => '',
			'order' => '',
			'limit' => '',
			'offset' => '',
			'finderQuery' => '',
			'deleteQuery' => '',
			'insertQuery' => ''
		)
	);

}
