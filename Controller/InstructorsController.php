<?php
App::uses('OfcmAppController', 'Ofcm.Controller');
/**
 * Instructors Controller
 *
 */
class InstructorsController extends OfcmAppController
{

	public function instructor_testRequirements($id = null)
	{
		$this->Instructor->contain(array(
			'User'
		));
		$data = $this->Instructor->read(null, $id);
		$this->set('instructor', $data);

		$this->set('results', $this->Instructor->Tier->TierRequirement->test($data));
		$this->Instructor->Tier->contain(array(
			'TierRequirement'
		));
		$this->set('tiers', $this->Instructor->Tier->find('all'));
	}

	public function instructor_index()
	{
		$this->Instructor->contain(array(
			'Tier',
			'Status'
		));
		$this->set('instructors', $this->Instructor->find('all', array('order'=>'created DESC', 'limit'=>10)));
	}
}
