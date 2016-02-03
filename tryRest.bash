#!/bin/bash

select ch in 'create' 'read' 'update' 'delete'
do
case $ch in
create) 
	curl -i http://localhost:9501/storage -d '{"template":{"data":[{"name":"i_name","value":"item1"},{"name":"i_num","value":"88"}]}}'
	;;
read) 
	# first items
	curl -i http://localhost:9501/storage/1
	;;
update) 
	curl -i http://localhost:9501/storage/1 -X PATCH -d '{"template":{"data":[{"name":"i_num","value":"99"}]}}'
	;;
delete) 
	curl -i http://localhost:9501/storage/1 -X DELETE
	;;
*) 
	echo 'anything else is quit!'
	exit
	;;
esac

#exit
done

