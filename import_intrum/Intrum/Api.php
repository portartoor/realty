<?php
	
    namespace Intrum;

	class Api
	{
		private static $instance = null;
	
		public function __construct(){}
		
		public static function getInstance()
		{
			if(null === self::$instance){
				self::$instance = new self();
			}

			return self::$instance;
		}
		
		public function setup(array $params)
		{
			$this->key   = $params['apikey'];
			$this->host  = $params['host'];
			$this->port  = (isset($params['port']) ? ((int)$params['port']): 81);
			$this->url   = "http://{$this->host}:{$this->port}/sharedapi";
			$this->cache = $params['cache'];
            
			
			return $this;
		}
		
        private $debug = 0;
        public $debugStack = array();
        public function setDebug($set)
        {
            $this->debug = $set;
        }
        
        public function printDebug()
        {
            return $this->debugStack;
        }
		/*
			Продукты
		*/
		
		// типы продуктов
		public function getStockTypes()
		{
			return $this->send("/stock/types");
		}
		
		// список категорий
		public function getStockCategory()
		{
			return $this->send("/stock/category");
		}
		
		// поля
		public function getStockFields()
		{
			return $this->send("/stock/fields");
		}
		
		// поиск
        /* $params{
            type       - id типа продукта (обязательное поле, если не указаны byid/by_ids)
            byid       - id продукта
            by_ids     - Список id, все продукты из списка должны быть одного типа
            category   - id категории продукта
            nested     - значение true или false, включить вложенные категории
            search     - поисковая строка может содержат имя продукта или вхождения в поля с типами text,select,multiselect (полнотекстовый поиск)
            manager    - id менеджера
            groups     - массив групп менеджеров
            fields     - массив условий поиска по полям [{id:id свойства,value: значение},{...}] 
                         для полей с типом integer,decimal,price,time,date,datetime возможно указывать границы:
                value: '>= значение' - больше или равно
                value: '<= значение' - меньше или равно
                value: 'значение_1 & значение_2' - между значением 1 и 2
            related_with_customer - связанный с продуктом клиент
            order                 - направление сортировки asc - по возрастанию, desc - по убыванию
            order_field           - если в качестве значения указать stock_activity_date выборка будет сортироваться по дате активности
            date                  - {from: "2015-10-29 09:45:23", to: "2015-11-19 13:05:12"} выборка за определенный период
            date_field            - если в качестве значения указать stock_activity_date выборка по параметру активности
            page                  - страница
            publish               - 1 - активные, 0 - удаленные, по умолчанию 1
            limit                 - число записей в выборке, по умолчанию 20, макс. 500
         * }
         */
		public function getStockByFilter(array $params)
		{
			return $this->send("/stock/filter",$params);
		}
        
        //Получение конкретного объекта
        public function getStockById($id)
        {
            $data = $this->getStockByFilter(array(
                'byid' => $id
            ));
            return $data['data']['list'][0];
        }
        
        //Картинки объектов
        public function getStockUrlPhoto($name)
        {
            return "http://" .$this->host . "/files/crm/product/" . rawurlencode($name);
        }
		
		//вставка
		public function insertStock(array $params)
		{
			return $this->send("/stock/insert",$params);
		}
		
		//обновление
		public function updateStock(array $params)
		{
			return $this->send("/stock/update",$params);
		}
		
        //Групповое обновление полей по фильтру
        /*  
         *  $filter = getStockByFilter/$params 
         *  $values = [
         *   {
                'property' : 870,
                'type'     : 'type',
                'value'    : "1", 
            }
         *  //Описание всех типов в примере /example/stock/editByGroup.php и в документации
         * ] 
         */
        public function updateStockByFilter(array $filter,array $values)
        {
            return $this->send("/stock/updateByFilter",array(
                'filter'  => $filter,
                'values'  => $values
            ));
        }
        
		//удаление
		public function deleteStock(array $params)
		{
			return $this->send("/stock/delete",$params);
		}
		
		// комментарии
		public function getStockComments($entity_id)
		{
			return $this->send("/stock/comments",array(
				'entity_id' => $entity_id
			));
		}
        
        //Добавление комментариев
        /*
         *  $params : {
         *      enity_id, обзательный
         *      text,     обязательный
         *      author    опционально
         *  }
         */
        public function addStockComment(array $params)
        {
            return $this->send("/stock/addComment",$params);
        }
        
        //список групп объектов
        public function getStockGroups()
        {
            return $this->send('/stock/groups');
        }
		
        //Создание дерева групп объектов из списка объектов
        public function createTreeStockGroup($list)
        {
            $tree = array();
            foreach($list as $key=>$item){
                if($item['copy'] == 0){
                    $item['childs'] = array();
                    $tree[ $item['id'] ] = $item;
                    unset($list[$key]);
                }
            }
            
            foreach($list as $item){
                $tree[ $item['copy'] ]['childs'][] = $item;
            }
            return $tree;
        }
        
		/*
			Сотрудники
		*/
		
		// поля
		public function getEmployeeFields()
		{
			return $this->send("/worker/fields");
		}
		
		// отделы
		public function getDepartment()
		{
			return $this->send("/worker/department");
		}
		
		// филиалы
		public function getFiliation()
		{
			return $this->send("/worker/filiation");
		}
		
		//Выборка сотрудников
        /* $params{
         *  group         - id группы менеджеров
            id            - массив id сотрудников
            division_id   - массив id отделов
            suboffice_id  - массив id филиалов
            surname       - фамилия
            name          - имя
            email         - email
            phone         - телефон
            fields        - массив условий для дополнительных свойств [{id:id, value:value}, ...]
            status        - Статус сотрудника, по умолчанию [onstate,outstate] - все работающие, полный список:
                            ['new', 'onstate', 'outstate', 'notworking', 'fired']
         * }
         */
		public function filterEmployee(array $params = array())
		{
			return $this->send("/worker/filter",$params);
		}
		
		/*
			Группы менеджеров
		*/
		
		//получение списка групп
		public function getAvailGroups()
		{
			return $this->send("/managergroup");
		}
		
		/*
			Статьи
		*/
		
		// список статей
		public function getArticlesList(array $params)
		{
			return $this->send("/publication/list",$params);
		}
		
		// содержимое статьи
		public function getArticleContent(array $params)
		{
			return $this->send("/publication/single",$params);
		}
		
		/*
			Заявки
		*/
		
		// типы заявок
		public function getRequestTypes()
		{
			return $this->send("/applications/types");
		}
		
		// поля
		public function getRequestFields()
		{
			return $this->send("/applications/fields");
		}
		
		// поиск
        /* $params{
         *  search       - поисковая строка (может содержать фамилию клиента или слова из комментария или названия заявки)
            groups       - массив групп менеджеров
            manager      - id менеджера
            byid         - id заявки
            by_ids       - массив id заявок
            customer     - id клиента
            types        - массив id типов
            order        - направление сортировки asc - по возрастанию, desc - по убыванию
            order_field  - Поле для сортировки,  request_activity_date - дате активности
            date         - {from: "2015-10-29", to: "2015-11-19"} выборка за определенный период
            date_field   - если в качестве значения указать request_activity_date выборка по параметру активности
            statuses     - массив id статусов 
                unselected - Не выбран
                mustbeprocessed - Требует обработки
                processnow - Требует срочной обработки
                processed - Требует доработки
                postponed - Обработан
                malformed - Отложен
                cancelled - Неверен
                reprocess - Отменен
            page - страница
            publish - 1 - активные, 0 - удаленные, по умолчанию 1
            limit - число записей в выборке (макс. 50)
         * }
         */
		public function filterRequests(array $params)
		{
			return $this->send("/applications/filter",$params);
		}
		
        /*
         *  Список ID заявок статус которых менялися за период времени
         *  $params {
         *      date_start - Дата в формате "2017-02-01"
         *      date_end   - Дата в формате "2017-02-01"
         *  }
         */
        public function getRequestChangeStatus(array $params)
        {
            if(!$params['date_start'] || !$params['date_end']){
                return array(
                    'error' => 'Необходимо передать обязательные параметры: date_start, date_end'
                );
            }
            return $this->send('/applications/getbychangestatus',$params);
        }
        
        //Список доступных статусов
        public function getRequestStatuses()
        {
            return $this->send("/applications/statuses");
        }
        
		//вставка
        /*
         *  $params [{
         *      request_type             - ID типа заявок (обязательное поле)
                customers_id             - ID клиента (обязательное поле)
         *      source                   - один из вариантов ('help_manager','online_consult','none','online_form')
                employee_id              - ID менеджера
                additional_employee_id   - Массив ID дополнительных ответственных
                request_name             - Название заявки
                status                   - один из вариантов ('unselected','mustbeprocessed','processnow','processed','postponed','malformed','cancelled','reprocess'), getRequestStatuses
         *      fields                   - [{id:id,value:value}, ...] 
         * }]
         */
		public function insertRequests(array $params)
		{
			return $this->send("/applications/insert",$params);
		}
		
		/*
         *  $params 
         * [ 
         *      {
                    id                       - ID заявки в CRM
                    employee_id              - ID менеджера
                    additional_employee_id   - Массив ID дополнительных ответственных
                    request_name             - Название заявки
                    status                   - один из вариантов ('unselected','mustbeprocessed','processnow','processed','postponed','malformed','cancelled','reprocess'), getRequestStatuses
             *      fields                   - [{id:id,value:value}, ...] 
            * }
           ]
         */
		public function updateRequests(array $params)
		{
			return $this->send("/applications/update",$params);
		}
		
        //Добавление комментариев
        /*
         *  $params : {
         *      enity_id, обзательный
         *      text,     обязательный
         *      author    опционально
         *  }
         */
        public function addRequestComment(array $params) 
        {
            return $this->send("/applications/addComment", $params);
        }

        //удаление
		public function deleteRequests(array $params)
		{
			return $this->send("/applications/delete",$params);
		}
		
		// комментарии
		public function getRequestComments($entity_id)
		{
			return $this->send("/applications/comments",array(
				'entity_id' => $entity_id
			));
		}
		
		/*
			Продажи
		*/
        
		/*  Список типов продаж
         */
		public function getSaleTypes()
		{
			return $this->send("/sales/types");
		}
		
        /*  Список fields для продаж
         */
		public function getSaleFields()
		{
			return $this->send("/sales/fields");
		}
		
        //Выборка продаж по 
        /* $params {
                search       - поисковая строка
                type         - массив id типов продаж
                stage        - массив id стадий продаж
                customer     - id клиента
                manager      - id ответственного менеджера
                page         - страница
                publish      - 1 - активные, 0 - удаленные, по умолчанию 1
                limit        - число записей в выборке (макс. 50)
                byid         - получение продажи по ее id
                order        - направление сортировки asc - по возрастанию, desc - по убыванию
                order_field  - если в качестве значения указать sale_activity_date выборка будет сортироваться по дате активности
                date         - {from: "2015-10-29", to: "2015-11-19"} выборка за определенный период
                date_field   - если в качестве значения указать sale_activity_date выборка по параметру активности,
                by_ids       - Выборка несколких продаж по их ID, [1,2,3,...]             
         * }
         */
		public function filterSales(array $params)
		{
			return $this->send("/sales/filter",$params);
		}
        
        /*
         *  Список ID сделок стадии которых менялись за период времени
         *  $params {
         *      date_start - Дата в формате "2017-02-01"
         *      date_end   - Дата в формате "2017-02-01"
         *  }
         */
        public function getSalesChangeStage(array $params)
        {
            if(!$params['date_start'] || !$params['date_end']){
                return array(
                    'error' => 'Необходимо передать обязательные параметры: date_start, date_end'
                );
            }
            return $this->send('/sales/getbychangestage',$params);
        }
        
		//Добавление сделок
        /*
         *  $params [
         *      {
                   customers_id            - id клиента
                   employee_id             - id ответственного менеджера
                   additional_employee_id  - массив id дополнительных менеджеров
                   sales_type_id           - id типа продажи
                   sales_status_id         - id стадии продажи
                   sale_name               - название продажи
                   fields                  - массив  [{id:123,values:''},...]
         *      }, 
         *      ...
         *  ]
         */
		public function insertSales(array $params)
		{
            if(!$this->checkParamArr($params)){
                return array(
                    'error' => "Неправильный формат данных"
                );
            }
			return $this->send("/sales/insert",$params);
		}
		
		//Редактирование сделок
        /*
         *  $params [
         *      {
                    id                      - ID сделки в CRM, обязательное поле 
                    customers_id            - id клиента
                    employee_id             - id ответственного менеджера
                    additional_employee_id  - массив id дополнительных менеджеров
                    sales_type_id           - id типа продажи
                    sales_status_id         - id стадии продажи
                    sale_name               - название продажи
                    fields                  - массив  [{id:123,values:''},...]
         *      }, 
         *      ...
         *  ]
         */
        public function updateSales(array $params)
		{
			return $this->send("/sales/update",$params);
		}
		
		//удаление
		public function deleteSales(array $params)
		{
			return $this->send("/sales/delete",$params);
		}
		
		//доп инфа по сделке
		public function getSaleDetails(array $params)
		{
			return $this->send("/sales/details",$params);
		}
		
		// комментарии
		public function getSalesComments($entity_id)
		{
			return $this->send("/sales/comments",array(
				'entity_id' => $entity_id
			));
		}
        
        //Добавление комментариев
        /*
         *  $params : {
         *      enity_id, обзательный
         *      text,     обязательный
         *      author    опционально
         *  }
         */
        public function addSalesComment(array $params) {
            return $this->send("/sales/addComment", $params);
        }

        /*
			Клиенты
		*/
		
		// поля
		public function getCustomerFields()
		{
			return $this->send("/purchaser/fields");
		}
		
		// поиск
        /*
            $params {
                groups - массив id групп менеджеров
                manager - id менеджера
                byid - id клиента или массив id
                marktype - массив id типов
                nattype - одно из значений подтипа physface - Юрлицо, jurface - Физлицо, по умолчанию выводятся все
                search - поисковая строка (может содержать фамилию или имя, email, телефон)
                fields - массив условий для дополнительных свойств [ {id,value}, ]
                order - направление сортировки asc - по возрастанию, desc - по убыванию
                order_field - если в качестве значения указать customer_activity_date выборка будет сортироваться по дате активности
                date - {from: "2015-10-29", to: "2015-11-19"} выборка за определенный период
                date_field - если в качестве значения указать customer_activity_date выборка по параметру активности
                page - страница
                publish - 1 - активные, 0 - удаленные, по умолчанию 1
                limit - число записей в выборке (макс. 50)
            }
         */
		public function filterCustomers(array $params)
		{
			return $this->send("/purchaser/filter",$params);
		}
		
        /*
         *  Обёртка над filterCustomers
         *  $params те-же что и у filterCustomers, но limit = 1000000, т.е. 500 * 200
         *  файлы возвращаются в формате {name: имя файла, link:путь к файлу}
         */
        public function getListCustomers(array $params)
        {
            $total = ($params['limit']) ? $params['limit'] : 1000000;
            if($total > 500){
                $params['limit'] = 500;
            }
            $count = $params['count'];
            $max = 199;
            $page = 0;
            $allCount = null;
            $list = array();
            while($page <= $max){
                $page ++;
                $params['page'] = $page;
                if($total < $params['limit']){
                    $params['limit'] = $total;
                }
                if($params['limit']<1){
                    break;
                }
                
                $data = $this->filterCustomers($params);
                
                if($allCount === null){
                    $allCount = $data['data']['count'];
                }
                
                $list = array_merge($list,(array)$data['data']['list']);
                $total -= count($data['data']['list']);
                
                if(!$data['data']['list'] || count($data['data']['list'])<$params['limit']){
                    break;
                }
            }
            
            foreach($list as $key=>$item){
                if($item['fields']){
                    foreach($item['fields'] as $key2=>$field){
                        if($field['datatype'] == 'file'){
                            $list[$key]['fields'][$key2]['link'] = $this->getCustomerUrlFile($field['value']);
                        }
                    }
                }
            }
            
            return array(
                'list'  => $list,
                'count' => $allCount
            );
        }
        
        //Файлы клиентов
        public function getCustomerUrlFile($name) {
            return "http://" . $this->host . "/files/crm/" . rawurlencode($name);
        }

        //Добавление клиента
        /*
         *  $params {
               name                  - Имя
               surname               - Фамилия
               secondname            - Отчество
               manager_id            - ID менеджера
               additional_manager_id - Массив ID дополнительных менеджеров
               marktype              - Тип
               email                 - массив email адресов
               phone                 - массив номеров телефонов
               fields                - Массив допполей
         *  }
         */
		public function insertCustomers(array $params)
		{
			return $this->send("/purchaser/insert",$params);
		}
		
		//обновление
		public function updateCustomers(array $params)
		{
			return $this->send("/purchaser/update",$params);
		}
		
        //Добавление комментариев
        /*
         *  $params : {
         *      enity_id, обзательный
         *      text,     обязательный
         *      author    опционально
         *  }
         */
        public function addCustomersComment(array $params) {
            return $this->send("/purchaser/addComment", $params);
        }

        //удаление
		public function deleteCustomers(array $params)
		{
			return $this->send("/purchaser/delete",$params);
		}
		
		// комментарии
		public function getCustomerComments($entity_id)
		{
			return $this->send("/purchaser/comments",array(
				'entity_id' => $entity_id
			));
		}
		
		// прикрепления
		public function getCustomerAttaches($entity_id)
		{
			return $this->send("/purchaser/attach",array(
				'ids' => $entity_id 
			));
		}
		
        
		/*
		 *	Счета
		 */
		
		//Поиск / выборка
        /* $aprams{
         *  type         - Тип счёта (in,out)
            date_start   - Счёт создан в данную дату или позже  (формат YYYY-mm-dd)
            date_start   - Счёт создан в данную дату или раньше (формат YYYY-mm-dd)
            active       - Активые / неактивные счета (1,0)
            pay_status   - Статус оплаты (not,part,full)
            client_id    - ID клиента в CRM
            search       - Строка поиска, поиск осуществляется : по номеру счёта, фамилии клиента, названию компании
            author       - ID сотрудника создавшего счёт
            company_id   - ID реквизитов клиента
            sale_id      - ID связанной сделки в CRM
            not_sale_id  - Выборка записей не связанных с указанной сделкой, при указании в фильтре sale_id и not_sale_id, not_sale_id - игнорируется
            ids          - Выборка записей входящих массив / строку(разделеную ",") ID счетов , применение этого фильтра, очищает фильтр по умолчанию
            orderType    - Сортировка по убыванию / возрастанию (ASC,DESC)
            order        - Поле сортировки ('b.id' - номер счёта, "b.date_create" - дата создания счёта)
            limit        - Количество результатов в одном (постарничном) запросе по умолчанию 1000
            page         - Номер страницы вывода
            period_pay   - Период полаты счёта, выодить счета по которам совершалась оплата в указанный период{date_start: YYYY-mm-dd, date_end: YYYY-mm-dd}
         * } */
		public function billsGet(array $params)
        {
			return $this->send("/accounts/get",$params);
		}
		
		//Получение подробной информации по массиву счетов
		public function billsGetFull(array $ids)
		{
			return $this->send("/accounts/get_full",array('ids' => $ids));
		}
		
		//добавление
        /* $aprams{
         *      act_id         - ID прикреплёного акта
                date_create    - Дата создания
                product        - Массив продуктов
                [    
                    {
                       count  - Кол-во продукта
                       name   - Название продукта
                       price  - Цена продукта
                    }
                ]
                sale_id            - ID связанной сделки
                client_company_id  - ID реквизитов клиента
                client_id          - ID клиента
                my_company_id      - ID реквизитов фирмы
                nds                - Наличие ндс
                type               - Тип счёта входящий / исходящий(in/out)
         * }
         */
		public function billsAdd(array $params)
		{
			return $this->send("/accounts/add",$params);
		}
		
		
		//Редактирование
        /*
         *  $params =
         *  {
                bill_id - Уникальный номер счёта
                act_id - ID прикрепленного акта
                date_create - Дата создания "ГГГГ-ММ-ДД" / "ДД.ММ.ГГГГ"
                product - Массив продуктов
                    count - Кол-во продукта
                    name - Название продукта
                    price - Цена продукта
                sale_id - ID связанной сделки
                outer_id - внешний ID
                client_company_id - ID реквизитов клиента
                client_id - ID клиента
                my_company_id - ID реквизитов фирмы
                nds - Наличие ндс
                is_cash - Оплата наличными
                type - Тип счёта входящий / исходящий(in/out)
            }
         */
		public function billsEdit(array $params)
		{
			return $this->send("/accounts/edit",$params);
		}
		
        //Множественное редактирование (обновление) счетов
        /*  
         *  $params =
         *  [
                {
                  bill_id - Уникальный номер счёта
                  act_id - ID прикреплёного акта
                  date_create - Дата создания
                  product - Массив продуктов
                      count - Кол-во продукта
                      name - Название продукта
                      price - Цена продукта
                  sale_id - ID связанной сделки
                  stockgroup_id - ID списка товаров
                  client_company_id - ID реквизитов клиента
                  client_id - ID клиента
                  my_company_id - ID реквизитов фирмы
                  nds - Наличие ндс
                  type - Тип счёта входящий / исходящий(in/out)
                }
            ]
         */
		public function billsUpdate(array $params)
		{
			return $this->send("/accounts/update",$params);
		}
        
		//Установить статус оплаты
        /*  
         *  $params
         *  {
                id - Уникальный номер счёта
                pay - Сумма платежа (необязательный)
            }
         */
		public function billsSetPay($id,$pay=null)
		{
			return $this->send("/accounts/set_pay",array('id'=>$id,'pay'=>$pay));
		}
		
		/*
			Акты
		*/
		//Поиск / выборка
		public function actsGet(array $params)
		{
			return $this->send("/acts/get",$params);
		}
		
		//добавление
		public function actsAdd(array $params)
		{
			return $this->send("/acts/add",$params);
		}
		
		//обновление
		public function actsUpdate(array $params)
		{
			return $this->send("/acts/update",$params);
		}
		
		//Редактирвоание
		public function actsEdit(array $params)
		{
			return $this->send("/acts/edit",$params);
		}
		//Установить статус оплаты
		public function actsSetPay($id,$pay=null)
		{
			return $this->send("/acts/set_pay",array('id'=>$id,'pay'=>$pay));
		}
		
		/*
			Выписки
		*/
		//Поиск / выборка
		public function checksGet(array $params)
		{
			return $this->send("/checks/get",$params);
		}
		
		//добавление
		public function checksAdd(array $params)
		{
			return $this->send("/checks/add",$params);
		}
		
		//обновление
		public function checksUpdate(array $params)
		{
			return $this->send("/checks/update",$params);
		}
		
		/* 
			Служебные 
		*/
		 
		// варианты выбора
		public function getSelectVariants(array $params)
		{
			return $this->send("/utils/variants",$params);
		}
		
		// дочерние варианты выбора привязанные к конкретному варианту родителя 
		public function getBindedSelectVariants(array $params)
		{
			return $this->send("/utils/binded",$params);
		}
		
        
        /*
         *  object - один из возможных вариантов (stock-Продукт, applications-Заявки, purchaser-Клиент)
         *  $fieldId - (int) id поля файла
         *  $list = {
         *      $idObject( ID сущности CRM ) : [
         *          "upload.jpg",
         *          "upload1.jpg" //Список абсолютных путей к картинкам
         *      ]
         *  }
         */
        public function addFilesToObjects($objectType,$fieldId,$list)
        {
            $map = array();
            $all = array();
            foreach($list as $object=>$images){
                foreach($images as $img){
                    $map[] = $object;
                    $all[] = $img;
                }
            }
            $upp = array();
            
            $res = $this->uploadFile($objectType, $all);
            
            if($res['status'] === 'success'){
                foreach($res['data']['name'] as $num=>$item){
                    $upp[ $map[$num] ]['id'] = $map[$num];
                    $upp[ $map[$num] ]['fields'][] = array(
                        'id'    => $fieldId,
                        'value' => $item,
                        'mode'  => 'insert' 
                    );
                }
                
                if($objectType == 'stock'){
                    $res = $this->updateStock($upp);
                }elseif($objectType == 'applications'){
                    $res = $this->updateSales($upp);
                }elseif($objectType == 'purchaser'){
                    $res = $this->updateCustomers($upp);
                }
                
               return $res;
            }
            
        }
        
		// загрузчик файлов
        /*
         *  object - один из возможных вариантов (stock-Продукт, applications-Заявки, purchaser-Клиент)
            upload - имя поля загружаемого файла, поддерживает множественную загрузку
         * 
         */
		public function uploadFile($object,$source)
		{
			$is_multiple = is_array($source);
			$source = (array) $source;
            
			foreach($source as $i => $s){
				if(!file_exists($s) or !is_readable($s)){
                    //Обработка ошибки, файл не найден
					unset($source[$i]);
				}
			}

			if(!$source){
				return array(
					"status"  => "fail",
					"message" => "FILE_UPLOAD_ERROR",
                    "error"   => "Нет доступных файлов для загрузки"
				);
			}	

			$boundary = "---------------------".substr(md5(rand(0,32000)), 0, 10);

			$data .= "--{$boundary}\n";
			$data .= "Content-Disposition: form-data; name=\"apikey\"\n\n{$this->key}\n";
			$data .= "--{$boundary}\n";
			$data .= "Content-Disposition: form-data; name=\"params[object]\"\n\n{$object}\n";
			$data .= "--{$boundary}\n";

			foreach($source as $s){
				$data .= "Content-Disposition: form-data; name=\"upload" . ($is_multiple ? '[]' : '') . "\"; filename=\"" . basename($s) . "\"\n";
				$data .= "Content-Transfer-Encoding: binary\n\n";
				$data .= file_get_contents($s)."\n";
				$data .= "--{$boundary}\n";
			}
		
			$context = stream_context_create(
				array(
					'http' => array(
						'method' => 'POST',
						'header' => 'Content-Type: multipart/form-data; boundary='.$boundary,
						'content' => $data
					)
				)
			);
			
			$response = file_get_contents(
				$this->url . "/utils/upload",
				false,
				$context
			);
			
			return json_decode($response,true);
		}
		
		private function send($sub_url,array $data = array())
		{
			if($this->cache == true){
				$hash = md5($sub_url.serialize($data));
				$cache = IntrumExternalCache::getInstance()->pop($hash);
				
				if($cache !== false){
                    if($this->debug){
                        $this->debugStack[] = array(
                            'url'     => $this->url . $sub_url,
                            'isCache' => true,
                            'params'  => $data,
                            'result'  => $cache
                        );
                    }
					return $cache;
				}
			}
		
			$context = stream_context_create(
				array(
					'http' => array(
						'method' => 'POST',
						'header' => 'Content-Type: application/x-www-form-urlencoded' . PHP_EOL,
						'content' => http_build_query(
							array(
								"apikey" => $this->key,
								"params" => $data
							)
						),
					)
				)
			);
			
			$response = file_get_contents(
				$this->url . $sub_url,
				false,
				$context
			);
            
			$res = json_decode($response,true);
			if(!$res){
                if ($this->debug) {
                    $this->debugStack[] = array(
                        'url'     => $this->url . $sub_url,
                        'isError' => true,
                        'params'  => $data,
                        'result'  => $response
                    );
                }
                file_put_contents('error', $response);
                return array(
                    'error' => $response, 
                ); 
            }
			
			if($this->cache == true){
				IntrumExternalCache::getInstance()->push($hash,$res);
			}
			
            if($this->debug) {
                $this->debugStack[] = array(
                    'url'     => $this->url . $sub_url,
                    'params'  => $data,
                    'result'  => $res
                );
            }

            return $res;
		}
        
        public function webhookPut()
        {
            $input = file_get_contents('php://input');
            $data = json_decode($input, 1);
            return $data;
        }
        
        /* Звонки */
        //Возвращает список возможных соединений
        public function callsGetTrunks()
        {
            return $this->send('/calls/trunks');
        }
        
        public function callsGetPhones()
        {
            return $this->send('/calls/phoneNumbers');
        }
        
        /*Список статусов*/
        public function callsGetStatuses()
        {
            return $this->send('/calls/statuses');
        }
        
        /* Импорт звонка */
        /*
         *  $params {
                uniqueId	    Уникальный идентификатор звонка. 32-х символьная строка
                from	        Номер, с которого совершён звонок
                to	            Номер, на который был совершён звонок
                timestamp	    Время совершения звонка. Unix Timestamp * 1000 ( в микросекундах )
                trunkId	ID      соединения, через которое был совершёл звонок
                callerId	    Caller ID звонящего (обычно совпадает с его номером), не актуален при указанном isIncoming
                isIncoming	    Направление звонка с т.з. CRM системы: true или 1 - входящий, иначе - исходящий
                url	            url записи звонка. Звонок будет скачан и сохранён в CRM системе через некоторое время
                callDuration	Длительность звонка в секундах
                isAnswered	    Был ли ответ на звонок
                customStatus	ID назначенного звонку статуса
         *  }
         */
        public function callsAdd(array $params)
        {
            return $this->send("/calls/import",$params);
        }
        
        /* Импорт звонков */
        /*
         *  $params [
               {
                   uniqueId	        Уникальный идентификатор звонка. 32-х символьная строка
                   from	            Номер, с которого совершён звонок
                   to	            Номер, на который был совершён звонок
                   timestamp	    Время совершения звонка. Unix Timestamp * 1000 ( в микросекундах )
                   trunkId	ID      соединения, через которое был совершёл звонок
                   callerId	        ID звонящего (обычно совпадает с его номером), не актуален при указанном isIncoming
                   isIncoming	    Направление звонка с т.з. CRM системы: true или 1 - входящий, иначе - исходящий
                   url	            url записи звонка. Звонок будет скачан и сохранён в CRM системе через некоторое время
                   callDuration	    Длительность звонка в секундах
                   isAnswered	    Был ли ответ на звонок
                   customStatus	    ID назначенного звонку статуса
                },
                ...
         *  ]
         */
        public function callsAddList(array $params)
        {
            return $this->send("/calls/importall",array(
                'calls' => $params
            ));
        }
        
        /* Обновление информации о звонке
         * $params {
                uniqueId        Уникальный идентификатор звонка, чья запись бует обновлена
                url             url записи звонка. Звонок будет скачан и сохранён в CRM системе через некоторое время
                callDuration	Длительность звонка в секундах
                isAnswered      Был ли ответ на звонок
                customStatus	ID назначенного звонку статуса
         * }
         */
        public function callsUpdate(array $params)
        {
            return $this->send('/calls/update',$params);
        }
        
        /* Получить список звонков по фильтру
         * $params {
         *      fromPhone   Фильтр по номеру с которого звонили
                toPhone     Фильтр по номеру на который звонили
                dateFrom    Искать звонки после даты
                dateTo      Искать звонки до даты
                type        Тип звонков: in, out
                limit       Ограничить ответ количеством записей (макс. 1000)
                page        Совместно с ограничением, определяет страницу выдачи
                orderField  Сортировать по полю (по-умолчанию поле даты,date_time)
                orderType   Направление сортировки: ASK, DESC (по-умолчанию DESK)
         * }
         */
        public function callsGetList(array $params)
        {
            return $this->send('/calls/history',$params);
        }
        
        private function checkParamArr($params)
        {
            print_r(gettype($params[0]));
            if(gettype($params[0]) == 'array'){
                return true;
            }
        }
	}
?>