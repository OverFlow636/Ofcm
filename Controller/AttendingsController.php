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
			'CourseType'
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

	public function dataTable($id=null,$type = 'datatable')
	{
		$conditions = array();

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
						'UserLocation.id = User.location_id'
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
						case 0: $order = array('User.last_name'=>$_GET['sSortDir_0']); break;
						case 1: $order = array('Agency.name'=>$_GET['sSortDir_0']); break;
						case 2: break;
						case 3: $order = array('Attending.status_id'=>$_GET['sSortDir_0']); break;
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
			}

		}


		if (!empty($_GET['sSearch']))
		{
			$or = array();
			$or[] = array('CONCAT(User.first_name, " ", User.last_name) LIKE'=>$_GET['sSearch'].'%');
			$or[] = array('Agency.name LIKE'=>$_GET['sSearch'].'%');
			$or[] = array('UserState.abbr LIKE'=>$_GET['sSearch'].'%');
			$or[] = array('AgencyState.abbr LIKE'=>$_GET['sSearch'].'%');

			switch($type)
			{
				case 'conference':
					$or[] = array('CourseType.shortname LIKE "'.$_GET['sSearch'].'%"');
					$or[] = array('PaymentStatus.status LIKE "'.$_GET['sSearch'].'%"');
				break;
			}

			$conditions[] = array('or'=>$or);
		}

		for($i = 0; $i<count($aColumns);$i++)
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

					case 'R': $this->Attending->delete(); break;
				}
			}
		$this->redirect(array('controller'=>'Courses', 'action'=>'view', $courseid, 'students'));
	}
}