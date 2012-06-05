<?php

App::uses('OfcmAppController', 'Ofcm.Controller');
/**
 * Courses Controller
 *
 */
class AttendingsController extends OfcmAppController
{

	public function apply($cid = null, $step = 1, $extra = null)
	{
		$this->Attending->Course->id = $cid;
		if ($cid == null || !$this->Attending->Course->exists($cid))
		{
			$this->Session->setFlash('Invalid Course', 'notices/error');
			$this->redirect(array('plugin'=>'ofcm', 'controller'=>'Courses', 'action'=>'upcoming'));
		}

		$this->Attending->Course->contain(array(
			'CourseType',
			'Attending'=>array(
				'conditions'=>array(
					'Attending.status_id'=>array(3, 26)
				)
			)
		));
		$this->set('course', $this->Attending->Course->read());

		switch ($step)
		{
			case 1:
				//<editor-fold defaultstate="collapsed" desc="First page, select or create user">
				if ($this->request->is('post'))
				{
					//<editor-fold defaultstate="collapsed" desc="Posted">
					if (!empty($this->request->data['User']['id']) && empty($this->request->data['User']['email']) )
					{
						//existing user selected
						$this->redirect(array('action'=>'apply', $cid, 2, $this->request->data['User']['id']));
					}
					else
					{
						unset($this->request->data['User']['id']);
						$this->Attending->User->create();

						$this->request->data['User']['group_id'] = 1;
						$this->request->data['User']['verified'] = 1;
						$this->request->data['User']['agency_id'] = $this->Auth->user('agency_id');

						if ($this->Attending->User->save($this->request->data))
							$this->redirect(array('action'=>'apply', $cid, 2, $this->Attending->User->getLastInsertId()));
						else
						{
							$this->Session->setFlash('Please fix the problems below in red.', 'notices/error');
						}
					}
					//</editor-fold>
				}

				$this->Attending->User->contain(array(
					'Attending'
				));
				$agencylist = $this->Attending->User->find('all',array(
					'conditions'=>array(
						'User.agency_id' => $this->Auth->user('agency_id')
					),
					'order'=>array(
						'User.name'
					)
				));

				$registered = array();
				foreach($agencylist as $idx => $user)
					if (count($user['Attending']))
						foreach($user['Attending'] as $attending)
							if ($attending['course_id'] == $cid)
							{
								$registered[] = $user;
								unset($agencylist[$idx]);
							}

				$this->set('available',  Set::combine($agencylist, '/User/id', '/User/name'));
				$this->set('registered', Set::combine($registered, '/User/id', '/User/name'));
				//</editor-fold>
			break;

			case 2:
				//<editor-fold defaultstate="collapsed" desc="Step 2, confirm user info">
				if ($this->request->is('post'))
				{
					if ($this->Attending->User->saveAll($this->request->data, array('validate'=>'first')))
					{
						$a['Attending'] = array(
							'user_id'=>$extra,
							'course_id'=>$cid,
							'status_id'=>1
						);

						$approved = $this->Attending->find('count', array(
							'conditions'=>array(
								'Attending.course_id'=>$cid,
								'Attending.status_id'=>array(3,26)
							)
						));

						$this->Attending->Course->contain(array(
							'CourseType'
						));
						$course = $this->Attending->Course->read(null, $cid);

						//die('approved:'. $approved. ' | max:'.$course['CourseType']['maxStudents']);

						if ($approved >= $course['CourseType']['maxStudents'])
							$a['Attending']['status_id'] = 25; //add to waitlist

						if ($extra != $this->Auth->user('id'))
							$a['Attending']['registered_by_id'] =  $this->Auth->user('id');

						if ($this->Attending->save($a))
						{
							$this->Session->setFlash('The course application has been submitted, you will be notified if you are approved or not.', 'notices/success');

							/*$this->Attending->Course->contain(array('Location.City','Location.State', 'CourseType', 'Status'));
							$c = $this->Attending->Course->read();
							$this->set('Course', $c);

							$this->Attending->User->contain(array(
								'Phone',
								'Agency'
							));
							$au = $this->Attending->User->read(null, $this->Auth->user('id'));
							$this->set('AuthUser', $au);

							$data = array(
								'AuthUser'		=> $au,
								'AppliedCourse'	=> $c
							);

							if ($extra != $this->Auth->user('id'))
							{
								$this->Attending->User->contain(array(
									'Phone'
								));
								$data['AppliedUser'] = $this->Attending->User->read(null, $extra);
							}
							else
								$data['AppliedUser'] = $au;

							$this->HLR->dispatch('UserApplicationSubmitted', $data);*/

							$this->redirect('/Profile');
						}
						else
						{
							$this->Session->setFlash('Error applying you to the course, Administrator has been emailed about the problem.', 'notices/error');
							$this->redirect('/');
						}
					}
					else
						$this->Session->setFlash('Please fix the problems below in red.', 'notices/error');
				}
				else
				{
					$this->Attending->User->contain(array(
						'Phone'
					));
					//$this->set('applyUser', $this->Attending->User->read(null, $extra));
					$this->request->data = $this->Attending->User->read(null, $extra);
				}
				//</editor-fold>
		}

		$this->render('Attendings'.DS.'steps'.DS.$step);
	}

