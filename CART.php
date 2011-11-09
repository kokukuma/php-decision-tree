<?php

require_once("Handling_Array.php");

class CART{

	function calc_delta_I($data,$base,$pred){
		$split_array = array();
		$gini_array  = array();

		// data 配列を予測変数の種類ごとに分割する.
		$split_array = Handling_Array::split_by_pred($data,$pred);
		//var_dump($split_array);


		// 各配列のジニ係数を計算する　	
		foreach ($split_array as $key => $value) {
			//var_dump($value);
			$gini_array[$key] = CART::calc_gini_index($value,$base);
		}
		//var_dump($gini_array);
		$gini_root = CART::calc_gini_index($data,$base);

		// delta-Iを計算
		$delta_I = $gini_root; 

		foreach ($split_array as $key => $value) {
			$odd = CART::probability_calculation($data,$pred,$key);
			$gini = $gini_array[$key];
			$delta_I -= $odd * $gini;
		}
		return $delta_I;

	}

	function calc_gini_index($data,$base)
	{
		$base_array 	= array();
		$feat_array 	= array();

		$tmp_sum = 0;
		$odds = array();


		// 予測変数の値毎の個体数を抽出
		$feat_array = Handling_Array::make_feat_array($data,$base);


		// 予測変数の値毎の出現確率を計算
		foreach ($feat_array as $key => $value) {
			$odd = CART::probability_calculation($data,$base,$value);
			array_push($odds , $odd);
		}	


		// Gini係数を計算
		$gini = 1;
		foreach ($odds as $key => $value) {
			$gini -= pow($value,2); 
		}
		
		return $gini;
	}

	function probability_calculation($data,$index,$value)
	{
			$tmp_sum = 0;

			foreach ($data as $dvalue) {
				if($dvalue[$index] == $value){$tmp_sum++;}
			}

			$odd = $tmp_sum / count($data);

			return $odd;
	}

}

?>
