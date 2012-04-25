<?php
App::uses('OfcmAppController', 'Ofcm.Controller');
App::uses('CakeEmail', 'network/Email');
/**
 * Courses Controller
 *
 */
class CoursesController extends OfcmAppController
{
	var $allowedActions = array(
		'getOpen'
	);

	public function __construct($request = null, $response = null)
	{
		$vars = Configure::read('Ofcm.CoursesController');
		foreach($vars as $var => $value)
			$this->$var = $value;
		parent::__construct($request, $response);
	}

	public function view($id = null)
	{
		if ($id != null)
		{


			$this->set('course', $this->Course->read(null, $id));
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

	public function dataTable($type='upcoming')
	{
		$conditions = array();
		switch($type)
		{
			case 'upcoming':
				$conditions[] = 'Course.startdate > NOW()';
				$conditions[] = array('Course.conference_id'=>0);

				$aColumns = array(
					'Course.startdate',
					'CourseType.shortname',
					'Course.location_description',
					'Course.id',
					'Status.id',
					'Status.id'
				);
			break;

			case 'admin_index':
				$aColumns = array(
					'Course.id',
					'Course.startdate',
					'CourseType.shortname',
					'Course.location_description',
					'Status.id'
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
						case 4: $order = array('Course.status_id'=>$_GET['sSortDir_0']); break;
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
		$conditions = array('conditions' => array('UNIX_TIMESTAMP(startdate) >=' => $vars['start'], 'UNIX_TIMESTAMP(startdate) <=' => $vars['end']));
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

	public function admin_index()
	{

	}

	public function admin_view($id = null, $page = 'dashboard')
	{
		if ($id == null)
			die('no');

		$this->fire('Plugin.Ofcm.adminView_beforeRead');

		$c = $this->Course->read(null, $id);
		$this->set('course', $c);
		$this->render('Courses/pages/'.$page);
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
				$course['Location']['gmap'] = '<a href="http://maps.google.com/maps?daddr=' . urlencode($course['Location']['addr1']. ', '.$course['Location']['City']['name'].', '.$course['Location']['State']['abbr'].' '.$course['Location']['zip']).'">Directions from Google Maps</a>';

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
		}


		if ($confirm)
		{
			switch($type)
			{
				case 'confirm':
					$offc = $sta = array();
					foreach($data['Attending'] as $att)
						switch($att['status_id'])
						{
							case 3:
							case 26: $offc[] = $att;
							break;

							case 4:
							case 5:
							case 8:
							case 16:
							case 17:
							case 18:
							case 19:
							case 22:
							case 23: $sta[] = $att;
						}

					foreach($offc as $user)
					{
						$args = array(
							'email_template_id'=>4,
							'sendTo'=>$user['User']['email'],
							'from'=>array('erin@alerrt.org'=>'Erin Etheridge')
						);
						if (!empty($user['RegisteredBy']))
							$args['cc'] = $user['RegisteredBy']['email'];

						die('woulda sent');
						$result = $this->_sendTemplateEmail($args, array_merge($user, $course));

						$this->Course->Attending->save(array(
							'id'=>$user['id'],
							'confirmation_message_id'=>$result['mid']
						));
					}
				break;

				case 'iconfirm':
					$offc = $sta = array();
					foreach($data['Instructing'] as $att)
						switch($att['status_id'])
						{
							case 3:
							case 26: $offc[] = $att;
							break;

							case 4:
							case 5:
							case 8:
							case 16:
							case 17:
							case 18:
							case 19:
							case 22:
							case 23: $sta[] = $att;
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
				break;
			}

			$this->redirect(array('action'=>'view', $id, 'messages'));
		}
		else
		{
			$this->set('data', $data);
		}

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

		$this->fire('Plugin.Ofcm.adminView_beforeRead');

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
}
