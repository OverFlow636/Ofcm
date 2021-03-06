<?php
App::uses('OfcmAppController', 'Ofcm.Controller');
/**
 * Instructors Controller
 *
 */
class InstructorsController extends OfcmAppController
{
	/**
	 *Instructor application process
	 */
	public function apply($step = 'apply')
	{

		$this->render('Instructors'.DS.'applicationpages'.DS.$step);
	}




	public function instructor_testRequirements($id = null)
	{
		$this->Instructor->contain(array(
			'User',
			'InstructorHistory'
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
			'Status',
			'User'
		));
		$this->set('instructors', $this->Instructor->find('all', array('order'=>'Instructor.created DESC')));
	}

	public function instructor_historyPrint($id = null)
	{
		$this->Instructor->contain(array(
			'User',
			'InstructorHistory'
		));
		$data = $this->Instructor->read(null, $id);
		$this->set('instructor', $data);

		$this->set('results', $this->Instructor->Tier->TierRequirement->test($data));
		$this->Instructor->Tier->contain(array(
			'TierRequirement'
		));
		$this->set('tiers', $this->Instructor->Tier->find('all'));

		$this->layout = 'ajax';
	}

	public function instructor_history($id = null)
	{
		$this->Instructor->contain(array('InstructorHistory'));
		$instructor = $this->Instructor->read(null, $id);
		if ($instructor['Instructor']['instructor_history_id'])
			$this->set('instructor', $instructor);
		else
		{
			$this->Session->setFlash('Please fill out the history document', 'notices/success');
			$this->redirect(array('action'=>'add_history', $id));
		}


	}

	public function instructor_add_history($id = null)
	{
		if ($this->request->is('post') || $this->request->is('put'))
		{
			$ins = $this->Instructor->read(null, $id);
			$history_id = $ins['Instructor']['instructor_history_id'];
			if ($history_id)
				$this->request->data['InstructorHistory']['id'] = $history_id;

			$this->request->data['InstructorHistory']['instructor_id'] = $id;

			$this->request->data['InstructorHistory']['contract_year'] = $this->request->data['InstructorHistory']['contract_year']['year'];
			$this->request->data['InstructorHistory']['basic_instructor_year'] = $this->request->data['InstructorHistory']['basic_instructor_year']['year'];
			$this->request->data['InstructorHistory']['firearms_instructor_year'] = $this->request->data['InstructorHistory']['firearms_instructor_year']['year'];

			$this->request->data['InstructorHistory']['instructed'] = serialize($this->request->data['instructed']);
			$this->request->data['InstructorHistory']['attended'] = serialize($this->request->data['attended']);
			$this->request->data['InstructorHistory']['additional_courses'] = serialize($this->request->data['additional_courses']);
			$this->request->data['InstructorHistory']['lead_courses'] = serialize($this->request->data['InstructorHistory']['lead_courses']);
			$this->request->data['InstructorHistory']['course_enhancement_courses']	= serialize($this->request->data['InstructorHistory']['course_enhancement_courses']);

			//die(pr($this->request->data['InstructorHistory']));
			if ($this->Instructor->InstructorHistory->save($this->request->data['InstructorHistory']))
			{
				if (!$history_id)
					$this->Instructor->save(array('id'=>$id, 'instructor_history_id'=>$this->Instructor->InstructorHistory->getLastInsertId()));

				$this->Session->setFlash('Successfully saved history data', 'notices/success');
				$this->redirect(array('plugin'=>'ofum','controller'=>'users','action'=>'view', $ins['Instructor']['user_id']));
			}
			else
			{
				$this->Session->setFlash('Error saving history data', 'notices/error');
				$this->redirect(array('plugin'=>'ofum','controller'=>'users','action'=>'view', $ins['Instructor']['user_id']));
			}
		}


		$this->Instructor->contain(array('InstructorHistory'));
		$this->data = $this->Instructor->read(null, $id);

		$this->render('instructor_edit_history');
	}





	public function admin_dataTable($type='sindex', $id=null)
	{
		$this->datatable($type, $id);
	}

