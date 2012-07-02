<?php

App::uses('OfcmAppController', 'Ofcm.Controller');
/**
 * Courses Controller
 *
 */
class InstructingsController extends OfcmAppController
{

	public function admin_dataTable($courseid =null, $type='datatable')
	{
		$this->dataTable($courseid, $type);
	}

	public function dataTable($courseid=null, $type='datatable')
	{
		$conditions = array();

		$joins = array(array(
				'table'=>'instructors',
				'alias'=>'Instructor',
				'type'=>'LEFT',
				'conditions'=>array(
					'Instructor.id = Instructing.instructor_id'
				)
			),
			array(
				'table'=>'users',
				'alias'=>'User',
				'type'=>'LEFT',
				'conditions'=>array(
					'User.id = Instructor.user_id'
				)
			),
			array(
				'table'=>'statuses',
				'alias'=>'Status',
				'type'=>'LEFT',
				'conditions'=>array(
					'Status.id = Instructing.status_id'
				)
			),
			array(
				'table'=>'tiers',
				'alias'=>'Tier',
				'type'=>'LEFT',
				'conditions'=>array(
					'Tier.id = Instructing.tier_id'
				)
			)
		);

		switch($type)
		{
			case 'user':
				$conditions['Instructing.user_id'] = $courseid;
				$aColumns = array(
					'CourseType.shortname',
					'Course.startdate',
					'Tier.id',
					'role',
					'Status.id'
				);
				$joins[] = array(
					'table'=>'courses',
					'alias'=>'Course',
					'type'=>'LEFT',
					'conditions'=>array(
						'Course.id = Instructing.course_id'
					));
				$joins[] = array(
					'table'=>'course_types',
					'alias'=>'CourseType',
					'type'=>'LEFT',
					'conditions'=>array(
						'CourseType.id = Course.course_type_id'
					));

			break;

			case 'datatable':
				$conditions['Instructing.course_id'] = $courseid;
				$aColumns = array(
					'',
					'User.name',
					'Agency.name',
					'Tier.name',
					'Status.id',
					'Instructing.created'
				);
				$joins[] = array(
					'table'=>'agencies',
					'alias'=>'Agency',
					'type'=>'LEFT',
					'conditions'=>array(
						'Agency.id = User.agency_id'
					));
			break;
		}

		$order = array(
			'Instructing.created'
		);

		if (isset($_GET['iDisplayStart']) && $_GET['iDisplayLength'] != '-1')
		{
			$limit = $_GET['iDisplayLength'];
			$offset = $_GET['iDisplayStart'];
		}
		else
		{
			$limit = 10;
			$offset = 0;
		}

		if (isset($_GET['iSortCol_0']))
		{
			switch ($type)
			{
				case 'datatable':
					switch($_GET['iSortCol_0'])
					{
						case 0: break;
						case 1: $order = array('User.last_name'=>$_GET['sSortDir_0']); break;
						case 2: $order = array('Agency.name'=>$_GET['sSortDir_0']); break;
						case 3: $order = array('Tier.id'=>$_GET['sSortDir_0']);break;
						case 4: $order = array('Instructing.status_id'=>$_GET['sSortDir_0']); break;
						case 5: $order = array('Instructing.created'=>$_GET['sSortDir_0']); break;
					}
				break;

				case 'user':
					switch($_GET['iSortCol_0'])
					{
						case 0: $order = array('CourseType.shortname'=>$_GET['sSortDir_0']); break;
						case 1: $order = array('Course.startdate'=>$_GET['sSortDir_0']); break;
						case 2: $order = array('Tier.id'=>$_GET['sSortDir_0']); break;
						case 3: $order = array('Instructing.role'=>$_GET['sSortDir_0']); break;
						case 4: $order = array('Instructing.status_id'=>$_GET['sSortDir_0']); break;
					}
				break;
			}
		}


		if (!empty($_GET['sSearch']))
		{
			$or = array();
			$or[] = array('User.first_name LIKE'=>$_GET['sSearch'].'%');
			$or[] = array('User.last_name LIKE'=>$_GET['sSearch'].'%');
			$or[] = array('User.email LIKE'=>$_GET['sSearch'].'%');
			$or[] = array('Tier.name LIKE'=>$_GET['sSearch'].'%');

			$conditions[] = array('or'=>$or);
		}

		for($i = 0; $i<count($aColumns);$i++)
		{
			if ($_GET['bSearchable_'.$i] == "true" && $_GET['sSearch_'.$i] != '' && $_GET['sSearch_'.$i] != '0')
				$conditions[] = array($aColumns[$i] => $_GET['sSearch_'.$i]);
		}

		$found = $this->Instructing->find('count', array(
			'conditions'=>$conditions,
			'joins'=>$joins
		));
		$courses = $this->Instructing->find('all', array(
			'conditions'=>$conditions,
			'order'=>$order,
			'limit'=>$limit,
			'offset'=>$offset,
			'joins'=>$joins,
			'fields'=>'*'
		));

		$this->set('found', $found);
		$this->set('courses', $courses);
		$this->render('Instructings'.DS.'tables'.DS.$type);
	}

