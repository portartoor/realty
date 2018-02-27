<?php
/*
 * Пример редактирования списка объектов
 */
require_once '../usage.php'; // настройте данный конфигурационный файл

$list = array(
    array(
        'id' => 32917, // это id редактируемого объекта в CRM
        'name' => "Первый товар",
        'articul' => "art123",
        'photo'  => array(
            $_SERVER['DOCUMENT_ROOT']."/img/1.jpg",
            $_SERVER['DOCUMENT_ROOT']."/img/2.jpg",
            $_SERVER['DOCUMENT_ROOT']."/img/3.jpg"
        ),
        'price' => 18000
    ),
    array(
        'id' => 32915,
        'name' => "Второй товар",
        'articul' => "art123",
        'photo' => array(
            $_SERVER['DOCUMENT_ROOT']."/img/4.jpg",
            $_SERVER['DOCUMENT_ROOT']."/img/5.jpg",
            $_SERVER['DOCUMENT_ROOT']."/img/6.jpg"
        ),
        'price' => 18000
    ),
    array(
        'id' => 32916,
        'name' => "Пятый товар",
        'articul' => "art123",
        'photo' => array(
            $_SERVER['DOCUMENT_ROOT']."/img/10.jpg",
        ),
        'price' => 18000
    )
);

// Отношение: тип объекта и id полей в CRM к вашим полям в массиве list
// например, в типе объекта=9, поле цена имеет id=475
// как определить id поля: 1) метод https://www.intrumnet.com/api/#stock-fields или 2) административная панель: см. скриншот https://yadi.sk/i/v-lUA90y3LPrhq
$relations = array(
    9 => array(
        'articul' => 420,
        'price'   => 475,
        'photo'   => 433
    ),
    20 => array(
        'articul' => 395,
        'price'   => 396,
        'photo'   => 398
    )
);

function crmIntrumEditGroupStock($list,$relations,$api)
{
    $stocks = array();
    
    $filesHash = array();
    
    foreach($list as $key=>$item){
        $stock = $api->getStockById($item['id']);
        if($stock){
            $stocks[$item['id']] = convertStock($stock,$relations[$stock['stock_type']],$api);
        }else{
            //Данного объекта не существует, либо превышен лимит запросов, либо необходимо создать новый объект
        }
        foreach($item['photo'] as $num=>$photo){
            $list[$key]['photo'][$num] = array(
                'value' => $photo,
                'hash'  => getHashPhoto($photo)
            );
        }
    }
    
    
    $filesHash = array();
    
    $updates = array();
    
    $addFiles = array();
    $updates = array();
    
    foreach($list as $item){
        $stock = $stocks[$item['id']];
        $relation = $relations[ $stock['stock_type'] ];
        
        if($stock['name'] != $item['name']){
            $updates[ $item['id'] ]['name'] = $item['name'];
        }
        
        if($stock['articul'] != $item['articul']){
            $updates[ $item['id'] ]['fields'][] = array(
                'id'    => $relation['articul'],
                'value' => $item['articul']
            );
        }
        
        if($stock['price'] != $item['price']){
            $updates[ $item['id'] ]['fields'][] = array(
                'id'    => $relation['price'],
                'value' => $item['price']
            );
        }
        
        foreach($item['photo'] as $photo){
            if( isset($stock['photo'][ $photo['hash'] ]) ){
                unset( $stock['photo'][ $photo['hash'] ] );
            }else{
                $addFiles[] = array(
                    'stock' => $item['id'],
                    'value' => $photo['value'],
                    'id'    => $relation['photo']
                );
            }
        }
        
        if($stock['photo']){
            foreach($stock['photo'] as $photo){
                $updates[ $item['id'] ]['fields'][] = array(
                    'id'    => $relation['photo'],
                    'value' => $photo,
                    'mode'  => 'delete'
                );
            }
        }
    }
    
    if($addFiles){
        $list = array_map(function($item){
            return $item['value'];
        },$addFiles);
        // format list  [ 'url', 'url' ]
        $files = $api->uploadFile('stock',$list);
        if($files['status'] === 'success'){
            foreach($files['data']['name'] as $key=>$it){
                $addFiles[$key]['value'] = $it;
            }
        }else{
            //Обработка ошибки загрузки фйлов
        }
        
        foreach($addFiles as $item){
            $updates[ $item['stock'] ]['fields'][] = array(
                'id'    => $item['id'],
                'value' => $item['value'],
                'mode'  => 'insert'
            );
        }
    }
    
    
    foreach($updates as $key=>$it){
        $updates[$key]['id'] = $key;
    }
    
    print_r($updates);
    
    $res = $api->updateStock($updates);
    
    print_r($res);
}

function convertStock($stock,$relations,$api)
{
    $map = array_combine(array_values($relations), array_keys($relations));
    
    $res['stock_type'] = $stock['stock_type'];
    $res['name']       = $stock['name'];
    
    foreach($stock['fields'] as $field){
        if($map[$field['id']]){
            if($map[$field['id']] == 'photo'){
                $url = $api->getStockUrlPhoto($field['value']);
                $res['photo'][ getHashPhoto($url) ] = $field['value'];
            }else{
                $res[ $map[$field['id']] ] = $field['value'];
            }
        }
    }
    return $res;
}

function getHashPhoto($file) {
    return md5(file_get_contents($file));
}

crmIntrumEditGroupStock($list,$relations,$api);
