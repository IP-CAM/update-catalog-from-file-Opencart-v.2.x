<?php

class ControllerCatalogUpdateCatalog extends Controller
{
    public function index()
    {
        ini_set('max_execution_time', 0);
        //  ini_set("memory_limit", "1000M");
        $this->load->model('catalog/update_catalog');
        $this->load->model('catalog/manufacturer');
        $this->load->model('catalog/product');
        $this->load->model('catalog/category');
        $this->load->model('catalog/attribute');
        $this->load->model('catalog/option');
        function getOptions(&$arr, &$thisclass)
        {
            $tmp = $thisclass->model_catalog_option->getOptions();
            foreach ($tmp as &$value) {
                $arr[$value["name"]] = $value;
                $tmp2 = $thisclass->model_catalog_option->getOptionValues($value["option_id"]);
                foreach ($tmp2 as &$value2) {
                    $arr[$value["name"]]["values"][$value2["name"]] = $value2;
                }
                unset($tmp2);
            }
        }

        $indexProduct = $this->model_catalog_update_catalog->getProductsAssocSCU();
        $indexCategory = $this->model_catalog_update_catalog->getCategoryAssocNAME();
        $indexAttribute = array();
        $tmp = $this->model_catalog_manufacturer->getManufacturers();
        $indexManufacturers = array();
        foreach ($tmp as &$value) {
            $indexManufacturers[$value["name"]] = $value;
        }
        unset($tmp);
        $tmp = $this->model_catalog_attribute->getAttributes();
        foreach ($tmp as &$value) {
            $indexAttribute[$value["name"]] = $value;
        }
        unset($tmp);

        $indexOptions = array();
        getOptions($indexOptions, $this);

        if (($handle = fopen("http://stripmag.ru/datafeed/opencart_csv-price-pro-importexport-4.csv", "r")) !== FALSE) {
            $first_line_flag = true;
            while (($data = fgetcsv($handle, 0, ";")) !== FALSE) {
                //пропускаем первую строчку файла
                if ($first_line_flag) {
                    $first_line_flag = false;
                    continue;
                }
                $tmp = explode("\n", $data[6]);
                $options = array();
                foreach ($tmp as &$value) {
                    $options[] = explode("|", $value);
                }
                unset($tmp);
                $categories = explode("|", $data[13]);
                $attributes = explode("\n", $data[9]);
                foreach ($attributes as &$val) {
                    $val = explode("|", $val);
                }
                //заполнение доп. изображений
                if (!empty($data[12])) {
                    $images = explode(",", $data[12]);
                } else {
                    $images = array();
                }


                //получение id производителя. Если его нет в базе - создаем
                if (!empty($indexManufacturers[$data[4]])) {
                    $manufacturer = $indexManufacturers[$data[4]]["manufacturer_id"];
                } else {
                    $indexManufacturers[$data[4]] = $manufacturer = $this->model_catalog_manufacturer->addManufacturer(array(
                        "name" => $data[4],
                        "manufacturer_store" => Array
                        (
                            "0" => 0
                        ),
                        "keyword" => "",
                        "image" => "",
                        "sort_order" => ""
                    ));
                }

                //получение id атрибутов. Если его нет в базе - создаем (необходим допил поиска/добавления группы атрибутов)
                $attributesToProduct = array();
                foreach ($attributes as $attribute) {
                    if (!empty($indexAttribute[$attribute[1]])) {
                        $attributesToProduct[] = array(
                            "name" => $attribute[1],
                            "attribute_id" => $indexAttribute[$attribute[1]]["attribute_id"],
                            "product_attribute_description" => array(
                                "1" => array(
                                    "text" => $attribute[2]
                                )
                            )
                        );
                    } else {
                        $indexAttribute[$attribute[1]]["attribute_id"] = $this->model_catalog_attribute->addAttribute(array(
                            "attribute_description" => Array
                            (
                                "1" => Array
                                (
                                    "name" => $attribute[1]
                                )

                            ),
                            "attribute_group_id" => 1,
                            "sort_order" => ""
                        ));
                        $attributesToProduct[] = array(
                            "name" => $attribute[1],
                            "attribute_id" => $indexAttribute[$attribute[1]]["attribute_id"],
                            "product_attribute_description" => array(
                                "1" => array(
                                    "text" => $attribute[2]
                                )
                            )
                        );

                    }
                }
                unset($attributes);

                //получение id категорий. Если нет в базе - создаем
                $categoriesToProduct = array();
                foreach ($categories as $key => $category) {
                    if (!empty($indexCategory[$category])) {
                        $categoriesToProduct[] = $indexCategory[$category];
                    } else {
                        if ($key > 0) {
                            for ($i = 0; $i < $key; $i++) {
                                if ($i == 0) $path = $categories[$i];
                                else $path .= " > " . $categories[$i];
                            }
                            $parent_id = $indexCategory[$categories[$key - 1]];
                        } else {
                            $path = "";
                            $parent_id = "";
                        }
                        $categoriesToProduct[] = $indexCategory[$category] = $this->model_catalog_category->addCategory(array(
                            "category_description" => Array
                            (
                                "1" => Array
                                (
                                    "name" => $category,
                                    "description" => "<p><br></p>",
                                    "meta_title" => $category,
                                    "meta_description" => "",
                                    "meta_keyword" => ""
                                )

                            ),

                            "path" => $path,
                            "parent_id" => $parent_id,
                            "filter" => "",
                            "category_store" => Array
                            (
                                "0" => 0
                            ),
                            "keyword" => "",
                            "image" => "",
                            "column" => 1,
                            "sort_order" => 0,
                            "status" => 1,
                            "category_layout" => Array
                            (
                                0 => ""
                            )

                        ));

                    }
                }
                unset($categories);
                $parseUrt = explode("/", $data[11], 5);

                if (!file_exists(DIR_IMAGE . "catalog/import/" . $parseUrt[4])) {
                    copy($data[11], DIR_IMAGE . "catalog/import/" . $parseUrt[4]);
                }
                $imageToProduct =  "catalog/import/" . $parseUrt[4];

                $imagesToProduct = array();
                foreach ($images as $image) {
                    $parseUrt = explode("/", $image, 5);

                    if (!file_exists(DIR_IMAGE . "catalog/import/" . $parseUrt[4])) {
                        copy($image, DIR_IMAGE . "catalog/import/" . $parseUrt[4]);
                    }
                    $imagesToProduct[] = array(
                        "image" => "catalog/import/" . $parseUrt[4],
                        "sort_order" => ""

                    );
                }
                unset($images);

                $product_option_to_product = array();
                foreach ($options as $option) {

                    if (!empty($indexOptions[$option[1]])) {

                        if (empty($product_option_to_product[$indexOptions[$option[1]]["option_id"]])) {
                            $product_option_to_product[$indexOptions[$option[1]]["option_id"]] = array(
                                "product_option_id" => "",
                                "name" => $option[1],
                                "option_id" => $indexOptions[$option[1]]["option_id"],
                                "type" => $option[0],
                                "required" => $option[3],
                                "product_option_value" => array()
                            );
                        }

                        if (!empty($indexOptions[$option[1]]["values"][$option[2]])) {
                            $product_option_to_product[$indexOptions[$option[1]]["option_id"]]['product_option_value'][] = array(
                                "option_value_id" => $indexOptions[$option[1]]["values"][$option[2]]["option_value_id"],
                                "product_option_value_id" => "",
                                "quantity" => $option[4],
                                "subtract" => $option[5],
                                "price_prefix" => $option[6],
                                "price" => $option[7],
                                "points_prefix" => $option[8],
                                "points" => $option[9],
                                "weight_prefix" => $option[10],
                                "weight" => $option[11]

                            );
                        } //обновление опции
                        else {
                            $optionTempVal = array(
                                "option_description" => array(
                                    1 => array(
                                        "name" => $option[1]
                                    )
                                ),
                                "type" => $option[0],
                                "sort_order" => 1
                            );

                            $optionTempVal["option_value"] = $indexOptions[$option[1]]["values"];
                            $optionTempVal['option_value'][] = array(
                                "option_value_id" => "",
                                "name" => "$option[2]",
                                "image" => "",
                                "sort_order" => '1'
                            );
                            foreach ($optionTempVal["option_value"] as &$tempval) {
                                $tempval["option_value_description"] = array(
                                    "1" => array(
                                        "name" => $tempval["name"]
                                    )
                                );
                                unset($tempval["name"]);

                            }
                            $this->model_catalog_option->editOption($indexOptions[$option[1]]["option_id"], $optionTempVal);
                            $indexOptions = array();
                            getOptions($indexOptions, $this);
                            $product_option_to_product[$indexOptions[$option[1]]["option_id"]]['product_option_value'][] = array(
                                "option_value_id" => $indexOptions[$option[1]]["values"][$option[2]]["option_value_id"],
                                "product_option_value_id" => "",
                                "quantity" => $option[4],
                                "subtract" => $option[5],
                                "price_prefix" => $option[6],
                                "price" => $option[7],
                                "points_prefix" => $option[8],
                                "points" => $option[9],
                                "weight_prefix" => $option[10],
                                "weight" => $option[11]

                            );

                        }
                    } //добавление опции
                    else {
                        ##############
                    }


                }




                $product_to_add = array(
                    "product_description" => Array(
                        "1" => Array(
                            "name" => $data[1],
                            "description" => $data[8],
                            "meta_title" => $data[18],
                            "meta_h1" => $data[17],
                            "meta_description" => "",
                            "meta_keyword" => "",
                            "tag" => "",
                        ),

                    ),

                    "image" => $imageToProduct,
                    "model" => $data[2],
                    "sku" => $data[3],
                    "upc" => "",
                    "ean" => "",
                    "jan" => "",
                    "isbn" => "",
                    "mpn" => "",
                    "location" => "",
                    "price" => ceil($data[5] - ($data[5] * 0.1)),
                    "price_retail" => "",
                    "tax_class_id" => "0",
                    "quantity" => $data[6],
                    "minimum" => "1",
                    "subtract" => "1",
                    "stock_status_id" => "7",
                    "shipping" => "1",
                    "keyword" => "",
                    "date_available" => date("Y-m-d"),
                    "length" => $data[16],
                    "width" => "",
                    "height" => "",
                    "length_class_id" => "1",
                    "weight" => $data[10],
                    "weight_class_id" => "1",
                    "status" => "1",
                    "sort_order" => "1",
                    "manufacturer_id" => $manufacturer,
                    "product_category" => $categoriesToProduct,

                    "category" => "",
                    "filter" => "",
                    "product_store" => Array(
                        "0" => "0"
                    ),

                    "download" => "",
                    "related" => "",
                    "product_attribute" => $attributesToProduct,

                    "option" => "",
                    "product_option" => $product_option_to_product,

                    "product_image" => $imagesToProduct,
                    "points" => "",
                    "product_reward" => Array(
                        "1" => Array(
                            "points" => ""
                        )
                    ),

                    "product_layout" => Array(
                        "0" => ""
                    )

                );
                if (!empty($indexProduct[$data[3]])) {
                    $this->model_catalog_product->editProduct($indexProduct[$data[3]], $product_to_add);

                }
                else {
                    $this->model_catalog_product->addProduct($product_to_add);
                }
            }
            fclose($handle);
        }

        unset($tmp);
        $this->model_catalog_update_catalog->set_last_update_time();
        echo "ok";
        die();


    }
}