<?php
App::uses('OfcmAppController', 'Ofcm.Controller');
/**
 * Courses Controller
 *
 */
class CoursesController extends OfcmAppController
{

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

		if ($render == 'list')
		{
			$this->set('courses', $this->Course->find('all', array(
				'conditions'=>array(
					'Course.startdate > NOW()',
					'Course.conference_id'=>0
				),
				'order'=>array(
					'Course.startdate'
				)
			)));
		}

		$this->set('render', $render);
		$this->render('pages/'.$render);
	}

	public function dataTable()
	{
		$conditions[] = 'Course.startdate > NOW()';
		$conditions[] = array('Course.conference_id'=>0);

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
			switch($_GET['iSortCol_0'])
			{
				case 0: $order = array('Course.startdate'=>$_GET['sSortDir_0']); break;
				case 1: $order = array('Course.course_type_id'=>$_GET['sSortDir_0']); break;
				case 2: $order = array('Course.location_description'=>$_GET['sSortDir_0']); break;
				case 3: $order = array('Course.status_id'=>$_GET['sSortDir_0']); break;
			}
		}


		if (!empty($_GET['sSearch']))
		{
			$or = array();
			$or[] = array('Course.location_description LIKE'=>$_GET['sSearch'].'%');
			$or[] = array('CourseType.shortname LIKE'=>$_GET['sSearch'].'%');

			$conditions[] = array('or'=>$or);
		}


		$found = $this->Course->find('count', array(
			'conditions'=>$conditions
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
	}

	public function calendarFeed($id=null)
	{
		$this->autoRender = false;
		$vars = $_GET;
		$conditions = array('conditions' => array('UNIX_TIMESTAMP(startdate) >=' => $vars['start'], 'UNIX_TIMESTAMP(startdate) <=' => $vars['end']));
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
					'url' => '/ofcm/CourseTypes/catalog/'.$event['CourseType']['id'],
					'details' => $event['CourseType']['slider_summary'],
					'color' => (empty($event['CourseType']['calendar_color'])?'#ccc':$event['CourseType']['calendar_color']),
					'textColor' => (empty($event['CourseType']['calendar_textColor'])?'white':$event['CourseType']['calendar_textColor']),
			);

			if (strtotime($event['Course']['startdate']) < time())
				$ce['className'] = 'ghost';

			$data[] = $ce;
		}
		$this->response->type('text');
		$this->response->body(json_encode($data));
	}
}
