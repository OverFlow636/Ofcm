<?php

App::uses('OfcmAppController', 'Ofcm.Controller');
/**
 * Courses Controller
 *
 */
class AttendingsController extends OfcmAppController
{

	public function apply($cid = null, $step = 1, $extra = null)
	{
		if ($cid == null)
		{
			$this->Session->setFlash('Invalid course', 'notice_error');
			$this->redirect('/');
		}

		$this->set('course', $this->Attending->Course->read(null, $cid));

		switch ($step)
		{
			case 1:

				if ($this->request->is('post'))
				{
					if (!empty($this->request->data['User']['id']) && empty($this->request->data['User']['email']) )
					{
						//existing user selected
						$this->redirect(array('action'=>'apply', $cid, 2, $this->request->data['User']['id']));
					}
					else
					{
						unset($this->request->data['User']['id']);
						$this->Attending->User->create();

						$this->request->data['User']['group_id'] = 1;
						$this->request->data['User']['verified'] = 1;
						$this->request->data['User']['agency_id'] = $this->Auth->user('agency_id');

						if ($this->Attending->User->save($this->request->data))
							$this->redirect(array('action'=>'apply', $cid, 2, $this->Attending->User->getLastInsertId()));
						else
						{
							$this->Session->setFlash('Please fix the problems below in red.', 'notice_error');
						}
					}

				}

				$this->Attending->User->contain(array(
					'Attending'
				));
				$agencylist = $this->Attending->User->find('all',array(
					'conditions'=>array(
						'User.agency_id' => $this->Auth->user('agency_id')
					)
				));

				$registered = array();
				foreach($agencylist as $idx => $user)
					if (count($user['Attending']))
						foreach($user['Attending'] as $attending)
							if ($attending['course_id'] == $cid)
							{
								$registered[] = $user;
								unset($agencylist[$idx]);
							}

				$this->set('available',  Set::combine($agencylist, '/User/id', '/User/name'));
				$this->set('registered', Set::combine($registered, '/User/id', '/User/name'));

			break;
		}

		$this->render('steps'.DS.$step);
	}

}