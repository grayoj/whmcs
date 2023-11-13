<?php
# Bring in Database Constants
require_once dirname(__FILE__) . '/../../../init.php';
# Setup include dir
$include_path = ROOTDIR . '/modules/registrars/COCCAepp';
set_include_path($include_path . PATH_SEPARATOR . get_include_path());
# Include EPP stuff we need
require_once 'COCCAepp.php';
# Additional functions we need
require_once ROOTDIR . '/includes/functions.php';
# Include registrar functions aswell
require_once ROOTDIR . '/includes/registrarfunctions.php';
require_once 'Net/EPP/Frame.php';
require_once 'Net/EPP/Frame/Command.php';
require_once 'Net/EPP/ObjectSpec.php';
use Illuminate\Database\Capsule\Manager as Capsule;

# Grab module parameters
$params = getregistrarconfigoptions('COCCAepp');
echo("|---------------COCCAepp Poll Report------------------------\n");
echo("|-----------------------------------------------------------\n");

# Let's Go...

 try {
        $client = _COCCAepp_Client();
        # Loop with message queue
        while (!$last) {
                # Request messages
                $request = $client->request('
                <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
                <command>
                <poll op="req"/>
                </command>
                </epp>
                ');
                # Decode response
                $doc= new DOMDocument();
                $doc->loadXML($request);
                # Pull off code
                $coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
                if ($coderes == 1301 || $coderes == 1300) {
                        $msgs = $doc->getElementsByTagName('msg');
                        for ($m = 0; $m < $msgs->length; $m++) {
                                        echo "CODE: $coderes, MESSAGE: '".$msgs->item($m)->textContent."'\n";
                                        // Messages to ignore
                                        $ignored = array(
                                                'Command completed successfully; ack to dequeue',
                                                'Command completed successfully; no messages'
                                        );
                                                
                                                $message = mysql_real_escape_string(trim($msgs->item($m)->textContent));
						//Get first part of Message
						$poll_msg = explode('\\n', $message);
                                                $doc2= new DOMDocument();
                                                $doc2->loadXML($poll_msg[0]);
                                       		$domname = $doc2->getElementsByTagName('name')->item(0)->nodeValue;
                                                $updates4dom = $doc2->getElementsByTagName('change')->item(0)->nodeValue;
                                                $detailsvar = $doc2->getElementsByTagName('details')->item(0)->nodeValue;
						echo ("|'".$domname."' EPP POLL Message was: '".$detailsvar."'\n");
						echo ("|-----------------------------------------------------------\n");
                                                
                       if ($detailsvar == "Domain deleted" || $detailsvar == "Domain Transferred Away" || $detailsvar == "Domain transfer approved on your behalf.") {
                                                // echo ("SQLQUERY FOR DELETION OR  TRANSFER");
//                                                    $sqlquery = "DELETE FROM tbldomains WHERE domain=$domname";
                                                      $sqlquery = "DELETE FROM tbldomains WHERE domain='".$domname."'";
//                                                    echo mysql_errno(mysql_query($sqlquery)) . ": " . mysql_error(mysql_query($sqlquery)) . "\n";

                                                  //    echo ("$sqlquery");
                                                 if ( mysql_query($sqlquery))  {
                                                      echo ("|NOTICE: '".$domname."' Has been DELETED FROM WHMCS \n");
                                                echo ("|-----------------------------------------------------------\n");

                                                                            }
                                                    
     					          
                                         }

                        }
                        # This is the last one
                        if ($coderes == 1300) {
                                $last = 1;
                        }
                        $msgq = $doc->getElementsByTagName('msgQ')->item(0);
                        if ($msgq) {
                                $msgid = $doc->getElementsByTagName('msgQ')->item(0)->getAttribute('id');
                                try {
                                        $res = COCCAepp_ACKPOLL($client,$msgid);
                                } catch (Exception $e) {
                                        echo("ERROR: ".$e->getMessage()."\n");
                                }
                        }
                } else {
                        $msgid = $doc->getElementsByTagName('svTRID')->item(0)->textContent;
                        $msgs = $doc->getElementsByTagName('msg');
                        for ($m = 0; $m < $msgs->length; $m++) {
                                echo "\n";
                                        echo "UNKNOWN CODE: $coderes, MESSAGE: '".$msgs->item($m)->textContent."', ID: $msgid\n";
                                echo $request;
                                echo "\n\n";
                        }
                }
        }
} catch (Exception $e) {
        echo("ERROR: ".$e->getMessage(). "\n");
        exit;
}


#ACKPOLL
function COCCAepp_ACKPOLL($client,$msgid) {

        $request = $client->request($xml = '
        <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
        <command>
                <poll op="ack" msgID="'.$msgid.'"/>
 </command>
        </epp>
');
        # Decipher XML
        $doc = new DOMDocument();
        $doc->loadXML($request);
        logModuleCall('CoCCAepp', 'ackpoll', $xml, $request);
        $coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
        $msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
        # Check result
        if($coderes != '1301' && $coderes != '1300' && $coderes != 1000) {
                throw new Exception("ackpoll/poll-ack($id): Code ($coderes) $msg");
        }

}