	public function dataTable($type='sindex', $id = null)
	{
		$conditions = array();
		$joins = array(
			array(
				'table'=>'users',
				'alias'=>'User',
				'type'=>'LEFT',
				'conditions'=>array(
					'User.id = Instructor.user_id'
			)),array(
				'table'=>'agencies',
				'alias'=>'Agency',
				'type'=>'LEFT',
				'conditions'=>array(
					'Agency.id = User.agency_id'
			)),array(
				'table'=>'tiers',
				'alias'=>'Tier',
				'type'=>'LEFT',
				'conditions'=>array(
					'Tier.id = Instructor.tier_id'
			)),array(
				'table'=>'statuses',
				'alias'=>'Status',
				'type'=>'LEFT',
				'conditions'=>array(
					'Status.id = Instructor.status_id'
			))
		);
		switch($type)
		{
			case 'online':
				$conditions['User.last_action >'] = date('Y-m-d H:i:s', strtotime('-5 minutes'));
				$type= 'admin_index';
			break;

			case 'rtoday':
				$conditions['User.created >='] = date('Y-m-d H:i:s', strtotime('12:01 am'));
				$type= 'admin_index';
			break;

			case 'agency':
				$conditions['User.agency_id'] = $id;
			break;

			case 'admin_index':

			break;

			case 'tier':
				$conditions['Instructor.tier_id'] = $id;
			break;
		}

		$order = array(
			'User.created'
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
				case 'sindex':
					switch($_GET['iSortCol_0'])
					{
						case 0: break;
						case 1: $order = array('User.last_name'=>$_GET['sSortDir_0']); break;
						case 2: $order = array('Agency.name'=>$_GET['sSortDir_0']); break;
						case 3: $order = array('Tier.id'=>$_GET['sSortDir_0']); break;
					}
				break;

				case 'admin_index':
					switch($_GET['iSortCol_0'])
					{
						case 0: $order = array('User.last_name'=>$_GET['sSortDir_0']); break;
						case 1: $order = array('Agency.name'=>$_GET['sSortDir_0']); break;
						case 2: $order = array('Tier.id'=>$_GET['sSortDir_0']); break;
						case 3: $order = array('Status.id'=>$_GET['sSortDir_0']); break;
					}
				break;

				case 'tier':
					switch($_GET['iSortCol_0'])
					{
						case 0: $order = array('User.last_name'=>$_GET['sSortDir_0']); break;
						case 1: $order = array('Agency.name'=>$_GET['sSortDir_0']); break;
						case 2: $order = array('Status.status'=>$_GET['sSortDir_0']); break;
					}
				break;
			}

		}


		if (!empty($_GET['sSearch']))
		{
			$or = array();
			$or[] = array('User.first_name LIKE'=>$_GET['sSearch'].'%');
			$or[] = array('User.last_name LIKE'=>$_GET['sSearch'].'%');
			$or[] = array('CONCAT(User.first_name, " ", User.last_name) LIKE'=>'%'.$_GET['sSearch'].'%');
			$or[] = array('User.email LIKE'=>$_GET['sSearch'].'%');
			$or[] = array('Agency.name LIKE'=>$_GET['sSearch'].'%');

			$or[] = array('Tier.name LIKE'=>$_GET['sSearch'].'%');
			$or[] = array('Tier.short'=>$_GET['sSearch']);
			$conditions[] = array('or'=>$or);
		}


		$found = $this->Instructor->find('count', array(
			'conditions'=>$conditions,
			'joins'=>$joins
		));

		$courses = $this->Instructor->find('all', array(
			'conditions'=>$conditions,
			'order'=>$order,
			'limit'=>$limit,
			'offset'=>$offset,
			'joins'=>$joins,
			'fields'=>'*'
		));

		//echo "/* ".print_r($order, true).' */';

