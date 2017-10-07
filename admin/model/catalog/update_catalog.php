<?php

class ModelCatalogUpdateCatalog extends Model
{
    public function getCategoryAssocNAME() {
        $query = $this->db->query("SELECT a.category_id, b.name FROM " . DB_PREFIX . "category a LEFT JOIN ". DB_PREFIX . "category_description b ON a.category_id = b.category_id");
        $result = array();
        foreach ($query->rows as &$row){
            $result[$row['name']] = $row["category_id"];
        }
        unset($query);
        return $result;
    }

    public function getProductsAssocSCU() {
        $query = $this->db->query("SELECT sku, 	product_id  FROM " . DB_PREFIX . "product");

        $result = array();
        foreach ($query->rows as &$row){
            $result[$row['sku']] = $row["product_id"];
        }
        unset($query);
        return $result;
    }
    public function get_last_update_time(){
        $query = $this->db->query("SELECT `value` FROM " . DB_PREFIX . "setting WHERE `key` = 'dateprices' ");
        return  date("d-m-Y H:i:s" ,(int) $query->rows[0]["value"]);

    }

    public function set_last_update_time(){
        $this->db->query("UPDATE " . DB_PREFIX . "setting SET `value` = '" . time() . "' WHERE `key` = 'dateprices'");
        return true;
    }
}