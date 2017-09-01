<?php
require __DIR__ . '/vendor/autoload.php';

use \Curl\Curl;


     $curl = new Curl();
     $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
     
    // ---------------My server:----------------------------------
    
      $server_address = "no1010042208156.corp.adobe.com";
    
    //-----------------------------------------------
      
      
    // ---------------B&D snad box----------------------------------
      
     // $server_address = "http://10.255.128.66:8080";
    
    //-----------------------------------------------
      
      
     $request_url_path = "/nl/jsp/soaprouter.jsp";
     $server_address = $server_address.$request_url_path;
    
     

     
    function request_session_logon($username, $password) {
     global $curl;
     global $server_address;
     
     
 $request_body = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:xtk:session">
						   <soapenv:Header/>
						   <soapenv:Body>
						      <urn:Logon>
						         <urn:sessiontoken></urn:sessiontoken>
						         <urn:strLogin>' . $username . '</urn:strLogin>
						         <urn:strPassword>' . $password . '</urn:strPassword>
						         <urn:elemParameters>
						            <!--You may enter ANY elements at this point-->
						         </urn:elemParameters>
						      </urn:Logon>
						   </soapenv:Body>
						</soapenv:Envelope>';
		
		$curl->setHeader('SOAPAction', 'xtk:session#Logon');
		$curl->post($server_address, $request_body);
               
                if ($curl->error) {
                 echo 'Error: ' . $curl->errorCode . ': ' . $curl->errorMessage . "\n";
                } else {
                        $response = xmlToArray($curl->response);
                             
                   	return $response["Envelope"]["SOAP-ENV:Body"]["ns:LogonResponse"]["ns:pstrSessionToken"]["$"];          
                }

	}
        
        
        
    function request_workflows($session_token, $campaign_id) {
        
         global $curl;
        global $server_address;
        
        $campaignExp = "[@operation-id]=".$campaign_id;
        $request_body = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:xtk:queryDef">
   <soapenv:Header/>
   <soapenv:Body><urn:ExecuteQuery>
   <urn:sessiontoken>'.$session_token.'</urn:sessiontoken>
	    <urn:entity>
		<queryDef schema="xtk:workflow" operation="select" >
		     <select>
		          <node expr="@id"  />
		          <node expr="@label"  />
		          <node expr="@state"  />
		      </select>
            <where>
   				<condition boolOperator="AND" expr="'.$campaignExp.'"/>
   				<condition boolOperator="AND" expr="@failed = 1" />
          </where>
    </queryDef>
  </urn:entity>
 </urn:ExecuteQuery>
   </soapenv:Body>
</soapenv:Envelope>';

		$curl->setHeader('SOAPAction', 'xtk:queryDef#ExecuteQuery');
		$curl->post($server_address, $request_body);
               
                if ($curl->error) {
                 echo 'Error: ' . $curl->errorCode . ': ' . $curl->errorMessage . "\n";
                } else {
                        $response = xmlToArray($curl->response);
                               
                        return $response["Envelope"]["SOAP-ENV:Body"]["ns:ExecuteQueryResponse"]["pdomOutput"]["ns:workflow-collection"];
                           
                }
	}  
        
        
        
    function request_delivery($session_token, $campaign_id) {
        
         global $curl;
        global $server_address;
        
        $campaignExp = "[@operation-id]=".$campaign_id;
        $request_body = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:xtk:queryDef">
   <soapenv:Header/>
   <soapenv:Body><urn:ExecuteQuery>
   <urn:sessiontoken>'.$session_token.'</urn:sessiontoken>
	    <urn:entity>
		<queryDef schema="nms:delivery" operation="select" >
                    <select>
                         <node expr="@id"  />
                         <node expr="@label"  />
                         <node expr="[indicators/@successRatio]"  />    
                    </select>
                   <where >
    <condition boolOperator="AND" expr="'.$campaignExp.'"/>
    <condition expr=" 0.95 > [indicators/@successRatio] "/>
  </where>
  </queryDef>
  </urn:entity>
 </urn:ExecuteQuery>
   </soapenv:Body>
</soapenv:Envelope>';

		$curl->setHeader('SOAPAction', 'xtk:queryDef#ExecuteQuery');
		$curl->post($server_address, $request_body);
               
                if ($curl->error) {
                 echo 'Error: ' . $curl->errorCode . ': ' . $curl->errorMessage . "\n";
                } else {
                        $response = xmlToArray($curl->response);
                              
            return $response["Envelope"]["SOAP-ENV:Body"]["ns:ExecuteQueryResponse"]["pdomOutput"]["ns:delivery-collection"];
                }
	}
        
        
        
    function xmlToArray($xml, $options = array(), $tab = "\t") {
	    $defaults = array(
	        'namespaceSeparator' => ':',//you may want this to be something other than a colon
	        'attributePrefix' => '@',   //to distinguish between attributes and nodes with the same name
	        'alwaysArray' => array(),   //array of xml tag names which should always become arrays
	        'autoArray' => true,        //only create arrays for tags which appear more than once
	        'textContent' => '$',       //key used for the text content of elements
	        'autoText' => true,         //skip textContent key if node has no attributes or child nodes
	        'keySearch' => false,       //optional search and replace on tag and attribute names
	        'keyReplace' => false       //replace values for above search values (as passed to str_replace())
	    );
	    $options = array_merge($defaults, $options);
	    $namespaces = $xml->getDocNamespaces();
	    $namespaces[''] = null; //add base (empty) namespace
	 
	    //get attributes from all namespaces
	    $attributesArray = array();
	    foreach ($namespaces as $prefix => $namespace) {
	        foreach ($xml->attributes($namespace) as $attributeName => $attribute) {
	            //replace characters in attribute name
	            if ($options['keySearch']) $attributeName =
	                    str_replace($options['keySearch'], $options['keyReplace'], $attributeName);
	            $attributeKey = $options['attributePrefix']
	                    . ($prefix ? $prefix . $options['namespaceSeparator'] : '')
	                    . $attributeName;
	            $attributesArray[$attributeKey] = (string)$attribute;
	        }
	    }
	 
	    //get child nodes from all namespaces
	    $tagsArray = array();
	    foreach ($namespaces as $prefix => $namespace) {
	        foreach ($xml->children($namespace) as $childXml) {

	            //recurse into child nodes
	            $childArray = xmlToArray($childXml, $options, $tab."\t");
	            list($childTagName, $childProperties) = each($childArray);
	 
	            //replace characters in tag name
	            if ($options['keySearch']) $childTagName =
	                    str_replace($options['keySearch'], $options['keyReplace'], $childTagName);
	            //add namespace prefix, if any
	            if ($prefix) $childTagName = $prefix . $options['namespaceSeparator'] . $childTagName;
	 
	            if (!isset($tagsArray[$childTagName])) {
	                //only entry with this key
	                //test if tags of this type should always be arrays, no matter the element count
	                $tagsArray[$childTagName] =
	                        in_array($childTagName, $options['alwaysArray']) || !$options['autoArray']
	                        ? array($childProperties) : $childProperties;
	            } elseif (
	                is_array($tagsArray[$childTagName]) && array_keys($tagsArray[$childTagName])
	                === range(0, count($tagsArray[$childTagName]) - 1)
	            ) {
	                //key already exists and is integer indexed array
	                $tagsArray[$childTagName][] = $childProperties;
	            } else {
	                //key exists so convert to integer indexed array with previous value in position 0
	                $tagsArray[$childTagName] = array($tagsArray[$childTagName], $childProperties);
	            }
	        }
	    }
	 
	    //get text content of node
	    $textContentArray = array();
	    $plainText = trim((string)$xml);
	    if ($plainText !== '') $textContentArray[$options['textContent']] = $plainText;
	 
	    //stick it all together
	    $propertiesArray = !$options['autoText'] || $attributesArray || $tagsArray || ($plainText === '')
	            ? array_merge($attributesArray, $tagsArray, $textContentArray) : $plainText;
	 
	    //return node as array
	    return array(
	        $xml->getName() => $propertiesArray
	    );
	}

    
    function campaign_monitoring($session_token, $campaign_id){
        
         
        $workflowCollection = request_workflows($session_token,$campaign_id);
        
        //print_r($workflowCollection);
        
        if(sizeof($workflowCollection)>0){
               
              $responseArray = array( 
                                         "status" => "Not Ok", 
                                         "error" => "workflow", 
                                         "workflows" =>$workflowCollection["workflow"] 
                                    );
              
                             return $responseArray;
                
        }
        else{
            
             $deliveryCollection = request_delivery($session_token,$campaign_id);
            
            if(sizeof($deliveryCollection)>0){
                
                
              $responseArray = array( 
                                         "status" => "Not Ok", 
                                         "error" => "delivery", 
                                         "deliveries" =>$deliveryCollection["delivery"] 
                                    );
              
                             return $responseArray;
                
            }
                
        else {
            
            
              $responseArray = array( 
                                         "status" => "Ok"
                                    );
              
                             return $responseArray;
            
        };
        }
               
        
       
        
    }    
        
    function processCampaignMonitoring($session_token,$campaigns){
        $response = array();
        
        for($i=0; $i<sizeof($campaigns);$i++)
        {
            
            $response[$campaigns[$i]] = campaign_monitoring($session_token,$campaigns[$i]);
        }
        
        echo json_encode($response);
                
        
    }
    // ---------------My server:----------------------------------
   // echo request_session_logon("internal", "pparmar");
     //campaign_monitoring("___7033BFE7-917E-46D8-9AF7-D72A2FDC46AC","636131");
    //---------------------------------------------------------------
    
    
    // ----------------B&D sandbox------------------------
     // echo request_session_logon("parvesh", "parvesh");
    //  campaign_monitoring("___fa9a561a-3426-468f-86e1-385b0930d63c","20250464");
    //----------------------------------------------------
   
    
    
?>