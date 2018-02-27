<?
IncludeModuleLangFile(__FILE__);

$arMobileMenuItems = array(
   array(
      "type" => "section",
      "text" =>"Раздел меню",
      "sort" => "100",
      "items" =>   array(
         array(
            "text" => "Web 1C",
            "data-url" => SITE_DIR."realty/",
            "class" => "menu-item",
            "id" => "main",
                "data-pageid"=>"main_page"
         )
      )
   )
);
?>