<?php
// project: Custom Soap Client
// file:    main.php
//
// author:  Victor Ivanov <victorivanov.spb@gmail.com>
// created: 2016-10-29
// updated: 2016-10-31 (last)
// ----------------------------------------------------------------------------
require_once('custom-soap-client.php');

try {
	$wsdl = "http://dax-srv-2.sth.local:8088/CDXRealtimeService/Service.svc?wsdl";
	$client = new CustomSoapClient($wsdl);

	$response = $client->isAlive();
	print_r( get_object_vars($response)["IsAliveResult"] );

	$requestData = new RequestData();
	$requestData->methodName = "inventoryLookupByStores";
	$shops = array(123, 222, 101); // NB: <Shops><Shop number="123" />...</Shops>
	$requestData->parameters->append($shops);
	$requestData->parameters->append("00000107597");

	$response = $client->inventoryLookupByStores($requestData);
	print_r( get_object_vars($response)["InvokeExtensionMethodResult"] );

} catch (Exception $e) {
	print($e->getMessage());
}
?>