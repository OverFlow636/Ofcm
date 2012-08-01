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
class InstructorHistory extends OfcmAppModel
{
	public $actsAs = array(
		'Containable'
	);

	public $belongsTo = array(
		'Instructor'
	);

	function afterFind($results, $primary)
	{
		if (isset($results['id']))
			return $this->processData($results);

		foreach($results as $idx => $instructor)
			if (!empty($results[$idx]['InstructorHistory']['id']))
				$results[$idx]['InstructorHistory'] = $this->processData($instructor['InstructorHistory']);

		return $results;
	}


	private function processData($data)
	{
		//Unserialize the history and make an entry
		if (!empty($data['instructed']))
			$data['instructed'] = unserialize($data['instructed']);
		else
			$data['instructed'] = array();

		if (!empty($data['attended']))
			$data['attended'] = unserialize($data['attended']);
		else
			$data['attended'] = array();

		//merge attending records with the history data
		$this->Attending = ClassRegistry::init('Ofcm.Attending');
		$this->Attending->contain(array(
			'Course'
		));
		$this->Instructor->id = $data['instructor_id'];
		$attend = $this->Attending->findAllByUserId($this->Instructor->field('user_id'));
		foreach($attend as $i => $attending)
			if ($attending['Attending']['status_id'] == 4 || $attending['Attending']['status_id'] == 16 || $attending['Attending']['status_id'] == 22 ||$attending['Attending']['status_id'] == 23)
				$data['attended'][$attending['Course']['course_type_id']] = 1;


		//merge instructing records with the history data
		$this->Instructing = ClassRegistry::init('Ofcm.Instructing');
		$this->Instructing->contain(array(
			'Course'
		));
		$taught = $this->Instructing->findAllByInstructorIdAndStatusId($data['instructor_id'], 3);
		if (!isset($data['processed']))
			$data['sponsored_courses_taught'] += count($taught);
		foreach($taught as $i => $instructing)
		{
			$data['instructed'][$instructing['Course']['course_type_id']] = 1;
			//lead
			if ($instructing['Instructing']['role'] == 'Lead')
				$data['lead']++;
		}

		$data['additional_courses'] = unserialize($data['additional_courses']);
		$data['lead_courses'] = unserialize($data['lead_courses']);

		$data['instruct_or_attend'] = array();
		if (!empty($data['instructed']))
			foreach($data['instructed'] as $cid => $value)
				if ($value)
					$data['instruct_or_attend'][$cid] = 1;
				else
					unset($data['instructed'][$cid]);

		if (!empty($data['attended']))
			foreach($data['attended'] as $cid => $value)
				if ($value)
					$data['instruct_or_attend'][$cid] = 1;
				else
					unset($data['attended'][$cid]);

		if (isset($data['instruct_or_attend'][41]) && isset($data['instruct_or_attend'][24]))
			unset($data['instruct_or_attend'][41]);

		if (isset($data['instructed'][41]) && isset($data['instructed'][24]))
			unset($data['instructed'][41]);

		if (!empty($data['course_enhancement_courses']))
			$data['course_enhancement_courses'] = unserialize($data['course_enhancement_courses']);

		$data['any_instructor'] = ($data['basic_instructor'] || $data['firearms_instructor'] || $data['medical_instructor']);
		$data['processed'] = true;
		return $data;
	}

}
