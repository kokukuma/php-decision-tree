<?php
class Handling_Array{

	// カテゴリ変数に含まれる値を返す 
	public function make_feat_array($data,$pred)
	{
		$feat_array = array();
		$result = array();

		foreach ($data as $key => $value) {
			$base_array[$key] = $value[$pred]; 
		}
		$feat_array = array_unique($base_array);

		$i = 0;
		foreach ($feat_array as $key => $value) {
			$result[$i] = $value; 	
			$i++;
		}
		return $result;
		
	}

	// 指定したカテゴリ変数で$dataを分割する。
	//public function fbv($data,$pred)
	public function split_by_pred($data,$pred)
	{
		$split_array = array();

		// data 配列を予測変数の種類ごとに抽出する.
		$feat_array = Handling_Array::make_feat_array($data,$pred);
		
		// 予測変数の値毎の出現確率を計算
		foreach ($feat_array as $value) {

			$i = 0;
			$newarray = array();

			if(is_array($data) && count($data)>0){
				foreach(array_keys($data) as $key2){
					$temp[$i] = $data[$key2][$pred];
					if ($temp[$i] == $value) {
						$newarray[$i] = $data[$key2];
						$i++;
					}	
				}
			}
			$split_array[$value] = $newarray;
		}
		return $split_array;

	}

}
?>
