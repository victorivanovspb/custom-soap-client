<?php
// project: Custom Soap Client
// file:    request-data-structures.php
//
// author:  Victor Ivanov <victorivanov.spb@gmail.com>
// created: 2016-10-30
// updated: 2016-10-31 (last)
//
// Представлены структуры данных, используемых классом CustomSoapClient.
// ----------------------------------------------------------------------------
define("PARAMETERS_VALUE_TYPE_TXT", 1);
define("PARAMETERS_VALUE_TYPE_XML", 2);
// ----------------------------------------------------------------------------

class RequestData {
	public $requestInfo;
	public $methodName;
	public $parameters;

	public function __construct($methodName = null, $requestInfo = null, $parameters = null) {
		if ($methodName == null) {
			$methodName = "";
		} else if (gettype($methodName) != "string") {
			throw new Exception("class RequestData: constructor: argument $methodName is not string.");
		}
		if ($requestInfo == null) {
			$requestInfo = new RequestInfo();
		} else if ($requestInfo != null && gettype($requestInfo) != "object" && get_class($requestInfo) != "RequestInfo") {
			throw new Exception("class RequestData: constructor: argument $requestInfo is not instance of the class RequestInfo.");
		}
		if ($parameters == null) {
			$parameters = new Parameters();
		} else if ($parameters != null && gettype($parameters) != "object" && get_class($parameters) != "Parameters") {
			throw new Exception("class RequestData: constructor: argument $parameters is not instance of the class Parameters.");
		}

		$this->methodName  = $methodName;
		$this->requestInfo = $requestInfo;
		$this->parameters  = $parameters;
	}
}
// End of class RequestData ---------------------------------------------------
// ----------------------------------------------------------------------------
class RequestInfo {
	public $clientType;
	public $company;
	public $language;
	public $profileId;

	public function __construct($clientType = null, $company = null, $language = null, $profileId = null) {
		$this->clientType 	= ($clientType 	!= null) ? $clientType 	: "TestClient";
		$this->company 		= ($company 	!= null) ? $company 	: "CS";
		$this->language 	= ($language 	!= null) ? $language 	: "RU";
		$this->profileId 	= ($profileId 	!= null) ? $profileId 	: "profile1";
	}

	public function getXML($dom) {
		$xml = $dom->createElement("requestInfo");
    	
    	$clientType = $dom->createElement("ClientType");
    	$clientType->nodeValue = $this->clientType;
    	$xml->appendChild($clientType);

    	$company = $dom->createElement("Company");
    	$company->nodeValue = $this->company;
    	$xml->appendChild($company);

    	$language = $dom->createElement("Language");
    	$language->nodeValue = $this->language;
    	$xml->appendChild($language);

    	$profileId = $dom->createElement("ProfileId");
    	$profileId->nodeValue = $this->profileId;
    	$xml->appendChild($profileId);

    	return $xml;
	}
}
// End of class RequestInfo ---------------------------------------------------
// ----------------------------------------------------------------------------
class Parameters extends ArrayObject {

	public function __construct() {
		return parent::__construct();
	}

	public function append($value) {
		if (gettype($value) == "array") {
			return $this->appendShops($value);
		}
		return parent::append( new ParamEntry($value, PARAMETERS_VALUE_TYPE_TXT) );
	}

	public function appendShops($shops) {
		if (gettype($shops) != "array") {
			throw new Exception("class Parameters: appendShops: argument $shops is not array.");
		}
		foreach ($shops as $num) {
			if (gettype($num) != "integer") {
				throw new Exception("class Parameters: appendShops: argument $shops is not array OF INTEGERS.");
			}
		}

		// Пример: <Shops><Shop number="123" /></Shops>
		$dom = new DOMDocument('1.0', 'utf-8');
		$xmlShops = $dom->createElement("Shops");
    	foreach ($shops as $num) {
			$curShop = $dom->createElement("Shop");
			$curShop->setAttribute("number", $num);
			$xmlShops->appendChild($curShop);
		}
		$dom->appendChild($xmlShops);

		// Удаляется XML-деларация из DOM-объекта.
		$theXMLSource = $dom->saveXml();
        $pattern = '^<\?xml version\=\"1\.0\" encoding\=\"utf-8\"\?>\n<^';
        $output = preg_replace($pattern, "<", $theXMLSource);

		return parent::append( new ParamEntry($output, PARAMETERS_VALUE_TYPE_XML) );
	}

	public function getXML($dom) {
		//	Пример:
		//	<parameters xmlns:a="http://schemas.microsoft.com/2003/10/Serialization/Arrays" xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
		//		<a:anyType i:type="b:string" xmlns:b="http://www.w3.org/2001/XMLSchema">
		//			<![CDATA[<Shops><Shop number="123" /></Shops>]]>
		//		</a:anyType>
		//	     <a:anyType i:type="b:string" xmlns:b="http://www.w3.org/2001/XMLSchema">
		//			00000107597
		//		</a:anyType>
		//	</parameters>
		$xml = $dom->createElement("parameters");
		$xml->setAttribute("xmlns:a", "http://schemas.microsoft.com/2003/10/Serialization/Arrays");
		$xml->setAttribute("xmlns:i", "http://www.w3.org/2001/XMLSchema-instance");

		$iterator = $this->getIterator();
		while ($iterator->valid()) {
			$el = $dom->createElement("a:anyType");
			$el->setAttribute("i:type", "b:string");
			$el->setAttribute("xmlns:b", "http://www.w3.org/2001/XMLSchema");

			switch ($iterator->current()->getEntryType()) {
				case PARAMETERS_VALUE_TYPE_TXT:
					$el->nodeValue = $iterator->current()->getEntryValue();
					break;

				case PARAMETERS_VALUE_TYPE_XML:
					$f = $dom->createDocumentFragment();
					$f->appendXML($iterator->current()->getEntryValueWithCDATA());
					$el->appendChild($f);
					break;
			}
			$xml->appendChild($el);
			$iterator->next();
		}
		return $xml;
	}
}
// End of class Parameters ----------------------------------------------------
// ----------------------------------------------------------------------------
class ParamEntry {
	public $type;
	public $value;

	public function __construct($value, $type = null) {
		$this->type = ($type != null) ? $type : PARAMETERS_VALUE_TYPE_TXT;
		$this->value = $value;
	}

	public function getEntryType() {
		return $this->type;
	}

	public function getEntryValue() {
		return $this->value;
	}

	public function getEntryValueWithCDATA() {
		return "<![CDATA[".$this->value."]]>";
	}
}
// End of class ParamEntry ----------------------------------------------------
?>