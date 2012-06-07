<?php
App::uses('OfcmAppController', 'Ofcm.Controller');
/**
 * Instructors Controller
 *
 */
class InstructorsController extends OfcmAppController
{

	public function instructor_testRequirements($id = null)
	{
		$this->Instructor->contain(array(
			'User'
		));
		$data = $this->Instructor->read(null, $id);
		$this->set('instructor', $data);

		$this->set('results', $this->Instructor->Tier->TierRequirement->test($data));
		$this->Instructor->Tier->contain(array(
			'TierRequirement'
		));
		$this->set('tiers', $this->Instructor->Tier->find('all'));
	}

	public function instructor_index()
	{
		$this->Instructor->contain(array(
			'Tier',
			'Status'
		));
		$this->set('instructors', $this->Instructor->find('all', array('order'=>'created DESC', 'limit'=>10)));
	}

	public function admin_dataTable($type='sindex', $id=null)
	{
		$this->datatable($type, $id);
	}

	public function dataTable($type='sindex', $id = null)
	{
		$conditions = array();
		$joins = array(
			array(
				'table'=>'users',
				'alias'=>'User',
				'type'=>'LEFT',
				'conditions'=>array(
					'User.id = Instructor.user_id'
			)),array(
				'table'=>'agencies',
				'alias'=>'Agency',
				'type'=>'LEFT',
				'conditions'=>array(
					'Agency.id = User.agency_id'
			)),array(
				'table'=>'tiers',
				'alias'=>'Tier',
				'type'=>'LEFT',
				'conditions'=>array(
					'Tier.id = Instructor.tier_id'
			))
		);
		switch($type)
		{
			case 'online':
				$conditions['User.last_action >'] = date('Y-m-d H:i:s', strtotime('-5 minutes'));
				$type= 'admin_index';
			break;

			case 'rtoday':
				$conditions['User.created >='] = date('Y-m-d H:i:s', strtotime('12:01 am'));
				$type= 'admin_index';
			break;

			case 'agency':
				$conditions['User.agency_id'] = $id;
			break;
		}

		$order = array(
			'User.created'
		);

		if (isset($_GET['iDisplayStart']) && $_GET['iDisplayLength'] != '-1')
		{
			$limit = $_GET['iDisplayLength'];
			$offset = $_GET['iDisplayStart'];
		}
		else
		{
			$limit = 10;
			$offset = 0;
		}

		if (isset($_GET['iSortCol_0']))
		{
			switch ($type)
			{
				case 'sindex':
					switch($_GET['iSortCol_0'])
					{
						case 0: break;
						case 1: $order = array('User.last_name'=>$_GET['sSortDir_0']); break;
						case 2: $order = array('Agency.name'=>$_GET['sSortDir_0']); break;
						case 3: $order = array('Tier.id'=>$_GET['sSortDir_0']); break;
					}
				break;
			}

		}


		if (!empty($_GET['sSearch']))
		{
			$or = array();
			$or[] = array('User.first_name LIKE'=>$_GET['sSearch'].'%');
			$or[] = array('User.last_name LIKE'=>$_GET['sSearch'].'%');
			$or[] = array('CONCAT(User.first_name, " ", User.last_name) LIKE'=>'%'.$_GET['sSearch'].'%');
			$or[] = array('User.email LIKE'=>$_GET['sSearch'].'%');
			$or[] = array('Agency.name LIKE'=>$_GET['sSearch'].'%');

			$or[] = array('Tier.name LIKE'=>$_GET['sSearch'].'%');
			$or[] = array('Tier.short'=>$_GET['sSearch']);
			$conditions[] = array('or'=>$or);
		}


		$found = $this->Instructor->find('count', array(
			'conditions'=>$conditions,
			'joins'=>$joins
		));

		$courses = $this->Instructor->find('all', array(
			'conditions'=>$conditions,
			'order'=>$order,
			'limit'=>$limit,
			'offset'=>$offset,
			'joins'=>$joins
		));

		//echo "/* ".print_r($order, true).' */';

		$this->set('found', $found);
		$this->set('users', $courses);
		$this->render('Instructors/tables'.DS.$type);
	}

}
