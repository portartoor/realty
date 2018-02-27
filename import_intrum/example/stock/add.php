<?php
//описание и документация по API INTRUM http://www.intrumnet.com/api/

    require_once '../usage.php'; //настройте данный конфигурационный файл

    /*
     * Пример добавления продукта и загрузки файлов
     */
    
    /* Файл загружаются после добавления продукта, сначало нужно добавить продукт со всеми необъодимыми полями */
    
    /* Для разных типов продуктов дополнительные поля отличаются. Чтобы получить список типов продуктов используйте: */
    $typesData = $api->getStockTypes();
    //Первый тип из результата
    $type = $typesData['data'][1]['id'];
    
    /* Список категорий продуктов */
    $categories = $api->getStockCategory();
    // Категории выбранного типа
    $myCategories = $categories['data'][$type]; 
    // Первая категория первого типа,
    $parent = $myCategories[0]['id'];
    
    
    //Выборка всех существующих полей
    //$fields = $api->getStockFields();
        
    //Список дополнительных полей выбранного типа
    //$myFields = $fields['data'][$type]['fields'];
    //print_r($myFields);
    
    // вернет массив id
    $result = $api->insertStock(
        // массив добавляемых записей
        array(
            // отдельная запись
            array(
                // id Категории продукта(обязательное поле)'
                'parent' => (int)$parent,
                // наименование
                'name' => 'Москва, Бутиковский переулок, д. 5',
                // id ответственного менеджера или 0
                'additional_author' => array(2,3), //Массив ответственных, не обязательный
                'author' => 0,
                // id клиента - собственника, если есть
                'related_with_customer' => 10643,
                // дополнительные поля
                'fields' => array(
                    // text
                    array(
                        'id' => 420,
                        'value' => 'Светлая и просторная двухэтажная квартира (143 м2) в центре Старого города'
                    ),
                    // price, integer, 
                    array(
                        'id' => 429,
                        'value' => 3765000
                    ),
                    // point
                    array(
                        'id' => 466,
                        'value' => array(
                            'lat' => 53.185577,
                            'lon' => 50.087652 
                        )
                    ),
                    // radio
                   array(
                       'id' => 624,
                       'value' => 1 // 1-да, 0 - нет
                   ),
                   //select
                   array(
                       'id' => 645,
                       'value' => 'Billion'
                   ),
                    //date
                   array(
                       'id' => 421,
                       'value' => "2017-01-01"
                   ),
                   //datetime
                   array(
                       'id' => 585,
                       'value' => "2017-01-01 20:00:00"
                   ),
                   //multiselect
                   array(
                        'id'    => 477,
                        'value' => 'one,four' //Выбранные варианты через запятую
                    ), 
                    
                )
            )
        )
    );
    
    print_r($result);
    
    // если продукт успешно добавлен
    if($result and $result['status'] === 'success'){
        $ids = $result['data'];

        // берем id продукта
        $id = reset($ids);
        // загрузка файла
        $fileResult = $api->uploadFile(
            'stock',
            __DIR__ . '../upload.jpg'
        );

        // проверка что файл загружен успешно
        if($fileResult and $fileResult['status'] === 'success'){
            $name = $fileResult['data']['name'];

            // привязка файла к продукту
            return $api->updateStock(array(
                array(
                    'id' => $id,
                    'fields' => array(
                        array(
                            'id'    => 345,
                            'value' => $name,
                            'mode'  => 'insert' 
                        )
                    )
                )
            ));
        };
    }
?>
