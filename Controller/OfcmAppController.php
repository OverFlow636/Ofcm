<?php

App::uses('CakeEventManager', 'Event');
App::uses('Folder', 'Utility');
App::uses('AppController', 'Controller');
class OfcmAppController extends AppController
{

    public function beforeFilter()
	{
        Configure::load('Ofcm.ofcm');

		$path = APP.'Plugin'.DS.'Ofcm'.DS.'Lib'.DS .'Event';
		if (file_exists($path))
		{
			$cacheFile = $path . DS . 'cache';
			if (file_exists($cacheFile) && Configure::read('debug') == 0)
				$listeners = unserialize(file_get_contents($cacheFile));
			else
			{
				$cf = new Folder($path);
				$listeners = $cf->findRecursive('.*\.php');
				file_put_contents($cacheFile, serialize($listeners));
			}

			$menu = array();
			foreach($listeners as $listenerFile)
			{
				$class = substr(basename($listenerFile),0,-4);
				require_once($listenerFile);
				$listener = new $class;

				$this->getEventManager()->attach($listener);
			}
		}

		parent::beforeFilter();
    }

	protected function fire($event)
	{
		$this->getEventManager()->dispatch(new CakeEvent($event, $this));
	}
}

