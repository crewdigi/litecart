<?php
  
  class ref_manufacturer {
    private $_cache_id;
    private $_data = array();
    
    function __construct($manufacturer_id) {
    
      if (empty($manufacturer_id)) trigger_error('Missing manufacturer id', E_USER_WARNING);
      
      $this->_cache_id = cache::cache_id('manufacturer_'.(int)$manufacturer_id);
      
      $cache = cache::get($this->_cache_id, 'file');
      
      $this->_data = array_merge(array('id' => (int)$manufacturer_id), $cache ? $cache : array());
    }
    
    public function __get($name) {
      
      if (array_key_exists($name, $this->_data)) {
        return $this->_data[$name];
      }
      
      $this->_data[$name] = null;
      $this->load($name);
      
      return $this->_data[$name];
    }
    
    public function __isset($name) {
      return $this->__get($name);
    }
    
    public function __set($name, $value) {
      trigger_error('Setting data is prohibited', E_USER_WARNING);
    }
    
    private function load($field='') {
    
      switch($field) {
      
        case 'description':
        case 'short_description':
        case 'head_title':
        case 'meta_description':
        case 'meta_keywords':
        case 'h1_title':
          
          $this->_data['info'] = array();
          
          $query = database::query(
            "select * from ". DB_TABLE_MANUFACTURERS_INFO ."
            where manufacturer_id = '". (int)$this->_data['id'] ."'
            and language_code in ('". implode("', '", array_keys(language::$languages)) ."');"
          );
          
          $fields = array(
            'description',
            'short_description',
            'head_title',
            'meta_description',
            'meta_keywords',
            'h1_title',
          );
          
          while ($row = database::fetch($query)) {
            foreach ($fields as $key) $this->_data[$key][$row['language_code']] = $row[$key];
          }
          
        // Fix missing translations
          foreach ($fields as $key) {
            foreach (array_keys(language::$languages) as $language_code) {
              if (empty($this->_data[$key][$language_code])) $this->_data[$key][$language_code] = $this->_data[$key][settings::get('default_language_code')];
            }
          }
          
          break;
          
        default:
          
          if (isset($this->_data['date_added'])) return;
          
          $query = database::query(
            "select * from ". DB_TABLE_MANUFACTURERS ."
            where id = '". (int)$this->_data['id'] ."'
            limit 1;"
          );
          
          $row = database::fetch($query);
          
          if (database::num_rows($query) == 0) trigger_error('Invalid manufacturer id ('. $this->_data['id'] .')', E_USER_ERROR);
          
          foreach ($row as $key => $value) $this->_data[$key] = $value;
          
          break;
      }
      
      cache::set($this->_cache_id, 'file', $this->_data);
    }
  }
  
?>