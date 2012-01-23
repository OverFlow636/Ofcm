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

}
