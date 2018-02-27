<?php
return array (
  'utf_mode' => 
  array (
    'value' => true,
    'readonly' => true,
  ),
  'default_charset' => 
  array (
    'value' => false,
    'readonly' => false,
  ),
  'no_accelerator_reset' => 
  array (
    'value' => false,
    'readonly' => false,
  ),
  'http_status' => 
  array (
    'value' => false,
    'readonly' => false,
  ),
  'cache' => 
  array (
    'value' => 
    array (
      'type' => 'files',
    ),
    'readonly' => false,
  ),
  'cache_flags' => 
  array (
    'value' => 
    array (
      'config_options' => 3600,
      'site_domain' => 3600,
    ),
    'readonly' => false,
  ),
  'cookies' => 
  array (
    'value' => 
    array (
      'secure' => false,
      'http_only' => true,
    ),
    'readonly' => false,
  ),
  'exception_handling' => 
  array (
    'value' => 
    array (
      'debug' => false,
      'handled_errors_types' => 4437,
      'exception_errors_types' => 4437,
      'ignore_silence' => false,
      'assertion_throws_exception' => true,
      'assertion_error_type' => 256,
      'log' => NULL,
    ),
    'readonly' => false,
  ),
  'connections' => 
  array (
    'value' => 
    array (
      'default' => 
      array (
        'className' => '\\Bitrix\\Main\\DB\\MysqliConnection',
        'host' => 'localhost',
        'database' => 'bitrix_relaty_new',
        'login' => 'bitrix_admin',
        'password' => 'bitrix_admin',
        'options' => 2,
      ),
    ),
    'readonly' => true,
  ),
);
