<?php

App::uses('CakeEventListener', 'Event');

class OfcmListener implements CakeEventListener
{
	public function implementedEvents()
	{
		return array(
			'Plugin.Ofcm.adminView_beforeRead'	=> 'adminViewBeforeRead'
		);
	}

	public function adminViewBeforeRead($event)
	{
		$event->subject->Course->contain(array(
			'CourseType',
			'Conference',
			'Attending.Status',
			'Attending.ConfirmationEmail'=>array(
				'fields'=>array('read', 'created', 'modified')
			),
			'Attending.CertStatusEmail'=>array(
				'fields'=>array('read', 'created', 'modified')
			),
			'Attending.User',
			'Instructing.ConfirmationEmail'=>array(
				'fields'=>array('read', 'created', 'modified')
			),
			'Instructing.AfterActionEmail'=>array(
				'fields'=>array('read', 'created', 'modified')
			),
			'Instructing.Status',
			'Instructing.Tier',
			/*'Instructing.Instructor.Tier',
			'Instructing.Instructor.User',*/
			'Location.City',
			'Location.State',
			'Funding',
			'ShippingLocation.City',
			'ShippingLocation.State',
			'Status',
			'Hosting.Agency',
			'Hosting.Status',
			'Contact.User'
		));

	}
}