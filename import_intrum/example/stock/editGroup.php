<?php

    /*
     * Пример редактирования группы объектов
     */
    require_once '../usage.php'; //настройте данный конфигурационный файл

    $groups = $api->getStockGroups();
    $myGroup = $groups['data'][0];

    //print_r($myGroup);
    
    //Получение списка объектов входящих в группу
    $data = $api->getStockByFilter(array(
        'type'     => $myGroup['type'],
        'group_id' => $myGroup['id']
    ));
    
    //Группа объектов состоит из главного (основного) объекта, поля которго одинаковые для всех дочерних,
    //и дочерних, поля которых отличаются 
    //(Пример : ЖК, глвный объект: ЖК "Новый дом", содержит адрес, тип, цвет, название, и дочерние объекты: квартиры, для каждой уникален номер, метраж, кол-во комнат)
    //У главных объектов группы, поле copy = 0, у кго дочерних элементов copy = id главного
    //При редактировании главных полей
     
    $tree = $api->createTreeStockGroup($data['data']['list']);
    
    $firstGroupObject = reset($tree);
    
    print_r($myGroup,$firstGroupObject);
    
    die();
    
    $stock = $data['data']['list'][0];
    //print_r($stock);
    
    //Выборка всех существующих полей
    $fields = $api->getStockFields();
    //Список дополнительных полей выбранного объекта
    $myFields = $fields['data'][$stock['stock_type']]['fields'];
    //print_r($myFields);
    
    //Список файлов с абсолютними путями, которые нужно загрузить
    $files = array(
        $_SERVER['DOCUMENT_ROOT']."/example/upload.jpg",
        $_SERVER['DOCUMENT_ROOT']."/example/upload.jpg"
    );
    
    
    $result = $api->uploadFile('stock', $files);
    if($result['status'] === 'success'){
        //Файлы успешно загруженны , можно обновлять продукт
        
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
