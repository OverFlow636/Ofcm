<?php

App::uses('OfcmAppController', 'Ofcm.Controller');

class CourseTypesController extends OfcmAppController
{
	public $allowedActions = array('view', 'catalog');

	/**
	 * Display the course catalog page, allows people to click on ct views
	 */
	public function catalog($id = null)
	{
		if ($id != null)
		{
			$this->CourseType->contain(array(
				'Course'=>array(
					'conditions'=>array(
						'startdate > NOW()',
						'Course.status_id'=>10,
						'Course.conference_id'=>0,
						'Course.public'=>1
					),
					'order'=>array(
						'startdate'=>'ASC'
					)
				),
				'Course.Attending.Status',
				'Course.Status',
				'Course.CourseType'
			));
			$this->fire('Plugin.Ofcm.catalog_beforeRead');
			$this->set('courseType', $this->CourseType->read(null, $id));
		}

		$this->set('courseTypes', $this->CourseType->find('all', array(
			'order'=>array(
				'section_order'=>'ASC'
			),
			'conditions'=>array(
				'catalog'=>1
			)
		)));
	}

	public function view($id = null)
	{
		$this->CourseType->id = $id;
		if (!$this->CourseType->exists())
			throw new NotFoundException(__('Invalid Course Type'));

		$this->set('courseType', $this->CourseType->read());
	}

	public function dataTable($type='admin_index', $extra = null)
	{
		$conditions = array();
		switch($type)
		{
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

			case 'admin_index':
				$aColumns = array(
					'CourseType.shortname',
					'CourseType.name'
				);
			break;

		}

		$order = array(
			'CourseType.shortname'
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
						case 0: $order = array('CourseType.shortname'=>$_GET['sSortDir_0']); break;
						case 1: $order = array('CourseType.name'=>$_GET['sSortDir_0']); break;
					}
				break;

			}

		}


		if (!empty($_GET['sSearch']))
		{
			$or = array();
			$or[] = array('CourseType.shortname LIKE'=>$_GET['sSearch'].'%');
			$or[] = array('CourseType.name LIKE'=>$_GET['sSearch'].'%');
			$conditions[] = array('or'=>$or);
		}

		for($i = 0; $i<count($aColumns);$i++)
		{
			if ($_GET['bSearchable_'.$i] == "true" && $_GET['sSearch_'.$i] != '' && $_GET['sSearch_'.$i] != '0')
				$conditions[] = array($aColumns[$i] => $_GET['sSearch_'.$i]);
		}

		$this->CourseType->recursive = 1;
		$found = $this->CourseType->find('count', array(
			'conditions'=>$conditions
		));
		$this->CourseType->contain(array(
		));
		$courses = $this->CourseType->find('all', array(
			'conditions'=>$conditions,
			'order'=>$order,
			'limit'=>$limit,
			'offset'=>$offset
		));

		//echo "/* ".print_r($order, true).' */';

		$this->set('found', $found);
		$this->set('courseTypes', $courses);
		$this->render('CourseTypes'.DS.'tables'.DS.$type);
	}


	public function admin_view($id = null)
	{
		$this->set('courseType', $this->CourseType->read(null, $id));
	}

	public function admin_edit($id = null)
	{
		if ($this->request->is('post') || $this->request->is('put'))
		{
			if ($this->CourseType->save($this->request->data))
			{
				$this->Session->setFlash('Successfully edited the course type.', 'notices/success');
				$this->redirect(array('action'=>'view', $id));
			}
		}
		else
			$this->request->data = $this->CourseType->read(null, $id);
	}

	public function admin_dataTable($type = 'admin_index', $extra = null)
	{
		$this->autoRender = false;
		$this->dataTable($type, $extra);
	}

	public function admin_index()
	{

	}
}