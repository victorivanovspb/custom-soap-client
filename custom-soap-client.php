<?php
// project: Custom Soap Client
// file:    custom-soap-client.php
//
// author:  Victor Ivanov <victorivanov.spb@gmail.com>
// created: 2016-10-29
// updated: 2016-10-31 (last)
//
// Класс CustomSoapClient позволяет выполнять SOAP-запросы.
// Вызов SOAP-метода "inventoryLookupByStores" выполняется с небольшим хаком:
// после прочтения WSDL-файла родительским классом SoapClient в функции __doRequest()
// редактируется отправляемый запрос.
// ----------------------------------------------------------------------------
require_once('request-data-structures.php');
// ----------------------------------------------------------------------------
define("CSC_CURRENT_UNDEFINED", 					0); // NB: CSC = Custom Soap Client
define("CSC_CURRENT_ISALIVE",   					1);
define("CSC_CURRENT_INVOKEEXT_INVENTORYLOOKUP",  	2);
// ----------------------------------------------------------------------------
class CustomSoapClient extends SoapClient {

	private $current;
	private $rData;

	public function __construct($wsdl, $options = null) {
		if ($options == null) {
			$options = array(
				"features"		=> SOAP_SINGLE_ELEMENT_ARRAYS,
	    		"exceptions" 	=> true,
	    		"trace" 		=> 0,
	    		"soap_version" 	=> SOAP_1_1,
			);
		}
        parent::__construct($wsdl, $options);
       	$this->current = CSC_CURRENT_UNDEFINED;
      	$this->rData = null;
      	$this->lastReq = "";
    }

    public function isAlive() {
   		$this->current = CSC_CURRENT_ISALIVE;
		return parent::__soapCall("isAlive", array());
    }

    public function inventoryLookupByStores($requestData) {
    	if (gettype($requestData) != "object" && get_class($requestData) != "RequestData") {
    		//throw new Exception("class CustomSoapClient: inventoryLookupByStores: argument $requestData is not instance of the class RequestData.");
    		return null;
    	}
    	$this->current = CSC_CURRENT_INVOKEEXT_INVENTORYLOOKUP;
    	$this->rData = $requestData;
    	return parent::__soapCall("InvokeExtensionMethod", array());
    }

    public function __doRequest($request, $location, $action, $version) {
    	if ($this->current == CSC_CURRENT_INVOKEEXT_INVENTORYLOOKUP) {
    		// Пример:
			//	<?xml version="1.0" encoding="utf-8"...
			//	<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
			//		<soap:Body>
			//		    <InvokeExtensionMethod xmlns="http://schemas.microsoft.com/dynamics/2012/05/CommerceRuntime/TransactionService">
			//				<requestInfo>...</requestInfo>
			//			    <methodName>inventoryLookupByStores</methodName>
			//				<parameters xmlns:a="http://schemas.microsoft.com/2003/10/Serialization/Arrays" xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
			//			    	<a:anyType i:type="b:string" xmlns:b="http://www.w3.org/2001/XMLSchema"><![CDATA[<Shops><Shop number="123" /></Shops>]]></a:anyType>
			//			        <a:anyType i:type="b:string" xmlns:b="http://www.w3.org/2001/XMLSchema">00000107597</a:anyType>
			//			    </parameters>
			//			</InvokeExtensionMethod>
			//		</soap:Body>
			//	</soap:Envelope>

    		$dom = new DOMDocument('1.0', 'utf-8');
			$xmlSoapEnv = $dom->createElement("soap:Envelope");
			$xmlSoapEnv->setAttribute("xmlns:soap", "http://schemas.xmlsoap.org/soap/envelope/");
			$xmlSoapEnv->setAttribute("xmlns:xsi",  "http://www.w3.org/2001/XMLSchema-instance");
			$xmlSoapEnv->setAttribute("xmlns:xsd",  "http://www.w3.org/2001/XMLSchema");

			$xmlSoapBody = $dom->createElement("soap:Body");
			$xmlSoapEnv->appendChild($xmlSoapBody);

			$xmlInvoke = $dom->createElement("InvokeExtensionMethod");
			$xmlInvoke->setAttribute("xmlns", "http://schemas.microsoft.com/dynamics/2012/05/CommerceRuntime/TransactionService");
			$xmlSoapBody->appendChild($xmlInvoke);

			$xmlRequestInfo = $this->rData->requestInfo->getXML($dom);
			$xmlInvoke->appendChild($xmlRequestInfo);

			$xmlMethodName = $dom->createElement("methodName");
			$xmlMethodName->nodeValue = $this->rData->methodName;
			$xmlInvoke->appendChild($xmlMethodName);

			$xmlParameters = $this->rData->parameters->getXML($dom);
			$xmlInvoke->appendChild($xmlParameters);

			$dom->appendChild($xmlSoapEnv);
			$request = $dom->saveXml();
    	}
		return parent::__doRequest($request, $location, $action, $version);
    }
	
    public function __soapCall($function_name, $arguments, $options = null, $input_headers = null, $output_headers = null) {
    	$this->current = CSC_CURRENT_UNDEFINED;
    	if (gettype($function_name) == "string") {
    		$this->current = $function_name;
    	}
    	return parent::__soapCall($function_name, $arguments, $options, $input_headers, $output_headers);
    }
}
// End of class CustomSoapClient ----------------------------------------------
?>