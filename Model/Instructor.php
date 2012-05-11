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
		'Status'
	);


	public $hasMany = array(
		'Ofcm.Instructing',
		'InstructorTierRequirement'
	);



	function afterFind($results, $primary)
	{
		if ($primary)
		{
			foreach($results as $idx => $instructor)
			{
				//Unserialize the history and make an entry
				if (!empty($results[$idx]['Instructor']['instructed']))
					$results[$idx]['Instructor']['instructed'] = unserialize($results[$idx]['Instructor']['instructed']);
				else
					$results[$idx]['Instructor']['instructed'] = array();

				if (!empty($results[$idx]['Instructor']['attended']))
					$results[$idx]['Instructor']['attended'] = unserialize($results[$idx]['Instructor']['attended']);
				else
					$results[$idx]['Instructor']['attended'] = array();

				//merge attending records with the history data
				$this->Attending = ClassRegistry::init('Ofcm.Attending');
				$this->Attending->contain(array(
					'Course'
				));
				$attend = $this->Attending->findAllByUserId($results[$idx]['Instructor']['user_id']);
				foreach($attend as $i => $attending)
					if ($attending['Attending']['status_id'] == 4)
						$results[$idx]['Instructor']['attended'][$attending['Course']['course_type_id']] = 1;

				//merge instructing records with the history data
				$this->Instructing = ClassRegistry::init('Ofcm.Instructing');
				$this->Instructing->contain(array(
					'Course'
				));
				$taught = $this->Instructing->findAllByUserId($results[$idx]['Instructor']['user_id']);
				foreach($taught as $i => $instructing)
					if ($instructing['Instructing']['status_id'] == 2)
						$results[$idx]['Instructor']['instructed'][$instructing['Course']['course_type_id']] = 1;

				$results[$idx]['Instructor']['additional_courses'] = unserialize($results[$idx]['Instructor']['additional_courses']);
				$results[$idx]['Instructor']['lead_courses'] = unserialize($results[$idx]['Instructor']['lead_courses']);

				$results[$idx]['Instructor']['instruct_or_attend'] = array();
				if (!empty($results[$idx]['Instructor']['instructed']))
					foreach($results[$idx]['Instructor']['instructed'] as $cid => $value)
						if ($value)
							$results[$idx]['Instructor']['instruct_or_attend'][$cid] = 1;
						else
							unset($results[$idx]['Instructor']['instructed'][$cid]);

				if (!empty($results[$idx]['Instructor']['attended']))
					foreach($results[$idx]['Instructor']['attended'] as $cid => $value)
						if ($value)
							$results[$idx]['Instructor']['instruct_or_attend'][$cid] = 1;
						else
							unset($results[$idx]['Instructor']['attended'][$cid]);

				if (isset($results[$idx]['Instructor']['instruct_or_attend'][41]) && isset($results[$idx]['Instructor']['instruct_or_attend'][24]))
					unset($results[$idx]['Instructor']['instruct_or_attend'][41]);

				if (isset($results[$idx]['Instructor']['instructed'][41]) && isset($results[$idx]['Instructor']['instructed'][24]))
					unset($results[$idx]['Instructor']['instructed'][41]);

				if (!empty($results[$idx]['Instructor']['course_enhancement_courses']))
					$results[$idx]['Instructor']['course_enhancement_courses'] = unserialize($results[$idx]['Instructor']['course_enhancement_courses']);

				$results[$idx]['Instructor']['any_instructor'] = ($results[$idx]['Instructor']['basic_instructor'] || $results[$idx]['Instructor']['firearms_instructor'] || $results[$idx]['Instructor']['medical_instructor']);
			}
		}

		return $results;
	}

}
