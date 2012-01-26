<?php

App::uses('OfcmAppController', 'Ofcm.Controller');
class CertificatesController extends OfcmAppController
{

	public function search()
	{
		if ($this->request->isPost())
		{
			$this->loadModel('Ofcm.Attending');

			$cond = array();
			if(!empty($this->request->data['Certificates']['lname']))
				$cond[] = array('User.last_name LIKE '=>'%' . $this->request->data['Certificates']['lname'] . '%');

			if(!empty($this->request->data['Certificates']['fname']))
				$cond[] = array('User.first_name LIKE '=>'%' . $this->request->data['Certificates']['fname'] . '%');

			if(!empty($this->request->data['Certificates']['email']))
				$cond[] = array('User.email LIKE '=>'%' . $this->request->data['Certificates']['email'] . '%');

			if(!empty($this->request->data['Certificates']['ssid']))
				$cond[] = array('User.ssid LIKE '=>'%' . $this->request->data['Certificates']['ssid'] . '%');

			if (empty($cond))
			{
				$this->Session->setFlash('Please search with at least one field', 'notices/error');
				$this->redirect(array('action'=>'search'));
			}

			$this->set('results', $this->Attending->find('all', array(
				'conditions'=>array(
					$cond
				),
				'contain'=>array(
					'User',
					'Course.CourseType',
					'Status'
				)
			)));

			$this->render('results');
		}
	}

	public function view($id = null)
	{

	}
}
