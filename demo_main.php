<?php
header("Content-type: application/json");
session_start();

//Global Variables
$TOILET_ID_LEN=4;
$PROVIDER_ID_LEN=4;
$PROVIDER_PIN_LEN=4;
$TICKET_ID_LEN=8;
$URL='http://sanitationimpact.org';
$USER_PASS='root:root123';

//local variables
$rand_num=0;
$temp_provider="provider";
$temp_user="user";
$user_text="";
$provider_text="";


// to get json data from a url
function curl_get($url,$user_cred)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERPWD, $user_cred);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}

//to post json data to a url
function curl_post($url,$post_parameters,$user_cred)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_USERPWD, $user_cred);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS,$post_parameters);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$output = curl_exec ($ch);
	curl_close ($ch);
	return $output;
}


require_once("response.php");
$r = new Response();
$r->setFiller("yes");

$ufp= fopen($temp_user, 'a+'); 
$pfp= fopen($temp_provider, 'a+');
 
if($_REQUEST['event']== "NewCall" ) 
{
	
	$_SESSION['caller_number']=$_REQUEST['cid'];
	$_SESSION['kookoo_number']=$_REQUEST['called_number']; 
	$_SESSION['session_id']   = $_REQUEST['sid'];
    $_SESSION['next_goto']='Welcome';
    $user_text=$_REQUEST['cid'];
} 

if ($_REQUEST['event']=="Disconnect" || $_REQUEST['event']=="Hangup" )
{
 	$fp=fopen($temp_user,'r');
	$string=fgets($fp);
	$values = explode(" ", $string);
	$count=count($values);
	$phone_number='';
	$toilet_id='';
	$bool=0;
	$prob_cat[20];
	$prob[20];
	$c=0;
	for($i=0;$i<$count;$i++)
	{
	    if($values[$i]!='')
	    {
	        if(strlen($values[$i])==10)
	        {
	            $phone_number=$values[$i];
	        }
	        elseif(strlen($values[$i])==$TOILET_ID_LEN)
	        {
	            $toilet_id=$values[$i];
	        }
	        elseif ($bool==0)
	        {
	            $prob_cat[$c]=$values[$i];
	            $bool=1;
	        }
	        elseif ($bool==1) {
	            $prob[$c]=$values[$i];
	            $c++;
	            $bool=0;
	        }

	    }
	}
	$pc_len=count($prob_cat);
	$p_len=count($prob);
	$min=min($pc_len,$p_len);
	for($i=0;$i<$min;$i++)
	{
	    curl_post('http://sanitationimpact.org/reporting/report_problem/','phone_number='.$phone_number.'&toilet_id='.$toilet_id.'&category_index='.$prob_cat[$i].'&problem_index='.$prob[$i],$USER_PASS);
	}

	$ufp= fopen($temp_user, 'w');
	$pfp= fopen($temp_provider, 'w');
} 
if($_SESSION['next_goto']=='Welcome')
{
 	$collectInput = New CollectDtmf();
	$collectInput->addPlayText('Welcome to Mobile Sanitation',4);
	$collectInput->addPlayText('Press 1 to report a problem',4);
	$collectInput->addPlayText('Press 2 for provider',4);
	$collectInput->setMaxDigits('1'); 
	$collectInput->setTimeOut('4000');  
	$r->addCollectDtmf($collectInput);
    $_SESSION['next_goto']='GetDetails';
}
else if($_REQUEST['event'] == 'GotDTMF' && $_SESSION['next_goto']=='GetDetails'){
 	if($_REQUEST['data']=='1')
 	{
 		$collectInput = New CollectDtmf();
		$collectInput->addPlayText('Enter the toilet number',4);
		$collectInput->setMaxDigits($TOILET_ID_LEN); 
		$collectInput->setTimeOut('4000');  
		$r->addCollectDtmf($collectInput);
	    $_SESSION['next_goto']='GetProblem';
 	}
 	else if($_REQUEST['data']=='2')
 	{
 		$collectInput = New CollectDtmf();
		$collectInput->addPlayText('Enter the Provider i d',4);
		$collectInput->setMaxDigits($PROVIDER_ID_LEN); 
		$collectInput->setTimeOut('4000');  
		$r->addCollectDtmf($collectInput);
	    $_SESSION['next_goto']='GetProviderPass';
 	}
 	else
 	{
 		$collectInput = New CollectDtmf();
		$collectInput->addPlayText('Incorrect Option',4);
		$collectInput->addPlayText('Press 1 to report a problem',4);
		$collectInput->addPlayText('Press 2 to for provider',4);
		$collectInput->setMaxDigits('1'); 
		$collectInput->setTimeOut('4000');  
		$r->addCollectDtmf($collectInput);
	    $_SESSION['next_goto']='GetDetails';
 	}
 	
}

