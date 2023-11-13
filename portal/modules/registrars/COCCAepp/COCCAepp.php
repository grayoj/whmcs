<?php
# Configuration array
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Domains\DomainLookup\ResultsList;
use WHMCS\Domains\DomainLookup\SearchResult;
use WHMCS\Module\Registrar\COCCAepp\ApiClient;

// Require any libraries needed for the module to function.
// require_once __DIR__ . '/path/to/library/loader.php';
//
// Also, perform any initialization required by the service's library.

function COCCAepp_MetaData()
{
    return array(
        'DisplayName' => 'CoCCA Registrar Module for WHMCS',
        'APIVersion' => '1.1',
    );
}


function COCCAepp_getConfigArray() {
	$configarray = array(
		"Username" => array( "Type" => "text", "Size" => "20", "Description" => "Enter your EPP username here" ),
		"Password" => array( "Type" => "password", "Size" => "20", "Description" => "Enter your EPP password here" ),
		"Server" => array( "Type" => "text", "Size" => "20", "Description" => "Enter EPP Server Address" ),
		"Port" => array( "Type" => "text", "Size" => "20", "Description" => "Enter EPP Server Port" ),
		"SSL" => array( "Type" => "yesno",'Description' => "Tick to enable" ),
		"Certificate" => array( "Type" => "text", "Description" => "(Optional) Path of certificate .pem" )
	);
	return $configarray;
}

function COCCAepp_AdminCustomButtonArray() {
	$buttonarray = array(
		"Approve Transfer" => "ApproveTransfer",
		"Cancel Transfer Request" => "CancelTransferRequest",
		"Reject Transfer" => "RejectTransfer",	
                "Update Additional fields"  => "UpdateFieldsAdditional", 	
	);
	return $buttonarray;
}

function COCCAepp_ClientAreaCustomButtonArray() {
	//$buttonarray = array(
	//"Lock Domain" => "LockDomain",
	//"Unlock Domain" => "UnlockDomain",
	//);
	return $buttonarray;
}

# Function to return current nameservers
function COCCAepp_GetNameservers($params) {
	# Grab variables
	$sld = $params["sld"];
	$tld = $params["tld"];
	$domain = "$sld.$tld";

	# Get client instance
	try {
		$client = _COCCAepp_Client();

		# Get list of nameservers for domain
		$result = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
   <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
     <command>
       <info>
         <domain:info
          xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
           <domain:name hosts="all">'.$domain.'</domain:name>
         </domain:info>
       </info>
       <clTRID>'.mt_rand().mt_rand().'</clTRID>
     </command>
   </epp>
');

		# Parse XML result
		$doc = new DOMDocument();
		$doc->loadXML($result);
		logModuleCall('COCCAepp', 'GetNameservers', $xml, $result);

		# Pull off status
		$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
		$msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
		# Check the result is ok
		if(!eppSuccess($coderes)) {
			$values["error"] = "GetNameservers/domain-info($domain): Code ($coderes) $msg";
			return $values;
		}

		# Grab hostObj array
        $ns = $doc->getElementsByTagName('hostObj');
        # Extract nameservers & build return result
        $i = 1;	$values = array();
        foreach ($ns as $nn) {
            $values["ns{$i}"] = $nn->nodeValue;
            $i++;
        }

        $values["status"] = $msg;

        return $values;

	} catch (Exception $e) {
		$values["error"] = 'GetNameservers/EPP: '.$e->getMessage();
		return $values;
	}

	return $values;
}



