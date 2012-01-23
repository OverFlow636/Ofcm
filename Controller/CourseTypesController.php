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
						'Course.status_id'=>10
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

}