else if($_REQUEST['event'] == 'GotDTMF' && $_SESSION['next_goto'] == 'GetProblem' )
{
	if($_REQUEST['data']=='' || strpos($_REQUEST['data'], '#')!==false || strpos($_REQUEST['data'], '*') !==false )
	{
		$collectInput = New CollectDtmf();
		$collectInput->addPlayText('Incorrect Toilet Number.',4);
		$collectInput->addPlayText('Enter the toilet number',4);
		$collectInput->setMaxDigits($TOILET_ID_LEN); 
		$collectInput->setTimeOut('4000');  
		$r->addCollectDtmf($collectInput);
	    $_SESSION['next_goto']='GetProblem';
	}
	else
	{
		$json_data=json_decode(curl_post($URL.'/reporting/is_valid_toilet/','toilet_id='.$_REQUEST['data'],$USER_PASS),true);
		if($json_data[success])
		{
		    $user_text=$_REQUEST['data'];
		    $collectInput = New CollectDtmf();
			$collectInput->addPlayText('Press 1 for Unclean Toilet. Press 2 for No Water. Press 3 for Toilet Broken or Needs Repair. Press 4 for Pit Problem.',4);
			$collectInput->setMaxDigits('1'); 
			$collectInput->setTimeOut('4000');  
			$r->addCollectDtmf($collectInput);
		    $_SESSION['next_goto']='Details';
		}
		else
		{
		    $collectInput = New CollectDtmf();
			$collectInput->addPlayText('Incorrect Toilet Number.',4);
			$collectInput->addPlayText('Enter the toilet number',4);
			$collectInput->setMaxDigits($TOILET_ID_LEN); 
			$collectInput->setTimeOut('4000');  
			$r->addCollectDtmf($collectInput);
		    $_SESSION['next_goto']='GetProblem';
		}
		
	}
}
else if($_REQUEST['event'] == 'GotDTMF' && $_SESSION['next_goto'] == 'Details' )
{
	if($_REQUEST['data']==''||$_REQUEST['data']=='#'||$_REQUEST['data']=='*')
	{
		
		$collectInput = New CollectDtmf();
		$collectInput->addPlayText('Please Choose from the list of problems.',4);
		$collectInput->addPlayText('Press 1 for Unclean Toilet. Press 2 for No Water. Press 3 for Toilet Broken or Needs Repair. Press 4 for Pit Problem.',4);
		$collectInput->setMaxDigits('1'); 
		$collectInput->setTimeOut('4000');  
		$r->addCollectDtmf($collectInput);
	    $_SESSION['next_goto']='Details';
	}
	else
	{
		
		if($_REQUEST['data']=='1')
		{
			$user_text=$_REQUEST['data'];
			$collectInput = New CollectDtmf();
			$collectInput->addPlayText('Press 1 for pile of feces in toilet bowl. Press 2 for urine all over the toilet bowl. Press 3 for garbage or insects or other pollutants founds in the toilet. Press 4 for entire toilet complex not clean or foul smell.',4);
			$collectInput->setMaxDigits('1'); 
			$collectInput->setTimeOut('4000');  
			$r->addCollectDtmf($collectInput);
		    $_SESSION['next_goto']='Finished';
		}
		else if($_REQUEST['data']=='2')
		{
			$user_text=$_REQUEST['data'];
			$collectInput = New CollectDtmf();
			$collectInput->addPlayText('Press 1 for Water tank not filled. Press 2 for No water supply available . Press 3 for have to get your own water.',4);
			$collectInput->setMaxDigits('1'); 
			$collectInput->setTimeOut('4000');  
			$r->addCollectDtmf($collectInput);
		    $_SESSION['next_goto']='Finished';
		}
		else if($_REQUEST['data']=='3')
		{
			$user_text=$_REQUEST['data'];
			$collectInput = New CollectDtmf();
			$collectInput->addPlayText('Press 1 for Flush broken. Press 2 for No light . Press 3 for Door broken or damaged. Press 4 No ventilation or poor ventilation. Press 5 Broken ceiling or flooring. Press 6 broken pit.',4);
			$collectInput->setMaxDigits('1'); 
			$collectInput->setTimeOut('4000');  
			$r->addCollectDtmf($collectInput);
		    $_SESSION['next_goto']='Finished';
		}
		else if($_REQUEST['data']=='4')
		{
			$user_text=$_REQUEST['data'];
			$detail_id = $_REQUEST['data'];
			$collectInput = New CollectDtmf();
			$collectInput->addPlayText('Press 1 for pit not emptied.',4);
			$collectInput->setMaxDigits('1'); 
			$collectInput->setTimeOut('4000');  
			$r->addCollectDtmf($collectInput);
		    $_SESSION['next_goto']='Finished';
		}
		else
		{
			$collectInput = New CollectDtmf();
			$collectInput->addPlayText('Please Choose from the list of problems.',4);
			$collectInput->addPlayText('Press 1 for Unclean Toilet. Press 2 for No Water. Press 3 for Toilet Broken or Needs Repair. Press 4 for Pit Problem.',4);
			$collectInput->setMaxDigits('1'); 
			$collectInput->setTimeOut('4000');  
			$r->addCollectDtmf($collectInput);
		    $_SESSION['next_goto']='Details';
		}

	}
}
else if($_REQUEST['event'] == 'GotDTMF' && $_SESSION['next_goto'] == 'Finished' )
{
	if($_REQUEST['data']==''||$_REQUEST['data']=='#'||$_REQUEST['data']=='*')
	{
		
		$collectInput = New CollectDtmf();
		$collectInput->addPlayText('Please Choose from the list of problems.',4);
		$collectInput->addPlayText('Press 1 for Unclean Toilet. Press 2 for No Water. Press 3 for Toilet Broken or Needs Repair. Press 4 for Pit Problem.',4);
		$collectInput->setMaxDigits('1'); 
		$collectInput->setTimeOut('4000');  
		$r->addCollectDtmf($collectInput);
	    $_SESSION['next_goto']='Details';
	}
	else
	{
		$user_text=$_REQUEST['data'];
		$collectInput = New CollectDtmf();
		$collectInput->addPlayText('Press 1 to report another problem. Press 2 to exit',4);
		$collectInput->setMaxDigits('1'); 
		$collectInput->setTimeOut('4000');  
		$r->addCollectDtmf($collectInput);
	    $_SESSION['next_goto']='Quit';
	}
}
else if($_REQUEST['event'] == 'GotDTMF' && $_SESSION['next_goto'] == 'Quit' )
{
	if($_REQUEST['data']=='1')
	{
		$collectInput = New CollectDtmf();
		$collectInput->addPlayText('Press 1 for Unclean Toilet. Press 2 for No Water. Press 3 for Toilet Broken or Needs Repair. Press 4 for Pit Problem.',4);
		$collectInput->setMaxDigits('1'); 
		$collectInput->setTimeOut('4000');  
		$r->addCollectDtmf($collectInput);
	    $_SESSION['next_goto']='Details';
	}
	else if($_REQUEST['data']=='2')
	{
		$r->addPlayText('Thank you for callling Mobile Sanitation',4);
		$r->addHangup();
		
	}
	else
	{
		$collectInput = New CollectDtmf();
		$collectInput->addPlayText('Please Choose a Valid option.',4);
		$collectInput->addPlayText('Press 1 to report another problem. Press 2 to exit',4);
		$collectInput->setMaxDigits('1'); 
		$collectInput->setTimeOut('4000');  
		$r->addCollectDtmf($collectInput);
	    $_SESSION['next_goto']='Quit';
	}
}
else if($_REQUEST['event'] == 'GotDTMF' && $_SESSION['next_goto']=='GetProviderPass'){
	if(strlen($_REQUEST['data'])==$PROVIDER_ID_LEN && strpos($_REQUEST['data'], '#')==false && strpos($_REQUEST['data'], '*') ==false)
	{
 		$json_data=json_decode(curl_post($URL.'/provider/is_valid_provider/','provider_id='.$_REQUEST['data'],$USER_PASS),true);
		if($json_data[success])
		{
		    $provider_text=$_REQUEST['data'];
		    $collectInput = New CollectDtmf();
			$collectInput->addPlayText('Enter Provider Pin',4);
			$collectInput->setMaxDigits($PROVIDER_PIN_LEN); 
			$collectInput->setTimeOut('4000');  
			$r->addCollectDtmf($collectInput);
		    $_SESSION['next_goto']='GetTicket';
		}
		else
		{
		    $collectInput = New CollectDtmf();
	 		$collectInput->addPlayText('Incorrect Provider i d',4);
			$collectInput->addPlayText('Enter the Provider i d',4);
			$collectInput->setMaxDigits($PROVIDER_ID_LEN); 
			$collectInput->setTimeOut('4000');  
			$r->addCollectDtmf($collectInput);
		    $_SESSION['next_goto']='GetProviderPass';
		}


 	}
 	else
 	{
 		$collectInput = New CollectDtmf();
 		$collectInput->addPlayText('Incorrect Provider i d',4);
		$collectInput->addPlayText('Enter the Provider i d',4);
		$collectInput->setMaxDigits($PROVIDER_ID_LEN); 
		$collectInput->setTimeOut('4000');  
		$r->addCollectDtmf($collectInput);
	    $_SESSION['next_goto']='GetProviderPass';
 	}
}
else if($_REQUEST['event'] == 'GotDTMF' && $_SESSION['next_goto']=='GetTicket'){
	if(strlen($_REQUEST['data'])==$PROVIDER_PIN_LEN && strpos($_REQUEST['data'], '#')==false && strpos($_REQUEST['data'], '*') ==false)
	{
 		$tempfp=fopen('provider','r');
 		$provider_id=fgets($tempfp);
 		while($provider_id==0)
 		{
 			$provider_id=fgets($tempfp);
 		}
 		fclose($tempfp);
 		$provider_id = trim(preg_replace('/\s\s+/', ' ', $provider_id));
 		$json_data=json_decode(curl_post($URL.'/provider/is_valid_provider_pin_code/','provider_id='.$provider_id.'&pin_code='.$_REQUEST['data'],$USER_PASS),true);
		if($json_data[success])
		{
		    $provider_text=$_REQUEST['data'];
		    $collectInput = New CollectDtmf();
			$collectInput->addPlayText('Enter Ticket i d',4);
			$collectInput->setMaxDigits($TICKET_ID_LEN); 
			$collectInput->setTimeOut('4000');  
			$r->addCollectDtmf($collectInput);
		    $_SESSION['next_goto']='CheckFix';
		}
		else
		{
		    $collectInput = New CollectDtmf();
	 		$collectInput->addPlayText('Incorrect Provider Pin',4);
			$collectInput->addPlayText('Enter Provider Pin',4);
			$collectInput->setMaxDigits($PROVIDER_PIN_LEN); 
			$collectInput->setTimeOut('4000');  
			$r->addCollectDtmf($collectInput);
		    $_SESSION['next_goto']='GetTicket';
		}

 		
 	}
 	else
 	{
 		$collectInput = New CollectDtmf();
 		$collectInput->addPlayText('Incorrect Provider Pin',4);
		$collectInput->addPlayText('Enter Provider Pin',4);
		$collectInput->setMaxDigits($PROVIDER_PIN_LEN); 
		$collectInput->setTimeOut('4000');  
		$r->addCollectDtmf($collectInput);
	    $_SESSION['next_goto']='GetTicket';
 	}
}
else if($_REQUEST['event'] == 'GotDTMF' && $_SESSION['next_goto']=='CheckFix')
{
	if($_REQUEST['data']=='' || strpos($_REQUEST['data'], '#')!==false || strpos($_REQUEST['data'], '*') !==false )
	{
 		$collectInput = New CollectDtmf();
		$collectInput->addPlayText('Incorrect Ticket i d',4);
		$collectInput->addPlayText('Enter Ticket i d',4);
		$collectInput->setMaxDigits($TICKET_ID_LEN); 
		$collectInput->setTimeOut('4000');  
		$r->addCollectDtmf($collectInput);
	    $_SESSION['next_goto']='CheckFix';
 	}
 	else
 	{
 		$tempfp=fopen('provider','r');
 		$provider_id=fgets($tempfp);
 		while($provider_id==0)
 		{
 			$provider_id=fgets($tempfp);
 		}
 		$provider_id = trim(preg_replace('/\s\s+/', ' ', $provider_id));
 		fclose($tempfp);
 		$json_data=json_decode(curl_post($URL.'/reporting/is_valid_provider_ticket/','provider_id='.$provider_id.'&ticket_id='.$_REQUEST['data'],$USER_PASS),true);
 		if($json_data[success])
		{
	 		$provider_text=$_REQUEST['data'];
	 		$collectInput = New CollectDtmf();
			$collectInput->addPlayText('Press 1 for problem fixed. Press 2 for problem not fixed',4);
			$collectInput->setMaxDigits('1'); 
			$collectInput->setTimeOut('4000');  
			$r->addCollectDtmf($collectInput);
		    $_SESSION['next_goto']='ReportFix';
		}
		else
		{
			$collectInput = New CollectDtmf();
			$collectInput->addPlayText('Incorrect Ticket i d',4);
			$collectInput->addPlayText('Enter Ticket i d',4);
			$collectInput->setMaxDigits($TICKET_ID_LEN); 
			$collectInput->setTimeOut('4000');  
			$r->addCollectDtmf($collectInput);
		    $_SESSION['next_goto']='CheckFix';
		}
 	}
}

