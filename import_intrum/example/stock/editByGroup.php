<?php
    /*
     *  Пример редактирования свойств продуктов по фильтру, 
     *  можно редактировать список полей для списка продуктов (список определяется фильтром) одного типа
     */
     
    require_once '../usage.php'; //настройте данный конфигурационный файл
    
    $stockId = 32916; //ID продукта в CRM
    
    /*
     *   При добавлении новых файлов к продукты - файлы нужно предварительно загрузить
     *   //Список файлов с абсолютними путями, которые нужно загрузить
        $files = array(
            $_SERVER['DOCUMENT_ROOT']."/example/upload.jpg",
            $_SERVER['DOCUMENT_ROOT']."/example/upload.jpg"
        );
        $result = $api->uploadFile('stock', $files);
     */
    
    $types = $api->getStockTypes();
    //print_r($types);
    
    $typeId = $types['data'][0]['id'];
        
    //Выборка всех существующих полей
    $fields = $api->getStockFields();
    //Список дополнительных полей выбранного типа
    $myFields = $fields['data'][$typeId]['fields'];
    
    //print_r($myFields);
    //
    
    /*
     *   Формат под разные типы полей
     *   
     */
    
    $values = array(
        //Radio
        array(
            'property' => 870,
            'type'     => 'radio',
            'value'    => "1", //Возможные ""-не выбрано, "1" - Да, "0"-Нет
        ),
        //text
        array(
            'property' => 878,
            'type'     => 'text',
            'value'    => "Новое описание", 
        ),
        //integer //float //price
        array(
            'property' => 876,
            'type'     => 'integer',
            'value'    =>  '15',      //Увеличит свойство объектов на 15%
            "option" => array(
                "mode"          => "update",  // set Установить значение  , update - установить значение в процентах от текущего, 
                "percent_mode"  => 1          // >0 = Увеличить, <0 - Уменьшить
            )
        ),
        //attach
        array(
            'property' => 882,
            'type'     => 'attach',
            'value'    => array(
                array(
                   'attach_id'   => "32915", //ID прикрепляемого объекта,
                   'attach_type' => "stock", //Тип прикрепляемого объекта, stock, customer, sale, request, employee,
                   'comment'     => "",      //Комментарий к связи,
                   'count'       => 1 ,      //Кол-во, 1 по умолчанию
                   'ext'         => 882      //Extproperty
                )
            ),
            "option" => array(
                "mode" => "rewrite" // add Установить значение если его нет, rewrite - установить значение в любом случае
            )
        ),
        //file
        array(
            'property' => 880,
            'type'     => 'file',
            'value'    => array(
                array(
                    "name"  => "597071f8ebcbc.jpg",
                    "title" => "Без названия (1).jpg"
                )
            ),
            "option"  => array(
                "filemode" => "del" //add - Добавить к существующим, del - Заменить существующие
            )
        ),
        //point
        array(
            "property" => 881,
            "type"     => "point",
            "value"    => "55.763634 37.613068 12"
        ),
        //select
        array(
            "property" => 871,
            "type"     => "select",
            "value"    => "two"
        ),
        //multiselect
        array(
            "property" => 872,
            "type"     => "multiselect",
            "option"   => array(
                "multiselect_write_mode" => "append"  
                // append  - Добавить к текущим
                // rewrite - заменить, очистить поле перед вставкой
                // delete  - удалить выбранные значения из поля
            ),
            "value"    => array(
                "two"
            )
        ),
        //Date
        array(
            "property" => 873,
            "type"     => "date",
            "value"    => "2017-07-21"
        ),
        //Datetime
        array(
            "property" => 874,
            "type"     => "datetime",
            "value"    => "2017-07-21 08:20:19"
        ),
        //time
        array(
            "property" => 874,
            "type"     => "time",
            "value"    => "08:20:19"
        ),
    );
    
    $res = $api->updateStockByFilter(array(
        'by_ids' => array(32916, 32909, 32908)
    ), $values);
    
    print_r($res);
   
?>
