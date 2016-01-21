<?php

class HotLogic{

	static public function handlePostReq($uri,$data,$redis)
	{
		// CREATE
		$res= static::__parseUri($uri);
		switch($res[0])
		{
			case 'c':
				// must be collection
				$arr = static::__saveItem($res[1],$data,$redis);
				break;
			default:
				break;
		}
		return isset($arr)? $arr : ['code'=>400];
	}

	static public function handleDelReq($uri,$redis)
	{
		// DELETE
		$res= static::__parseUri($uri);
		switch($res[0])
		{
			case 'i':
				// must be item
				$arr = static::__delItem(
						$res[1],$res[2],$redis);
				break;
			default:
				break;
		}
		return isset($arr)? $arr : ['code'=>400];
	}

	static public function handlePutReq($uri,$data,$redis)
	{
		// UPDATE
		$res= static::__parseUri($uri);
		switch($res[0])
		{
			case 'i':
				// must be item
				$arr = static::__putItem(
						$res[1],$res[2],$data,$redis);
				break;
			default:
				break;
		}
		return isset($arr)? $arr : ['code'=>400];
	}

	static public function handlePatchReq($uri,$data,$redis)
	{
		// UPDATE
		$res= static::__parseUri($uri);
		switch($res[0])
		{
			case 'i':
				// must be item
				$arr = static::__patchItem(
						$res[1],$res[2],$data,$redis);
				break;
			default:
				break;
		}
		return isset($arr)? $arr : ['code'=>400];
	}

	static public function handleGetReq($uri,$redis)
	{
		// QUERY
		$res= static::__parseUri($uri);
		switch($res[0])
		{
			case 'r':
				// root '/'
				$arr['href']=$uri;
				$arr['items']=['storage'=>[],'log'=>[]];
				break;
			case 'c':
				// some collection
				$arr = static::__assembleCollection($res[1]);
				if(!is_null($arr))
					$arr['href']=$uri;
				break;
			case 'i':
				// some items
				$arr = static::__assembleItem($res[1],$res[2],$redis);
				if(!is_null($arr))
					$arr['href']=$uri;
				break;
			case 'e':
				return ['code'=>400];
			default:
				break;
		}

		return isset($arr)? ['arr'=>$arr]:['code'=>404]; 
	}

	static public function handleSearchReq($uri,$get,$redis)
	{
		if(null===$get)
		{
			$ret_arr['code']=404;
		}
		return ['code'=>501]; 
	}

	/*
   **['r']
   **['c','col_name']
   **['i','col_name','item']
   */
	static private function __parseUri($uri)
	{
		if(strlen($uri)>1 && substr_compare($uri,'/',-1)===0)
		{
			//remove appending '/'
			$uri=substr($uri,0,-1);
		}

		$uri_arr = explode('/',$uri);
		switch(sizeof($uri_arr))
		{
			case 2:
				if(empty($uri_arr[1]))
				{
					// root
					$ret_arr[0]='r';
				}
				else
				{
					//collection
					$ret_arr[0]='c';
					$ret_arr[1]=$uri_arr[1];
				}
				break;
			case 3:
				if(!empty($uri_arr[2]))
				{
					// item
					$ret_arr[0]='i';
					$ret_arr[1]=$uri_arr[1];
					$ret_arr[2]=$uri_arr[2];
				}
				else
				{
					$ret_arr[0]='e';
				}
				break;
			default:
				$ret_arr[0]='e';
				break;
		}
		return $ret_arr;
	}

	static private function __assembleCollection($collection)
	{
		if($collection=='storage')
		{
			// queries
			$arr['prompt']='search storage';
			$arr['data']=['i_name'=>'Array'];

			$ret_arr['queries']=$arr;

			// template
			$ret_arr['template']=[
				'i_name'=>'Name of Items',
				'i_num'=>'Number of Items',
			];

			return $ret_arr;
		}
		
		return null;
	}

	static private function __assembleItem($collection,$item,$redis)
	{
		// classified collection
		if($collection=='storage')
		{
			// select db
			$redis->select(1);
			$data=$redis->hGetAll('storage:'.$item);
			if(!empty($data))
			{
				$arr[$item]=['data'=>$data];
				$ret_arr['items']=$arr;
				return $ret_arr;
			}
		}
		
		return null;
	}

	static private function __saveItem($collection,$data,$redis)
	{
		do{
			// validate data format
			if(!array_key_exists('template',$data)||
					!array_key_exists('data',$data['template']) )
				break;

			if($collection=='storage')
			{
				$redis->select(1);
				// CAS 4 concurrent programming
				do{
					$redis->watch('storage_count');
					$idx=$redis->get('storage_count');
					if(false==$idx)
						$idx=0;
					$multi=$redis->multi()
						->set('storage_count',$idx+1);
				}while(false === $multi->exec());

				$d= $data['template']['data'];
				if(!array_key_exists('i_name',$d) ||
						!array_key_exists('i_num',$d) )
					break;

				$hash_data['i_name']=$d['i_name'];
				$hash_data['i_num']=$d['i_num'];
				$hash_data['last_timestamp']=time();

				if($redis->hMSet('storage:'.$idx,$hash_data))
				{
					$arr=['code'=>201,'location'=>$collection.$idx];
				}
				else
				{
					// save failed
					$arr=['code'=>204];
				}
			}

		}while(false);

		return isset($arr)? $arr :null;
	}

	static private function __delItem($collection,$item,$redis)
	{
		if($collection=='storage')
		{
			// select db
			$redis->select(1);
			$del_num=$redis->del('storage:'.$item);
			if(0!==$del_num)
			{
				$arr=['code'=>204];
			}
			else
			{
				// 200 without content
				$arr=['code'=>200];
			}
		}

		return isset($arr)? $arr :null;
	}
	
	static private function __putItem($collection,$item,$data,$redis)
	{
		do{
			// validate data format
			if(!array_key_exists('template',$data)||
					!array_key_exists('data',$data['template']) )
				break;

			if($collection=='storage')
			{
				$redis->select(1);
				if(!$redis->exists('storage:'.$item))
				{
					$arr=['code'=>404];
					break;
				}

				$d= $data['template']['data'];
				if(!array_key_exists('i_name',$d) ||
						!array_key_exists('i_num',$d) )
					break;

				$hash_data['i_name']=$d['i_name'];
				$hash_data['i_num']=$d['i_num'];
				$hash_data['last_timestamp']=time();

				if($redis->hMSet('storage:'.$item,$hash_data))
				{
					$arr=['code'=>200];
				}
				else
				{
					// save failed
					$arr=['code'=>204];
				}
			}

		}while(false);

		return isset($arr)? $arr :null;
	}

	static private function __patchItem($collection,$item,$data,$redis)
	{
		do{
			// validate data format
			if(!array_key_exists('template',$data)||
					!array_key_exists('data',$data['template']) )
				break;

			if($collection=='storage')
			{
				$redis->select(1);
				if(!$redis->exists('storage:'.$item))
				{
					$arr=['code'=>404];
					break;
				}

				$d= $data['template']['data'];
				$hash_data['last_timestamp']=time();

				$fields=['i_name','i_num'];
				foreach($fields as $f){
					if(array_key_exists($f,$d))
					{
						$hash_data[$f]=$d[$f];
					}
				}

				if($redis->hMSet('storage:'.$item,$hash_data))
				{
					$arr=['code'=>200];
				}
				else
				{
					// save failed
					$arr=['code'=>204];
				}
			}

		}while(false);

		return isset($arr)? $arr :null;
	}
};

?>
