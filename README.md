# update_catalog_from_file_opencart_2
File directory update module opencart2102


files from the folder view and controller / common, it is advisable not to replace, but to use as an example
execute the query in the database:
INSERT INTO `oc_setting` (` setting_id`, `store_id`,` code`, `key`,` value`, `serialized`) VALUES (NULL, '0', 'config', 'dateprices', UNIX_TIMESTAMP (), '0'); 
