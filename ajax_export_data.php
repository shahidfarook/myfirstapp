<?php
	//This ajax is to get state,county,city,office,rep based on the selected field
	require_once ("../../includes/configure.php");
	require_once ("../../includes/common_function.php");
	require_once ("../includes/functions.php");


	$Conn				=	new NEWCDB_DBConn();
	$PDO				=	$Conn->NEWCDB_DBConnectFun();
	$mongoDB			=	$Conn->NEWCDB_MongoDBConnectFun();

	$selected_market_code		=	addslashes(trim($_POST['market']));
	$selected_state_id		=	addslashes(trim($_POST['state']));
	$selected_county_id		=	addslashes(trim($_POST['county']));
	$registration_type		=       addslashes(trim($_POST['registration_type']));
	$time_frames			=       addslashes(trim($_POST['time_frames']));
	$device_type			=       addslashes(trim($_POST['device_type']));
	$start_date			=       addslashes(trim($_POST['start_date']));
	$end_date			=       addslashes(trim($_POST['end_date']));
	$first_name			=	addslashes(trim($_POST['cust_fname']));
	$last_name			=	addslashes(trim($_POST['cust_lname']));
	$rep_first_name			=	addslashes(trim($_POST['rep_fname']));
	$rep_last_name			=	addslashes(trim($_POST['rep_lname']));	
	$company			=	addslashes(trim($_POST['company']));
	$phone_no			=	addslashes(trim($_POST['phone_no']));
	$ProgramStatus			=	addslashes(trim($_POST['ProgramStatus']));
	$selrep				=	addslashes(trim($_POST['selrep']));
	$pageNo				=	(int)(($_POST['CDBUserListPageCount']));
	$pagelimit			=	(int)(($_POST['CDBUserListLimitCount']));
	
	//print_r($_POST);
	//exit;
	$today_ts = round(microtime(true));

	if($pageNo == "")
		$pageNo = 0;

	if($pagelimit == "")
		$pagelimit = 50;

	
	$condition = "AND u.user_type = 1 ";
	$condition1 = "";
	if($selected_market_code != "" && $selected_market_code != "All"){
		$condition .= $condition1 .= "AND um.market_code LIKE '".$selected_market_code."' ";	
	}

	if($selected_state_id != "" && $selected_state_id != "All"){
		$condition .= $condition1 .= "AND um.state_id = '".$selected_state_id."' ";
	}

	if($selected_county_id != "" && $selected_county_id != 0){
		$condition .= $condition1 .= "AND um.county_id = '".$selected_county_id."' ";
	}

	if($device_type != "") {
		$condition .= $condition1 .= "AND d.platform LIKE '".$device_type."' ";
	}

	if($first_name != ""){
		$condition .= $condition1 .= "AND u.first_name LIKE '%".$first_name."%' ";
	}

	if($last_name != ""){
		$condition .= $condition1 .= "AND u.last_name LIKE '%".$last_name."%' ";
	}

	if($company != ""){
		$condition .= $condition1 .= "AND u.company LIKE '%".$company."%' ";
	}


	if($phone_no != ""){
		$condition .= $condition1 .= "AND u.phone LIKE '%".$last_name."%' ";
	}

	if($ProgramStatus != "All") {
		$condition .= $condition1 .= "AND u.program_status LIKE '%".$ProgramStatus."%' ";
	}

	if($rep_first_name != ""){
		$condition1 .= "AND r.first_name LIKE '%".$rep_first_name."%' ";
	}

	if($rep_last_name != ""){
		$condition1 .= "AND r.last_name LIKE '%".$rep_last_name."%' ";
	}

	if($selrep != 0 && $selrep != 1){
		if($selrep == 2)
			$condition .= $condition1 .= "AND r.status  = 1 ";

		if($selrep == 3)
			$condition .= $condition1 .= "AND r.status  = 0 ";

		if($selrep == 4)
			$condition .= $condition1 .= "AND r.status  = 0 ";
	}	

	$joinquery = "";
	if($registration_type != ""){
		
		if($registration_type == "renewals")
			$joinquery = "INNER JOIN customer_renew AS cr ON cr.cust_id = u.id"; 

		if($registration_type == "switched_cust")		
			$joinquery = "INNER JOIN tbl_switch_user AS su ON su.cust_id = u.id";
			
	}	
		

	if($start_date != "" && $end_date != ""){
		$condition	.= "AND (date_format(u.created_at,'%Y-%m-%d') between '$start_date' and '$end_date') ";
		if($registration_type != "" && $registration_type == "renewals") 
			$condition	.= "AND (date_format(cr.created_at,'%Y-%m-%d') between '$start_date' and '$end_date') ";

		if($registration_type != "" && $registration_type == "switched_cust") 
			$condition	.= "AND (date_format(su.switched_datetime,'%Y-%m-%d') between '$start_date' and '$end_date') ";
		
	}

	if($time_frames != ""){
		$end_date				=	date('Y-m-d');
		if($time_frames == 7) {
			$start_date = date('Y-m-d',mktime(0,0,0,date('m'),date('d')-7,date('Y')));
		} else if($time_frames == 12) {
			$start_date	=  date('Y-m-d',mktime(0,0,0,date('m'),date('d'),date('Y')-1));
		} else {
			$start_date   =	date('Y-m-d',mktime(0,0,0,date('m')-$time_frames,date('d'),date('Y')));
		}

		if($start_date != "" && $end_date != ""){
			$condition	.= "AND (date_format(u.created_at,'%Y-%m-%d') between '$start_date' and '$end_date') ";
			if($registration_type != "" && $registration_type == "renewals") 
				$condition	.= "AND (date_format(cr.created_at,'%Y-%m-%d') between '$start_date' and '$end_date') ";

			if($registration_type != "" && $registration_type == "switched_cust") 
				$condition	.= "AND (date_format(su.switched_datetime,'%Y-%m-%d') between '$start_date' and '$end_date') ";
		}
			
	}

		
	
	
	$Query = "SELECT DISTINCT u.*,
				  um.market_code,
				  c.city_name,
				  r.name AS rep_name,
				  d.platform AS device_type,
				  date_format(u.created_at,'%Y-%m-%d') AS created_at		 
					FROM 
						`user` AS u 
						 INNER JOIN `user_market` AS um ON u.id = um.user_id
						 LEFT JOIN `user` AS r ON r.id = um.rep_id  
						  ".$joinquery."		
						 LEFT JOIN `device` AS d ON u.id = d.user_id 
						 LEFT JOIN `city` AS c ON c.city_id = um.city_id WHERE 1 ".$condition;
	//$Query = "";	

	if($rep_first_name != "" || $rep_last_name != ""){
		$condition1 .= "AND r.user_type != 1 AND um.rep_id != 0 AND u.id = um.rep_id";
		$Query .= ' UNION ';
		$Query .= "SELECT DISTINCT u.*,
				  um.market_code,
				  c.city_name,
				  r.name AS rep_name,
				  d.platform AS device_type,
				  date_format(u.created_at,'%Y-%m-%d') AS created_at		 
					FROM 
						`user` AS u 
						 INNER JOIN `user_market` AS um ON u.id = um.user_id
						 INNER JOIN `user` AS r ON r.id = um.rep_id 
						 ".$joinquery."	
						 LEFT JOIN `device` AS d ON u.id = d.user_id 
						 LEFT JOIN `city` AS c ON c.city_id = um.city_id WHERE 1 ".$condition1;
		
	}

	$query .= $query." LIMIT $pageNo,$pagelimit";	
	//echo $Query;exit;
	$QueryRun = $PDO->query($Query);//execute query	
	$QueryFetch = $QueryRun->fetchAll(\PDO::FETCH_ASSOC);//fetch query
	

	if($QueryRun && count($QueryFetch) > 0)//success
	{
		foreach($QueryFetch as $fetch) {
			$expiry_date_ts=strtotime($fetch['expiry_date']);
			$DateDifference	=	$expiry_date_ts - $today_ts;
			$days = floor($DateDifference / (60 * 60 * 24));
			if($days <= -31 && strstr($days,'-')){
				$days	=	"Expired";
			}else{
				$days	=	$days;
			}
			$fetch['days_for_renewal'] = $days;
			$recResult[] = $fetch; 			
		}
	} else {//failed
		$recResult = [];
	}	

		
	$Response="success";
	$Message="";
	$ResultArray	=	array(	"Status"=>$Response,
					"Response"=>$Message,
					"TotalRecord"=>count($recResult),
					"pageNo"=>$pageNo,
					"skip"=>$pageNo+$pagelimit,
					"limit"=>$pagelimit,
					"ResultValues"=>$recResult
				);
	
	//echo json_encode($recResult);
	echo json_encode($ResultArray, true);
	//echo base64_encode(json_encode($recResult));
?>
