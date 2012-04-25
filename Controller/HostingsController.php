<?php

class HostingsController extends OfcmAppController
{


	function admin_view($id = null)
	{
		$this->Hosting->contain(array(
			'Course.CourseType',
			'Agency',
			'Status'
		));
		$this->set('hosting', $this->Hosting->read(null, $id));
	}

	function admin_edit($id = null)
	{
		if ($this->request->is('ajax'))
			$this->layout = 'ajax';

		if ($this->request->is('post'))
		{
			if ($this->Hosting->save($this->request->data))
				$this->set('saved', true);
			else
				die('error');
		}

		$this->Hosting->contain(array(
			'Course.CourseType',
			'Agency',
			'Status'
		));
		$this->request->data = $this->Hosting->read(null, $id);
		$this->set('statuses', $this->Hosting->Status->find('list'));
	}

	function admin_add($cid = null)
	{
		if ($this->request->is('post'))
		{
			if ($this->Hosting->save($this->request->data))
			{
				if ($this->request->data['Hosting']['poc_id'] == 1) //new
					$this->redirect(array('controller'=>'Contacts', 'action'=>'newContactForAgency', $this->request->data['Hosting']['agency_id'], $cid));
				else
				{
					if ($this->request->data['Hosting']['poc_id'] > 0)
					{
						$arr = array(
							'course_id'=>$this->request->data['Hosting']['course_id'],
							'user_id'=>$this->request->data['Hosting']['poc_id']
						);
						if ($this->Hosting->Course->Contact->save($arr))
							$this->set('saved', true);
						else
							die('error3');
					}
					else
						$this->set('saved', true);
				}
			}
			else
				die('error');
		}

		$this->Hosting->Course->contain(array(
			'CourseType'
		));
		$this->set('course', $this->Hosting->Course->read(null, $cid));
		$this->set('statuses', $this->Hosting->Status->find('list'));
	}

	function admin_delete($id = null)
	{
		if ($this->Hosting->delete($id))
			$this->Session->setFlash('Hosting record was deleted', 'notices/success');
		else
			$this->Session->setFlash('Error deleteing hosting record', 'notices/error');
		$this->redirect($this->referer());
	}
}