else if($_REQUEST['event'] == 'GotDTMF' && $_SESSION['next_goto'] == 'ReportFix' )
{
	if($_REQUEST['data']=='1')
	{
		
		$tempfp=fopen('provider','r');
 		$provider_id=fgets($tempfp);
 		while($provider_id==0)
 		{
 			$provider_id=fgets($tempfp);
 		}
 		$provider_id = trim(preg_replace('/\s\s+/', ' ', $provider_id));
 		$provider_pin=fgets($tempfp);
 		while($provider_pin==0)
 		{
 			$provider_pin=fgets($tempfp);
 		}
 		$provider_pin = trim(preg_replace('/\s\s+/', ' ', $provider_pin));
 		$ticket_id=fgets($tempfp);
 		while($ticket_id==0)
 		{
 			$ticket_id=fgets($tempfp);
 		}
 		$ticket_id = trim(preg_replace('/\s\s+/', ' ', $ticket_id));
 		fclose($tempfp);
 		curl_post($URL.'/reporting/report_fix/','provider_id='.$provider_id.'&pin_code='.$provider_pin.'&ticket_id='.$ticket_id.'&validity_checked=True',$USER_PASS);
		$r->addPlayText('Thank you for callling Mobile Sanitation',4);
		$r->addHangup();
	}
	else if($_REQUEST['data']=='2')
	{
		$_SESSION['next_goto'] = 'Record_Status';
		$r->addPlayText('Please record your message after the beep.    Press hash to stop recording ');
		$rand_num=rand(100,10000);
		$r->addRecord('recording'+(string)$rand_num,'wav','300');
	}
	else
	{
		$collectInput = New CollectDtmf();
		$collectInput->addPlayText('Incorrect Option.',4);
		$collectInput->addPlayText('Press 1 for problem fixed. Press 2 for problem not fixed',4);
		$collectInput->setMaxDigits('1'); 
		$collectInput->setTimeOut('4000');  
		$r->addCollectDtmf($collectInput);
	    $_SESSION['next_goto']='ReportFix';
	}
}
else if($_REQUEST['event'] == 'Record' && $_SESSION['next_goto'] == 'Record_Status' )
{
	 $r->addPlayText('your recorded audio is ');
	 $_SESSION['record_url']=$_REQUEST['data'];
	 $collectInput = New CollectDtmf();
	 $collectInput->addPlayText('Press 1 to send recording. Press 2 to record again',4);
	 $collectInput->setMaxDigits('1'); 
	 $collectInput->setTimeOut('4000');  
	 $r->addCollectDtmf($collectInput);
     $_SESSION['next_goto']='ReportRecording';	
}
else if($_REQUEST['event'] == 'GotDTMF' && $_SESSION['next_goto'] == 'ReportRecording' )
{
	if($_REQUEST['data']=='1')
	{
		
		$r->addPlayText('Thank you for callling Mobile Sanitation',4);
		$r->addHangup();
	}
	else if($_REQUEST['data']=='2')
	{
		$_SESSION['next_goto'] = 'Record_Status';
		$r->addPlayText('Please record your message after the beep.    Press hash to stop recording ');
		$r->addRecord('recording'+(string)$rand_num,'wav','300');
	}
	else
	{
		$collectInput = New CollectDtmf();
		$collectInput->addPlayText('Incorrect Option.',4);
		$collectInput->addPlayText('Press 1 for problem fixed. Press 2 for problem not fixed',4);
		$collectInput->setMaxDigits('1'); 
		$collectInput->setTimeOut('4000');  
		$r->addCollectDtmf($collectInput);
	    $_SESSION['next_goto']='ReportFix';
	}
}
$r->getXML();
$r->send();
fwrite($ufp,$user_text.' ');
fwrite($pfp,$provider_text."\n");
?>
