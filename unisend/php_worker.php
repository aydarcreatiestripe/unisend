﻿<?php

require_once('main_cfg.php');
require_once('query.class.php');
require_once('uniapi_source.php');

foreach ($connectors as $connectorname) {
	require_once('connectors'.DS.$connectorname.'.php');
	${$connectorname.'object'} = new $connectorname();
}


$leads = (new Query())->select('unisend_leads')->order_by('id','ASC')->execute();
foreach ($leads as $lead) {
	$leads = (new Query())->select('unisend_leads')
		->where(array('phone'=>$lead['phone'],'id<'=>$lead['id']))
		->additional_where('`timestamp` >= DATE_SUB(NOW(),INTERVAL 1 HOUR)')->execute();
	if(!empty($leads)){
		(new Query())->delete('unisend_leads')->where(array('id'=>$lead['id']))->execute();
	}
}

$leads = (new Query())->select('unisend_leads')->where(array('status'=>0))->execute();
foreach ($leads as $lead) {
	foreach ($connectors as $connectorname) {
		if((${$connectorname.'object'}->send($lead))===true){
			(new Query())->update('unisend_leads',array('status'=>1))->where(array('id'=>$lead['id']))->execute();
		}
	}
}

?>