		$this->set('found', $found);
		$this->set('users', $courses);
		$this->render('Instructors/tables'.DS.$type);
	}

	public function admin_view($id = null, $page = 'view')
	{
		if ($id == null)
			die('no');

		if ($this->request->is('ajax'))
			$this->layout = 'ajax';

		switch($page)
		{
			//<editor-fold defaultstate="collapsed" desc="View">
			case 'view':

				$this->Instructor->contain(array(
					'User.Agency',
					'Status',
					'Tier',
					'InstructorHistory'
				));
				$this->set('instructor', $this->Instructor->read(null, $id));

				$this->render('admin_view');
			break;
		//</editor-fold>

			//<editor-fold defaultstate="collapsed" desc="Tier Map">
			case 'tiermap':

				$this->Instructor->contain(array(
					'User.Agency',
					'InstructorHistory'
				));
				$data = $this->Instructor->read(null, $id);
				$this->set('instructor', $data);

				$this->set('results', $this->Instructor->Tier->TierRequirement->test($data));
				$this->Instructor->Tier->contain(array(
					'TierRequirement'
				));
				$this->set('tiers', $this->Instructor->Tier->find('all'));
				$this->render('Instructors/pages/'.$page);
			break;
			//</editor-fold>

			//<editor-fold defaultstate="collapsed" desc="Dashboard">
			case 'dashboard':

				$recent = $this->Instructor->Instructing->find('all', array(
					'conditions'=>array(
						'instructor_id'=>$id,
						'Course.startdate >='=>date('Y-m-d', strtotime('-1 week')),
						'Course.startdate <='=>date('Y-m-d', strtotime('+1 week'))
					),
					'joins'=>array(
						array(
							'table'=>'courses',
							'alias'=>'Course',
							'type'=>'LEFT',
							'conditions'=>array(
								'Course.id = Instructing.course_id'
							)
						),
						array(
							'table'=>'course_types',
							'alias'=>'CourseType',
							'type'=>'LEFT',
							'conditions'=>array(
								'CourseType.id = Course.course_type_id'
							)
						)
					),
					'fields'=>'*'
				));
				$this->set('recentCourses', $recent);

				$this->Instructor->contain(array(
				));
				$c = $this->Instructor->read(null, $id);
				$this->set('instructor', $c);
				$this->render('Instructors/pages/'.$page);
			break;
			//</editor-fold>

			//<editor-fold defaultstate="collapsed" desc="Tier Reviews">
			case 'tr':

				$this->Instructor->contain(array(
					'TierReview.Review.Status',
					'TierReview.Review.Author',
					'TierReview.Review.Course.CourseType',
					'TierReview.Tier',
					'TierReview.Status'
				));
				$data = $this->Instructor->read(null, $id);
				$this->set('instructor', $data);

				$authorids = array_unique(Set::extract('/TierReview/Review/Author/id', $data));

				$author = array();
				foreach($authorids as $aid)
				{
					$this->Instructor->contain(array(
						'User'
					));
					$author[$aid] = $this->Instructor->read(null, $aid);
				}

				$this->set('authors', $author);


				$this->render('Instructors/pages/'.$page);
			break;
			//</editor-fold>

			case 'courses':
				$c = $this->Instructor->read(null, $id);
				$this->set('instructor', $c);
				$this->render('Instructors/pages/'.$page);
			break;

			case 'invoice':
				$c = $this->Instructor->read(null, $id);
				$this->set('instructor', $c);
				$this->render('Instructors/pages/'.$page);
			break;


			case 'pr':
				$this->loadModel('PeerReview');
				$pr = $this->PeerReview->find('all', array(
					'conditions'=>array(
						'or'=>array(
							'instructor_id'=>$id,
							'instructor1_id'=>$id,
							'instructor2_id'=>$id,
							'instructor3_id'=>$id,
							'instructor4_id'=>$id
						)
					),
					'contain'=>array(
						'I1.User',
						'I2.User',
						'I3.User',
						'I4.User',
						'Course.CourseType',
						'Instructor.User'
					)
				));
				$this->set('pr', $pr);
				$this->set('ins', $id);
				$this->render('Instructors/pages/'.$page);
			break;
		}
	}

	public function admin_index()
	{

	}

	public function admin_edit($id)
	{
		if ($this->request->is('post') || $this->request->is('put'))
		{
			if ($this->Instructor->save($this->request->data))
			{
				$this->Session->setFlash('Successfully edited instructor record', 'notices/success');
				$this->redirect(array('action'=>'view', $id));
			}
		}

		$this->request->data = $this->Instructor->read(null, $id);
		$this->set('tiers', $this->Instructor->Tier->find('list'));
		$this->set('statuses', $this->Instructor->Status->find('list'));
	}

	public function admin_email($step=1)
	{
		if ($this->request->is('post'))
		{
			switch($step)
			{
				case 1:
					$conditions = array();

					if ($this->request->data['Instructor']['pending'])
						$conditions[] = array('status_id'=>$this->request->data['Instructor']['pending']);

					if ($this->request->data['Instructor']['approved'])
						$conditions[] = array('status_id'=>$this->request->data['Instructor']['approved']);

					if ($this->request->data['Instructor']['inactive'])
						$conditions[] = array('status_id'=>$this->request->data['Instructor']['inactive']);

					$instructors = $this->Instructor->find('all', array(
						'conditions'=>array('or'=>$conditions),
						'contain'=>array(
							'User.email'
						)
					));

					$emails = Set::extract('/User/email', $instructors);

					$this->Session->write('pendingEmails', $emails);
					$this->Session->write('subject', $this->request->data['Instructor']['subject']);
					$this->Session->write('body', $this->request->data['Instructor']['body']);
					$this->redirect(array('action'=>'email', 3));
				break;
			}
		}


		switch($step)
		{
			case 2:

				$emails = $this->Session->read('pendingEmails');
				$subject = $this->Session->read('subject');
				$body = $this->Session->read('body');
				$count = 0;
				$bcc = array();
				foreach($emails as $idx => $sendto)
				{
					$bcc[] = $sendto;

					unset($emails[$idx]);
					$count++;
					if ($count >= 90)
						break;
				}

				$email = new CakeEmail('smtp');
				$email->from(array('noreply@alerrt.org' => 'ALERRT'));
				$email->from('noreply@alerrt.org');
				$email->bcc($bcc);
				$email->emailFormat('html');
				$email->subject($subject);
				$send = $email->send($body);

				if (!$send)
					die('send error');

				if (!empty($emails))
				{
					$this->Session->write('pendingEmails', $emails);
					$this->render('Instructors/pages/email/3');
				}
				else
				{
					$this->Session->setFlash('Sent all messages', 'notices/success');
					$this->redirect(array('action'=>'index'));
				}
			break;

			case 3:

				$this->render('Instructors/pages/email/3');

			break;
		}






	}
}
