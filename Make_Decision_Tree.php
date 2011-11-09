<?php

require_once('Listing_Combination.php');
require_once('Handling_Array.php');
require_once('CART.php');

//nodeのデータ構造 
class DT_Data{
	var $number;
	var $match;
	var $unmatch;

	var $split_key;
	var $split_value;
}

//nodeを構成するデータ構造
class DT_Node{
	var $dtdata;
	var $left;
	var $right;
	var $terminal;
}

class Decision_Tree{
	var $bv_data;
	var $data;
	var $base_key;
	var $base_value;
	var $false_value;
	var $tree;

	function Decision_Tree($data)
	{
		$this->data       = $data;
	}
	public function classify($base_key,$base_value,$false_value){

		$this->base_key     = $base_key;
		$this->base_value 	= $base_value;
		$this->false_value 	= $false_value;

		// 2値変数に変換
		$this->bv_data = Decision_Tree::make_binary_variable_data($this->data,$base_key);


		// Decision_Treeの生成 
		$tree = Decision_Tree::make_decision_tree($this->bv_data,
												  $this->base_key,
												  $this->base_value,
												  $this->base_key,
												  $this->base_value);

		$this->tree = $tree;
		return $tree;
	} 
	public function prognosis($target){
		$result = Decision_Tree::exe_prognosis($this->tree,$target);	
		return $result;
	} 
	private function make_binary_variable_data($ori_data,$base_key)
	{
		$multiple_param = array();
		$continuous_param = array();

		$feat_array 	= array();
		$delta_I_array 	= array();


		// dataをコピー
		$data = $ori_data;


		// 各変数の種類を確認 (3種類以上ある変数はどれか)	
		$keys = array_keys($data[0]); 
		foreach ($keys as $k => $key) {
			// 目的変数は対象外 
			if ($key == $base_key) {
				continue;
			}

			// 「連続変数」と「多値変数」の判断
			$feat_array = Handling_Array::make_feat_array($data,$key);
			if(count($feat_array)>=3){
				if(is_numeric($feat_array[0])){
					// 値の種類を取出し、3つ以上あり値が数値なら「連続変数」
					array_push($continuous_param,$key);
				}else {
					// 値が数値でなければ「多値変数」と判断する.
					array_push($multiple_param,$key);
				}
			}
		}



		// 2値変数へ圧縮
		foreach ($multiple_param as $key => $pred) {
			// 指定した多値変数を2値変数に圧縮する
			$data = Decision_Tree::multiple_to_binary($data,$base_key,$pred);
		}
		foreach ($continuous_param as $key => $pred) {
			// 指定した連続変数を2値変数に圧縮する
			$data = Decision_Tree::continuous_to_binary($data,$base_key,$pred);
		}

		return $data;
	}
	private function continuous_to_binary($data,$base_key,$pred)
	{

		// カテゴリ変数の種類を抽出
		$feat_array = Handling_Array::make_feat_array($data,$pred);

		// 昇順に並べ替える
		asort($feat_array);


		// グループ分けして, delta_Iを計算する
		for ($i=1; $i < count($feat_array); $i++) { 

			// groupを作成 
			$combs[$i] = array_slice($feat_array,0,$i); 

			// 2値変数に圧縮
			$tmpdata = Decision_Tree::to_binary_data($data,$pred,$combs[$i],'type1','type2');

			// delta_Iを計算
			$delta_I_array[$i] = CART::calc_delta_I($tmpdata,$base_key,$pred);

		}

		// delta_Iが最大になるグループのキーを調べる 
		$maxdikeys = array_keys($delta_I_array,max($delta_I_array));
		$maxdikey  = $maxdikeys[0];


		// type1 , type2 の名前を設定
		$type1_name = "";
		$type2_name = "";
		$groupmax = max($combs[$maxdikey]);

		$type1_name = $groupmax . " <=x";
		$type2_name = $groupmax . " >x";



		// 名前を変更したdataを作成する。
		foreach ($data as $num => $array) {
			$chk = $array[$pred];
			if(in_array($chk,$combs[$maxdikey])){
				$tmparray = $array;
				$tmparray[$pred] = $type1_name;  
				$tmpdata[$num] = $tmparray; 
			}else{
				$tmparray = $array;
				$tmparray[$pred] = $type2_name; 
				$tmpdata[$num] = $tmparray; 
			}
		}

		return $tmpdata;
	}
	private function multiple_to_binary($data,$base,$pred)
	{

		// カテゴリ変数の種類を抽出
		$feat_array = Handling_Array::make_feat_array($data,$pred);

		// 組み合わせを抽出
		//$combs = split_data($feat_array);
		$combs = Listing_Combination::list_comb($feat_array);


		// グループ分けして、各グループのdelta_Iを計算する
		foreach ($combs as $combkey => $comb) {
			// 2値変数に圧縮した配列を作成 
			$tmpdata = Decision_Tree::to_binary_data($data,$pred,$comb,'type1','type2');

			// delta_Iを計算
			$delta_I_array[$combkey] = CART::calc_delta_I($tmpdata,$base,$pred);
		}


		// delta_Iが最大になるグループのキーを調べる 
		$maxdikeys = array_keys($delta_I_array,max($delta_I_array));
		$maxdikey  = $maxdikeys[0];


		// type1 , type2 の名前を設定
		$type1_name = "";
		$type2_name = "";
		foreach ($feat_array as $key => $value) {
			if(in_array($value,$combs[$maxdikey])){
				$type1_name .= $value;	
			}else {
				$type2_name .= $value; 
			}
		}

		// 名前を変更したdataを作成する。
		$tmpdata = Decision_Tree::to_binary_data($data,$pred,$combs[$maxdikey],$type1_name,$type2_name);


		return $tmpdata;
	}

