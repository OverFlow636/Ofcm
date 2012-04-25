<?php
App::uses('OfcmAppController', 'Ofcm.Controller');
/**
 * Instructors Controller
 *
 */
class InstructorsController extends OfcmAppController
{

/**
 * Scaffold
 *
 * @var mixed
 */
	public $scaffold;


	public function test()
	{
		die(pr($this->Instructor->read(null, 199)));
	}

}
