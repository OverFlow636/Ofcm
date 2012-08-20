<?php
App::uses('OfcmAppController', 'Ofcm.Controller');

/**
 * Courses Controller
 *
 */
class CoursesController extends OfcmAppController
{
	var $allowedActions = array(
		'upcoming',
		'dataTable',
		'calendarFeed',
		'view'
	);

	public function __construct($request = null, $response = null)
	{
		$vars = Configure::read('Ofcm.CoursesController');
		if (!empty($vars))
			foreach($vars as $var => $value)
				$this->$var = $value;
		parent::__construct($request, $response);
	}

	public function view($id = null)
	{
		if ($id != null)
		{

			$this->Course->contain(array(
				'CourseType',
				'Hosting.Agency',
				'Contact.User.Agency',
				'Instructing'=>array(
					'conditions'=>array('Instructing.status_id'=>3)
				),
				'Attending.User'
			));
			$this->set('course', $this->Course->read(null, $id));
			$this->set('statuses', $this->Course->Status->find('list'));
		}
		else
		{
			$this->Session->setFlash('Invalid course', 'notice_error');
			$this->redirect('/');
		}
	}

	public function upcoming($render='calendar')
	{
		$this->set('render', $render);
		$this->render('Courses'.DS.'pages'.DS.$render);
	}

	public function admin_dataTable($type = 'upcoming', $extra = null)
	{
		$this->autoRender = false;
		$this->dataTable($type, $extra);
	}

	public function dataTable($type='upcoming', $extra = null)
	{
		$conditions = array();
		switch($type)
		{
			case 'funding':
				$conditions[] = array('Course.funding_id'=>$extra);

				$aColumns = array(
					'Course.id',
					'Course.startdate',
					'CourseType.shortname',
					'Course.location_description'
				);
				$type = 'admin_index';
			break;

			case 'upcoming':
				$conditions[] = 'Course.startdate > NOW()';
				$conditions[] = array('Course.conference_id'=>0);
				$conditions[] = array('Course.status_id'=>10);

				$aColumns = array(
					'Course.startdate',
					'CourseType.shortname',
					'Course.location_description',
					'Course.id',
					'Status.id',
					'Status.id'
				);
			break;

			case 'past':
				$conditions[] = array('Course.startdate <= NOW()', 'Course.status_id'=>10);
				$aColumns = array(
					'Course.id',
					'Course.startdate',
					'CourseType.shortname',
					'Course.location_description'
				);
				$type = 'admin_index';
			break;

			case 'aupcoming':
				$conditions[] = array('Course.startdate > NOW()', 'Course.status_id'=>10);

				$aColumns = array(
					'Course.id',
					'Course.startdate',
					'CourseType.shortname',
					'Course.location_description'
				);
				$type = 'admin_index';
			break;


			case 'agency':
				$courses = $this->Course->Hosting->findAllByAgencyId($extra);
				$ids = Set::extract('{n}/Hosting/course_id', $courses);
				$conditions['Course.id'] = $ids;
				$type= 'admin_index';

			case 'admin_index':
				if ($extra)
					$conditions['Course.status_id'] = $extra;
				$aColumns = array(
					'Course.id',
					'Course.startdate',
					'CourseType.shortname',
					'Course.location_description'
				);
			break;

			case 'conference':
				$conditions = array(
					'Course.conference_id'=>$extra
				);
				$aColumns = array(
					'Course.id',
					'CourseType.shortname',
					'Course.location_description',
					'Status.id',
					'Course.public',
					'Course.attending_count',
					'Course.disabled_spots',
					'CourseType.maxStudents',
					''
				);
			break;
		}

		$order = array(
			'Course.startdate'
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
				case 'upcoming':
					switch($_GET['iSortCol_0'])
					{
						case 0: $order = array('Course.startdate'=>$_GET['sSortDir_0']); break;
						case 1: $order = array('Course.course_type_id'=>$_GET['sSortDir_0']); break;
						case 2: $order = array('Course.location_description'=>$_GET['sSortDir_0']); break;
						case 3: $order = array('Course.status_id'=>$_GET['sSortDir_0']); break;
					}
				break;

				case 'admin_index':
					switch($_GET['iSortCol_0'])
					{
						case 0: $order = array('Course.id'=>$_GET['sSortDir_0']); break;
						case 1: $order = array('Course.startdate'=>$_GET['sSortDir_0']); break;
						case 2: $order = array('Course.course_type_id'=>$_GET['sSortDir_0']); break;
						case 3: $order = array('Course.location_description'=>$_GET['sSortDir_0']); break;
						case 4: $order = array('Course.iclosed'=>$_GET['sSortDir_0']); break;
						case 5: $order = array('Course.attending_count'=>$_GET['sSortDir_0']); break;
					}
				break;

				case 'conference':
					switch($_GET['iSortCol_0'])
					{
						case 0: $order = array('Course.id'=>$_GET['sSortDir_0']); break;
						case 1: $order = array('Course.course_type_id'=>$_GET['sSortDir_0']); break;
						case 2: $order = array('Course.location_description'=>$_GET['sSortDir_0']); break;
						case 3: $order = array('Course.public'=>$_GET['sSortDir_0']); break;
						case 4: $order = array('Course.status_id'=>$_GET['sSortDir_0']); break;
						case 5: $order = array('Course.attending_count'=>$_GET['sSortDir_0']); break;
						case 6: $order = array('Course.disabled_spots'=>$_GET['sSortDir_0']); break;
						case 7: $order = array('CourseType.maxStudents'=>$_GET['sSortDir_0']); break;
					}
				break;
			}

		}


