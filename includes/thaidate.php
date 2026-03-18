<?php
	// function ThDate(){
	// 	//วันภาษาไทย
	// 	$ThDay = array ( "อาทิตย์", "จันทร์", "อังคาร", "พุธ", "พฤหัสบดี", "ศุกร์", "เสาร์" );
	// 	//เดือนภาษาไทย
	// 	$ThMonth = array ( "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน","พฤษภาคม", "มิถุนายน", "กรกฏาคม", "สิงหาคม","กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม" );
	// 	//กำหนดคุณสมบัติ
	// 	$week = date( "w" ); // ค่าวันในสัปดาห์ (0-6)
	// 	$months = date( "m" )-1; // ค่าเดือน (1-12)
	// 	$day = date( "d" ); // ค่าวันที่(1-31)
	// 	$years = date( "Y" )+543; // ค่า ค.ศ.บวก 543 ทำให้เป็น ค.ศ.
		
	// 	return "วัน$ThDay[$week] 
	// 			ที่ $day  
	// 			เดือน $ThMonth[$months] 
	// 			พ.ศ. $years";
	// }

	// function  thaiDate1($strDate) {
	// 	$strYear = date("Y",strtotime($strDate))+543;
	// 		$strMonth= date("n",strtotime($strDate));
	// 		$strDay= date("j",strtotime($strDate));
	// 		$strHour= date("H",strtotime($strDate));
	// 		$strMinute= date("i",strtotime($strDate));
	// 		$strSeconds= date("s",strtotime($strDate));
	// 		$strMonthCut = Array("","ม.ค.","ก.พ.","มี.ค.","เม.ย.","พ.ค.","มิ.ย.","ก.ค.","ส.ค.","ก.ย.","ต.ค.","พ.ย.","ธ.ค.");
	// 		$strMonthThai=$strMonthCut[$strMonth];
	// 		return "$strDay $strMonthThai $strYear";
	// }

	// แสดงวันที่เป็นภาษไทยหลายๆๆแบบ
	$dayTH = ['อาทิตย์','จันทร์','อังคาร','พุธ','พฤหัสบดี','ศุกร์','เสาร์'];
	$monthTH = [null,'มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
	$monthTH_brev = [null,'ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
	function thai_date_and_time($time){   // 19 ธันวาคม 2556 เวลา 10:10:43
		global $dayTH,$monthTH;   
		$thai_date_return = date("j",$time);   
		$thai_date_return.=" ".$monthTH[date("n",$time)];   
		$thai_date_return.= " ".(date("Y",$time)+543);   
		$thai_date_return.= " เวลา ".date("H:i:s",$time);
		return $thai_date_return;   
	} 
	function thai_date_and_time_short($time){   // 19  ธ.ค. 2556 10:10:4
		global $dayTH,$monthTH_brev;   
		$thai_date_return = date("j",$time);   
		$thai_date_return.=" ".$monthTH_brev[date("n",$time)];   
		$thai_date_return.= " ".(date("Y",$time)+543);   
		$thai_date_return.= " ".date("H:i:s",$time);
		return $thai_date_return;   
	} 
	function thai_date_short($time){   // 19  ธ.ค. 2556
		global $dayTH,$monthTH_brev;   
		$thai_date_return = date("j",$time);   
		$thai_date_return.=" ".$monthTH_brev[date("n",$time)];   
		$thai_date_return.= " ".(date("Y",$time)+543);   
		return $thai_date_return;   
	} 
	function thai_date_fullmonth($time){   // 19 ธันวาคม 2556
		global $dayTH,$monthTH;   
		$thai_date_return = date("j",$time);   
		$thai_date_return.=" ".$monthTH[date("n",$time)];   
		$thai_date_return.= " ".(date("Y",$time)+543); 
		return $thai_date_return;   
	} 
	function thai_date_short_number($time){   // 19-12-56
		global $dayTH,$monthTH;   
		$thai_date_return = date("d",$time);   
		$thai_date_return.="-".date("m",$time);   
		$thai_date_return.= "-".substr((date("Y",$time)+543),-2);   
		return $thai_date_return;   
	} 


?>