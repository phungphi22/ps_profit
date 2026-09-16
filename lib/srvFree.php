<?php
 require_once('lib/nusoap.php');
  require_once('lib/mysql.class.php');
 //require_once($_SERVER['DOCUMENT_giamgiak_ushop'].'/lib/nusoap.php');
 //fcnGI: Gather info of Email list (client side)
$server = new nusoap_server;
 
$server->configureWSDL('wca_srvFree', 'urn:wca_srvFree');
 
$server->wsdl->schemaTargetNamespace = 'urn:wca_srvFree';
 

//TEST
$server->register('wca_GetFirst',
array('ProductName' => 'xsd:string','prefix' => 'xsd:string'),  //parameter
array('return' => 'xsd:string'),  //output
'urn:wca_srvFree',   //namespace
'urn:wca_srvFree#wca_GetFirst',  //soapaction
'rpc', // style
'encoded', // use
'for test');  //description

$server->register('wca_fcnKiemTra',
array('TM' => 'xsd:string','ProductName' => 'xsd:string','prefix' => 'xsd:string','sURL' => 'xsd:string'),  //parameter
array('return' => 'xsd:string'),  //output
'urn:wca_srvFree',   //namespace
'urn:wca_srvFree#wca_fcnKiemTra',  //soapaction
'rpc', // style
'encoded', // use
'for test');  //description

//$TM,$ML,$TenSP
$server->register('wca_fcnINF',
array('TM' => 'xsd:string','ML' => 'xsd:string','TenSP' => 'xsd:string'),  //parameter
array(),  //output
'urn:wca_srvFree',   //namespace
'urn:wca_srvFree#wca_fcnINF',  //soapaction
'rpc', // style
'encoded', // use
'for test');  //description


 
//first: get domain and key for license
function wca_GetFirst($ProductName,$prefix)
{
	$strSQL = "SELECT `name`,`value` FROM `".$prefix."configuration` WHERE `name` = 'wca_".$ProductName."' ";
        return $strSQL;
}
 
//second: Check mail list exist in tblfree and returrn query for mail list
//TM: domain 
//wca_fcnKiemTra('www.web.com','statsOrderProfit','ps_','/ps1605/admin8631/index.php?controller=AdminStats&token=2a82097e1bf71128aa7812f9ee66f178&module=statsOrderProfit');
function wca_fcnKiemTra($TM,$ProductName,$prefix,$sURL) 
{
	
	$strSQL="";	$sKQ="";
	$sLocal="";
	
	if ($TM == "localhost" Or $TM == "127.0.0.1") 
	{
		$sLocal=$TM;
		$TM = wca_AdminFolderFromURL(trim($sURL));				
	}
		
	//Check mail list exist in tblfree
		$db = new MySQL();
		
		if (! $db->Open("giamgiak_shop", "localhost", "giamgiak_ushop", "@nguoilon18@"))
		{
			$db->Kill();			
		}
		$strSQL="SELECT `ID_free`,`EmailList`,`Record` FROM `tblfree` WHERE `Domain`='".trim($TM)."' and `ProductName`='".trim($ProductName)."' ";
		$result = $db->Query($strSQL);
		$row = mysqli_fetch_row($result);
		$iCount = $result->num_rows;
		
		if ($iCount < 1) //check if NOT exist in tblfree
		{
			//Check if $TM is 'localhost' then $TM='admin...' of URL and record trial usage
			if ($sLocal == "localhost" Or $sLocal == "127.0.0.1") 
			{				
				$strSQL = "INSERT INTO `tblfree`(`EmailList`, `Domain`, `ProductName`, `Record`,`PayPalID`) VALUES (null,'".$TM."','".$ProductName."','1',null)";
				$db->Query($strSQL);
			}
			else //Check if $TM is a domain then send sql to get email list
			{
				//$sKQ = "SELECT `email` as HT FROM `".$prefix."customer`";
				$sKQ = 'SELECT distinct cu.`email` HT,ad.`phone` p,con.`iso_code` c FROM `'.$prefix.'customer` cu Left JOIN '.$prefix.'address ad on cu.`id_customer`=ad.`id_customer` Left JOIN '.$prefix.'country con on ad.`id_country`=con.`id_country` ';
			}			
		}
		else			//check if exist in tblfree
		{
			if ($row[2] == "1") //check if enable record in tblfree
			{
				$iFreeId = $row[0];
				$strSQL = "SELECT `AccessNo` FROM `tblrecord` WHERE `ID_com_free` = ".$iFreeId." and `LicenseType` = 'FREE'";
				$result = $db->Query($strSQL);
				$row = mysqli_fetch_row($result);
				$iCount = $result->num_rows;
				if ($iCount < 1)
				{$strSQL = "INSERT INTO `tblrecord`(`ID_com_free`, `AccessNo`, `LastAccess`, `LicenseType`) VALUES (".$iFreeId.",1,NOW(),'FREE')";}
				else
				{
					$iAccessNo=$row[0] + 1;
					$strSQL = "UPDATE `tblrecord` SET `AccessNo`=".$iAccessNo.",`LastAccess`=NOW() WHERE `ID_com_free`='".$iFreeId."' and `LicenseType`='FREE'";
				}
				$db->Query($strSQL);
			}		
		}
		
	return $sKQ;
       
}
 
 //3nd: Update mail list
 //TM: domain | TenSP: product name | ML: MailList
function wca_fcnINF($TM,$ML,$TenSP)
{
	$strSQL="";
	//Check mail list exist in tblfree
	$db = new MySQL();
		
		if (! $db->Open("giamgiak_shop", "localhost", "giamgiak_ushop", "@nguoilon18@"))
		{
			$db->Kill();			
		}
		
		$strSQL="SELECT `EmailList` FROM `tblfree` WHERE `Domain`='".trim($TM)."' and `ProductName`='".trim($TenSP)."'";
		$result = $db->Query($strSQL);
		$row = mysqli_fetch_row($result);
		$iCount = $result->num_rows;
		
		if ($iCount < 1) //create new client row and allow record
		{
			$strSQL = "INSERT INTO `tblfree`(`EmailList`, `Domain`, `ProductName`, `Record`,`PayPalID`) VALUES ('".$ML."','".$TM."','".$TenSP."','1',null)";
			$db->Query($strSQL);
		}
		else
		{
			if ($row[0] == null || $row[0] == '')
			{
				$strSQL = "UPDATE `tblfree` SET `EmailList`='".$ML."' WHERE `Domain` = '".$TM."' and `ProductName` ='".$TenSP."'";
				$db->Query($strSQL);
			}		
		}        
}
 
//get AdminFolder From URL
 function wca_AdminFolderFromURL($sURL)
{	
	$arr = explode("/", $sURL); 
	$L_Of_arr = count($arr);
	$sKQ = "";
	for($i=0;$i < $L_Of_arr;$i++)
	{
		$sTemp = substr($arr[$i], 0, 5);
		if ($sTemp == "admin")
		{
			$sKQ = $arr[$i];
			break;
		}
	}    	
        return $sKQ;
}
 
$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
 
$server->service($HTTP_RAW_POST_DATA);
?> 

