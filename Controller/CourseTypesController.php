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
}