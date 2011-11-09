<?php
class Listing_Combination
{
	//$data  = array(1, 2, 3,4);
	//
	//$res = split_data($data);
	//
	//foreach ($res as $key => $value) {
	//	//echo implode($value)."\n";
	//	//echo var_dump($value);
	//	//echo "--------------\n";
	//}

	function list_comb($data)
	{
		$result = array(); 
		$return = array(); 

		// 組み合わせを抽出 
		for ($i=0; $i < count($data)-1; $i++) { 
			$res = Listing_Combination::calc_combination($data , $i+1);
			foreach ($res as $key => $value) {
				array_push($result,$value);
			}
		}

		// 重複を排除
		$length = floor( count($data) / 2);
		$flg    = count($data) % 2; 

		foreach ($result as $key => $value) {
			if(count($value) <= $length){
				switch ($flg) {
				case 0:
					$max = max($data);	
					if(!array_search($max, $value)){
						array_push($return,$value);
					}
					break;
				case 1:
					array_push($return,$value);
					break;
				}
			}
		}
		return $return;
	}

	function calc_combination($array,$num)
	{
		$arrnum = count($array); 
		if($arrnum < $num){
			return;	
		}else if ($num==1) {
			for ($i=0; $i < $arrnum; $i++) { 
				$arrs[$i] = array($array[$i]);
			}
		}elseif ($num>1) {
			$j=0;
			for ($i=0; $i < $arrnum-$num+1; $i++) { 
				$ts = Listing_Combination::calc_combination(array_slice($array,$i+1),$num-1);
				foreach ($ts as $t ) {
					array_unshift($t,$array[$i]);
					$arrs[$j]= $t;
					$j++;
				}
			}
		}
		return $arrs;
	}
}
?>
