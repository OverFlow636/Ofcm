<?php

class ContactsController extends OfcmAppController
{


	public function acu($agency =null)
	{
		if ($agency != null)
		{
			$this->Contact->User->contain();
			$this->set('users', $this->Contact->User->find('all', array(
				'conditions'=>array(
					'agency_id'=>$agency
				)
			)));
			$this->response->type('ajax');
		}
	}

	public function admin_newContactForAgency($aid =null, $cid = null)
	{
		if ($this->request->is('post') && !empty($this->request->data['User']))
		{
			$this->request->data['User']['agency_id']=$aid;
			$this->request->data['User']['group_id']=1;
			if ($this->Contact->User->save($this->request->data))
			{
				//add contact to class, set saved true
				$arr = array(
					'course_id'=>$cid,
					'user_id'=>$this->Contact->User->getLastInsertId()
				);
				if ($this->Contact->save($arr))
					$this->set('saved', true);
				else
					die('error3');
			}
			else
				echo 'no valid';
		}

		$this->set('agency', $this->Contact->User->Agency->read(null, $aid));
		$this->set('course', $this->Contact->Course->read(null, $cid));
	}
}