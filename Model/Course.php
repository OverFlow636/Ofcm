<?php
App::uses('OfcmAppModel', 'Ofcm.Model');
/**
 * Course Model
 *
 */
class Course extends OfcmAppModel
{
	public $actsAs = array(
		'Containable'
	);

	public $belongsTo = array(
		'Ofcm.CourseType',
		'Conference'=>array(
			'counterCache'=>true
		),
		'Funding',
		'ShippingLocation' => array(
			'className' => 'Location',
			'foreignKey' => 'shipping_location_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		),
		'Location',
		'Status'
	);

	public $hasMany = array(
		'Attending'=>array(
			'className'=>'Ofcm.Attending',
			'dependent'=>true
		),
		'Contact'=>array(
			'className'=>'Ofcm.Contact',
			'dependent'=>true
		),
		'Hosting'=>array(
			'className'=>'Ofcm.Hosting',
			'dependent'=>true
		),
		'Instructing'=>array(
			'className'=>'Ofcm.Instructing',
			'dependent'=>true
		)
	);

	public $hasAndBelongsToMany = array(
		'Note'
	);



	public function afterSave($created)
	{
		if (Configure::read('debug') == 0)
		{
			App::import('Component', 'Gcal');
			$gcal = new GcalComponent(new ComponentCollection());
			$gcal->startup(new Controller());
			$cal = $gcal->getCal();

			$gcal_id = $this->field('gcal_id');
			if ($gcal_id != "-1")
			{
				if ($created || !$gcal_id)
					$event = $gcal->getNewEvent();
				else
					$event = $gcal->getNewEvent($gcal_id);

				$desc = $this->getDescription($this->id);
				$event->setSummary($desc['title']);
				$event->setDescription($desc['desc']);
				$event->setLocation($desc['loc']);

				$start = new EventDateTime();
				$start->setDate(date('Y-m-d', strtotime($desc['start'])));
				$event->setStart($start);

				$end = new EventDateTime();
				$end->setDate(date('Y-m-d', strtotime($desc['end'].'+1 day')));
				$event->setEnd($end);

				$event->attendees = array();


				if ($created || !$gcal_id)
				{
					$event = $cal->events->insert('primary', $event);
					if (!empty($event['id']))
						$this->saveField('gcal_id', $event['id']);
					else
						$this->saveField('gcal_id', "-1");
				}
				else
					$cal->events->update('primary', $gcal_id, $event);
			}
		}
	}


	public function getDescription($id)
	{
		$this->contain(array(
			'CourseType.name',
			'CourseType.shortname',
			'Conference.name',
			'Funding.name',
			'ShippingLocation.City',
			'ShippingLocation.State',
			'Location.City',
			'Location.State',
			'Status.status',				//color by status?
			'Instructing'=>array(
				'conditions'=>array(
					'Instructing.status_id'=>3
				)
			),
			'Instructing.User.name',
			'Hosting.Agency.name',
			'Contact.User.Agency.name',
			'Note'=>array(
				'conditions'=>array(
					'visibility'=>1
				)
			),
			'Note.User.name'
		));
		$data = $this->read(null, $id);

		$desc = '';

		$desc .= "Class Details\n";
		$desc .= "\tType: {$data['CourseType']['name']}\n";
		$desc .= "\tFunding: {$data['Funding']['name']}\n";
		$desc .= "\tScheduled: ".date('F d, Y', strtotime($data['Course']['created']))."\n";
		$desc .= "\n";

		$desc .= "Instructors\n";
		if (!empty($data['Instructing']))
		{
			foreach($data['Instructing'] as $ins)
				if ($ins['role'])
					$desc .= "\t{$ins['User']['name']} - {$ins['role']}\n";
				else
					$desc .= "\t{$ins['User']['name']}\n";
		}
		else
			$desc .= "\tNone Picked Yet\n";
		$desc .= "\n";

		$desc .= "Host Agency\n";
		if (!empty($data['Hosting']))
		{
			foreach($data['Hosting'] as $host)
				$desc .= "\t{$host['Agency']['name']} - {$host['seats']} seats\n";
		}
		else
			$desc .= "\tNone Picked Yet\n";
		$desc .= "\n";

		$desc .= "Point of Contact\n";
		if (!empty($data['Contact']))
		{
			foreach($data['Contact'] as $con)
				$desc .= "\t{$con['User']['name']} - {$con['User']['Agency']['name']} - {$con['User']['main_phone']} - {$con['User']['email']}\n";
		}
		else
			$desc .= "\tNone Picked Yet\n";
		$desc .= "\n";

		$desc .= "Training Location\n";
		if ($data['Location']['name'])
		{
			$desc .= "\t{$data['Location']['name']}\n";
			$desc .= "\t{$data['Location']['addr1']}\n";
			if ($data['Location']['addr2'])
				$desc .= "\t{$data['Location']['addr2']}\n";
			$desc .= "\t{$data['Location']['City']['name']}, {$data['Location']['State']['abbr']} {$data['Location']['zip5']}\n";
		}
		else
			$desc .= "\tTBA\n";
		$desc .= "\n";

		$desc .= "Shipping Location\n";
		if ($data['ShippingLocation']['name'])
		{
			$desc .= "\t{$data['ShippingLocation']['name']}\n";
			$desc .= "\t{$data['ShippingLocation']['addr1']}\n";
			if ($data['ShippingLocation']['addr2'])
				$desc .= "\t{$data['ShippingLocation']['addr2']}\n";
			$desc .= "\t{$data['ShippingLocation']['City']['name']}, {$data['ShippingLocation']['State']['abbr']} {$data['ShippingLocation']['zip5']}\n";
		}
		else
			$desc .= "\tTBA\n";
		$desc .= "\n";

		$desc .= "Notes\n";
		if (!empty($data['Note']))
		{
			foreach($data['Note'] as $note)
				$desc .= "\t{$note['User']['name']}: ".strip_tags($note['note'])."\n";
		}
		else
			$desc .= "\tNone\n";
		$desc .= "\n";


		//echo '<pre>'.$desc.'</pre>';
		$title = "{$data['CourseType']['shortname']} in {$data['Course']['location_description']} from {$data['Funding']['name']}";
		if ($data['Course']['status_id'] != 10)
			$title = $data['Status']['status'].': '.$title;

		//pr($data);


		return array(
			'desc'=>$desc,
			'title'=>$title,
			'loc'=>$data['Course']['location_description'],
			'start'=>$data['Course']['startdate'],
			'end'=>$data['Course']['enddate']
		);
	}
}
