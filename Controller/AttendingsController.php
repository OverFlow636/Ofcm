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
		$this->Attending->Course->id = $cid;
		if ($cid == null || !$this->Attending->Course->exists($cid))
		{
			$this->Session->setFlash('Invalid Course', 'notices/error');
			$this->redirect(array('plugin'=>'ofcm', 'controller'=>'Courses', 'action'=>'upcoming'));
		}

		$this->Attending->Course->contain(array(
			'CourseType'
		));
		$this->set('course', $this->Attending->Course->read());

		switch ($step)
		{
			case 1:
				//<editor-fold defaultstate="collapsed" desc="First page, select or create user">
				if ($this->request->is('post'))
				{
					//<editor-fold defaultstate="collapsed" desc="Posted">
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
							$this->Session->setFlash('Please fix the problems below in red.', 'notices/error');
						}
					}
					//</editor-fold>
				}

				$this->Attending->User->contain(array(
					'Attending'
				));
				$agencylist = $this->Attending->User->find('all',array(
					'conditions'=>array(
						'User.agency_id' => $this->Auth->user('agency_id')
					),
					'order'=>array(
						'User.name'
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
				//</editor-fold>
			break;

			case 2:
				//<editor-fold defaultstate="collapsed" desc="Step 2, confirm user info">
				if ($this->request->is('post'))
				{
					if ($this->Attending->User->saveAll($this->request->data, array('validate'=>'first')))
					{
						$a['Attending'] = array(
							'user_id'=>$extra,
							'course_id'=>$cid,
							'status_id'=>1
						);

						$approved = $this->Attending->find('count', array(
							'conditions'=>array(
								'Attending.course_id'=>$cid,
								'Attending.status_id'=>array(3,26)
							)
						));

						$this->Attending->Course->contain(array(
							'CourseType'
						));
						$course = $this->Attending->Course->read(null, $cid);

						//die('approved:'. $approved. ' | max:'.$course['CourseType']['maxStudents']);

						if ($approved >= $course['CourseType']['maxStudents'])
							$a['Attending']['status_id'] = 25; //add to waitlist

						if ($extra != $this->Auth->user('id'))
							$a['Attending']['registered_by_id'] =  $this->Auth->user('id');

						if ($this->Attending->save($a))
						{
							$this->Session->setFlash('The course application has been submitted, you will be notified if you are approved or not.', 'notices/success');

							/*$this->Attending->Course->contain(array('Location.City','Location.State', 'CourseType', 'Status'));
							$c = $this->Attending->Course->read();
							$this->set('Course', $c);

							$this->Attending->User->contain(array(
								'Phone',
								'Agency'
							));
							$au = $this->Attending->User->read(null, $this->Auth->user('id'));
							$this->set('AuthUser', $au);

							$data = array(
								'AuthUser'		=> $au,
								'AppliedCourse'	=> $c
							);

							if ($extra != $this->Auth->user('id'))
							{
								$this->Attending->User->contain(array(
									'Phone'
								));
								$data['AppliedUser'] = $this->Attending->User->read(null, $extra);
							}
							else
								$data['AppliedUser'] = $au;

							$this->HLR->dispatch('UserApplicationSubmitted', $data);*/

							$this->redirect('/ofcm/Courses/upcoming');
						}
						else
						{
							$this->Session->setFlash('Error applying you to the course, Administrator has been emailed about the problem.', 'notices/error');
							$this->redirect('/');
						}
					}
					else
						$this->Session->setFlash('Please fix the problems below in red.', 'notices/error');
				}
				else
				{
					$this->Attending->User->contain(array(
						'Phone'
					));
					//$this->set('applyUser', $this->Attending->User->read(null, $extra));
					$this->request->data = $this->Attending->User->read(null, $extra);
				}
				//</editor-fold>
		}

		$this->render('steps'.DS.$step);
	}

}