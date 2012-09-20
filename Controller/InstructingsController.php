<?php

App::uses('OfcmAppController', 'Ofcm.Controller');
/**
 * Courses Controller
 *
 */
class InstructingsController extends OfcmAppController
{
	var $allowedActions = array(
		'tierChange'
	);

	public function tierChange($instructing, $approve)
	{
		$this->Instructing->id = $instructing;
		$courseid = $this->Instructing->field('course_id');
		$iid = $this->Instructing->field('user_id');
		$user = $this->Instructing->User->read(null, $iid);

		$series = array();
		$this->Instructing->Course->contain(array('CourseType'));
		$firstcourse = $course = $this->Instructing->Course->read(null, $courseid);
		$series[] = $courseid;

		while ($course['Course']['next_course_id']!=null)
		{
			$this->Instructing->Course->contain();
			$id = $course['Course']['next_course_id'];
			$course = $this->Instructing->Course->read(null, $id);
			$series[] = $id;
		}

		if ($approve)
		{
			$this->Instructing->updateAll(
				array('status_id'=>3),
				array('course_id'=>$series, 'Instructing.user_id'=>$iid)
			);

			//send approval to instructor
			$args = array(
				'email_template_id'=>5,
				'sendTo'=>$user['User']['email'],
				'from'=>array('curnutt@alerrt.org'=>'John Curnutt')
			);
			$firstcourse['Course']['CourseType'] = $firstcourse['CourseType'];
			$user['Instructor']['User']['first_name'] = $user['User']['first_name'];
			$this->_sendTemplateEmail($args, array_merge($user, $firstcourse));


		}
		else
		{
			$this->Instructing->updateAll(
				array('status_id'=>7),
				array('course_id'=>$series, 'Instructing.user_id'=>$iid)
			);

		}

		//let john know they made a decision
		$args = array(
			'email_template_id'=>16,
			'from'=>$user['User']['email'],
		);
		if (Configure::read('debug'))
			$args['sendTo'] = 'jan@alerrt.org';
		else
			$args['sendTo'] = 'curnutt@alerrt.org';
		$this->_sendTemplateEmail($args, array_merge($user, $firstcourse, array('Tc'=>array('action'=>($approve?'Approved':'Declined')))));


		$this->redirect(array('instructor'=>true,'controller'=>'Courses', 'action'=>'view', $firstcourse['Course']['id']));
	}

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
				$set = true;

			case 'instructor':
				if (!isset($set))
					$conditions['Instructing.instructor_id'] = $courseid;
				$type='user';

