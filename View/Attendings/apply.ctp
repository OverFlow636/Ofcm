<?php

$content = '<pre>'.print_r($course, true).'</pre>';


echo $this->element('full-width-horizontal-nav', array(
	'title'=>'Applying for: <font color=orange>' . $course['CourseType']['shortname'].'</font> on <font color=orange>'.date('m-d-y', strtotime($course['Course']['startdate'])).'</font>',
	'content'=>$content
));