	private function to_binary_data($data,$pred,$comb,$name1,$name2) 
	{
		// 2値変数に圧縮した配列を作成 
		foreach ($data as $num => $array) {
			$chk = $array[$pred];
			if(in_array($chk,$comb)){
				$tmparray = $array;
				$tmparray[$pred] = $name1;
				$tmpdata[$num] = $tmparray; 
			}else {
				$tmparray = $array;
				$tmparray[$pred] = $name2;
				$tmpdata[$num] = $tmparray; 
			}
		}
		return $tmpdata;	
	}

	public function exe_prognosis($tree,$target){

		// 終端ノードなら、そのノードにおける目的変数の値を返す 
		if($tree->terminal){
			// 目的変数値の種類ごとの数を確認
			$true_num  = $tree->dtdata->match;
			$false_num = $tree->dtdata->unmatch;

			//   
			if($true_num > $false_num){
				$pars = $true_num / ($true_num + $false_num);
				echo $pars."\n";
				return $this->base_value;
			}else {
				$pars = $false_num / ($true_num + $false_num);
				echo $pars."\n";
				return $this->false_value;
			}
		// 終端ノードでなければ、分岐条件を確認する
		}else {
			$split_key = $tree->left->dtdata->split_key;
			$lval 	   = $tree->left->dtdata->split_value;
			$rval 	   = $tree->right->dtdata->split_value;
		}

		
		// 連続変数かどうかを確認する
		$feat_array = Handling_Array::make_feat_array($this->data,$split_key);
		if(count($feat_array)>3  && is_numeric($feat_array[0])){
			if(!(strstr('<=x',$lval)==false)){
				$flg = 1;
				$border = trim($lval,'<=x');			
			}else {
				$flg = 2;
				$border = trim($rval,'<=x');			
			}
		}else {
			$flg = 0;
		}

		// 分岐条件に従い、次のノードの計算を行う。 
		switch ($flg) {
		case 0:
			// カテゴリ変数が元連続変数でない場合の分岐方法 
			if(!(strstr($lval,$target[$split_key])==false))
			{
				return Decision_Tree::exe_prognosis($tree->left,$target);
			}
			else if (!(strstr($rval,$target[$split_key])==false))
			{
				return Decision_Tree::exe_prognosis($tree->right,$target);			
			}
			break;
		case 1:
			// カテゴリ変数が元連続変数である場合の分岐方法
			if($border >= $target[$split_key]){
				return Decision_Tree::exe_prognosis($tree->right,$target);
			}else{
				return Decision_Tree::exe_prognosis($tree->left,$target);			
			}
			break;
		case 2:
			// カテゴリ変数が元連続変数である場合の分岐方法2
			if($border >= $target[$split_key]){
				return Decision_Tree::exe_prognosis($tree->left,$target);
			}else{
				return Decision_Tree::exe_prognosis($tree->right,$target);			
			}
			break;
		default:
			break;
		}	
	} 

	private function make_decision_tree($data,$base,$base_value,$split_key,$split_value){

		$delta_I_array = array();	
		$dtnode = new DT_Node();
		$dtdata = new DT_Data();

		// dtdataをセット 
		$dtdata = Decision_Tree::set_DtData($data,$base,$base_value);
		$dtdata->split_key   = $split_key;
		$dtdata->split_value = $split_value;
		$dtnode->dtdata 	 = $dtdata; 
		$dtnode->terminal    = false;



		// カテゴリ変数ごとにdelta_Iを計算する 
		$keys = array_keys($data[0]); 
		foreach ($keys as $k => $key) {
			if ($key == $base) {
				continue;
			}
			// delta_Iを計算
			$delta_I_array[$key] = CART::calc_delta_I($data,$base,$key);
		}
		

		// delta_Iが全部ゼロなら終了
		// この辺の終了条件はかなり適当
		$flg =0;
		foreach ($delta_I_array as $key => $value) {
			if($value != 0.0){$flg=1;}
		}
		if($flg==0){
			$dtnode->terminal= true;
			return $dtnode;
		}


		// 最大のdelta_Iを抽出
		$split_key = array_keys($delta_I_array,max($delta_I_array));

		// delta_Iが最大となるカテゴリ変数でdataを分割する
		$split_array = Handling_Array::split_by_pred($data,$split_key[0]);
		$i = 0;
		foreach ($split_array as $key => $value) {
			switch ($i) {
			case 0:
				$dtnode->left = Decision_Tree::make_decision_tree($value,$base,$base_value,$split_key[0],$key);
				break;
			case 1:
				$dtnode->right = Decision_Tree::make_decision_tree($value,$base,$base_value,$split_key[0],$key);
				break;
			default:
				break;
			}
			$i++;
		}


		// 決まったら、DT_Nodeを返却
		return $dtnode;

	}
	private function set_DtData($data,$base,$value)
	{
		// カテゴリ変数の抽出
		$split_array = Handling_Array::split_by_pred($data,$base);


		// DT_Dataを作成
		$dtdata = new DT_Data();
		$dtdata->number = count($data);
		if(isset($split_array[$value])){
			$dtdata->match   = count($split_array[$value]);
		}else {
			$dtdata->match   = 0;
		}
		$dtdata->unmatch   = $dtdata->number - $dtdata->match; 

		return $dtdata;
	}

}

?>
	
	