		if (!empty($_GET['sSearch']))
		{
			$or = array();
			$or[] = array('Course.location_description LIKE'=>$_GET['sSearch'].'%');
			$or[] = array('CourseType.shortname LIKE'=>$_GET['sSearch'].'%');
			$or[] = array('DATE_FORMAT(Course.startdate, "%M") LIKE'=>$_GET['sSearch'].'%');
			$or[] = array('Course.id'=>$_GET['sSearch']);

			$conditions[] = array('or'=>$or);
		}

		for($i = 0; $i<count($aColumns);$i++)
		{
			if ($_GET['bSearchable_'.$i] == "true" && $_GET['sSearch_'.$i] != '' && $_GET['sSearch_'.$i] != '0')
				$conditions[] = array($aColumns[$i] => $_GET['sSearch_'.$i]);
		}

		$this->Course->recursive = 1;
		$found = $this->Course->find('count', array(
			'conditions'=>$conditions
		));
		$this->Course->contain(array(
			'Attending.Status',
			'CourseType',
			'Status'
		));
		$courses = $this->Course->find('all', array(
			'conditions'=>$conditions,
			'order'=>$order,
			'limit'=>$limit,
			'offset'=>$offset
		));

		//echo "/* ".print_r($order, true).' */';

		$this->set('found', $found);
		$this->set('courses', $courses);
		$this->render('Courses'.DS.'tables'.DS.$type);
	}

	public function calendarFeed()
	{
		$this->autoRender = false;
		$vars = $_GET;
		$conditions = array(
			'conditions' => array(
				'UNIX_TIMESTAMP(startdate) >=' => $vars['start'], 'UNIX_TIMESTAMP(startdate) <=' => $vars['end'],
				'status_id'=>10,
				'conference_id'=>0,
				'course_type_id !='=>26
		));
		$this->Course->contain(array('CourseType', 'Status'));
		$events = $this->Course->find('all', $conditions);
		$data = array();
		foreach($events as $event)
		{
			$ce = array(
					'id' => $event['Course']['id'],
					'title'=>$event['CourseType']['shortname'].' in '.$event['Course']['location_description'],
					'start'=>date('Y-m-d 08:00', strtotime($event['Course']['startdate'])),
					'end' => date('Y-m-d 17:00', strtotime($event['Course']['enddate'])),
					'allDay' => false,
					'url' => '/ofcm/Attendings/apply/'.$event['Course']['id'],
					'details' => $event['CourseType']['slider_summary'],
					'color' => (empty($event['CourseType']['calendar_color'])?'#ccc':$event['CourseType']['calendar_color']),
					'textColor' => (empty($event['CourseType']['calendar_textColor'])?'white':$event['CourseType']['calendar_textColor']),
			);
			if (!$this->Auth->user() || !$event['Course']['public'])
				$ce['url'] = '/ofcm/Courses/view/'.$event['Course']['id'];

			if (strtotime($event['Course']['startdate']) < time())
			{
				$ce['className'] = 'ghost';
				$ce['url'] = '#';
			}

			$data[] = $ce;
		}
		$this->response->type('text');
		$this->response->body(json_encode($data));
	}

	function getOpen($theme = 'old')
	{
		$cond = array(
			'status_id'	=> 10,
			'startdate > NOW()',
			'conference_id'=>0
		);

		if ($theme != 'instructors')
			$cond['public'] = 1;
		else
			$cond['iclosed'] = false;


		$data = $this->Course->find('all', array(
			'limit'=>5,
			'conditions'=>$cond,
			'contain'=>array(
				'Attending'	=> array('status_id = 3'),
				'CourseType'
			),
			'order'=>array(
				'startdate'=>'ASC'
			)
		));

		foreach($data as $idx => $cdata)
		{
			$data[$idx]['Course']['available'] = $cdata['CourseType']['maxStudents'] - count($cdata['Attending']);
			unset($data[$idx]['Attending']);
		}

		return $data;
	}



	/** admin functions **/

	public function admin_index($page=null)
	{
		if ($page)
			$this->render('Courses'.DS.'pages'.DS.'index'.DS.$page);
	}

	public function admin_view($id = null, $page = 'view')
	{
		if ($id == null)
			die('no');

		if ($this->request->is('ajax'))
			$this->layout = 'ajax';

		$this->Course->id = $id;
		$ac = $this->Course->Attending->find('count', array(
			'conditions'=>array(
				'course_id'=>$id,
				'status_id'=>array(3,4,5,8,12,13,16,17,18,19,22,23)
			)
		));

		if ($ac != $this->Course->field('approved_count'))
			$this->Course->saveField('approved_count', $ac);

		switch($page)
		{
			//<editor-fold defaultstate="collapsed" desc="View">
			case 'view':

				$this->Course->contain(array(
					'CourseType',
					'Status',
					'Funding',
					'Location.City',
					'Location.State',
					'ShippingLocation.City',
					'ShippingLocation.State',
					'Conference.id',
					'Conference.name',
					/*Hosting.Agency.id',
					'Hosting.Agency.name',
					'Contact.User',
					'Instructing'=>array(
						'conditions'=>array(
							'status_id'=>3
						)
					)*/
				));

				$this->set('hostings', $this->Course->Hosting->find('all',array(
					'conditions'=>array(
						'course_id'=>$id
					),
					'contain'=>array(
						'Agency.name'
					)
				)));
				$this->set('contacts', $this->Course->Contact->find('all',array(
					'conditions'=>array(
						'course_id'=>$id
					),
					'contain'=>array(
						'User'
					)
				)));

				$this->set('instructors', $this->Course->Instructing->find('all',array(
					'conditions'=>array(
						'course_id'=>$id,
						'Instructing.status_id'=>3
					),
					'contain'=>array(
						'Tier',
						'User.first_name',
						'User.last_name',
						'Instructor.Tier.short'
					)
				)));



				$c = $this->Course->read(null, $id);

				$this->Course->Instructing->contain(array(
					'Instructor.Tier',
					'Instructor.User'
				));

				$c['Instructing'] = $this->Course->Instructing->find('all', array(
					'conditions'=>array(
						'Instructing.course_id'=>$id,
						'Instructing.status_id'=>3
					)
				));
				$this->set('course', $c);
				$this->render('admin_view');
			break;
		//</editor-fold>

			//<editor-fold defaultstate="collapsed" desc="Dashboard">
			case 'dashboard':
				$this->Course->contain(array(
					'CourseType',
					'Attending.Status',
					'Attending.ConfirmationEmail'=>array(
						'fields'=>array('read', 'created', 'modified')
					),
					'Attending.CertStatusEmail'=>array(
						'fields'=>array('read', 'created', 'modified')
					),
					//'Attending.User',
					'Instructing.ConfirmationEmail'=>array(
						'fields'=>array('read', 'created', 'modified')
					),
					'Instructing.AfterActionEmail'=>array(
						'fields'=>array('read', 'created', 'modified')
					),
					'Instructing.Status',
					'Status',
				));
				$c = $this->Course->read(null, $id);
				$this->set('course', $c);
				$this->render('Courses/pages/'.$page);
			break;
			//</editor-fold>

			//<editor-fold defaultstate="collapsed" desc="Instructors">
			case 'instructors':
				$this->Course->contain(array(
					'Instructing.ConfirmationEmail'=>array(
						'fields'=>array('read', 'created', 'modified')
					),
					'Instructing.AfterActionEmail'=>array(
						'fields'=>array('read', 'created', 'modified')
					),
					'Instructing.Status',
					'Status',
				));
				$c = $this->Course->read(null, $id);
				$this->set('course', $c);
				$this->set('tiers', $this->Course->Instructing->Tier->find('list'));
				$this->render('Courses/pages/'.$page);
			break;
			//</editor-fold>

			//<editor-fold defaultstate="collapsed" desc="Students">
			case 'students':
				$this->Course->contain(array(
					'Attending.Status',
					'Attending.ConfirmationEmail'=>array(
						'fields'=>array('read', 'created', 'modified')
					),
					'Attending.CertStatusEmail'=>array(
						'fields'=>array('read', 'created', 'modified')
					),
					'Attending.User',
				));
				$c = $this->Course->read(null, $id);
				$this->set('course', $c);


				$clist = $this->Course->find('all', array(
					'joins'=>array(
						array(
							'table'=>'hostings',
							'alias'=>'Hosting',
							'type'=>'LEFT',
							'conditions'=>array(
								'Hosting.course_id = Course.id'
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
					'conditions'=>array(
						'Hosting.agency_id'=>1,
						'Course.startdate > NOW()'
					),
					'fields'=>'*'
				));
				$list = array();
				foreach($clist as $course)
					$list[$course['Course']['id']] = $course['CourseType']['shortname']. ' '. $course['Course']['startdate'];
				$this->set('smcourses', $list);

				$this->render('Courses/pages/'.$page);
			break;
			//</editor-fold>

			//<editor-fold defaultstate="collapsed" desc="Hosting">
			case 'hosting':
				$this->Course->contain(array(
					'Hosting.Agency.name',
					'Hosting.Status'
				));
				$c = $this->Course->read(null, $id);
				$this->set('course', $c);
				$this->render('Courses/pages/'.$page);
			break;
			//</editor-fold>

			//<editor-fold defaultstate="collapsed" desc="Messages">
			case 'messages':

				$this->Course->Attending->contain(array(
					'Status',
					'ConfirmationEmail'=>array(
						'fields'=>array('read', 'created', 'modified')
					),
					'CertStatusEmail'=>array(
						'fields'=>array('read', 'created', 'modified')
					),
					'User'
				));
				$att = $this->Course->Attending->find('all', array(
					'conditions'=>array(
						'course_id'=>$id,
						'or'=>array(
							array('status_id'=>3),
							array('status_id'=>26),
							array('status_id'=>4),
							array('status_id'=>5),
							array('status_id'=>8),
							array('status_id'=>15),
							array('status_id'=>16),
							array('status_id'=>17),
							array('status_id'=>18),
							array('status_id'=>19),
							array('status_id'=>22),
							array('status_id'=>23)
						)
					)
				));
				$c['Attending'] = $att;

				$this->Course->Instructing->contain(array(
					'Status',
					'ConfirmationEmail'=>array(
						'fields'=>array('read', 'created', 'modified')
					),
					'AfterActionEmail'=>array(
						'fields'=>array('read', 'created', 'modified')
					),
					'Instructor.User'
				));
				$att = $this->Course->Instructing->find('all', array(
					'conditions'=>array(
						'course_id'=>$id,
						'Instructing.status_id'=>3,
					)
				));
				$c['Instructing'] = $att;


				$this->set('course', $c);
				$this->render('Courses/pages/'.$page);
			break;
			//</editor-fold>

			//<editor-fold defaultstate="collapsed" desc="Teleform">
			case 'teleform':

				$this->loadModel('TeleformData');
				$tfd = $this->TeleformData->find('all', array(
					'conditions'=>array(
						'BatchCust1'=>$id
					)
				));

				$this->set('tfd', $tfd);
				$this->set('id', $id);
				$this->render('Courses/pages/'.$page);
			break;
			//</editor-fold>

			case 'notes':

				$this->Course->contain(array(
					'Note.User'=>array(
						'order'=>'created ASC'
					)
				));
				$course = $this->Course->read(null, $id);

				$this->set('course', $course);
				$this->render('Courses/pages/'.$page);
			break;
		}
	}

	public function admin_changeStatus($id = null)
	{
		if ($this->request->is('post') || $this->request->is('put'))
		{
			$this->Course->id = $id;
			$this->set('saved', $this->Course->saveField('status_id', $this->request->data['Course']['status_id']));
		}

		$this->Course->recursive=1;
		$this->request->data = $this->Course->read(null, $id);

		$this->set('statuses', $this->Course->Status->find('list'));
	}

	public function admin_sendMessages($id = null, $type = null, $confirm=false)
	{
		$this->Course->contain(array(
			'Status',
			'CourseType',
			'Location.State',
			'Location.City',
		));
		$course = $this->Course->read(null, $id);

		switch($type)
		{
			case 'confirm':
			case 'status':
				$course['Course']['startdatef'] = date('l, F jS, Y', strtotime($course['Course']['startdate']));
				$course['Course']['enddatef'] = date('l, F jS, Y', strtotime($course['Course']['enddate']));
				if (isset($course['Location']['addr1']))
					$course['Location']['gmap'] = '<a href="http://maps.google.com/maps?daddr=' . urlencode($course['Location']['addr1']. ', '.$course['Location']['City']['name'].', '.$course['Location']['State']['abbr'].' '.$course['Location']['zip5']).'">Directions from Google Maps</a>';
				else
					$this->set('missingLocation', true);

				$this->Course->contain(array(
					'Attending.Status',
					'Attending.User',
					'Attending.RegisteredBy'
				));
				$data = $this->Course->read(null, $id);
			break;

			case 'iconfirm':
				$this->Course->contain(array(
					'Instructing.Instructor.User',
					'Instructing.Status',
					'Instructing.Tier'
				));
				$data = $this->Course->read(null, $id);
			break;

			case 'itc':
				$this->Course->contain(array(
					'Instructing.Instructor.User'
				));
				$data = $this->Course->read(null, $id);
			break;
		}

		//<editor-fold defaultstate="collapsed" desc="Confirm -- send the mails">
		if ($confirm)
		{
			switch($type)
			{
				case 'confirm':
					//<editor-fold defaultstate="collapsed" desc="Confirmation emails">
					$offc = $sta = array();
					foreach($data['Attending'] as $att)
						switch($att['status_id'])
						{
							case 3:
							case 26:
								if (!$att['confirmation_message_id'])
									$offc[] = $att;
							break;

							case 4:
							case 5:
							case 8:
							case 16:
							case 17:
							case 18:
							case 19:
							case 22:
							case 23:
								if (!$att['certificate_message_id'])
									$sta[] = $att;
						}

					foreach($offc as $user)
					{
						$args = array(
							'email_template_id'=>4,
							'sendTo'=>$user['User']['email'],
							'from'=>array('erin@alerrt.org'=>'Erin Etheridge')
						);
						if (!empty($user['RegisteredBy']['email']))
							$args['cc'] = $user['RegisteredBy']['email'];

						$result = $this->_sendTemplateEmail($args, array_merge($user, $course));

						$this->Course->Attending->save(array(
							'id'=>$user['id'],
							'confirmation_message_id'=>$result['mid']
						));
					}
					//</editor-fold>
				break;

				case 'iconfirm':
					//<editor-fold defaultstate="collapsed" desc="Instructor confirmation emails">
					$offc = $sta = array();
					foreach($data['Instructing'] as $att)
						switch($att['status_id'])
						{
							case 3:
							case 26:
								if (!$att['confirmation_message_id'])
									$offc[] = $att;
							break;

							case 4:
							case 5:
							case 8:
							case 16:
							case 17:
							case 18:
							case 19:
							case 22:
							case 23:
								if (!$att['afteraction_message_id'])
									$sta[] = $att;
						}

					foreach($offc as $user)
					{
						$args = array(
							'email_template_id'=>5,
							'sendTo'=>$user['Instructor']['User']['email'],
							'from'=>array('curnutt@alerrt.org'=>'John Curnutt')
						);
						//$args['cc'] = 'watkins@alerrt.org';

						$result = $this->_sendTemplateEmail($args, array_merge($user, $course));

						$this->Course->Instructing->save(array(
							'id'=>$user['id'],
							'confirmation_message_id'=>$result['mid']
						));
					}
					//</editor-fold>
				break;

				case 'status':
					//<editor-fold defaultstate="collapsed" desc="status update">
					$offc = $sta = array();
					foreach($data['Attending'] as $att)
						switch($att['status_id'])
						{
							case 3:
							case 26:
								if (!$att['confirmation_message_id'])
									$offc[] = $att;
							break;

							case 4:
							case 16:
							case 22:
							case 23:
								if (!$att['certificate_message_id'])
									$sta['pass'][] = $att;
							break;

							case 5:
							case 17:
								if (!$att['certificate_message_id'])
									$sta['fail'][] = $att;
							break;

							case 8:
							case 18:
							case 19:
								if (!$att['certificate_message_id'])
									$sta['other'][] = $att;
						}

					foreach($sta as $template => $list)
					{
						switch($template)
						{
							case 'pass': $template_id = 12; break;
							case 'fail': $template_id = 13; break;
							case 'other': $template_id = 14; break;
						}

						foreach($list as $user)
						{
							$args = array(
								'email_template_id'=>$template_id,
								'sendTo'=>$user['User']['email'],
								'from'=>array('erin@alerrt.org'=>'Erin Etheridge')
							);
							if (!empty($user['RegisteredBy']['email']))
								$args['cc'] = $user['RegisteredBy']['email'];

							//die(pr(array_merge($user, $course, array('Attending'=>$user))));
							$result = $this->_sendTemplateEmail($args, array_merge($user, $course, array('Attending'=>$user)));

							$this->Course->Attending->save(array(
								'id'=>$user['id'],
								'certificate_message_id'=>$result['mid']
							));
						}
					}
					//</editor-fold>
				break;

				case 'itc':
					//<editor-fold defaultstate="collapsed" desc="Instructor tier change approval">
					$tc = array();
					foreach($data['Instructing'] as $att)
						switch($att['status_id'])
						{
							case 28:
								if (!$att['tier_change_message_id'])
									$tc[] = $att;
							break;
						}

					foreach($tc as $user)
					{

						$args = array(
							'email_template_id'=>15,
							'sendTo'=>$user['Instructor']['User']['email'],
							'from'=>array('curnutt@alerrt.org'=>'John Curnutt')
						);
						$tier = $this->Course->Instructing->Tier->read(null, $user['tier_id']);

						//die(pr(array_merge($user, $course, $tier)));
						$result = $this->_sendTemplateEmail($args, array_merge($user, $course, $tier));

						$this->Course->Instructing->save(array(
							'id'=>$user['id'],
							'tier_change_message_id'=>$result['mid']
						));
					}
					//</editor-fold>
				break;
			}

			$this->redirect(array('action'=>'view', $id, 'messages'));
		}
		else
		{
			$this->set('data', $data);
		}
		//</editor-fold>

		$this->render('Courses'.DS.'messages'.DS.$type);
	}

	public function admin_leadPopup($id = null)
	{
		if ($this->request->is('post') || $this->request->is('put'))
		{
			if (!empty($this->request->data['Course']['lead']))
			{
				$this->Course->Instructing->updateAll(array('role'=>null), array('course_id'=>$id, 'role'=>'Lead'));
				$this->Course->Instructing->save(array(
					'id'=>$this->request->data['Course']['lead'],
					'role'=>'Lead'
				));
				$this->set('saved', true);
			}
		}

		$this->Course->contain(array(
			'Instructing.Instructor.User',
			'Instructing.Instructor.Tier',
			'Instructing.Tier'
		));
		$this->set('course', $this->Course->read(null, $id));
	}

	public function admin_edit($id = null)
	{
		if ($id == null)
			die('no');

		if ($this->request->is('post')||$this->request->is('put'))
		{
			if ($this->Course->save($this->request->data))
				$this->Session->setFlash('Course changes were saved', 'notices/success');
			else
				$this->Session->setFlash('Error while saving course changes', 'notices/error');

			$this->redirect(array('action'=>'view', $id));
		}

		$c = $this->Course->read(null, $id);
		$this->set('course', $c);
		$this->request->data = $c;

		$this->set('courseTypes', $this->Course->CourseType->find('list'));
		$conf = $this->Course->Conference->find('list');
		$conf[0] = 'Not a conference class';
		$this->set('conferences', $conf);
		$this->set('fundings', $this->Course->Funding->find('list'));
		$this->set('statuses', $this->Course->Status->find('list'));
	}

	public function admin_setLocation($id = null, $type = 'classroom')
	{
		if ($id != null)
		{
			if ($this->request->is('post'))
			{
				if ($this->request->data['Course']['location'] == 0)
					$this->redirect(array('plugin'=>false, 'controller'=>'Locations', 'action'=>'addToAgencyByCourse', $id, $type));
				else
				{
					$arr = array();
					if ($type == 'classroom')
						$arr['Course']['location_id'] = $this->request->data['Course']['location'];

					if ($type == 'shipping')
						$arr['Course']['shipping_location_id'] = $this->request->data['Course']['location'];

					$arr['Course']['id'] = $id;

					if ($this->Course->save($arr))
						$this->set('saved', true);
					else
						die('error');
				}
			}

			$this->Course->contain(array(
				'Hosting.Agency.Location.City',
				'Hosting.Agency.Location.State'
			));
			$this->set('course', $this->Course->read(null, $id));

			$this->set('type', $type);
		}
	}

	public function admin_add($step=1, $extra = null)
	{
		//<editor-fold defaultstate="collapsed" desc="post">
		if ($this->request->is('post') || $this->request->is('put'))
		{
			switch($step)
			{
				case 1:
					$this->Session->write('newcourse.info',$this->request->data['Course']);
					$this->redirect(array('action'=>'add', 2));
				break;

				case 2:
					$newcourse = $this->Session->read('newcourse');
					$newcourse = array_merge($newcourse, $this->request->data);
					$this->Session->write('newcourse', $newcourse);
					$this->redirect(array('action'=>'add', 3));
				break;

				case 3:

					//create agency if no exist
					if (empty($this->request->data['Course']['agency_id']))
					{
						$find = $this->Course->Hosting->Agency->findByName($this->request->data['Course']['agency']);
						if ($find)
							$this->request->data['Course']['agency_id'] = $find['Agency']['id'];
						else
						{
							$this->Course->Hosting->Agency->save(array('name'=>$this->request->data['Course']['agency']));
							$this->request->data['Course']['agency_id'] = $this->Course->Hosting->Agency->getLastInsertId();
						}
					}

					// poc_id == 0  = no contact
					// poc_id == 1  = create new contact
					// other = contact_user_id
					if ($this->request->data['Course']['poc_id'] == 1)
					{
						if ($this->request->data['User']['id']>0)
						{
							//update
							$this->Course->Attending->User->create($this->request->data['User']);
							if ($this->Course->Attending->User->save())
							{
								$this->request->data['Course']['poc_id'] = $this->Course->Attending->User->getLastInsertId();
							}
						}
						else
						{
							//new user
							unset($this->request->data['User']['id']);
							$this->request->data['User']['agency_id'] = $this->request->data['Course']['agency_id'];
							$this->Course->Attending->User->create($this->request->data['User']);
							if ($this->Course->Attending->User->save())
							{
								$this->request->data['Course']['poc_id'] = $this->Course->Attending->User->getLastInsertId();
							}
						}
					}

					if ($this->request->data['Course']['poc_id'] != 1)
					{
						$u = $this->Course->Attending->User->read(null, $this->request->data['Course']['poc_id']);
						$this->request->data['Course']['poc'] = $u['User']['name'];

						$newcourse = $this->Session->read('newcourse');
						$newcourse['Hosting'][] = $this->request->data['Course'];
						$this->Session->write('newcourse', $newcourse);
						$this->redirect(array('action'=>'add', 6));
					}

					//die(pr($this->request->data));
				break;

				case 4:
					$newcourse = $this->Session->read('newcourse.info');

					if ($this->request->data['Course']['shipping_location'] == 0)
					{
						$name = $this->request->data['ShippingLocation']['name'];
						$result = $this->Usps->process($this->request->data['ShippingLocation']['addr1'], $this->request->data['ShippingLocation']['zip5'], $this->request->data['ShippingLocation']['addr2']);
						$this->loadModel('Location');
						$result['agency_id'] = $this->Session->read('newcourse.Hosting.0.agency_id');
						$result['name']=$name;
						$this->request->data['Course']['shipping_location'] = $this->Location->process($result, $this);
					}

					$newcourse['shipping_location_id'] = $this->request->data['Course']['shipping_location'];
					$this->Session->write('newcourse.info', $newcourse);
					$this->redirect(array('action'=>'add', 5));
				break;

				case 5:
					$newcourse = $this->Session->read('newcourse.info');

					if ($this->request->data['Course']['location'] == 0)
					{
						$name = $this->request->data['Location']['name'];
						$result = $this->Usps->process($this->request->data['Location']['addr1'], $this->request->data['Location']['zip5'], $this->request->data['Location']['addr2']);
						$this->loadModel('Location');
						$result['agency_id'] = $this->Session->read('newcourse.Hosting.0.agency_id');
						$result['name']=$name;
						$this->request->data['Course']['location'] = $this->Location->process($result, $this);
					}

					$newcourse['location_id'] = $this->request->data['Course']['location'];
					$this->Session->write('newcourse.info', $newcourse);
					$this->redirect(array('action'=>'add', 7));
				break;




				case 9:
					if (!empty($this->request->data['Course']['contacts']))
						foreach($this->request->data['Course']['contacts'] as $contact)
							if (strpos($contact, '@'))
								$send[] = $contact;

					$more = preg_split("/[,\\n]+/", $this->request->data['Course']['moreemails']);
					if (!empty($more))
						foreach($more as $contact)
							if (strpos($contact, '@'))
								$send[] = trim($contact);

					$send = $this->Course->sendCalendarInvite($this->request->data['Course']['id'], $send);

					$send = $this->Course->sendCourseAnnouncment($this->request->data['Course']['id']);

					$this->Session->setFlash('Finished creating course.', 'notices/success');
					$this->redirect(array('controller'=>'Courses', 'action'=>'index'));
				break;
			}
		}
		//</editor-fold>

		switch($step)
		{
			case 1:
				if ($this->Session->check('newcourse.info'))
					$this->request->data = array('Course'=>$this->Session->read('newcourse.info'));
			break;

			case 2:
				if ($this->Session->check('newcourse'))
					$this->request->data = $this->Session->read('newcourse');
			break;

			case 3:
				$this->set('ct', $this->Course->CourseType->read(null, $this->Session->read('newcourse.info.course_type_id')));
			break;

			case 4:
			case 5:
				$locations = array();
				$newcourse = $this->Session->read('newcourse');
				foreach($newcourse['Hosting'] as $host)
				{
					$this->Course->Location->contain(array(
						'City',
						'State'
					));
					$locations[$host['agency']] = $this->Course->Location->findAllByAgencyId($host['agency_id']);
				}

				$this->set('locations', $locations);
			break;

			case 7:
				$this->Course->Location->contain(array('State', 'City'));
				$this->set('shippingLocation', $this->Course->Location->read(null, $this->Session->read('newcourse.info.shipping_location_id')));

				$this->Course->Location->contain(array('State', 'City'));
				$this->set('trainingLocation', $this->Course->Location->read(null, $this->Session->read('newcourse.info.location_id')));
			break;

			case 8:
				$newcourse = $this->Session->read('newcourse');
				$newids = array();
				foreach($newcourse['Course'] as $course)
				{
					$c = array_merge($newcourse['info'], $course);

					$this->Course->create($c);
					if ($this->Course->save())
					{
						$cid = $this->Course->getLastInsertId();
						$newids[] = $cid;
						foreach($newcourse['Hosting'] as $host)
						{
							$hosting = array(
								'agency_id'=>$host['agency_id'],
								'seats'=>intval($host['seats']),
								'status_id'=>$host['status_id'],
								'course_id'=>$cid
							);
							$this->Course->Hosting->create($hosting);
							$this->Course->Hosting->save();

							$contact = array(
								'user_id'=>$host['poc_id'],
								'course_id'=>$cid,
								'status_id'=>$host['contact_status_id']
							);
							$this->Course->Contact->create($contact);
							$this->Course->Contact->save();
						}
					}
					else
						die('course create error');
				}
				$this->Session->write('newids', $newids);
				$this->Session->delete('newcourse');

				$this->redirect(array('action'=>'add', 9));
			break;

			case 9:
				$courses = array();
				$ids = $this->Session->read('newids');
				foreach($ids as $id)
				{
					$this->Course->contain(array(
						'Contact.User'
					));
					$courses[$id] = $this->Course->read(null, $id);
				}
				$this->set('courses', $courses);
			break;

			case 10: //remove hosting record
				$newcourse = $this->Session->read('newcourse');
				foreach($newcourse['Hosting'] as $idx => $host)
					if ($host['agency_id'] == $extra)
						unset($newcourse['Hosting'][$idx]);

				$this->Session->write('newcourse', $newcourse);
				$this->Session->setFlash('Successfully removed hosting agency', 'notices/success');
				$this->redirect(array('action'=>'add', 6));
			break;
		}
		$this->set('courseTypes', $this->Course->CourseType->find('list'));
		$conf = $this->Course->Conference->find('list');
		$conf[0] = 'Not a conference class';
		$this->set('conferences', $conf);
		$this->set('fundings', $this->Course->Funding->find('list'));
		$this->set('statuses', $this->Course->Status->find('list'));

		$this->set('step', $step);
		$this->render('Courses'.DS.'pages'.DS.'add_steps'.DS.$step);
	}

	public function admin_quickAdd()
	{
		if ($this->request->is('post') || $this->request->is('put'))
		{
			if ($this->Course->validates($this->request->data))
			{
				if ($this->Course->save($this->request->data))
				{
					$this->Session->setFlash('Course created.', 'notices/success');
					$this->redirect(array('action'=>'view', $this->Course->getLastInsertId()));
				}
				else
				{
					$this->Session->setFlash('Error saving course.', 'notices/error');
				}
			}
			else
				$this->Session->setFlash('Please correct the validation errors below to continue.', 'notices/notice');
		}

		$this->set('courseTypes', $this->Course->CourseType->find('list'));
		$conf[0] = 'Not a conference class';
		$conf = array_merge($conf, $this->Course->Conference->find('list'));
		$this->set('conferences', $conf);
		$this->set('fundings', $this->Course->Funding->find('list'));
		$this->set('statuses', $this->Course->Status->find('list'));
	}

	public function admin_delete($id = null)
	{
		if ($this->request->is('post') || $this->request->is('put'))
		{
			if ($this->Course->delete($id))
			{
				$this->Session->setFlash('Successfully deleted course', 'notices/success');
				$this->redirect(array('action'=>'index'));
			}
		}

		$this->request->data = $this->Course->read(null, $id);
	}

	public function admin_ajax_iclose($id = null, $action = 'close')
	{
		$this->Course->id = $id;
		if ($this->Course->saveField('iclosed', $action=='close'))
			die('Saved!');
		else
			die('Error while changing');
	}

	public function admin_calendartest($id)
	{
		//$cals = $this->Gcal->addEvent();//getCal()->calendarList->listCalendarList();

		//die(var_dump($cals)); //l6a2e7mhd4hhp3cds2df6bck80

		//$cals = $this->Gcal->updateEvent('l6a2e7mhd4hhp3cds2df6bck80');
		//die(var_dump($cals));

		$meeting_date = "2012-07-21 13:40:00";
		$meetingstamp = STRTOTIME($meeting_date . " UTC");
		$dtstart= GMDATE("Ymd\THis\Z",$meetingstamp);
		$dtend= GMDATE("Ymd\THis\Z",$meetingstamp+3600);
		$todaystamp = GMDATE("Ymd\THis\Z");

		//Create unique identifier
		$cal_uid = DATE('Ymd').'T'.DATE('His')."-".RAND()."@mydomain.com";

		$desc = $this->Course->getDescription($id);


$ics= 'BEGIN:VCALENDAR
METHOD:REQUEST
PRODID:Microsoft Exchange Server 2007
VERSION:2.0
BEGIN:VTIMEZONE
TZID:Central Standard Time
BEGIN:STANDARD
DTSTART:16010101T020000
TZOFFSETFROM:-0500
TZOFFSETTO:-0600
RRULE:FREQ=YEARLY;INTERVAL=1;BYDAY=1SU;BYMONTH=11
END:STANDARD
BEGIN:DAYLIGHT
DTSTART:16010101T020000
TZOFFSETFROM:-0600
TZOFFSETTO:-0500
RRULE:FREQ=YEARLY;INTERVAL=1;BYDAY=2SU;BYMONTH=3
END:DAYLIGHT
END:VTIMEZONE
BEGIN:VEVENT
DESCRIPTION;LANGUAGE=en-US:'.$desc['desc'].'
SUMMARY;LANGUAGE=en-US:'.$desc['title'].'
DTSTART;TZID=Central Standard Time:20120716T000000
DTEND;TZID=Central Standard Time:20120718T000000
UID:'.$cal_uid.'
CLASS:PUBLIC
PRIORITY:5
DTSTAMP:'.$todaystamp.'
TRANSP:OPAQUE
STATUS:CONFIRMED
SEQUENCE:2
LOCATION;LANGUAGE=en-US:New York\, NY
X-MICROSOFT-CDO-APPT-SEQUENCE:2
X-MICROSOFT-CDO-OWNERAPPTID:2110183705
X-MICROSOFT-CDO-BUSYSTATUS:FREE
X-MICROSOFT-CDO-INTENDEDSTATUS:FREE
X-MICROSOFT-CDO-ALLDAYEVENT:TRUE
X-MICROSOFT-CDO-IMPORTANCE:1
X-MICROSOFT-CDO-INSTTYPE:0
BEGIN:VALARM
ACTION:DISPLAY
DESCRIPTION:REMINDER
TRIGGER;RELATED=START:-PT18H
END:VALARM
END:VEVENT
END:VCALENDAR';

		file_put_contents('/tmp/meeting.ics', $ics);


		$email = new OutlookEmail('smtp');
		$email->from(array('noreply@alerrt.org' => 'No Reply'))
			->to('alerrt@txstate.edu')
			->subject($desc['title'])
			->attachments(array(
				'meeting.ics'=>array(
					'file'=>'/tmp/meeting.ics',
					'mimetype'=>'text/calendar; charset="utf-8"; method=REQUEST',
					'contentDisposition'=>false
				)
			))
			->send($desc['desc']);



		die('done');
	}

	/** Instructor functions **/

	public function instructor_view($id = null, $showApply=1)
	{
		$this->Course->contain(array(
			//'Attending.User.Agency',
			//'Attending.Status',
			'Instructing.Instructor.Tier',
			'Instructing.Status',
			'Instructing.User',
			'Location.City',
			'Location.State',
			'Hosting.Agency',
			'Hosting.Status',
			'Contact.User.Agency',
			'Contact.Status',
			'CourseType',
			'Status',
			'Note',
			'Funding'
		));
		$this->set('course', $this->Course->read(null, $id));

		$this->Course->Instructing->Instructor->contain(array(
			'Tier',
			'User'
		));
		$this->set('Instructor', $this->Course->Instructing->Instructor->findByUserId($this->Auth->User('id')));
		$this->set('showApply', $showApply);
	}

	public function instructor_viewTabs($id = null)
	{
		$this->Course->contain(array('CourseType'));
		$course = $this->Course->read(null, $id);
		$list[$id] = $course['CourseType']['shortname'].' on '.date('m-d-Y', strtotime($course['Course']['startdate']));

		while ($course['Course']['next_course_id']!=null)
		{
			$this->Course->contain(array('CourseType'));
			$id = $course['Course']['next_course_id'];
			$course = $this->Course->read(null, $id);
			$list[$id] = $course['CourseType']['shortname'].' on '.date('m-d-Y', strtotime($course['Course']['startdate']));
		}

		$this->set('list', $list);
	}

	public function instructor_index()
	{
		$this->Course->contain(array(
			'Instructing.Status',
			'Instructing.user_id = '.$this->Auth->user('id'),
			'CourseType',
			'NextCourse',
			'PrevCourse'
		));
		$this->set('courses', $this->Course->find('all', array(
			'conditions'=>array(
				'Course.startdate > now()',
				'Course.status_id'=>10,
				'Course.conference_id'=>0
			),
			'order'=>'Course.startdate ASC'
		)));
	}
}
