<?php
/*
 * Пример редактирования списка клиентов
 */
require_once '../usage.php'; // настройте данный конфигурационный файл

$list = array(
    array(
        'id'         => 98330, // это id редактируемого объекта в CRM
        'name'       => "CRM",
        'surname'    => "Интрум",
        'photo'      => array(
            realpath("3.jpg")
        ),
        'vk' => 'https://vk.com/intrum'
    ),
    array(
        'id'         => 98328, // это id редактируемого объекта в CRM
        'name'       => "Иван",
        'surname'    => "Иванов",
        'secondname' => "Иванович",
        'photo'      => array(
            realpath("1.jpg"),
            realpath("2.jpg"),
            realpath("3.jpg")
        ),
        'vk' => 'https://vk.com/id353123398'
    ),
    array(
        'id'         => 98329, // это id редактируемого объекта в CRM
        'name'       => "Андрей",
        'surname'    => "Лященко",
        'secondname' => "",
        'photo'      => array(
            realpath("1.jpg"),
            realpath("2.jpg"),
        ),
        'vk' => 'https://vk.com/id14034522'
    )
);

// Отношение: id полей в CRM к вашим полям в массиве list
$relations = array(
    "vk"    => 663,
    "photo" => 222
);

function crmIntrumEditGroupCustomer($list,$relation,$api)
{
    $ids = array_map(function($item){
        return $item['id'];
    },$list);
    
    $filesHash = array();
    
    $data = $api->filterCustomers(array(
        'byid' => $ids
    ));
    
    $dataList = array();
    foreach($data['data']['list'] as $item){
        $dataList[$item['id']] = $item;
    }
    
    $customers = array();
    
    foreach($list as $key=>$item){
        if($dataList[$item['id']]){
            $customers[$item['id']] = convertCustomer($dataList[$item['id']],$relation,$api);
        }else{
            //Данного клиента не существует, надо его добавить
        }
        foreach($item['photo'] as $num=>$photo){
            $list[$key]['photo'][$num] = array(
                'value' => $photo,
                'hash'  => getHashPhoto($photo)
            );
        }
    }
    
    $filesHash = array();
    
    $addFiles = array();
    
    $updates = array();
    
    foreach($list as $item){
        $customer = $customers[$item['id']];
        
        if($customer['name'] != $item['name']){
            $updates[ $item['id'] ]['name'] = $item['name'];
        }
        if($customer['surname'] != $item['surname']){
            $updates[ $item['id'] ]['surname'] = $item['surname'];
        }
        if($customer['secondname'] != $item['secondname']){
            $updates[ $item['id'] ]['secondname'] = $item['secondname'];
        }
        
        if($customer['vk'] != $item['vk']){
            $updates[ $item['id'] ]['fields'][] = array(
                'id'    => $relation['vk'],
                'value' => $item['vk']
            );
        }
        
        foreach($item['photo'] as $photo){
            if( isset($customer['photo'][ $photo['hash'] ]) ){
                unset($customer['photo'][ $photo['hash'] ] );
            }else{
                $addFiles[] = array(
                    'customer' => $item['id'],
                    'value'    => $photo['value'],
                    'id'       => $relation['photo']
                );
            }
        }
        
        if($customer['photo']){
            foreach($customer['photo'] as $photo){
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
        $files = $api->uploadFile('purchaser',$list);
        if($files['status'] === 'success'){
            foreach($files['data']['name'] as $key=>$it){
                $addFiles[$key]['value'] = $it;
            }
        }else{
            //Обработка ошибки загрузки фйлов
        }
        
        foreach($addFiles as $item){
            $updates[ $item['customer'] ]['fields'][] = array(
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
    
    if($updates){
        $res = $api->updateCustomers($updates);
        print_r($res);
    }
}

function convertCustomer($customer,$relations,$api)
{
    $map = array_combine(array_values($relations), array_keys($relations));
    
    $res['name']        = $customer['name'];
    $res['surname']     = $customer['surname'];
    $res['secondname']  = $customer['secondname'];
    
    foreach($customer['fields'] as $field){
        if($map[$field['id']]){
            if($map[$field['id']] == 'photo'){
                $url = $api->getCustomerUrlFile($field['value']);
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

crmIntrumEditGroupCustomer($list,$relations,$api);
