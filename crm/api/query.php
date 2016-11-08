<?php
////////////////////////////////
//This is the REST web service that will give back info from the Seltzer DB.
////////////////////////////////
//Josh Pritt  ramgarden@gmail.com
//Created: February 17, 2015

//This function cleans the input from
// malicious strings and returns the clean
// version.  There might be a better way
// to do this but this works for the most
// part.  :/
function testInput($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}

// This function will take a pin string and compare agianst
// the last four numbers of the contact's primary phone
// Returns true or false
function testPin($cid,$pin=0000) {
  
  $memberdata = member_data(array('cid'=>$memberID));
  $memberPhone = substr($memberActive[0]['contact']['phone'], -4);
  if ($pin == $memberPhone) {
    $result = true;
  } else {
    $result = false;
  }
  
  return $result;
}

//This function takes in an RFID alpha numeric string 
//and a comma separated list of field names to return.
//The RFID belongs to the member and the fields are the 
//ones you want to have back.
// function getMemberInfoByRFID($rfid,$fieldNames)
// {  
//   require('db.inc.php');
  
//   $rfid = testInput($rfid);
//   $fieldNames = testInput($fieldNames);
  
//   $memberInfo = array();

//   if($fieldNames == "")
//   {
//   	$fieldNames = "*";
//   }

//   //first build the query
//   $query = "SELECT " . $fieldNames . " FROM 
// 			(
// 			`key` k
// 			LEFT JOIN  `contact` c ON k.cid = c.cid
// 			)
// 			WHERE k.serial = '" . $rfid . "'";
  
//   //then get the matching member
//   $result = mysqli_query($con, $query) 
// 		  or die(json_encode(array("getMemberInfoByRFIDQueryERROR" => mysql_error())));
 
//   //then stick the member info into an assoc array
//   $memberInfo = mysqli_fetch_assoc($result);    

//   return $memberInfo;
// }

// //This function returns the unix timestamp of the last payment made
// // for the member with the given RFID.
// function getMemberLastPaymentTimestamp($rfid)
// { 
//   require('db.inc.php');
  
//   $rfid = testInput($rfid);
  
//   $memberInfo = array();

//   //first see if the key is even in the system.
//   //We could just do a big join all at once but we wouldn't know
//   // if the key or the member was not found, etc.
//   $query = "SELECT cid FROM `key` WHERE serial = '" . $rfid . "'";
  
//   //then get the matching member
//   $result = mysqli_query($con, $query) 
// 		  or die(json_encode(array("getKeyQueryERROR"=>mysql_error())));
		  
//   $keyRow = mysqli_fetch_assoc($result);    

//   if($keyRow == 0)
//   {
//   	return array("ERROR"=>"No key found for RFID: " . $rfid);
//   }

//   //then get the last payment entered for this member
//   $query = "SELECT UNIX_TIMESTAMP(MAX(date)) FROM payment WHERE value > 0 and credit = " . $keyRow['cid'];
  
//   $result = mysqli_query($con, $query) 
// 		  or die(json_encode(array("getPaymentQueryERROR"=>mysql_error())));
 
//   $paymentInfo = mysqli_fetch_array($result);
  
//   $timestamp = $paymentInfo[0];
  
//   if($timestamp == NULL)
//   {
//   	return array("ERROR"=>"No payments found for key owner.");
//   }
  
//   $iso8601 = date('c', $timestamp);
  
//   $jsonResponse = array("timestamp"=>$timestamp,"iso8601"=>$iso8601);
//   return $jsonResponse;
// }

//action=getRFIDWhitelist
//returns JSON array of all key serial values for all active members
function getRFIDWhitelist()
{
	require('db.inc.php');
	
	$whiteList = array();
	
	//Get all active members
	$activeMembers = member_data(array('filter'=>array('active'=>true)));

	// for each active member, find their active keys
	foreach ($activeMembers as $member) {
		$cid = $member['cid'];
		$firstName = $member['contact']['firstName'];
		$lastName = $member['contact']['lastName'];
    // get active keys for this user
    $query = "SELECT serial FROM `key` WHERE cid = " . $cid . " AND end IS NULL";
    $result = mysqli_query($con, $query) 
		  		or die(json_encode(array("getRFIDWhitelistQueryERROR"=>mysqli_error($con))));
    $r = mysqli_fetch_assoc($result);
    foreach ($r as $serial) {
				$whiteList[] = array("firstName"=>$firstName,"lastName"=>$lastName,"serial"=>$serial);	
			}
    }
	
	return $whiteList;
}

//action=doorLockCheck&rfid=<scanned RFID>
//returns JSON string TRUE if key is valid and owner is active.
// FALSE otherwise.
function doorLockCheck($rfid, $pin = NULL)
{
	require('db.inc.php');
	
	$rfid = testInput($rfid);
	
	//get the key owner and their current membership plan
	$query = "SELECT cid
  FROM `key`
  where serial = '" . $rfid . "'
  AND end IS NULL";
				
	$result = mysqli_query($con, $query) 
		  or die(json_encode(array("doorLockCheckQueryERROR"=>mysqli_error($con))));
 
 	//if no rows returned then that key wasn't even found in the DB
 	if(mysqli_num_rows($result) == 0)
 	{
 		$jsonResponse = array("False");
	}	else {		
	 	$row = mysqli_fetch_assoc($result); 	
	 	$memberID = $row["cid"];
    $memberActive = member_data(array('cid'=>$memberID,'filter'=>array('active'=>true)));
    if ($memberActive) {
      if(isset($pin)) {
        // a pin was submitted, check to see if it's correct
        // correct is the last four of the primary phone number on file
        $jsonResponse = testPin($row['cid'],$pin) ? 'True' : 'False';
      } else {
        $jsonResponse = array("False");
      }
    }
	}
	return $jsonResponse;
}


//////////////////////////////////////
//other functions for service go here. 
// don't forget to add the action to the 
// $possible_url array below!!!!!
//You will then have to add the entry for
// the switch case below as well.
//////////////////////////////////////


$possible_url = array("getMemberInfoByRFID", "getMemberLastPaymentTimestamp", "getRFIDWhitelist", "otherFunctionName",
	"doorLockCheck");

$value = "An error has occurred";

if (isset($_GET["action"]) && in_array($_GET["action"], $possible_url))
{
  switch ($_GET["action"])
    {
    //   case "getMemberInfoByRFID":
    //     $value = getMemberInfoByRFID($_GET['rfid'], $_GET['fieldNames']);
    //     break;
  	 // case "getMemberLastPaymentTimestamp":
    //     $value = getMemberLastPaymentTimestamp($_GET['rfid']);
    //     break;
      case "getRFIDWhitelist":
        $value = getRFIDWhitelist($_GET['fields']);
        break;
      case "doorLockCheck":
        $value = doorLockCheck($_GET['rfid'],$_GET['pin']);
        break;
      case "get_app":
        if (isset($_GET["id"]))
          $value = get_app_by_id($_GET["id"]);
        else
          $value = "Missing argument";
        break;
    }
}

//return JSON object as the response to client
exit(json_encode($value));
?>