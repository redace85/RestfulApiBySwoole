<?php

class Json_Collection {

	static $version='1.0';
	static $host='http://localhost:9501/';
	static $labels=[
		'version','href','links',
		'items','queries','template',
		'error',
		];

	public function setHerf($suf)
	{
		$this->coll['href']=static::$host.$suf;
		return $this;
	}

	public function setError($title,$code,$msg)
	{
		$this->coll['error']=[
			'title'=>$title,
			'code'=>$code,
			'message'=>$msg,
			];
		return $this;
	}

	public function setTemplate($data)
	{
		foreach($data as $name=>$prompt)
		{
			$data_arr[]=[
				'name'=>$name,
				'value'=>'',
				'prompt'=>$prompt,
				];
		}

		if(isset($data_arr))
		{
			$this->coll['template']=['data'=>$data_arr];
		}
		return $this;
	}

	public function setQueries($suf,$prompt,$data)
	{
		$qu_arr['rel']='search';
		$qu_arr['href']= static::$host.$suf.'/search';
		$qu_arr['prompt']=$prompt;

		foreach($data as $name=>$value)
		{
			$data_arr[]=[
				'name'=>$name,
				'value'=>$value,
					];
		}

		if(isset($data_arr))
		{
			$qu_arr['data']=$data_arr;
		}

		$this->coll['queries']=$qu_arr;
		return $this;
	}

	public function setLinks($data)
	{
		foreach($data as $rel=>$href)
		{
			$data_arr[]=[
				'rel'=>$rel,
				'href'=>$href
					];
		}

		if(isset($data_arr))
		{
			$this->coll['links']=$data_arr;
		}
		return $this;
	}

	public function setItems($rec_arr)
	{
		foreach($rec_arr as $pk=>$dl)
		{
			unset($item_arr);
			$item_arr['href'] = static::$host.$pk;
			// data and link
			if(array_key_exists('data',$dl))
			{
				unset($data_arr);
				foreach($dl['data'] as $name=>$value){
					$data_arr[]=[
						'name'=>$name,
						'value'=>$value,
						];
				}
				if(isset($data_arr))
				{
					$item_arr['data']=$data_arr;
				}
			}

			// may have links
			if(array_key_exists('links',$dl))
			{
				unset($links_arr);
				foreach($dl['links'] as $rel=>$href)
				{
					$links_arr[]=[
						'rel'=>$rel,
						'href'=>$href
							];
				}
				if(isset($links_arr))
				{
					$item_arr['links']=$links_arr;
				}
			}
			$items_arr[]=$item_arr;
		}

		if(isset($items_arr))
		{
			$this->coll['items']=$items_arr;
		}
		return $this;
	}

	public function getEncodedStr()
	{
		$col_arr['version']=static::$version;
		foreach(static::$labels as $l)
		{
			if(array_key_exists($l,$this->coll))
			{
				$col_arr[$l]=$this->coll[$l];
			}
		}

		return json_encode(['collection'=>$col_arr]);
	}
};

/*
$j = new Json_Collection;
$j->setHerf('Shits')
->setItems(['i1'=>['data'=>
		['n1'=>'v1','n2'=>'v2','n3'=>'v3'],
		'links'=>
		['r1'=>'h1','r3'=>'h3'],
		],
		'i2'=>['data'=>
		['n21'=>'v21','n22'=>'v22','n23'=>'v23'],
		],
		]);
//->setError('tt','X324F','msg should be shown here!')
//->setQueries('itesss','prompt text',['aa'=>'shit! aaa','B'=>'goods mock BBB']);
//->setLinks(['aa'=>'shit! aaa','B'=>'goods mock BBB']);
//->setTemplate(['A'=>'shit! aaa','B'=>'goods mock BBB','C'=>'ccccc coolll']);

echo $j->getEncodedStr();
*/

?>
