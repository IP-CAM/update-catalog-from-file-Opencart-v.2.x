# update_catalog_from_file_opencart_2
Модуль обновления каталога сфайла opencart2102


файлы из папки view и сontroller/common желательно не заменять, а использовать как пример
в БД выполнить запрос: 
INSERT INTO `oc_setting` (`setting_id`, `store_id`, `code`, `key`, `value`, `serialized`) VALUES (NULL, '0', 'config', 'dateprices', UNIX_TIMESTAMP(), '0');

