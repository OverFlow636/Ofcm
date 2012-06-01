<?php

App::uses('OfcmAppController', 'Ofcm.Controller');
class CertificatesController extends OfcmAppController
{
	var $allowedActions = array(
		'view', 'search'
	);

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
				),
				'limit'=>30
			)));

			$this->render('results');
		}
	}

	function view($id = null, $type=null)
	{
		if ($id != null)
		{
			if ($type != null)
			{
				$this->loadModel('SurveyCertificate');
				$this->SurveyCertificate->contain(array(
					'Conference'
				));
				$this->SurveyCertificate->id = $id;
				$sc = $this->SurveyCertificate->read();

				$cert['User']['first_name'] = $sc['SurveyCertificate']['fname'];
				$cert['User']['last_name'] = $sc['SurveyCertificate']['lname'];
				$cert['Course']['CourseType']['certName'] = $sc['Conference']['name'];
				$cert['Course']['CourseType']['hours'] = $sc['SurveyCertificate']['hours'];
				$cert['Course']['enddate'] = date('F m Y',strtotime($sc['Conference']['enddate']));
				$cert['Course']['CourseType']['id'] = 1;

				$this->set('cert', $cert);
				$this->set('type', $type);
				$this->layout = 'pdf';
			}
			else
			{
				$this->loadModel('Ofcm.Attending');
				$this->Attending->id=$id;
				$this->Attending->contain(array(
					'User',
					'Course',
					'Course.CourseType',
					'Status'
				));
				$this->set('cert', $this->Attending->read());
				$this->layout = 'pdf';
			}
		}
	}
}
