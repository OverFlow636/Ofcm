<?php

App::uses('CakeEventListener', 'Event');

class OfcmListener implements CakeEventListener
{
	public function implementedEvents()
	{
		return array(
			'Plugin.Ofcm.catalog_beforeRead'	=> 'catalogBeforeRead'
		);
	}

	public function catalogBeforeRead($event)
	{
		

	}
}