	public function admin_bulkEdit($courseid = null)
	{
		foreach($this->request->data['instructing'] as $attid => $selected)
			if ($selected)
			{
				$this->Instructing->id = $attid;
				switch($this->request->data['Instructing']['action'])
				{
					case 'A': $this->Instructing->saveField('status_id', 3); break;
					case 'D': $this->Instructing->saveField('status_id', 7); break;
					case 'W': $this->Instructing->saveField('status_id', 25); break;

					case 'L': $this->Instructing->saveField('role', 'Lead'); break;
					case 'S': $this->Instructing->saveField('role', 'Shadow'); break;
					case 'B': $this->Instructing->saveField('role', ''); break;

					case 'R': $this->Instructing->delete(); break;

				}
			}

		$this->set('message', 'Saved instructor changes');
		//$this->redirect(array('controller'=>'Courses', 'action'=>'view', $courseid, 'instructors'));
	}

	public function instructor_apply($course = null)
	{
		if ($this->request->is('post') || $this->request->is('put'))
		{
			$inst = $this->Instructing->Instructor->findByUserId($this->Auth->user('id'));

			$this->Instructing->Course->contain(array(
				'CourseType'
			));
			$course = $this->Instructing->Course->read(null, $this->request->data['Instructing']['course_id']);
			$tier = $this->Instructing->Instructor->Tier->read(null, $inst['Instructor']['tier_id']);


			$this->request->data['Instructing']['user_id'] = $this->Auth->user('id');
			$this->request->data['Instructing']['instructor_id'] = $inst['Instructor']['id'];
			$this->request->data['Instructing']['tier_id'] = $inst['Instructor']['tier_id'];
			$this->request->data['Instructing']['status_id'] = 1;

			if ($this->Instructing->save($this->request->data))
			{
				$args = array(
					'email_template_id'=>7,
					'from'=>'noreply@alerrt.org',
					'replyTo'=>$this->Auth->user('email'),
					'sendTo'=>'curnutt@alerrt.org'
				);
				$result = $this->_sendTemplateEmail($args, array_merge($this->Session->read('Auth'), $course, $tier));

				$args = array(
					'email_template_id'=>6,
					'from'=>'noreply@alerrt.org',
					'sendTo'=>$this->Auth->user('email'),
					'replyTo'=>'curnutt@alerrt.org'
				);
				$result = $this->_sendTemplateEmail($args, array_merge($this->Session->read('Auth'), $course, $tier));

				$this->Session->setFlash('Successfully applied to teach this course', 'notices/success');
				$this->redirect(array('controller'=>'Courses','action'=>'view', $course['Course']['id']));
			}
			else
			{
				$this->Session->setFlash('Error applying to the course', 'notices/error');
				$this->redirect(array('controller'=>'Courses','action'=>'view', $course['Course']['id']));
			}
		}

		$this->Instructing->Course->contain(array(
			'CourseType',
			'Status'
		));
		$this->set('course', $this->Instructing->Course->read(null, $course));
	}

	public function admin_add($id)
	{
		$this->set('id', $id);

		if ($this->request->is('post') || $this->request->is('put'))
		{
			if (isset($this->request->data['instructor']))
			{
				$instructors = array();
				foreach($this->request->data['instructor'] as $uid => $sel)
				{
					if ($sel)
					{
						$this->Instructing->Instructor->User->contain(array(
							'Instructor.Tier'
						));
						$instructors[] = $this->Instructing->Instructor->User->read(null, $uid);
					}
				}
				$this->set('instructors', $instructors);
				$this->set('statuses', $this->Instructing->Status->find('list'));
				$this->set('tiers', $this->Instructing->Tier->find('list'));
				$this->render('Instructings'.DS.'pages'.DS.'instructor_add');
			}
			elseif (isset($this->request->data['Instructing']))
			{
				if ($this->Instructing->saveMany($this->request->data['Instructing']))
				{
					$this->set('saved', true);
				}
			}
		}
		$this->set('statuses', $this->Instructing->Status->find('list'));
	}
}