# Function to save set of nameservers
function COCCAepp_SaveNameservers($params) {
	# Grab variables
	$sld = $params["sld"];
	$tld = $params["tld"];
	$domain = "$sld.$tld";

    # Generate array of new nameservers
    $nameservers=array();
    if (!empty($params["ns1"]))
        array_push($nameservers,$params["ns1"]);
    if (!empty($params["ns2"]))
        array_push($nameservers,$params["ns2"]);
    if(!empty($params["ns3"]))
        array_push($nameservers,$params["ns3"]);
    if(!empty($params["ns4"])) 
        array_push($nameservers,$params["ns4"]);
    if(!empty($params["ns5"])) 
        array_push($nameservers,$params["ns5"]);

	# Get client instance
	try {
		$client = _COCCAepp_Client();

		for($i=0; $i < count($nameservers); $i++) {
            # Get list of nameservers for domain
        	$result = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
   <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
     <command>
       <info>
         <host:info
          xmlns:host="urn:ietf:params:xml:ns:host-1.0">
           <host:name>'.$nameservers[$i].'</host:name>
         </host:info>
       </info>
       <clTRID>'.mt_rand().mt_rand().'</clTRID>
     </command>
   </epp>');
            # Parse XML result
            $doc = new DOMDocument();
            $doc->preserveWhiteSpace = false;
            $doc->loadXML($result);
            logModuleCall('COCCAepp', 'GetNameservers', $xml, $result);

            # Pull off status
            $coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
            $msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
            # Check if the nameserver exists in the registry...if not, add it
            if($coderes == '2303') {
                $request = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
   <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
     <command>
       <create>
         <host:create
          xmlns:host="urn:ietf:params:xml:ns:host-1.0">
           <host:name>'.$nameservers[$i].'</host:name>        
         </host:create>
       </create>
       <clTRID>'.mt_rand().mt_rand().'</clTRID>
     </command>
   </epp>
');

                # Parse XML result
                $doc= new DOMDocument();
                $doc->loadXML($request);
                logModuleCall('COCCAepp', 'SaveNameservers', $xml, $request);

                # Pull off status
                $coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
                $msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
                # Check if result is ok
                if(!eppSuccess($coderes)) {
                    $values["error"] = "Could not Create host($nameservers[$i]): Code ($coderes) $msg";
                    return $values;
                }
            }
        }
        # Generate XML for nameservers to add
        if ($nameserver1 = $params["ns1"]) {
		    $add_hosts = '
	<domain:hostObj>'.$nameserver1.'</domain:hostObj>
';
        }
        if ($nameserver2 = $params["ns2"]) {
            $add_hosts .= '
	<domain:hostObj>'.$nameserver2.'</domain:hostObj>
';
        }
        if ($nameserver3 = $params["ns3"]) {
            $add_hosts .= '
	<domain:hostObj>'.$nameserver3.'</domain:hostObj>
';
        }
        if ($nameserver4 = $params["ns4"]) {
            $add_hosts .= '
	<domain:hostObj>'.$nameserver4.'</domain:hostObj>
';
        }
        if ($nameserver5 = $params["ns5"]) {
            $add_hosts .= '
	<domain:hostObj>'.$nameserver5.'</domain:hostObj>
';
        }

        # Grab list of current nameservers
        $request = $client->request($xml='<?xml version="1.0" encoding="UTF-8" standalone="no"?>
   <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
     <command>
       <info>
         <domain:info
          xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
           <domain:name hosts="all">'.$domain.'</domain:name>
         </domain:info>
       </info>
       <clTRID>'.mt_rand().mt_rand().'</clTRID>
     </command>
   </epp>
');

        # Parse XML result
        $doc= new DOMDocument();
        $doc->loadXML($request);
        # Pull off status
        $coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
        $msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
        # Check if result is ok
        if(!eppSuccess($coderes)) {
            $values["error"] = "SaveNameservers/domain-info($sld.$tld): Code ($coderes) $msg";
            return $values;
        }

        $values["status"] = $msg;

        # Generate list of nameservers to remove
        $hostlist = $doc->getElementsByTagName('hostObj');
        $rem_hosts = '';
        foreach ($hostlist as $host) {
            $rem_hosts .= '
	<domain:hostObj>'.$host->nodeValue.'</domain:hostObj>
';
    	}

        # Build request
	    $request = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
   <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
     <command>
       <update>
         <domain:update
          xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
				<domain:name>'.$sld.'.'.$tld.'</domain:name>
				<domain:add>
					<domain:ns>'.$add_hosts.' </domain:ns>
				</domain:add>								  
				<domain:rem>
					<domain:ns>'.$rem_hosts.'</domain:ns>
				</domain:rem>
			</domain:update>
		</update>
		<clTRID>'.mt_rand().mt_rand().'</clTRID>
	</command>
</epp>
');

        # Parse XML result
        $doc= new DOMDocument();
        $doc->loadXML($request);
        logModuleCall('COCCAepp', 'SaveNameservers', $xml, $request);

        # Pull off status
        $coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
        $msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
        # Check if result is ok
        if(!eppSuccess($coderes)) {
            $values["error"] = "SaveNameservers/domain-update($sld.$tld): Code ($coderes) $msg";
            return $values;
        }

        $values['status'] = "Domain update Successful";

	} catch (Exception $e) {
		$values["error"] = 'SaveNameservers/EPP: '.$e->getMessage();
		return $values;
	}

	return $values;
}


function COCCAepp_GetRegistrarLock($params) {

		# Grab variables
		$sld = $params["sld"];
		$tld = $params["tld"];
		$domain = "$sld.$tld";
	        // what is the current domain status?
		# Grab list of current nameservers
		try {
			 $client = _COCCAepp_Client();
			 $request = $client->request($xml='<?xml version="1.0" encoding="UTF-8" standalone="no"?>
				   <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
						 <command>
						   <info>
								 <domain:info
								  xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
								   <domain:name hosts="all">'.$domain.'</domain:name>
								 </domain:info>
						   </info>
						   <clTRID>'.mt_rand().mt_rand().'</clTRID>
						 </command>
				   </epp>');

		# Parse XML result
		$doc= new DOMDocument();
		$doc->loadXML($request);
		logModuleCall('COCCAepp', 'GetRegistrarLock', $xml, $request);
		$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
		$msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
		# Check result
		if(!eppSuccess($coderes)) {
				$lockstatus = "GetRegistrarLock for Domain:($sld.$tld): Code (".$coderes.") ".$msg;
				return  $lockstatus;
					 }
		$statusarray = $doc->getElementsByTagName("status");
								$currentstatus = array();
								foreach ($statusarray as $nn) {
										$currentstatus[] = $nn->getAttribute("s");
								}
						}
						catch (Exception $e) {
								$values["error"] = $e->getMessage();
								return $values;
						}

		# Get lock status
	if (array_key_exists(array_search("clientDeleteProhibited", $currentstatus), $currentstatus) == 1 || array_key_exists(array_search("clientTransferProhibited", $currentstatus), $currentstatus) == 1 ) {
								$lockstatus = "locked";
						}
						else {
								$lockstatus = "unlocked";
						}
						return $lockstatus;
				}

function COCCAepp_SaveRegistrarLock($params) {
		# Grab variables
		$sld = $params["sld"];
		$tld = $params["tld"];
		$domain = "$sld.$tld";

		if (COCCAepp_GetRegistrarLock($params) == "unlocked" && $params["lockenabled"] == "locked") {
			COCCAepp_LockDomain($params);
		}else
		 {
		  if (COCCAepp_GetRegistrarLock($params) == "locked"  && $params["lockenabled"] == "unlocked") {
				   COCCAepp_UnlockDomain($params);

		  } else
			  {

				$values["error"] = "SaveRegistrar LOCK: Domain Status unknown ";
				return $values;


			  }
		 }
       }
function COCCAepp_LockDomain($params) {
        $sld = $params["sld"];
        $tld = $params["tld"];
        $domain = "$sld.$tld";

try {
                if (!isset($client)) {
                        $client = _COCCAepp_Client();
                }

                # Lock Domain
                //First lock the less restrictive locks
                $request = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <command>
    <update>
      <domain:update xmlns:domain="urn:ietf:params:xml:ns:domain-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
        <domain:name>'.$sld.'.'.$tld.'</domain:name>
        <domain:add>
          <domain:status s="clientDeleteProhibited"/>
          <domain:status s="clientTransferProhibited"/>
        </domain:add>
      </domain:update>
    </update>
    <clTRID>'.mt_rand().mt_rand().'</clTRID>
  </command>
</epp>
');
# Parse XML result
        $doc= new DOMDocument();
        $doc->loadXML($request);
        logModuleCall('COCCAepp', 'Lock-Delete-Transfer', $xml, $request);
        $coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
        $msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
        # Check result
        if(!eppSuccess($coderes)) {
                $values["error"] = "Lock Domain($sld.$tld): Code (".$coderes.") ".$msg;
                return $values;
        }
} catch (Exception $e) {
                $values["error"] = 'Domain Lock/EPP: '.$e->getMessage();
                return $values;
        }

        return $values;
}
function COCCAepp_UnlockDomain($params) {
		# Grab variables
				$sld = $params["sld"];
				$tld = $params["tld"];
				$domain = "$sld.$tld";
		try {
						if (!isset($client)) {
								$client = _COCCAepp_Client();
						}

						# Lift Update Prohibited Lock
						$request = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
		<epp xmlns="urn:ietf:params:xml:ns:epp-1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
		  <command>
			<update>
			  <domain:update xmlns:domain="urn:ietf:params:xml:ns:domain-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
				<domain:name>'.$sld.'.'.$tld.'</domain:name>
				<domain:rem>
				<domain:status s="clientUpdateProhibited"/>
				</domain:rem>
			  </domain:update>
			</update>
			<clTRID>'.mt_rand().mt_rand().'</clTRID>
		  </command>
		</epp>
		');
		# Parse XML result
				$doc= new DOMDocument();
				$doc->loadXML($request);
			logModuleCall('COCCAepp', 'Remove UpdateProhibited', $xml, $request);

		$request = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
		<epp xmlns="urn:ietf:params:xml:ns:epp-1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
		  <command>
			<update>
			  <domain:update xmlns:domain="urn:ietf:params:xml:ns:domain-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
				<domain:name>'.$sld.'.'.$tld.'</domain:name>
				<domain:rem>
				  <domain:status s="clientDeleteProhibited"/>
				  <domain:status s="clientTransferProhibited"/>
				  <domain:status s="clientRenewProhibited"/>
				</domain:rem>
			  </domain:update>
			</update>
			<clTRID>'.mt_rand().mt_rand().'</clTRID>
		  </command>
		</epp>
		');
				# Parse XML result
				$doc= new DOMDocument();
				$doc->loadXML($request);
		 logModuleCall('COCCAepp', 'Domain UnLock', $xml, $request);

				$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
				$msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
				# Check result
				if(!eppSuccess($coderes)) {
						$values["error"] = "Domain Unlock($sld.$tld): Code (".$coderes.") ".$msg;
						return $values;
				}
		} catch (Exception $e) {
						$values["error"] = 'Domain UnLock/EPP: '.$e->getMessage();
						return $values;
				}

				return $values;
		}



# Function to register domain
function COCCAepp_RegisterDomain($params) {
    // user defined configuration values
	# Grab varaibles
	$sld = $params["sld"];
	$tld = $params["tld"];
	$regperiod = $params["regperiod"];
        $domain = "$sld.$tld";
	# Get registrant details
	$RegistrantFirstName = $params["firstname"];
	$RegistrantLastName = $params["lastname"];
	$RegistrantAddress1 = $params["address1"];
	$RegistrantAddress2 = $params["address2"];
	$RegistrantCity = $params["city"];
	$RegistrantStateProvince = $params["state"];
	$RegistrantPostalCode = $params["postcode"];
	$RegistrantCountry = $params["country"];
	$RegistrantEmailAddress = $params["email"];
	$RegistrantPhone = $params["fullphonenumber"];
	
        switch('.' . $tld){
	case '.ma':
			$RegistrantType= $params['additionalfields']['Type'];
			$RegistrantNID= $params['additionalfields']['NID'];
                        $RegistrantTID = $params['additionalfields']['TID'];
       case '.ote.ma':
                        $RegistrantType= $params['additionalfields']['Type'];
                        $RegistrantNID= $params['additionalfields']['NID'];
                        $RegistrantTID = $params['additionalfields']['TID'];

                              }

        #Generate Handle
	$regHandle = generateHandle();
	# Get admin details
	$AdminFirstName = $params["adminfirstname"];
	$AdminLastName = $params["adminlastname"];
	$AdminAddress1 = $params["adminaddress1"];
	$AdminAddress2 = $params["adminaddress2"];
	$AdminCity = $params["admincity"];
	$AdminStateProvince = $params["adminstate"];
	$AdminPostalCode = $params["adminpostcode"];
	$AdminCountry = $params["admincountry"];
	$AdminEmailAddress = $params["adminemail"];
	$AdminPhone = $params["adminphonenumber"];
        $RegistrantOrganizationName = $params["companyname"];
	#Generate Handle
	$admHandle = generateHandle();
	
	
	
    # Generate array of new nameservers
    $nameservers=array();
    if(!empty($params["ns1"]))
        array_push($nameservers,$params["ns1"]);
    if(!empty($params["ns2"]))
        array_push($nameservers,$params["ns2"]);
    if(!empty($params["ns3"]))
        array_push($nameservers,$params["ns3"]);
    if(!empty($params["ns4"]))
        array_push($nameservers,$params["ns4"]);
    if(!empty($params["ns5"]))
        array_push($nameservers,$params["ns5"]);

# Get client instance
	try {
	$client = _COCCAepp_Client();
        for($i=0; $i < count($nameservers); $i++) {
            # Get list of nameservers for domain
        	$result = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
   <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
     <command>
       <info>
         <host:info
          xmlns:host="urn:ietf:params:xml:ns:host-1.0">
           <host:name>'.$nameservers[$i].'</host:name>
         </host:info>
       </info>
       <clTRID>'.mt_rand().mt_rand().'</clTRID>
     </command>
   </epp>');
            # Parse XML result
            $doc = new DOMDocument();
            $doc->preserveWhiteSpace = false;
            $doc->loadXML($result);
            logModuleCall('COCCAepp', 'GetNameservers', $xml, $result);

            # Pull off status
            $coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
            $msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
            # Check the result is ok
            if($coderes == '2303') {
                $request = $client->request($xml ='<?xml version="1.0" encoding="UTF-8" standalone="no"?>
   <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
     <command>
       <create>
         <host:create
          xmlns:host="urn:ietf:params:xml:ns:host-1.0">
           <host:name>'.$nameservers[$i].'</host:name>        
         </host:create>
       </create>
       <clTRID>'.mt_rand().mt_rand().'</clTRID>
     </command>
   </epp>
');

                # Parse XML result
                $doc= new DOMDocument();
                $doc->loadXML($request);
                logModuleCall('COCCAepp', 'SaveNameservers', $xml, $request);


                # Pull off status
                $coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
                $msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
                # Check if result is ok
                if(!eppSuccess($coderes)) {
                    $values["error"] = "Could not Create host($nameservers[$i]): Code ($coderes) $msg";
                    return $values;
                }
            }
        }

// End create nameservers  /////////

        # Generate XML for nameservers
        if ($nameserver1 = $params["ns1"]) {
            $add_hosts = '
        <domain:hostObj>'.$nameserver1.'</domain:hostObj>
    ';
        }
        if ($nameserver2 = $params["ns2"]) {
            $add_hosts .= '
        <domain:hostObj>'.$nameserver2.'</domain:hostObj>
    ';
        }
        if ($nameserver3 = $params["ns3"]) {
            $add_hosts .= '
        <domain:hostObj>'.$nameserver3.'</domain:hostObj>
    ';
        }
        if ($nameserver4 = $params["ns4"]) {
            $add_hosts .= '
        <domain:hostObj>'.$nameserver4.'</domain:hostObj>
    ';
        }
        if ($nameserver5 = $params["ns5"]) {
            $add_hosts .= '
        <domain:hostObj>'.$nameserver5.'</domain:hostObj>
    ';
        }
    $eppKey = authKey();
	# Create Registrant
        if (!empty($RegistrantType) && $RegistrantType=='IND' ) {
        $request = $client->request($xml ='<?xml version="1.0" encoding="UTF-8" standalone="no"?>
	<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
           <command>
                <create>
                        <contact:create
          xmlns:contact="urn:ietf:params:xml:ns:contact-1.0">
                                <contact:id>'.$regHandle.'</contact:id>
                                <contact:postalInfo type="int">
                                        <contact:name>'.$RegistrantFirstName.' '.$RegistrantLastName.'</contact:name>
                                        <contact:org>'.$RegistrantOrganizationName.'</contact:org>
                                        <contact:addr>
                                                <contact:street>'.$RegistrantAddress1.'</contact:street>
                                                <contact:street>'.$RegistrantAddress2.'</contact:street>
                                                <contact:city>'.$RegistrantCity.'</contact:city>
                                                <contact:sp>'.$RegistrantStateProvince.'</contact:sp>
                                                <contact:pc>'.$RegistrantPostalCode.'</contact:pc>
                                                <contact:cc>'.$RegistrantCountry.'</contact:cc>
                                        </contact:addr>
                                </contact:postalInfo>
                                <contact:voice x="">'.$params["phonenumber"].'</contact:voice>
                                <contact:fax></contact:fax>
                                <contact:email>'.$RegistrantEmailAddress.'</contact:email>
                                <contact:authInfo>
                                        <contact:pw>CoCCA'.$eppKey.'</contact:pw>
                                </contact:authInfo>
                        </contact:create>
                </create>
        <extension>
         <contact:create xmlns:contact="urn:ietf:params:xml:ns:contact-id-1.0">
            <contact:person>
               <contact:NID>'.$RegistrantNID.'</contact:NID>
            </contact:person>
            </contact:create>
        </extension>

                <clTRID>'.mt_rand().mt_rand().'</clTRID>
        </command>
    </epp>
');

        }else  if (!empty($RegistrantType)){
    	$request = $client->request($xml ='<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
	<command>
		<create>
			<contact:create
          xmlns:contact="urn:ietf:params:xml:ns:contact-1.0">
				<contact:id>'.$regHandle.'</contact:id>
				<contact:postalInfo type="int">
					<contact:name>'.$RegistrantFirstName.' '.$RegistrantLastName.'</contact:name>
					<contact:org>'.$RegistrantOrganizationName.'</contact:org>
					<contact:addr>
						<contact:street>'.$RegistrantAddress1.'</contact:street>
						<contact:street>'.$RegistrantAddress2.'</contact:street>
						<contact:city>'.$RegistrantCity.'</contact:city>
						<contact:sp>'.$RegistrantStateProvince.'</contact:sp>
						<contact:pc>'.$RegistrantPostalCode.'</contact:pc>
						<contact:cc>'.$RegistrantCountry.'</contact:cc>
					</contact:addr>
				</contact:postalInfo>
				<contact:voice x="">'.$params["phonenumber"].'</contact:voice>
				<contact:fax></contact:fax>
				<contact:email>'.$RegistrantEmailAddress.'</contact:email>
				<contact:authInfo>
					<contact:pw>CoCCA'.$eppKey.'</contact:pw>
				</contact:authInfo>
			</contact:create>
		</create>
        <extension>
         <contact:create xmlns:contact="urn:ietf:params:xml:ns:contact-id-1.0">
         <contact:organization>
        <contact:NID>'.$RegistrantTID.'</contact:NID>
       </contact:organization>
      </contact:create>
    </extension>

		<clTRID>'.mt_rand().mt_rand().'</clTRID>
	</command>
</epp>
');
} else{
        $request = $client->request($xml ='<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
        <command>
                <create>
                        <contact:create
          xmlns:contact="urn:ietf:params:xml:ns:contact-1.0">
                                <contact:id>'.$regHandle.'</contact:id>
                                <contact:postalInfo type="int">
                                        <contact:name>'.$RegistrantFirstName.' '.$RegistrantLastName.'</contact:name>
                                        <contact:org>'.$RegistrantOrganizationName.'</contact:org>
                                        <contact:addr>
                                                <contact:street>'.$RegistrantAddress1.'</contact:street>
                                                <contact:street>'.$RegistrantAddress2.'</contact:street>
                                                <contact:city>'.$RegistrantCity.'</contact:city>
                                                <contact:sp>'.$RegistrantStateProvince.'</contact:sp>
                                                <contact:pc>'.$RegistrantPostalCode.'</contact:pc>
                                                <contact:cc>'.$RegistrantCountry.'</contact:cc>
                                        </contact:addr>
                                </contact:postalInfo>
                                <contact:voice x="">'.$params["phonenumber"].'</contact:voice>
                                <contact:fax></contact:fax>
                                <contact:email>'.$RegistrantEmailAddress.'</contact:email>
                                <contact:authInfo>
                                        <contact:pw>CoCCA'.$eppKey.'</contact:pw>
                                </contact:authInfo>
                        </contact:create>
                </create>

                <clTRID>'.mt_rand().mt_rand().'</clTRID>
        </command>
</epp>
');

}
        # Parse XML result
        $doc= new DOMDocument();
        $doc->loadXML($request);
        # Pull off status
        $coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
        $msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
        if(eppSuccess($coderes)) {
            $values['contact'] = 'Contact Created';
        } else if($coderes == '2302') {
            $values['contact'] = 'Contact Already exists';
        } else {
            $values["error"] = "RegisterDomain/Reg-create($regHandle): Code ($coderes) $msg";
            return $values;
        }

        $values["status"] = $msg;
        $eppKey =  authKey();
        //Create Domain Admin
        $request = $client->request($xml ='<?xml version="1.0" encoding="UTF-8" standalone="no"?>
   <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
     <command>
       <create>
         <contact:create
          xmlns:contact="urn:ietf:params:xml:ns:contact-1.0">
				<contact:id>'.$admHandle.'</contact:id>
				<contact:postalInfo type="int">
					<contact:name>'.$AdminFirstName.' '.$AdminLastName.'</contact:name>
					<contact:addr>
						<contact:street>'.$AdminAddress1.'</contact:street>
						<contact:street>'.$AdminAddress2.'</contact:street>
						<contact:city>'.$AdminCity.'</contact:city>
						<contact:sp>'.$AdminStateProvince.'</contact:sp>
						<contact:pc>'.$AdminPostalCode.'</contact:pc>
						<contact:cc>'.$AdminCountry.'</contact:cc>
					</contact:addr>
				</contact:postalInfo>
				<contact:voice>'.$AdminPhone.'</contact:voice>
				<contact:fax></contact:fax>
				<contact:email>'.$AdminEmailAddress.'</contact:email>
				<contact:authInfo>
					<contact:pw>CoCCA'.$eppKey.'</contact:pw>
				</contact:authInfo>
			</contact:create>
		</create>
		<clTRID>'.mt_rand().mt_rand().'</clTRID>
	</command>
</epp>
');

        # Parse XML result
        $doc = new DOMDocument();
        $doc->loadXML($request);
        # Pull off status
        $coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
        $msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
        if(eppSuccess($coderes)) {
            $values['contact'] = 'Contact Created';
        } else if($coderes == '2302') {
            $values['contact'] = 'Contact Already exists';
        } else {
            $values["error"] = "RegisterDomain/Admin Contact-create($admHandle): Code ($coderes) $msg";
            return $values;
        }
        $eppKey =  authKey();
        $values["status"] = $msg;
       //Create the Domain
        $request = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
   <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
     <command>
       <create>
         <domain:create
          xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
                  		<domain:name>'.$sld.'.'.$tld.'</domain:name>
<domain:period unit="y">'.$regperiod.'</domain:period>
				<domain:ns>'.$add_hosts.'</domain:ns>
				<domain:registrant>'.$regHandle.'</domain:registrant>
				<domain:contact type="admin">'.$admHandle.'</domain:contact>
				<domain:contact type="tech">'.$admHandle.'</domain:contact>
				<domain:contact type="billing">'.$admHandle.'</domain:contact>
				<domain:authInfo>
					<domain:pw>CoCCA'.$eppKey.'</domain:pw>
				</domain:authInfo>
			</domain:create>
		</create>
	<clTRID>'.mt_rand().mt_rand().'</clTRID>	
	</command>
</epp>
');

        $doc= new DOMDocument();
        $doc->loadXML($request);
        logModuleCall('COCCAepp', 'RegisterDomain', $xml, $request);

        $coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
        $msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
        if(!eppSuccess($coderes)) {
            $values["error"] = "RegisterDomain/domain-create($sld.$tld): Code ($coderes) $msg";
            return $values;
        }

        $values["status"] = $msg;

        return $values;

	} catch (Exception $e) {
		$values["error"] = 'RegisterDomain/EPP: '.$e->getMessage();
		return $values;
	}

	return $values;

}



# Function to transfer a domain
function COCCAepp_TransferDomain($params) {
	# Grab variables
	$testmode = $params["TestMode"];
	$sld = $params["sld"];
	$tld = $params["tld"];
$domain = "$sld.$tld";
	# Domain info
	$regperiod = $params["regperiod"];
	$transfersecret = $params["transfersecret"];
	$nameserver1 = $params["ns1"];
	$nameserver2 = $params["ns2"];
	# Registrant Details
	$RegistrantFirstName = $params["firstname"];
	$RegistrantLastName = $params["lastname"];
	$RegistrantAddress1 = $params["address1"];
	$RegistrantAddress2 = $params["address2"];
	$RegistrantCity = $params["city"];
	$RegistrantStateProvince = $params["state"];
	$RegistrantPostalCode = $params["postcode"];
	$RegistrantCountry = $params["country"];
	$RegistrantEmailAddress = $params["email"];
	$RegistrantPhone = $params["fullphonenumber"];
		
	
	# Get client instance
	try {
		$client = _COCCAepp_Client();

		# Initiate transfer
		$request = $client->request($xml ='<?xml version="1.0" encoding="UTF-8" standalone="no"?>
   <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
     <command>
       <transfer op="request">
         <domain:transfer
          xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
				<domain:name>'.$sld.'.'.$tld.'</domain:name>
				<domain:authInfo><domain:pw>'.$transfersecret.'</domain:pw></domain:authInfo>
			</domain:transfer>
		</transfer>
		<clTRID>'.mt_rand().mt_rand().'</clTRID>
	</command>
</epp>');

		# Parse XML result
		$doc= new DOMDocument();
		$doc->loadXML($request);
		logModuleCall('COCCAepp', 'TransferDomain', $xml, $request);

		$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
		$msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
		if(!eppSuccess($coderes)) {
			$values["error"] = "TransferDomain/domain-transfer($sld.$tld): Code ($coderes) $msg";
			return $values;
		}

		$values["status"] = $msg;

		
	} catch (Exception $e) {
		$values["error"] = 'TransferDomain/EPP: '.$e->getMessage();
		return $values;
	}

	$values["status"] = $msg;

	return $values;
}


# Function to renew domain
function COCCAepp_RenewDomain($params) {
# Grab variables
        $sld = $params["sld"];
        $tld = $params["tld"];
        $regperiod = $params["regperiod"];
        $domain = "$sld.$tld";
        # Get client instance
        try {
                $client = _COCCAepp_Client();

                # Send renewal request
                $request = $client->request($xml='<?xml version="1.0" encoding="UTF-8" standalone="no"?>
                                            <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
                                            <command>
                                              <info>
                                                <domain:info
                                                 xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
                                                 <domain:name>'.$sld.'.'.$tld.'</domain:name>
                                                </domain:info>
                                              </info>
                                              <clTRID>'.mt_rand().mt_rand().'</clTRID>
                                           </command>
                                                    </epp>');

                # Parse XML result
                $doc= new DOMDocument();
                $doc->loadXML($request);
                logModuleCall('COCCAepp', 'RenewDomain', $xml, $request);

                $coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
                $msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
                if(!eppSuccess($coderes)) {
                        $values["error"] = "RenewDomain/domain-info($sld.$tld)): Code ($coderes) $msg";
                        return $values;
                }
                  // check domain status

                  if ($doc->getElementsByTagName('status')->item(0)) {
                        $statusres = $doc->getElementsByTagName('status')->item(0)->getAttribute('s');
                        $requestedby = $doc->getElementsByTagName('status')->item(0)->nodeValue;
                        $createdate = substr($doc->getElementsByTagName('crDate')->item(0)->nodeValue,0,10);
                        $nextduedate = substr($doc->getElementsByTagName('exDate')->item(0)->nodeValue,0,10);
                        $deldate= $doc->getElementsByTagName('upDate')->item(0)->nodeValue;
                        $currdate1 = date("Y-m-d");
                        $currdate2 = date("H:i:s");
                } else {
                        $values['error'] = "RenewDomain/domain-info($domain): Domain not found";
                        return $values;
                }
                $values['status'] = $msg;
                # Sanitize expiry date
 $expdate = substr($doc->getElementsByTagName('exDate')->item(0)->nodeValue,0,10);
                if (empty($expdate)) {
                        $values["error"] = "RenewDomain/domain-info($sld.$tld): Domain info not available";
                        return $values;
                }

                # Check if domain in pendingdelete state then send a restore command with resotore report
                  if ($statusres == "pendingDelete") {
                       // $values['error'] = "RenewDomain/domain-info($domain): Domain Status :pendingDelete";
                    // here will send restore command
                     $restore_request = $client->request($xml='<?xml version="1.0" encoding="UTF-8" standalone="no"?>
                                           <epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
                                                        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                                                        xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0
                                                        epp-1.0.xsd">
                                                 <command>
                                                   <update>
                                                         <domain:update
                                                          xmlns:domain="urn:ietf:params:xml:ns:domain-1.0"
                                                          xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0
                                                          domain-1.0.xsd">
                                                           <domain:name>'.$sld.'.'.$tld.'</domain:name>
                                                           <domain:chg/>
                                                         </domain:update>
                                                   </update>
                                                   <extension>
                                                         <rgp:update xmlns:rgp="urn:ietf:params:xml:ns:rgp-1.0"
                                                          xsi:schemaLocation="urn:ietf:params:xml:ns:rgp-1.0
                                                          rgp-1.0.xsd">
                                                           <rgp:restore op="report">
                                                                 <rgp:report>
                                                                   <rgp:preData>Registrant did not Renew domain name
                                                                   in Renewal Grace Period.</rgp:preData>
                                                                   <rgp:postData>I am not restoring this domain name
                                                                   in order to assume the rights to use or sell the
                                                                   domain name for myself or for any third party.The
                                                                   domain name will be returned to the control of its
                                                                   former registrant.</rgp:postData>
                                                                   <rgp:delTime>'.$deldate.'</rgp:delTime>
                                                                   <rgp:resTime>'.$currdate1.'T'.$currdate2.'.675Z</rgp:resTime>
                                                                   <rgp:resReason>Registrant error.</rgp:resReason>
                                                                   <rgp:statement>This registrar has not restored the
                                                                   Registered Name in order to assume the rights to use
                                                                   or sell the Registered Name for itself or for any
                                                                   third party.</rgp:statement>
                                                                   <rgp:statement>The information in this report is
                                                                   true to best of this registrars knowledge, and this
                                                                   registrar acknowledges that intentionally supplying
                                                                   false information in this report shall constitute an
                                                                   incurable material breach of the
                                                                   Registry-Registrar Agreement.</rgp:statement>
                                                                   <rgp:other>Supporting information goes
                                                                   here.</rgp:other>
                                                                 </rgp:report>
                                                           </rgp:restore>
                                                         </rgp:update>
                                                   </extension>
                                                   <clTRID>'.mt_rand().mt_rand().'</clTRID>
                                                 </command>
                                           </epp>');
                      # Parse XML restore result
                        $doc= new DOMDocument();
                        $doc->loadXML($request);
                        logModuleCall('COCCAepp', 'RestoreDomain', $xml, $request);
                        $coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
                        $msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
                         if(!eppSuccess($coderes)) {
                            $values["error"] = "RenewDomain/Restore domain-info($sld.$tld)): Code ($coderes) $msg";
                            return $values;
                            }
                    }
                //
                $values['status'] = $msg;
                # Send request to renew
                $request = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
                                                                                   <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
                                                                                         <command>
                                                                                           <renew>
                                                                                               <domain:renew
                                                                                                  xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
                                                                                                  <domain:name>'.$sld.'.'.$tld.'</domain:name>
                                                                                                  <domain:curExpDate>'.$expdate.'</domain:curExpDate>
                                                                                                  <domain:period unit="y">'.$regperiod.'</domain:period>
                                                                                                </domain:renew>
                                                                                            </renew>
                                                                                          </command>
                                                                                          <clTRID>'.mt_rand().mt_rand().'</clTRID>
                                                                                        </epp>');
                # Parse XML result
                $doc= new DOMDocument();
                $doc->loadXML($request);
                logModuleCall('COCCAepp', 'RenewDomain', $xml, $request);
                $coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
                $msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
                if(!eppSuccess($coderes)) {
                        $values["error"] = "RenewDomain/domain-renew($sld.$tld,$expdate): Code (".$coderes.") ".$msg;
                        return $values;
                }

                $values["status"] = $msg;

                } catch (Exception $e) {
                   $values["error"] = 'RenewDomain/EPP: '.$e->getMessage();
                   return $values;
                }

               # If error, return the error message in the value below
                 return $values;

}



# Function to grab contact details
function COCCAepp_GetContactDetails($params) {
	# Grab variables
	$sld = $params["sld"];
	$tld = $params["tld"];
$domain = "$sld.$tld";
	# Get client instance
	try {
		if (!isset($client)) {
			$client = _COCCAepp_Client();
		}

		# Grab domain info
		$result = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <command>
    <info>
      <domain:info xmlns:domain="urn:ietf:params:xml:ns:domain-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
        <domain:name>'.$sld.'.'.$tld.'</domain:name>
      </domain:info>
    </info>
    <clTRID>'.mt_rand().mt_rand().'</clTRID>
  </command>
</epp>');

        # Parse XML result
        $doc= new DOMDocument();
        $doc->loadXML($result);
        logModuleCall('COCCAepp', 'Get Contact Details', $xml, $result);

        $coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
        $msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;

        # Check result
        if(!eppSuccess($coderes)) {
            $values["error"] = "GetContactDetails/domain-info($sld.$tld): Code (".$coderes.") ".$msg;
            return $values;
        }

        # Grab contact Handles
        $registrant = $doc->getElementsByTagName('registrant')->item(0)->nodeValue;
        if (empty($registrant)) {
            $values["error"] = "GetContactDetails/domain-info($sld.$tld): Registrant info not available";
            return $values;
        }

        $domaininfo=array();
        for ($i=0; $i<=2; $i++) {
            $x=$doc->getElementsByTagName('contact')->item($i);
            if(!empty($x)){
                $domaininfo[$doc->getElementsByTagName('contact')->item($i)->getAttribute('type')]=$doc->getElementsByTagName('contact')->item($i)->nodeValue;
            }
            else{
                break;
            }
        }

        $contactIDs[$registrant] = array();
        foreach($domaininfo as $id) {
            if($id != '')
                $contactIDs[$id] = array();
        }
        foreach($contactIDs as $id => $k) {
            $contactIDs[$id] = getContactDetail($client, $id);
        }

        $Contacts["Admin"]=$domaininfo["admin"];
        $Contacts["Tech"]=$domaininfo["tech"];
        $Contacts["Billing"]=$domaininfo["billing"];

        # Grab Registrant Contact
        $values["Registrant"] = $contactIDs[$registrant];

        #Get Admin, Tech and Billing Contacts
        foreach ($Contacts as $type => $value) {
            if ($value!=""){
                $values["$type"] = $contactIDs[$value];
            }else{
                $values["$type"]["Contact Name"] = "";
                $values["$type"]["Company Name"] = "";
                $values["$type"]["Address 1"] = "";
                $values["$type"]["Address 2"] = "";
                $values["$type"]["City"] = "";
                $values["$type"]["State"] = "";
                $values["$type"]["ZIP code"] = "";
                $values["$type"]["Country"] = "";
                $values["$type"]["Phone"] = "";
                $values["$type"]["Email"] = "";
            }
        }

        return $values;

    } catch (Exception $e) {
		$values["error"] = 'GetContactDetails/EPP: '.$e->getMessage();
		return $values;
	}
}

function getContactDetail($client, $contactID) {
    $request =  $client->request($xml ='<?xml version="1.0" encoding="UTF-8" standalone="no"?>
   <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
     <command>
       <info>
         <contact:info
          xmlns:contact="urn:ietf:params:xml:ns:contact-1.0">
          <contact:id>'.$contactID.'</contact:id>
         </contact:info>
       </info>
    <clTRID>'.mt_rand().mt_rand().'</clTRID>
   </command>
   </epp>');

    # Parse XML result
    $doc= new DOMDocument();
    $doc->loadXML($request);
    logModuleCall('COCCAepp', 'GetContactDetails', $xml, $request);

    $coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
    $msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;

    # Check results
    if(!eppSuccess($coderes)) {
        throw new Exception("contact-info($contactID): Code (".$coderes.") ".$msg);
    }

    $contact["Contact Name"] = $doc->getElementsByTagName('name')->item(0)->nodeValue;
    $contact["Company Name"] = $doc->getElementsByTagName('org')->item(0)->nodeValue;
    $contact["Address 1"] = $doc->getElementsByTagName('street')->item(0)->nodeValue;
    $contact["Address 2"] = $doc->getElementsByTagName('street')->item(1)->nodeValue;
    $contact["City"] = $doc->getElementsByTagName('city')->item(0)->nodeValue;
    $contact["State"] = $doc->getElementsByTagName('sp')->item(0)->nodeValue;
    $contact["ZIP code"] = $doc->getElementsByTagName('pc')->item(0)->nodeValue;
    $contact["Country"] = $doc->getElementsByTagName('cc')->item(0)->nodeValue;
    $contact["Phone"] = $doc->getElementsByTagName('voice')->item(0)->nodeValue;
    $contact["Email"] = $doc->getElementsByTagName('email')->item(0)->nodeValue;

    return $contact;
}

# Function to save contact details
function COCCAepp_SaveContactDetails($params) {
	# Grab variables
	$tld = $params["tld"];
	$sld = $params["sld"];
    $details = $params["contactdetails"];
$domain = "$sld.$tld";
	# Get client instance
	try {
        $client = _COCCAepp_Client();

        # Grab domain info
        $request = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp:epp xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:epp="urn:ietf:params:xml:ns:epp-1.0"
		xmlns:domain="urn:ietf:params:xml:ns:domain-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
	<epp:command>
		<epp:info>
			<domain:info xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
				<domain:name hosts="all">' . $sld . '.' . $tld . '</domain:name>
			</domain:info>
		</epp:info>
	</epp:command>
</epp:epp>');

        # Parse XML	result
        $doc = new DOMDocument();
        $doc->loadXML($request);
        $coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
        $msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
        if (!eppSuccess($coderes)) {
            $values["error"] = "SaveContactDetails/domain-info($sld.$tld): Code (" . $coderes . ") " . $msg;
            return $values;
        }

        $values["status"] = $msg;
        # Grab Registrant contact Handles
        $registrantHandle = $doc->getElementsByTagName('registrant')->item(0)->nodeValue;
        if (empty($registrantHandle)) {
            $values["error"] = "GetContactDetails/domain-info($sld.$tld): Registrant info not available";
            return $values;
        }
        $domaininfo = array();
        for ($i = 0; $i <= 2; $i++) {
            $x = $doc->getElementsByTagName('contact')->item($i);
            if (!empty($x)) {
                $domaininfo[$doc->getElementsByTagName('contact')->item($i)->getAttribute('type')] = $doc->getElementsByTagName('contact')->item($i)->nodeValue;
            } else {
                break;
            }
        }

        $Contacts["Admin"] = $domaininfo["admin"];
        $Contacts["Tech"] = $domaininfo["tech"];
        $Contacts["Billing"] = $domaininfo["billing"];

        $cIDs[$registrantHandle] = 'Registrant';
        foreach($Contacts as $type => $handle) {
            if(isset($handle))
                if(!array_key_exists($handle, $cIDs)) {
                    $cIDs[$handle] = $type;
                }
                else {
                    $removeContact[$type] = $handle;
                    $Contacts[$type] = null;
                }
        }

        foreach ($Contacts as $type => $handle) {
            if (isset($handle)) {
                if (!array_empty($details[$type]))
                    changeContact($client, $details[$type], $handle, $type);
                else
                    $removeContact[$type] = $handle;
            } else {
                if (!array_empty($details[$type]))
                    $addContact[$type] = createContact($client, $details[$type], $type);
            }
        }

        $xmlAddContact = '';
        if(isset($addContact)) {
            $xmlAddContact = "<domain:add>\n";
            foreach ($addContact as $type => $handle) {
                $xmlAddContact .= '<domain:contact type="'.strtolower($type).'">'.$handle.'</domain:contact>'."\n";
            }
            $xmlAddContact .= "</domain:add>\n";
        }

        $xmlRemContact = '';
        if(isset($removeContact)) {
            $xmlRemContact = "<domain:rem>\n";
            foreach ($removeContact as $type => $handle) {
                $xmlRemContact .= '<domain:contact type="'.strtolower($type).'">'.$handle.'</domain:contact>'."\n";
            }
            $xmlRemContact .= "</domain:rem>\n";
        }

        # Save Registrant contact details
        changeContact($client, $details['Registrant'], $registrantHandle, "Registrant");

        # change the domain contacts
        if(!empty($xmlAddContact) || !empty($xmlRemContact)) {
            $request = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
  <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
    <command>
      <update>
        <domain:update xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
         <domain:name>' . $sld . '.' . $tld . '</domain:name>' .
                $xmlAddContact .
                $xmlRemContact . '
        </domain:update>
       </update>
       <clTRID>' . mt_rand() . mt_rand() . '</clTRID>
     </command>
   </epp>');

            $doc = new DOMDocument();
            $doc->loadXML($request);
            logModuleCall('COCCAepp', 'SaveContactDetails', $xml, $request);

            $coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
            $msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
            if (!eppSuccess($coderes)) {
                $values["error"] = "Domain contact update error: Code ($coderes) $msg";
                return $values;
            }

            $values["status"] = $msg;
        }
        else {
            $values["status"] = 'OK';
        }

    } catch (Exception $e) {
		$values["error"] = 'SaveContactDetails/EPP: '.$e->getMessage();
		return $values;
	}

	return $values;
}

function createContact($client, $data, $type = "") {
    //Create Billing Contacts
    $handle = generateHandle();
    $eppKey =  authKey();
    $result = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
  <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
    <command>
      <create>
        <contact:create xmlns:contact="urn:ietf:params:xml:ns:contact-1.0">
          <contact:id>'.$handle.'</contact:id>
          <contact:postalInfo type="int">
            <contact:name>'.$data["Contact Name"].' </contact:name>
            <contact:org>'.$data["Company Name"].'</contact:org>
            <contact:addr>
              <contact:street>'.$data["Address 1"].'</contact:street>
              <contact:street>'.$data["Address 2"].'</contact:street>
              <contact:city>'.$data["City"].'</contact:city>
              <contact:sp>'.$data["State"].'</contact:sp>
              <contact:pc>'.$data["ZIP code"].'</contact:pc>
              <contact:cc>'.$data["Country"].'</contact:cc>
            </contact:addr>
          </contact:postalInfo>
          <contact:voice>'.$data["Phone"].'</contact:voice>
          <contact:email>'.$data["Email"].'</contact:email>
          <contact:authInfo>
            <contact:pw>CoCCA'.$eppKey.'</contact:pw>
          </contact:authInfo>
        </contact:create>
      </create>
      <clTRID>'.mt_rand().mt_rand().'</clTRID>
	</command>
  </epp>');

    # Parse XML result
    $doc = new DOMDocument();
    $doc->loadXML($result);
    logModuleCall('COCCAepp', 'SaveContactDetails', $xml, $result);

    $coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
    $msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
    if(!eppSuccess($coderes)) {
        throw new Exception("contact-create($handle) $type: Code ($coderes) $msg");
    }

    return $handle;
}
function COCCAepp_UpdateFieldsAdditional($params) {
        $tld = $params["tld"];
        $sld = $params["sld"];
        $domain = "$sld.$tld";
    if ($tld == 'ma'){
 
try { 
    
     $client = _COCCAepp_Client();

     $request1 = $client->request($xml='<?xml version="1.0" encoding="UTF-8" standalone="no"?>
      <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
       <command>
        <info>
         <domain:info
          xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
                                <domain:name>'.$domain.'</domain:name>
                        </domain:info>
                </info>
                <clTRID>'.mt_rand().mt_rand().'</clTRID>
        </command>
      </epp>');

                $doc= new DOMDocument();
                $doc->loadXML($request1);
                $coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
                $msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
               if (!eppSuccess($coderes)) {
                  $values["error"] = "UpdateFieldsAdditional/domain-info('.$domain.'): Code (" . $coderes . ") " . $msg;
                   return $values;
                }

      if($coderes == '1000') {

                $registrantid = $doc->getElementsByTagName('registrant')->item(0)->nodeValue ;
                $registrantContact=getContactDetail($client, $registrantid);
         $result = getNID($domain);
         $typo= $result['Type'];
         $IDvalue = $result['Value'];
        switch('.' . $tld){
        case '.as':     
                      if ($result['Type'] == 'IND' ){
                          $RegistrantType= 'IND';
                          $RegistrantNID= $result['Value'];
                          $RegistrantTID = $result['Value'];
                       }else{
                          $RegistrantType= 'ORG';
                          $RegistrantNID=  $result['Value'];
                          $RegistrantTID = $result['Value'];

                          }
       case '.ote.as':
                      if ($result['Type'] == 'IND' ){
                          $RegistrantType= 'IND';
                          $RegistrantNID= $result['Value'];
                          $RegistrantTID = $result['Value'];
                       }else{
                          $RegistrantType= 'ORG';
                          $RegistrantNID=  $result['Value'];
                          $RegistrantTID = $result['Value'];

                          }
                              }
  if (!empty($RegistrantType) && $RegistrantType=='IND' ) {
    $result = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
  <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
    <command>
      <update>
        <contact:update xmlns:contact="urn:ietf:params:xml:ns:contact-1.0">
          <contact:id>'.$registrantid.'</contact:id>
          <contact:chg>
            <contact:postalInfo type="int">
              <contact:name>'.$registrantContact["Contact Name"].' </contact:name>
              <contact:org>'.$registrantContact["Company Name"].'</contact:org>
              <contact:addr>
                <contact:street>'.$registrantContact["Address 1"].'</contact:street>
                <contact:street>'.$registrantContact["Address 2"].'</contact:street>
                <contact:city>'.$registrantContact["City"].'</contact:city>
                <contact:sp>'.$registrantContact["State"].'</contact:sp>
                <contact:pc>'.$registrantContact["ZIP code"].'</contact:pc>
                <contact:cc>'.$registrantContact["Country"].'</contact:cc>
              </contact:addr>
            </contact:postalInfo>
            <contact:voice>'.$registrantContact["Phone"].'</contact:voice>
            <contact:email>'.$registrantContact["Email"].'</contact:email>
          </contact:chg>
        </contact:update>
        </update>
        <extension>
         <contact:update xmlns:contact="urn:ietf:params:xml:ns:contact-id-1.0">
            <contact:person>
               <contact:NID>'.$RegistrantNID.'</contact:NID>
            </contact:person>
            </contact:update>
        </extension>      
      <clTRID>'.mt_rand().mt_rand().'</clTRID>
        </command>
  </epp>');
     }else{
    $result = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
  <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
    <command>
      <update>
        <contact:update xmlns:contact="urn:ietf:params:xml:ns:contact-1.0">
          <contact:id>'.$registrantid.'</contact:id>
          <contact:chg>
            <contact:postalInfo type="int">
              <contact:name>'.$registrantContact["Contact Name"].' </contact:name>
              <contact:org>'.$registrantContact["Company Name"].'</contact:org>
              <contact:addr>
                <contact:street>'.$registrantContact["Address 1"].'</contact:street>
                <contact:street>'.$registrantContact["Address 2"].'</contact:street>
                <contact:city>'.$registrantContact["City"].'</contact:city>
                <contact:sp>'.$registrantContact["State"].'</contact:sp>
                <contact:pc>'.$registrantContact["ZIP code"].'</contact:pc>
                <contact:cc>'.$registrantContact["Country"].'</contact:cc>
              </contact:addr>
            </contact:postalInfo>
            <contact:voice>'.$registrantContact["Phone"].'</contact:voice>
            <contact:email>'.$registrantContact["Email"].'</contact:email>
          </contact:chg>
        </contact:update>
      </update>
      <extension>
        <contact:update xmlns:contact="urn:ietf:params:xml:ns:contact-id-1.0">
         <contact:organization>
           <contact:NID>'.$RegistrantTID.'</contact:NID>
          </contact:organization>
      </contact:update>
      </extension>
      <clTRID>'.mt_rand().mt_rand().'</clTRID>
 </command>
  </epp>');

}
    # Parse XML result
    $doc = new DOMDocument();
    $doc->loadXML($result);
    logModuleCall('COCCAepp', 'SaveContactDetailsWithAddionalFields', $xml, $result);

    $coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
    $msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
    if(!eppSuccess($coderes)) {
        throw new Exception("contact-create($handle) $type: Code ($coderes) $msg");
    }
}else{
  $values["error"] = "Save additional fields/('.$domain.'): Code (" . $coderes . ") " . $msg;
     return  $values;

}
                                        } catch (Exception $e) {
                                                        $values["error"] = 'Save Additional Fields/EPP: '.$e->getMessage();
                                                        return $values;
                                        }

   }
}

function changeContact($client, $newdata, $handle, $type = "") {
    $result = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
  <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
    <command>
      <update>
        <contact:update xmlns:contact="urn:ietf:params:xml:ns:contact-1.0">
          <contact:id>'.$handle.'</contact:id>
          <contact:chg>
            <contact:postalInfo type="int">
              <contact:name>'.$newdata["Contact Name"].' </contact:name>
              <contact:org>'.$newdata["Company Name"].'</contact:org>
              <contact:addr>
                <contact:street>'.$newdata["Address 1"].'</contact:street>
                <contact:street>'.$newdata["Address 2"].'</contact:street>
                <contact:city>'.$newdata["City"].'</contact:city>
                <contact:sp>'.$newdata["State"].'</contact:sp>
                <contact:pc>'.$newdata["ZIP code"].'</contact:pc>
                <contact:cc>'.$newdata["Country"].'</contact:cc>
              </contact:addr>
            </contact:postalInfo>
            <contact:voice>'.$newdata["Phone"].'</contact:voice>
            <contact:email>'.$newdata["Email"].'</contact:email>
          </contact:chg>
        </contact:update>
      </update>
      <clTRID>'.mt_rand().mt_rand().'</clTRID>
	</command>
  </epp>');

    # Parse XML result
    $doc = new DOMDocument();
    $doc->loadXML($result);
    logModuleCall('COCCAepp', 'SaveContactDetails', $xml, $result);

    $coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
    $msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
    if(!eppSuccess($coderes)) {
        throw new Exception("contact-create($handle) $type: Code ($coderes) $msg");
    }

    return $handle;
}

# Function to get EPP Code
function COCCAepp_GetEPPCode($params) {
        # Grab variables
        $username = $params["Username"];
        $password = $params["Password"];
        $testmode = $params["TestMode"];
        $sld = $params["sld"];
        $tld = $params["tld"];
        $newEppKey = authKey();
        # Grab client instance
        try {
                $client = _COCCAepp_Client();

            # Register nameserver
            $request = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
			<epp xmlns="urn:ietf:params:xml:ns:epp-1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
			<command>
			<update>
			<domain:update xmlns:domain="urn:ietf:params:xml:ns:domain-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
			<domain:name>'.$sld.'.'.$tld.'</domain:name>
			<domain:chg>
			<domain:authInfo>
			<domain:pw>' . $newEppKey . '</domain:pw>
			</domain:authInfo>
			</domain:chg>
			</domain:update>
			</update>
			<clTRID>'  .mt_rand().mt_rand() . '</clTRID>
			</command>
			</epp>
			');
							# Parse XML result
							$doc= new DOMDocument();
							$doc->loadXML($request);
							logModuleCall('COCCAepp', 'EPPCODE', $xml, $request);



					$values["eppcode"] = $newEppKey;
					# If error, return the error message in the value below
							 $coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
					$msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
					if(!eppSuccess($coderes)) {
						$values["error"] = "Authcode/EPP($sld.$tld): Code ($coderes) $msg";
						return $values;
					}

					$values["status"] = $msg;

					return $values;

					} catch (Exception $e) {
							$values["error"] = 'Authcode/EPP: '.$e->getMessage();
							return $values;
					}

					return $values;
			}


# Function to register nameserver
function COCCAepp_RegisterNameserver($params) {
	# Grab variables
	$username = $params["Username"];
	$password = $params["Password"];
	$testmode = $params["TestMode"];
	$sld = $params["sld"];
	$tld = $params["tld"];
	$nameserver = $params["nameserver"];
	$ipaddress = $params["ipaddress"];
$domain = "$sld.$tld";	

	# Grab client instance
	try {
		$client = _COCCAepp_Client();

		# Register nameserver
		$request = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
   <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
     <command>
       <create>
         <host:create
          xmlns:host="urn:ietf:params:xml:ns:host-1.0">
           <host:name>'.$nameserver.'</host:name>
           <host:addr ip="v4">'.$ipaddress.'</host:addr>           
         </host:create>
       </create>
       <clTRID>'.mt_rand().mt_rand().'</clTRID>
     </command>
   </epp>
');
		# Parse XML result
		$doc= new DOMDocument();
		$doc->loadXML($request);
		logModuleCall('COCCAepp', 'RegisterNameserver', $xml, $request);

		# Pull off status
		$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
		$msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
		logModuleCall('COCCAepp', 'SaveHost', $xml, $request);
		# Check if result is ok
		if(!eppSuccess($coderes)) {
			$values["error"] = "RegisterNameserver($nameserver): Code ($coderes) $msg";
			return $values;
		}

		$values['status'] = $msg;

	} catch (Exception $e) {
		$values["error"] = 'SaveNameservers/EPP: '.$e->getMessage();
		return $values;
	}


	return $values;
}



# Modify nameserver
function COCCAepp_ModifyNameserver($params) {
	# Grab variables
	$username = $params["Username"];
	$password = $params["Password"];
	$testmode = $params["TestMode"];
	$tld = $params["tld"];
	$sld = $params["sld"];
	$nameserver = $params["nameserver"];
	$currentipaddress = $params["currentipaddress"];
	$newipaddress = $params["newipaddress"];

$domain = "$sld.$tld";	
	# Grab client instance
	try {
		$client = _COCCAepp_Client();

		# Modify nameserver
		$request = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
   <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
     <command>
       <update>
         <host:update
          xmlns:host="urn:ietf:params:xml:ns:host-1.0">
           <host:name>'.$nameserver.'</host:name>
           <host:add>
             <host:addr ip="v4">'.$newipaddress.'</host:addr>
               </host:add>
           <host:rem>
             <host:addr ip="v4">'.$currentipaddress.'</host:addr>
           </host:rem>           
         </host:update>
       </update>
       <clTRID>'.mt_rand().mt_rand().'</clTRID>
     </command>
   </epp>
');
		# Parse XML result
		$doc= new DOMDocument();
		$doc->loadXML($request);
		logModuleCall('COCCAepp', 'ModifyNameserver', $xml, $request);

		# Pull off status
		$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
		$msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
		# Check if result is ok
		if(!eppSuccess($coderes)) {
			$values["error"] = "ModifyNameserver/domain-update($nameserver): Code ($coderes) $msg";
			return $values;
		}

		$values['status'] = $msg;

	} catch (Exception $e) {
		$values["error"] = 'SaveNameservers/EPP: '.$e->getMessage();
		return $values;
	}

	return $values;
}


# Delete nameserver
function COCCAepp_DeleteNameserver($params) {
	# Grab variables
	$username = $params["Username"];
	$password = $params["Password"];
	$testmode = $params["TestMode"];
	$tld = $params["tld"];
	$sld = $params["sld"];
	$nameserver = $params["nameserver"];
	
$domain = "$sld.$tld";
	# Grab client instance
	try {
		$client = _COCCAepp_Client();

		

		# Delete nameserver
		$request = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
   <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
     <command>
       <delete>
         <host:delete
          xmlns:host="urn:ietf:params:xml:ns:host-1.0">
           <host:name>'.$nameserver.'</host:name>
         </host:delete>
       </delete>
       <clTRID>'.mt_rand().mt_rand().'</clTRID>
     </command>
   </epp>
');
		# Parse XML result
		$doc= new DOMDocument();
		$doc->loadXML($request);
		logModuleCall('COCCAepp', 'DeleteNameserver', $xml, $request);

		# Pull off status
		$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
		$msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
		# Check if result is ok
		if(!eppSuccess($coderes)) {
			$values["error"] = "DeleteNameserver/domain-update($sld.$tld): Code ($coderes) $msg";
			return $values;
		}

		$values['status'] = $msg;

	} catch (Exception $e) {
		$values["error"] = 'SaveNameservers/EPP: '.$e->getMessage();
		return $values;
	}

	return $values;
}


# Function to return meaningful message from response code
function _COCCAepp_message($code) {
	return "Code $code";
}

# Function to create internal EPP request
function _COCCAepp_Client() {
	# Setup include dir
	$include_path = ROOTDIR . '/modules/registrars/COCCAepp';
	set_include_path($include_path . PATH_SEPARATOR . get_include_path());
	# Include EPP stuff we need
	require_once 'Net/EPP/Client.php';
	require_once 'Net/EPP/Protocol.php';

	# Grab module parameters
	$params = getregistrarconfigoptions('COCCAepp');
	# Check if module parameters are sane
	if (empty($params['Username']) || empty($params['Password'])) {
		throw new Exception('System configuration error(1), please contact your provider');
	}

       // Define some parameters
       $host= $params['Server'];
       $port= $params['Port'];
       
        //Get the EPP Configurations for the extension:
        
        # Create SSL context
	#$context = stream_context_create();
	$context = stream_context_create([
                'ssl' => [
                    'verify_peer'      => false,
                    'verify_peer_name' => false
                ]
            ]);
	# Are we using ssl?
	$use_ssl = true;
	if (!empty($params['SSL']) && $params['SSL'] == 'on') {
		$use_ssl = true;
	}
	# Set certificate if we have one
	if ($use_ssl && !empty($params['Certificate'])) {
		if (!file_exists($params['Certificate'])) {
			throw new Exception("System configuration , please contact your provider");
		}
		# Set client side certificate
		stream_context_set_option($context, 'ssl', 'local_cert', $params['Certificate']);
	}

	# Create EPP client
	$client = new Net_EPP_Client();

	# Connect
	$res = $client->connect($host, $port, 60, $use_ssl, $context);

	# Perform login
	$request = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
   <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
     <command>
       <login>
         <clID>'.$params['Username'].'</clID>
         <pw>'.$params['Password'].'</pw>
         <options>
           <version>1.0</version>
           <lang>en</lang>
         </options>
         <svcs>
           <objURI>urn:ietf:params:xml:ns:obj1</objURI>
           <objURI>urn:ietf:params:xml:ns:obj2</objURI>
           <objURI>urn:ietf:params:xml:ns:obj3</objURI>
           <svcExtension>
             <extURI>http://custom/obj1ext-1.0</extURI>
           </svcExtension>
         </svcs>
       </login>
       <clTRID>'.mt_rand().mt_rand().'</clTRID>
     </command>
   </epp>
');
       logModuleCall('COCCAepp', 'Connect', $xml, $request);
       return $client;
}

function COCCAepp_TransferSync($params) {
	$domainid = $params['domainid'];
	$domain = $params['domain'];
	$sld = $params['sld'];
	$tld = $params['tld'];
	$registrar = $params['registrar'];
	$regperiod = $params['regperiod'];
	$status = $params['status'];
	$dnsmanagement = $params['dnsmanagement'];
	$emailforwarding = $params['emailforwarding'];
	$idprotection = $params['idprotection'];
$domain = "$sld.$tld";
	# Other parameters used in your _getConfigArray() function would also be available for use in this function

	try {
		$client = _COCCAepp_Client();
		# Grab domain info
		$request = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
   <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
     <command>
       <info>
         <domain:info
          xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
				<domain:name hosts="all">'.$sld.'.'.$tld.'</domain:name>
			</domain:info>
		</info>
		<clTRID>'.mt_rand().mt_rand().'</clTRID>
	</command>
</epp>
');

		$doc= new DOMDocument();
		$doc->loadXML($request);
		logModuleCall('COCCAepp', 'TransferSync', $xml, $request);

		$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
		$msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
		# Check result
		if ($coderes == '2303') {
			$values['error'] = "TransferSync/domain-info($domain): Domain not found";
			return $values;
		} else if (!eppSuccess($coderes)) {
			$values['error'] = "TransferSync/domain-info($domain): Code("._COCCAepp_message($coderes).") $msg";
			return $values;
		}

		# Check if we can get a status back
		if ($doc->getElementsByTagName('status')->item(0)) {
			$statusres = $doc->getElementsByTagName('status')->item(0)->getAttribute('s');
			$createdate = substr($doc->getElementsByTagName('crDate')->item(0)->nodeValue,0,10);
			$nextduedate = substr($doc->getElementsByTagName('exDate')->item(0)->nodeValue,0,10);
		} else {
			$values['error'] = "TransferSync/domain-info($domain): Domain not found";
			return $values;
		}

		$values['status'] = $msg;

		# Check status and update
		if ($statusres == "ok") {
			$values['completed'] = true;

		} else {
			$values['error'] = "TransferSync/domain-info($domain): Unknown status code '$statusres'";
		}

		$values['expirydate'] = $nextduedate;

	} catch (Exception $e) {
		$values["error"] = 'TransferSync/EPP: '.$e->getMessage();
		return $values;
	}

	return $values;
}
// we can use COCCAepp_Sync script instaed of using this function
function COCCAepp_Sync($params) {

$sld = $params["sld"];
$tld = $params["tld"];
$domain = "$sld.$tld";	
# Get client instance

# Let's Go...
try {
    $isactive = true;
    $isexpired = false;
    $istransferredAway = false;
    $expireddomain = false;
    $errorMsg = '';
    if (!isset($client)) {
            $client = _COCCAepp_Client();
    }
    # Grab domain info

        # Pull list of domains which are registered using this module
        # Loop with each one
                # Query domain
     
     $request = $client->request($xml='<?xml version="1.0" encoding="UTF-8" standalone="no"?>
      <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
       <command>
        <info>
         <domain:info
          xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
                                <domain:name>'.$domain.'</domain:name>
                        </domain:info>
                </info>
                <clTRID>'.mt_rand().mt_rand().'</clTRID>
        </command>
      </epp>');

                $doc= new DOMDocument();
                $doc->loadXML($request);
                $coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
                $resultpr =  $doc->getElementsByTagName('result')->item(0)->nodeValue;
                $msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
               if (!eppSuccess($coderes)) {
                  $values["error"] = "Sync/domain-info('.$domain.'): Code (" . $coderes . ") " . $msg;
                   return  array (
                            'error' => $msg
                              );

                   $errorMsg =  $values["error"];
                }

                if($coderes == '1000') {
                        if( $doc->getElementsByTagName('status')) {
                                if($doc->getElementsByTagName('status')->item(0)) {
                                    
                                        $statusres = $doc->getElementsByTagName('status')->item(0)->getAttribute('s');
                                        $createdate = substr($doc->getElementsByTagName('crDate')->item(0)->nodeValue,0,10);
                                        $nextduedate = substr($doc->getElementsByTagName('exDate')->item(0)->nodeValue,0,10);
			                if((time()-(60*60*24)) > strtotime($nextduedate)){
                  				$expireddomain = true;
                                               }
            
                 } else {
                     $values["error"] = "Sync/domain-info('.$domain.'): Code (" . $coderes . ") " . $msg;
                                        $errorMsg = "Domain $domain not registered!";
                           return  array (
                            'error' => 'Domain $domain not registered!'
                              );
                                }
                               if($doc->getElementsByTagName('status')->item(1)) {
                                       $statusres2 = $doc->getElementsByTagName('status')->item(1)->getAttribute('s');
                                }
                        }
                 } else {
                       $pendingStatus = 'Pending';
                        if (strcmp($domainStatus, 'pending') == 0) {
                          }
                         elseif  ($resultpr == 'Object does not exist') {
                                 $values["error"] = "Sync/domain-info('.$domain.'): Code (" . $resultpr . ") " . $msg;
                         return  array (
        	            'error' => 'Object does not exist'
	                 );

                               }
                         elseif ($resultpr == 'Authorization error') {
                               $istransferredAway = true;
                           return  array (
                            'transferredAway' => true, // Return true if the domain is transferred out
                            'error' => 'Authorization error.'
                         );

                             }
                            else {

                                  }
                }

                # This is the template we going to use below for our updates
                # Check status and update
                if ($statusres == "ok") {
                    $isactive = true;
                } elseif ($expireddomain == false && $statusres == "inactive" && $statusres2 == "serverHold") {
                    $isactive = false;
                } elseif ($expireddomain == false && $statusres == "inactive" && $statusres2 != "serverHold") {
                    $isactive = true;
                } elseif ($expireddomain == false && $statusres == "serverHold" && $statusres != "inactive"){
                  $isactive = false;
                
                } elseif ( $statusres == "pendingCreate") {
                    $isactive = false;
                } elseif ($statusres == "pendingDelete") {
                    $isexpired = true;
                } elseif ($statusres == "expired" || expireddomain == true ) {
                    $isexpired = true;
                } else {
                    $isexpired = false; 
                 }
        
                 return  array (
                    'expirydate' => $nextduedate, // Format: YYYY-MM-DD
                    'active' => (bool) $isactive , // Return true if the domain is active
                    'expired' => (bool) $isexpired, // Return true if the domain has expired
                    'transferredAway' => (bool) $istransferredAway, // Return true if the domain is transferred out
                 );
   
} catch (Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
}

}


function COCCAepp_RequestDelete($params) {
	$sld = $params['sld'];
	$tld = $params['tld'];
$domain = "$sld.$tld";	
	try {
		$client = _COCCAepp_Client();

		# Request Delete
		$request = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
   <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
     <command>
      <delete>
       <domain:delete xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
        <domain:name>'.$sld.'.'.$tld.'</domain:name>
       </domain:delete>
      </delete>
        <clTRID>'.mt_rand().mt_rand().'</clTRID>
     </command>
    </epp>

');

		# Parse XML result
		$doc = new DOMDocument();
		$doc->loadXML($request);
		logModuleCall('COCCAepp', 'RequestDelete', $xml, $request);

		$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
		$msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;

		# Check result
		if(!eppSuccess($coderes)) {
			$values['error'] = 'RequestDelete/domain-info('.$sld.'.'.$tld.'): Code('._COCCAepp_message($coderes).") $msg";
			return $values;
		}

		$values['status'] = $msg;

	} catch (Exception $e) {
		$values["error"] = 'RequestDelete/EPP: '.$e->getMessage();
		return $values;
	}

	return $values;
}

function COCCAepp_ApproveTransfer($params) {
	$sld = $params['sld'];
	$tld = $params['tld'];
$domain = "$sld.$tld";	
	# 
	try {
		$client = _COCCAepp_Client();

		# Approve Transfer Request
		$request = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
   <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
	<command>
		<transfer op="approve">
			<domain:transfer xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
				<domain:name>'.$sld.'.'.$tld.'</domain:name>
			</domain:transfer>
		</transfer>
		<clTRID>'.mt_rand().mt_rand().'</clTRID>
	</command>
</epp>
');

		# Parse XML result
		$doc = new DOMDocument();
		$doc->loadXML($request);
		logModuleCall('COCCAepp', 'ApproveTransfer', $xml, $request);

		$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
		$msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;

		# Check result
		if(!eppSuccess($coderes)) {
			$values['error'] = 'ApproveTransfer/domain-info('.$sld.'.'.$tld.'): Code('._COCCAepp_message($coderes).") $msg";
			return $values;
		}

		$values['status'] = $msg;

	} catch (Exception $e) {
		$values["error"] = 'ApproveTransfer/EPP: '.$e->getMessage();
		return $values;
	}

	return $values;
}


function COCCAepp_CancelTransferRequest($params) {
	$sld = $params['sld'];
	$tld = $params['tld'];
$domain = "$sld.$tld";	
	try {
		$client = _COCCAepp_Client();

		# Cancel Transfer Request
		$request = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
   <epp xmlns="urn:ietf:params:xml:ns:epp1.0">
	<command>
		<transfer op="cancel">
			<domain:transfer xmlns:domain="urn:ietf:params:xml:ns:domain1.0">
				<domain:name>'.$sld.'.'.$tld.'</domain:name>
			</domain:transfer>
		</transfer>
		<clTRID>'.mt_rand().mt_rand().'</clTRID>
	</command>
</epp>
');

		# Parse XML result
		$doc = new DOMDocument();
		$doc->loadXML($request);
		logModuleCall('COCCAepp', 'CancelTransferRequest', $xml, $request);

		$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
		$msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;

		# Check result
		if(!eppSuccess($coderes)) {
			$values['error'] = 'CancelTransferRequest/domain-info('.$sld.'.'.$tld.'): Code('._COCCAepp_message($coderes).") $msg";
			return $values;
		}

		$values['status'] = $msg;

	} catch (Exception $e) {
		$values["error"] = 'CancelTransferRequest/EPP: '.$e->getMessage();
		return $values;
	}

	return $values;
}


function COCCAepp_RejectTransfer($params) {
	$sld = $params['sld'];
	$tld = $params['tld'];
$domain = "$sld.$tld";	
	try {
		$client = _COCCAepp_Client();

		# Reject Transfer
		$request = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
   <epp xmlns="urn:ietf:params:xml:ns:epp1.0">
	<command>
		<transfer op="reject">
			<domain:transfer xmlns:domain="urn:ietf:params:xml:ns:domain1.0">
				<domain:name>'.$sld.'.'.$tld.'</domain:name>
			</domain:transfer>
		</transfer>
		<clTRID>'.mt_rand().mt_rand().'</clTRID>
	</command>
</epp>
');

		# Parse XML result
		$doc = new DOMDocument();
		$doc->loadXML($request);
		logModuleCall('COCCAepp', 'RejectTransfer', $xml, $request);

		$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
		$msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;

		# Check result
		if(!eppSuccess($coderes)) {
			$values['error'] = 'RejectTransfer/domain-info('.$sld.'.'.$tld.'): Code('._COCCAepp_message($coderes).") $msg";
			return $values;
		}

		$values['status'] = $msg;

	} catch (Exception $e) {
		$values["error"] = 'RejectTransfer/EPP: '.$e->getMessage();
		return $values;
	}

	return $values;
}

function remove_locks($domain, $status) {

		
}

function generateHandle() {
    $stamp = time();
    $shuffled = str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ");
    $randStr = substr($shuffled, mt_rand(0, 45), 5);
    $handle = "$stamp$randStr";
    return $handle;
}

function array_push_assoc($array, $key, $value){
	$array[$key] = $value;
	return $array;
}

function eppSuccess($code) {
	if ($code >= 1000 && $code < 2000) {
            return true;
        }
	return false;
}

function array_empty($a) {
    foreach($a as $e)
        if(!empty($e))
            return false;

    return true;
}

function authKey() {
                $chars = "a0Zb1Yc2Xd3We4Vf5Ug6Th7Si8Rj9Qk8Pl7Om6Nn5Mo4Lp3Kq2Jr1Is0Ht1Gu2Fv3Ew4Dx5Cy6Bz7A";
                $max = strlen($chars) - 1;
                $eppKey = null;
                $i = 0;
                while ($i < 10) {
                        $eppKey .= $chars[mt_rand(0, $max)];
                        ++$i;
                }
                return $eppKey;
        }

function getNID($domain)
{
        require_once dirname(__FILE__) . '/../../../init.php';

 
        $queryresult = mysql_query("SELECT  value  FROM tbldomainsadditionalfields WHERE domainid = (SELECT id FROM tbldomains WHERE domain = '" . $domain . "')");
        if (!$queryresult) {
             die('Query failed: ' . mysql_error());
          }
       $temparray =  array();
       $getvalue=false;
       while($data = mysql_fetch_array($queryresult)) {
                 
                 $dataValue=trim($data['value']);
                 if ($dataValue == 'IND' || $dataValue =='ORG'){
                  $additinaldata['Type']= $dataValue;  
                 }else{
                   if (!empty($dataValue)) {         
                    $additinaldata['Value']= $dataValue ;
                    break;
                }
            }
        }
	return $additinaldata;
}


