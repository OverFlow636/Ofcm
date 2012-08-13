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

	function instructor_historyPrint($id = null)
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

			case 'courses':
				$c = $this->Instructor->read(null, $id);
				$this->set('instructor', $c);
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

	public function admin_email()
	{
		if ($this->request->is('post'))
		{
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

			$email = new CakeEmail('smtp');
			$send = $email->from(array('noreply@alerrt.org' => 'ALERRT'))
				->to('noreply@alerrt.org')
				->bcc($emails)
				->emailFormat('html')
				->subject($this->request->data['Instructor']['subject'])
				->send($this->request->data['Instructor']['body']);

			if ($send)
			{
				$this->Session->setFlash('Successfully sent spam', 'notices/success');
				$this->redirect(array('action'=>'index'));
			}
			else
				$this->Session->setFlash('Unable to send spam, try again later?', 'notices/error');
		}
	}
}
