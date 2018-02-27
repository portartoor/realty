<?php
    /*
     * Пример редактирования продукта с загрузкой фото
     */
     
    require_once '../usage.php'; //настройте данный конфигурационный файл
    
    $stockId = 32916; //ID продукта в CRM
    
    //Актульная информация по объекту в CRM
    $data = $api->getStockByFilter(array(
        'byid' => $stockId
    ));
            
    $stock = $data['data']['list'][0];
    //print_r($stock);
    
    //Выборка всех существующих полей
    $fields = $api->getStockFields();
    //Список дополнительных полей выбранного объекта
    $myFields = $fields['data'][$stock['stock_type']]['fields'];
    //print_r($myFields);
    
    //Список файлов с абсолютными путями, которые нужно загрузить
    $files = array(
        $_SERVER['DOCUMENT_ROOT']."/example/upload.jpg",
        $_SERVER['DOCUMENT_ROOT']."/example/upload.jpg"
    );
    
    
    $result = $api->uploadFile('stock', $files);
    if($result['status'] === 'success'){
        //Файлы успешно загруженны, можно обновлять продукт
        
        $updateStock = array(
            'id'     => $stockId,
            'fields' => array(
                array(
                    'id'    => 396,
                    'value' => 50000, //Новая цена
                ),
                array(
                    'id'    => 395,
                    'value' => 'art123454', //Новый артикул
                ),
            )
        );
        
        //print_r($result);
        
        //Добавляем новые фото
        foreach($result['data']['name'] as $file){
            $updateStock['fields'][] = array(
                'id'    => 398,
                'value' => $file,
                'mode'  => 'insert'
            );
        }
        
        //Удаляем старые
        foreach ($stock['fields'] as $field) {
            if ($field['id'] == 398) {
                $updateStock['fields'][] = array(
                    'id'    => 398,
                    'value' => $field['value'],
                    'mode'  => 'delete'
                );
            }
        }

        $res = $api->updateStock(array($updateStock));

        print_r($res);
    }
   
?>