	public function admin_dataTable($courseid =null, $type = 'datatable')
	{
		$this->autoRender = false;
		$this->dataTable($courseid, $type);
	}

	public function dataTable($id=null, $type = 'datatable')
	{
		$conditions = array();

		$group = null;

		$joins = array(
			array(
				'table'=>'users',
				'alias'=>'User',
				'type'=>'LEFT',
				'conditions'=>array(
					'User.id = Attending.user_id'
				)
			),
			array(
				'table'=>'agencies',
				'alias'=>'Agency',
				'type'=>'LEFT',
				'conditions'=>array(
					'Agency.id = User.agency_id'
				)
			)
		);

		switch($type)
		{
			case 'user':
				$conditions['Attending.user_id'] = $id;
				$aColumns = array(
					'CourseType.shortname',
					'CourseType.startdate',
					'Status.status',
					'Attending.created'
				);
				$joins[] = array(
					'table'=>'courses',
					'alias'=>'Course',
					'type'=>'LEFT',
					'conditions'=>array(
						'Course.id = Attending.course_id'
					));
				$joins[] = array(
					'table'=>'course_types',
					'alias'=>'CourseType',
					'type'=>'LEFT',
					'conditions'=>array(
						'CourseType.id = Course.course_type_id'
					));
				$joins[] = array(
					'table'=>'statuses',
					'alias'=>'Status',
					'type'=>'LEFT',
					'conditions'=>array(
						'Status.id = Attending.status_id'
					));
			break;

			case 'datatable':
				$conditions['Attending.course_id'] = $id;
				$aColumns = array(
					'',
					'User.name',
					'Agency.name',
					'',
					'Status.id',
					'Attending.created'
				);
				$joins[] = array(
					'table'=>'statuses',
					'alias'=>'Status',
					'type'=>'LEFT',
					'conditions'=>array(
						'Status.id = Attending.status_id'
					));
			break;

			case 'conference':
				$conditions['Attending.conference_id'] = $id;
				$group = 'Attending.user_id';
				$aColumns = array(
					'User.name',
					'Agency.name',
					'CourseType.shortname',
					'PaymentStatus.id',
					'AgencyState.id',
					'Attending.created'
				);
				$joins[] = array(
					'table'=>'locations',
					'alias'=>'UserLocation',
					'type'=>'LEFT',
					'conditions'=>array(
						'UserLocation.id = User.home_address'
					));
				$joins[] = array(
					'table'=>'states',
					'alias'=>'UserState',
					'type'=>'LEFT',
					'conditions'=>array(
						'UserState.id = UserLocation.state_id'
					));
				$joins[] = array(
					'table'=>'locations',
					'alias'=>'AgencyLocation',
					'type'=>'LEFT',
					'conditions'=>array(
						'AgencyLocation.id = Agency.main_address_id'
					));
				$joins[] = array(
					'table'=>'states',
					'alias'=>'AgencyState',
					'type'=>'LEFT',
					'conditions'=>array(
						'AgencyState.id = AgencyLocation.state_id'
					));
				$joins[] = array(
					'table'=>'payments',
					'alias'=>'Payment',
					'type'=>'LEFT',
					'conditions'=>array(
						'Payment.id = Attending.payment_id'
					));
				$joins[] = array(
					'table'=>'statuses',
					'alias'=>'PaymentStatus',
					'type'=>'LEFT',
					'conditions'=>array(
						'PaymentStatus.id = Payment.status_id'
					));
				$joins[] = array(
					'table'=>'courses',
					'alias'=>'Course',
					'type'=>'LEFT',
					'conditions'=>array(
						'Course.id = Attending.course_id'
					));
				$joins[] = array(
					'table'=>'course_types',
					'alias'=>'CourseType',
					'type'=>'LEFT',
					'conditions'=>array(
						'CourseType.id = Course.course_type_id'
					));
			break;
		}

		$order = array(
			'Attending.created'
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
						case 3: $order = array('User.main_phone'=>$_GET['sSortDir_0']); break;
						case 4: $order = array('Status.status'=>$_GET['sSortDir_0']); break;
						case 5: $order = array('Attending.created'=>$_GET['sSortDir_0']); break;

					}
				break;

				case 'conference':
					switch($_GET['iSortCol_0'])
					{
						case 0: $order = array('User.last_name'=>$_GET['sSortDir_0']); break;
						case 1: $order = array('Agency.name'=>$_GET['sSortDir_0']); break;
						case 2: $order = array('CourseType.shortname'=>$_GET['sSortDir_0']);break;
						case 3: $order = array('PaymentStatus.status'=>$_GET['sSortDir_0']); break;
						case 4: $order = array('UserState.abbr'=>$_GET['sSortDir_0']); break;
						case 5: $order = array('Attending.created'=>$_GET['sSortDir_0']); break;
					}
				break;

				case 'user':
					switch($_GET['iSortCol_0'])
					{
						case 0: $order = array('CourseType.shortname'=>$_GET['sSortDir_0']);break;
						case 1: $order = array('Course.startdate'=>$_GET['sSortDir_0']);break;
						case 2: $order = array('Status.status'=>$_GET['sSortDir_0']); break;
						case 3: $order = array('Attending.created'=>$_GET['sSortDir_0']); break;
					}
				break;
			}

		}

		if (!empty($_GET['sSearch']))
		{
			$or = array();
			$or[] = array('CONCAT(User.first_name, " ", User.last_name) LIKE'=>$_GET['sSearch'].'%');
			$or[] = array('User.last_name LIKE'=>$_GET['sSearch'].'%');
			$or[] = array('Agency.name LIKE'=>$_GET['sSearch'].'%');

			switch($type)
			{
				case 'conference':
					$or[] = array('CourseType.shortname LIKE "'.$_GET['sSearch'].'%"');
					$or[] = array('PaymentStatus.status LIKE "'.$_GET['sSearch'].'%"');
					$or[] = array('UserState.abbr LIKE'=>$_GET['sSearch'].'%');
					$or[] = array('AgencyState.abbr LIKE'=>$_GET['sSearch'].'%');
				break;
			}

			$conditions[] = array('or'=>$or);
		}

		for($i = 0; $i<count($aColumns);$i++)
		{
			if ($_GET['bSearchable_'.$i] == "true" && $_GET['sSearch_'.$i] != '' && $_GET['sSearch_'.$i] != '0')
				$conditions[] = array($aColumns[$i] => $_GET['sSearch_'.$i]);
		}

		$arr = array(
			'conditions'=>$conditions,
			'joins'=>$joins
		);
		if (!empty($group))
			$arr['group']= $group;

		$found = $this->Attending->find('count', $arr);

		$arr = array(
			'conditions'=>$conditions,
			'order'=>$order,
			'limit'=>$limit,
			'offset'=>$offset,
			'joins'=>$joins,
			'fields'=>'*'
		);
		if (!empty($group))
			$arr['group']= $group;
		$courses = $this->Attending->find('all', $arr);

		//echo "/* ".print_r($order, true).' */';

		$this->set('found', $found);
		$this->set('courses', $courses);
		$this->render('Attendings'.DS.'tables'.DS.$type);
	}

	public function admin_bulkEdit($courseid = null)
	{
		foreach($this->request->data['attending'] as $attid => $selected)
			if ($selected)
			{
				$this->Attending->id = $attid;
				switch($this->request->data['Attending']['action'])
				{
					case 'A': $this->Attending->saveField('status_id', 3); break;
					case 'P': $this->Attending->saveField('status_id', 4); break;
					case 'F': $this->Attending->saveField('status_id', 5); break;
					case 'C': $this->Attending->saveField('status_id', 26); break;
					case 'W': $this->Attending->saveField('status_id', 25); break;
					case 'N': $this->Attending->saveField('status_id', 15); break;
					case 'S': $this->Attending->saveField('status_id', 19); break;
					case 'I': $this->Attending->saveField('status_id', 8); break;

					case 'R': $this->Attending->delete(); break;
				}
			}

		$this->set('message', 'Saved instructor changes');
		//$this->redirect(array('controller'=>'Courses', 'action'=>'view', $courseid, 'students'));
	}


	public function fromState($state)
	{
		$joins = array(
			array(
				'table'=>'users',
				'alias'=>'User',
				'type'=>'LEFT',
				'conditions'=>array(
					'User.id = Attending.user_id'
				)
			),
			array(
				'table'=>'agencies',
				'alias'=>'Agency',
				'type'=>'LEFT',
				'conditions'=>array(
					'Agency.id = User.agency_id'
				)
			),
			array(
				'table'=>'locations',
				'alias'=>'Location',
				'type'=>'LEFT',
				'conditions'=>array(
					'Location.id = Agency.main_address_id'
				)
			),
			array(
				'table'=>'states',
				'alias'=>'State',
				'type'=>'LEFT',
				'conditions'=>array(
					'State.id = Location.state_id'
				)
			),
			array(
				'table'=>'locations',
				'alias'=>'UserLocation',
				'type'=>'LEFT',
				'conditions'=>array(
					'UserLocation.id = User.home_address'
				)
			),
			array(
				'table'=>'states',
				'alias'=>'UserState',
				'type'=>'LEFT',
				'conditions'=>array(
					'UserState.id = UserLocation.state_id'
				)
			)
		);


		$this->Attending->contain(array(
		));

		$this->set('count', $this->Attending->find('count', array(
			'conditions'=>array(
				'or'=>array(
					'State.id'=>$state,
					'UserState.id'=>$state
				)
			),
			'joins'=>$joins,
			'fields'=>'*'
		)));

		$this->Attending->contain(array(
		));
		$this->set('attendings', $this->Attending->find('all', array(
			'conditions'=>array(
				'State.id'=>$state
			),
			'joins'=>$joins,
			'fields'=>'*',
			'group'=>'Agency.id',
			'order'=>'Agency.name'
		)));

		$this->set('trained', $state);

		$this->loadModel('State');
		$this->set('state', $this->State->read(null, $state));
	}

	public function admin_add($id)
	{
		if ($this->request->is('post') || $this->request->is('put'))
		{
			if (!$this->request->data['User']['Agency']['id'])
				unset($this->request->data['User']['Agency']['id']);
			if (!$this->request->data['User']['id'])
				unset($this->request->data['User']['id']);

			$this->request->data['Attending']['course_id'] = $id;

			if ($this->Attending->validateAssociated($this->request->data, array('deep'=>true)))
				if ($this->Attending->saveAssociated($this->request->data, array('deep'=>true)))
					$this->set('saved', true);

		}
		$this->set('statuses', $this->Attending->Status->find('list'));
	}

	public function admin_export($id, $type='CJD')
	{
		$this->Attending->Course->Instructing->contain(array(
			'User.HomeAddress',
			'User.Agency.name',
			'Status',
			'Course.CourseType'
		));
		$instructors = $this->Attending->Course->Instructing->find('all', array(
			'conditions'=>array(
				'Instructing.course_id'=>$id,
				'Instructing.status_id'=>3)
		));

		$this->Attending->contain(array(
			'User' => array(
				'order' => array(
					'User.last_name'
				)
			),
			'User.Agency',
			'Status'=>array(
				'conditions'=>array(
					'Status.id'=>array(3,4,5,8,16,17,18,22,23,26)
				)
			),
			'User.HomeAddress.City',
			'User.HomeAddress.State',
			'Course.CourseType'
		));
		$students = $this->Attending->findAllByCourseId($id);

		if ($type=='BJA')
		{
			header("Content-Disposition: attachment; filename=export.csv");
			echo "Physical Address, Location, Class Type, Date, Last Name, First Name\n";

			foreach($students as $user)
			{
				echo '"'.$user['User']['HomeAddress']['addr1'].'",';
				echo '"'.$user['Course']['location_description'].'",';
				echo '"'.$user['CourseType']['name'].'",';
				echo '"'.date('m/d/y',strtotime($user['Course']['startdate'])). ' - ' . date('m/d/y',strtotime($user['Course']['enddate'])).'",';
				echo '"'.$user['User']['last_name'].'",';
				echo '"'.$user['User']['first_name']."\"\n";
			}

			foreach($instructors as $user)
			{
				echo '"'.$user['User']['HomeAddress']['addr1'].'",';
				echo '"'.$user['Course']['location_description'].'",';
				echo '"'.$user['CourseType']['name'].'",';
				echo '"'.date('m/d/y',strtotime($user['Course']['startdate'])). ' - ' . date('m/d/y',strtotime($user['Course']['enddate'])).'",';
				echo '"'.$user['User']['last_name'].'",';
				echo '"'.$user['User']['first_name']."\"\n";
			}
			die();
		}

		if ($type=='CJD')
		{
			header("Content-Disposition: attachment; filename=export.csv");
			echo "First Name, Last Name, Phone Number, Email, DOB, PID, Status\n";

			foreach($students as $user)
			{
				echo '"'.$user['User']['first_name'].'",';
				echo '"'.$user['User']['last_name'].'",';
				echo '"'.$user['User']['main_phone'].'",';
				echo '"'.$user['User']['email'].'",';
				echo '"'.date('m/d/y',strtotime($user['User']['dob'])).'",';
				echo '"'.$user['User']['pid'].'",';
				echo '"'.$user['Status']['status']."\"\n";
				//echo '"'.$user['User']['first_name']."\"\n";
			}
			foreach($instructors as $user)
			{
				echo '"'.$user['User']['first_name'].'",';
				echo '"'.$user['User']['last_name'].'",';
				echo '"'.$user['User']['main_phone'].'",';
				echo '"'.$user['User']['email'].'",';
				echo '"'.date('m/d/y',strtotime($user['User']['dob'])).'",';
				echo '"'.$user['User']['pid'].'",';
				echo '"'.$user['Status']['status']."\"\n";
				//echo '"'.$user['User']['first_name']."\"\n";
			}
			die();
		}
	}

	public function admin_index()
	{
		$this->set('count', $this->Attending->find('count'));

		$this->set('courses', $this->Attending->find('count', array('group'=>'course_id')));

		$this->set('states', $this->Attending->find('count', array(
			'joins'=>array(
				array(
					'table'=>'users',
					'alias'=>'User',
					'type'=>'LEFT',
					'conditions'=>array(
						'User.id = Attending.user_id'
					)
				),array(
					'table'=>'locations',
					'alias'=>'HomeAddress',
					'type'=>'LEFT',
					'conditions'=>array(
						'HomeAddress.id = User.home_address'
					)
				),array(
					'table'=>'states',
					'alias'=>'State',
					'type'=>'LEFT',
					'conditions'=>array(
						'State.id = HomeAddress.state_id'
					)
				)
			),
			'fields'=>'count(distinct(State.id)) as count'
		)));



	}

	public function admin_search($view=null)
	{
		switch($view)
		{
			case 'dt':

				$conditions= array();

				$filter = $this->Session->read('filter');
				if ($filter)
				{
					$ors = array();
					if (!empty($filter['User']['first_name']))
						$ors[] = 'User.first_name LIKE "'.$filter['User']['first_name'].'%"';

					if (!empty($filter['User']['last_name']))
						$ors[] = 'User.last_name LIKE "'.$filter['User']['last_name'].'%"';

					if (!empty($filter['User']['email']))
						$ors[] = 'User.email LIKE "%'.$filter['User']['email'].'%"';

					if (!empty($filter['Agency']['name']))
						$ors[] = 'Agency.name LIKE "%'.$filter['Agency']['name'].'%"';

					$ands = array();
					if (!empty($filter['Attending']['course_type_id']) && $filter['Attending']['course_type_id'] > 0)
						$ands['Course.course_type_id'] = $filter['Attending']['course_type_id'];

					if (!empty($filter['Attending']['state_id']) && $filter['Attending']['state_id'] > 0)
						$ands['UserLocation.state_id'] = $filter['Attending']['state_id'];

					if (!empty($filter['Attending']['district']) && $filter['Attending']['district'] > 0)
						$ands['UserLocation.congressional_district'] = $filter['Attending']['district'];

					if (!empty($ors))
						$conditions['or']= $ors;
					if (!empty($ands))
						$conditions['and']= $ands;
				}
				$joins[] = array(
					'table'=>'users',
					'alias'=>'User',
					'type'=>'LEFT',
					'conditions'=>array(
						'User.id = Attending.user_id'
					));
				$joins[] = array(
					'table'=>'locations',
					'alias'=>'UserLocation',
					'type'=>'LEFT',
					'conditions'=>array(
						'UserLocation.id = User.home_address'
					));
				$joins[] = array(
					'table'=>'agencies',
					'alias'=>'Agency',
					'type'=>'LEFT',
					'conditions'=>array(
						'Agency.id = User.agency_id'
					));
				$joins[] = array(
					'table'=>'states',
					'alias'=>'UserState',
					'type'=>'LEFT',
					'conditions'=>array(
						'UserState.id = UserLocation.state_id'
					));
				$joins[] = array(
					'table'=>'locations',
					'alias'=>'AgencyLocation',
					'type'=>'LEFT',
					'conditions'=>array(
						'AgencyLocation.id = Agency.main_address_id'
					));
				$joins[] = array(
					'table'=>'states',
					'alias'=>'AgencyState',
					'type'=>'LEFT',
					'conditions'=>array(
						'AgencyState.id = AgencyLocation.state_id'
					));
				$joins[] = array(
					'table'=>'statuses',
					'alias'=>'Status',
					'type'=>'LEFT',
					'conditions'=>array(
						'Status.id = Attending.status_id'
					));
				$joins[] = array(
					'table'=>'courses',
					'alias'=>'Course',
					'type'=>'LEFT',
					'conditions'=>array(
						'Course.id = Attending.course_id'
					));
				$joins[] = array(
					'table'=>'course_types',
					'alias'=>'CourseType',
					'type'=>'LEFT',
					'conditions'=>array(
						'CourseType.id = Course.course_type_id'
					));

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
					switch($_GET['iSortCol_0'])
					{
						case 0: $order = array('User.last_name'=>$_GET['sSortDir_0']); break;
						case 1: $order = array('Agency.name'=>$_GET['sSortDir_0']); break;
						case 2: $order = array('UserState.abbr'=>$_GET['sSortDir_0']); break;
						case 3: $order = array('CourseType.shortname'=>$_GET['sSortDir_0']);break;
						case 4: $order = array('Status.status'=>$_GET['sSortDir_0']); break;
						case 5: $order = array('Course.startdate'=>$_GET['sSortDir_0']); break;
					}
				}

				if (!empty($_GET['sSearch']))
				{
					$or = array();
					$or[] = array('CONCAT(User.first_name, " ", User.last_name) LIKE'=>$_GET['sSearch'].'%');
					$or[] = array('User.last_name LIKE'=>$_GET['sSearch'].'%');
					$or[] = array('Agency.name LIKE'=>$_GET['sSearch'].'%');
					$or[] = array('CourseType.shortname LIKE "'.$_GET['sSearch'].'%"');
					$or[] = array('UserState.abbr LIKE'=>$_GET['sSearch'].'%');
					$or[] = array('AgencyState.abbr LIKE'=>$_GET['sSearch'].'%');

					$conditions[] = array('or'=>$or);
				}

				for($i = 0; $i<5;$i++)
				{
					if ($_GET['bSearchable_'.$i] == "true" && $_GET['sSearch_'.$i] != '' && $_GET['sSearch_'.$i] != '0')
						$conditions[] = array($aColumns[$i] => $_GET['sSearch_'.$i]);
				}

				$found = $this->Attending->find('count', array(
					'conditions'=>$conditions,
					'joins'=>$joins
				));
				$courses = $this->Attending->find('all', array(
					'conditions'=>$conditions,
					'order'=>$order,
					'limit'=>$limit,
					'offset'=>$offset,
					'joins'=>$joins,
					'fields'=>'*'
				));

				$this->set('found', $found);
				$this->set('courses', $courses);
				$this->render('Attendings'.DS.'tables'.DS.'search');
			break;
			case null:

				if ($this->request->is('post') || $this->request->is('put'))
				{
					$this->Session->write('filter', $this->request->data);
					$this->render('Attendings'.DS.'options'.DS.$this->request->data['Attending']['opt']);
				}
				$this->Attending->Course->CourseType->displayField = 'shortname';
				$this->set('courseTypes', array_merge(array(0=>'None'), $this->Attending->Course->CourseType->find('list')));
				$this->loadModel('State');
				$this->set('states', array_merge(array(0=>'None'), $this->State->find('list')));
			break;
		}
	}

	public function beforeFilter()
	{
		if ($this->request->params['action'] == 'admin_search')
			$this->Security->csrfCheck = false;
		parent::beforeFilter();
	}
}