				$aColumns = array(
					'CourseType.shortname',
					'Course.startdate',
					'Tier.id',
					'role',
					'InvoiceStatus.id',
					'signed_date',
					'id'
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

			case 'invoice':

				$conditions['Instructing.instructor_id'] = $courseid;
				$conditions['Instructing.invoice_status_id NOT'] = null;

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
					'table'=>'statuses',
					'alias'=>'InvoiceStatus',
					'type'=>'LEFT',
					'conditions'=>array(
						'InvoiceStatus.id = Instructing.invoice_status_id'
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
					'Instructing.role',
					'Tier.name',
					'Status.id',
					'Instructing.created'
				);
				$joins[] = array(
					'table'=>'tier_reviews',
					'alias'=>'TierReview',
					'type'=>'LEFT',
					'conditions'=>array(
						'TierReview.instructor_id = Instructor.id',
						'TierReview.tier_id = Instructor.tier_id',
						'TierReview.status_id = 1'
					));
				$joins[] = array(
					'table'=>'tiers',
					'alias'=>'InstructorTier',
					'type'=>'LEFT',
					'conditions'=>array(
						'InstructorTier.id = Instructor.tier_id'
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
						case 2: $order = array('Instructing.role'=>$_GET['sSortDir_0']); break;
						case 3: $order = array('Tier.id'=>$_GET['sSortDir_0']);break;
						case 4: $order = array('Instructing.status_id'=>$_GET['sSortDir_0']); break;
						case 5: $order = array('Instructing.created'=>$_GET['sSortDir_0']); break;
					}
				break;

				case 'user':
				case 'invoice':
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
		$series = array();
		$this->Instructing->Course->contain();
		$course = $this->Instructing->Course->read(null, $courseid);
		$series[] = $courseid;

		while ($course['Course']['next_course_id']!=null)
		{
			$this->Instructing->Course->contain();
			$id = $course['Course']['next_course_id'];
			$course = $this->Instructing->Course->read(null, $id);
			$series[] = $id;
		}

		foreach($this->request->data['instructing'] as $attid => $selected)
			if ($selected)
			{
				$this->Instructing->id = $attid;
				$iid = $this->Instructing->field('user_id');
				switch($this->request->data['Instructing']['action'])
				{
					case 'A': $this->Instructing->updateAll(array('status_id'=>3, 'invoice_status_id'=>1), array('course_id'=>$series, 'Instructing.user_id'=>$iid)); break;
					case 'D': $this->Instructing->updateAll(array('status_id'=>7,'role'=>'null','tier_review_id'=>'null'), array('course_id'=>$series, 'Instructing.user_id'=>$iid)); break;
					case 'W': $this->Instructing->updateAll(array('status_id'=>25,'role'=>'null','tier_review_id'=>'null'), array('course_id'=>$series, 'Instructing.user_id'=>$iid)); break;

					case 'L': $this->Instructing->updateAll(array('role'=>'\'Lead\'','tier_review_id'=>'null'), array('course_id'=>$series, 'Instructing.user_id'=>$iid)); break;
					case 'S': $this->Instructing->updateAll(array('role'=>'\'Shadow\'','tier_review_id'=>'null'), array('course_id'=>$series, 'Instructing.user_id'=>$iid)); break;
					case 'B': $this->Instructing->updateAll(array('role'=>'null','tier_review_id'=>'null'), array('course_id'=>$series, 'Instructing.user_id'=>$iid)); break;

					case 'M':
						$this->Instructing->updateAll(array('tier_id'=>$this->request->data['Instructing']['tier_id']), array('course_id'=>$series, 'Instructing.user_id'=>$iid));
						$this->Instructing->updateAll(array('status_id'=>28), array('course_id'=>$series, 'Instructing.user_id'=>$iid));
					break;

					case 'T':
						//lid is the user id of the instructor checked, need to get his tier review id
						$this->loadModel('Ofcm.Instructor');
						$this->Instructor->contain(array('User'));
						$ins = $this->Instructor->read(null, $this->Instructing->field('instructor_id'));

						$this->loadModel('TierReview');
						$tr = $this->TierReview->findByInstructorIdAndTierId($ins['Instructor']['id'], $ins['Instructor']['tier_id']);

						//review_id
						$this->Instructing->updateAll(array('role'=>'\'TR: '.$ins['User']['first_name'].'\'', 'tier_review_id'=>$tr['TierReview']['id']), array('course_id'=>$series, 'Instructing.user_id'=>$this->request->data['Instructing']['review_id']));

					break;

					case 'R': $this->Instructing->deleteAll(array('course_id'=>$series, 'Instructing.user_id'=>$iid)); break;

				}
			}

		$this->set('message', 'Saved instructor changes');
		//$this->redirect(array('controller'=>'Courses', 'action'=>'view', $courseid, 'instructors'));
	}

	public function instructor_apply($course = null)
	{
		//<editor-fold defaultstate="collapsed" desc="post">
		if ($this->request->is('post') || $this->request->is('put'))
		{
			$inst = $this->Instructing->Instructor->findByUserId($this->Auth->user('id'));

			$this->Instructing->Course->contain(array(
				'CourseType'
			));
			$course = $this->Instructing->Course->read(null, $this->request->data['Instructing']['course_id']);
			if (empty($this->request->data['Instructing']['tier_select']))
				$tier = $this->Instructing->Instructor->Tier->read(null, $inst['Instructor']['tier_id']);
			else
				$tier = $this->Instructing->Instructor->Tier->read(null, $this->request->data['Instructing']['tier_select']);

			$this->request->data['Instructing']['user_id'] = $this->Auth->user('id');
			$this->request->data['Instructing']['instructor_id'] = $inst['Instructor']['id'];
			$this->request->data['Instructing']['tier_id'] = $tier['Tier']['id'];
			$this->request->data['Instructing']['status_id'] = 1;

			if ($this->Instructing->save($this->request->data))
			{
				while ($course['Course']['next_course_id']!=null)
				{
					$this->Instructing->Course->contain();
					$id = $course['Course']['next_course_id'];
					$course = $this->Instructing->Course->read(null, $id);

					$this->request->data['Instructing']['course_id'] = $id;
					$this->Instructing->create();
					$this->Instructing->save($this->request->data);
				}

				$args = array(
					'email_template_id'=>7,
					'from'=>'noreply@alerrt.org',
					'replyTo'=>$this->Auth->user('email'),
				);

				if (Configure::read('debug'))
					$args['sendTo'] = 'jan@alerrt.org';
				else
					$args['sendTo'] = 'curnutt@alerrt.org';

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
		//</editor-fold>

		$id = $course;
		$this->Instructing->Course->contain(array('CourseType','Status', 'Funding'));
		$course = $this->Instructing->Course->read(null, $id);
		$list[$id] = $course;

		while ($course['Course']['next_course_id']!=null)
		{
			$this->Instructing->Course->contain(array('CourseType','Status', 'Funding'));
			$id = $course['Course']['next_course_id'];
			$course = $this->Instructing->Course->read(null, $id);
			$list[$id] = $course;
		}

		$this->set('courses', $list);

		$this->Instructing->Instructor->contain(array('Tier'));
		$this->set('instructor', $this->Instructing->Instructor->findByUserId($this->Auth->user('id')));

		$this->set('tiers', $this->Instructing->Tier->find('all'));
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


	public function instructor_invoices()
	{
		$ins = $this->Instructing->Instructor->findByUserId($this->Auth->user('id'));
		if ($ins)
		{
			$inv = $this->Instructing->find('all', array(
				'conditions'=>array(
					'invoice_status_id NOT'=>null,
					'Course.previous_course_id'=>null
				),
				'contain'=>array(
					'User',
					'Status',
					'Tier',
					'Course.Funding',
					'Course.CourseType',
					'InvoiceStatus'
				)
			));

			$series = array();
			foreach($inv as $invoice)
			{
				$courses = array();
				$course = $invoice['Course'];
				$courses[] = $course;
				while($course['next_course_id'])
				{
					$this->Instructing->Course->contain(array('Funding','CourseType'));
					$course = $this->Instructing->Course->read(null, $course['next_course_id']);
					$fun = $course['Funding'];
					$ct = $course['CourseType'];
					$course = $course['Course'];
					$course['Funding'] = $fun;
					$course['CourseType'] = $ct;
					$courses[] = $course;
				}

				unset($invoice['Course']);
				$series[] = array(
					'Invoice'=>$invoice,
					'Courses'=>$courses
				);
			}

			$this->set('series', $series);
		}
	}

	public function instructor_printInvoice($id)
	{
		$this->Instructing->contain(array(
			'Instructor.Location.City',
			'Instructor.Location.State',
			'User',
			'Tier'
		));
		$ins = $this->Instructing->read(null, $id);
		$this->set('instructing', $ins);


		$this->Instructing->Course->contain(array('CourseType', 'Funding'));
		$course = $this->Instructing->Course->read(null, $ins['Instructing']['course_id']);
		$list[$id] = $course;
		while ($course['Course']['next_course_id']!=null)
		{
			$this->Instructing->Course->contain(array('CourseType', 'Funding'));
			$id = $course['Course']['next_course_id'];
			$course = $this->Instructing->Course->read(null, $id);
			$list[$id] = $course;
		}

		$this->set('courses', $list);
	}

	public function instructor_signInvoice($id)
	{
		if ($this->request->is('post') || $this->request->is('put'))
		{
			if (empty($this->request->data['Instructing']['name']) || $this->request->data['Instructing']['name'] != $this->Auth->user('first_name').' '.$this->Auth->user('last_name'))
				$this->Session->setFlash('Please enter your name exactly how it is below.', 'notices/error');
			else
			{
				$this->Instructing->id = $id;
				$this->Instructing->saveField('invoice_status_id', 29);
				$this->Instructing->saveField('signed_date', date('Y-m-d'));
				$this->Session->setFlash('Successfully signed and submitted invoice.', 'notices/success');

				$this->Instructing->contain(array(
					'User'
				));
				$ins = $this->Instructing->read(null, $id);

				$args = array(
					'email_template_id'=>18,
					'sendTo'=>'jan@alerrt.org',
					'from'=>array($ins['User']['email']=>$ins['User']['name']),
					'replyTo'=>$ins['User']['email']
				);
				$result = $this->_sendTemplateEmail($args, $ins);


				$this->redirect(array('action'=>'invoices'));


			}
		}

		$this->Instructing->contain(array(
			'Instructor.Location.City',
			'Instructor.Location.State',
			'User',
			'Tier'
		));
		$ins = $this->Instructing->read(null, $id);
		$this->set('instructing', $ins);


		$this->Instructing->Course->contain(array('CourseType', 'Funding'));
		$course = $this->Instructing->Course->read(null, $ins['Instructing']['course_id']);
		$list[$id] = $course;
		while ($course['Course']['next_course_id']!=null)
		{
			$this->Instructing->Course->contain(array('CourseType', 'Funding'));
			$id = $course['Course']['next_course_id'];
			$course = $this->Instructing->Course->read(null, $id);
			$list[$id] = $course;
		}

		$this->set('courses', $list);
	}
}