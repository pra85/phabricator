<?php

abstract class PhabricatorConfigSchemaSpec extends Phobject {

  private $server;
  private $utf8Charset;
  private $utf8Collation;

  public function setUTF8Collation($utf8_collation) {
    $this->utf8Collation = $utf8_collation;
    return $this;
  }

  public function getUTF8Collation() {
    return $this->utf8Collation;
  }

  public function setUTF8Charset($utf8_charset) {
    $this->utf8Charset = $utf8_charset;
    return $this;
  }

  public function getUTF8Charset() {
    return $this->utf8Charset;
  }

  public function setServer(PhabricatorConfigServerSchema $server) {
    $this->server = $server;
    return $this;
  }

  public function getServer() {
    return $this->server;
  }

  abstract public function buildSchemata();

  protected function buildLiskSchemata($base) {

    $objects = id(new PhutilSymbolLoader())
      ->setAncestorClass($base)
      ->loadObjects();

    foreach ($objects as $object) {
      $database = $this->getDatabase($object->getApplicationName());

      $table = $this->newTable($object->getTableName());

      $cols = $object->getSchemaColumns();
      foreach ($cols as $name => $type) {
        $details = $this->getDetailsForDataType($type);
        list($column_type, $charset, $collation) = $details;

        $column = $this->newColumn($name)
          ->setDataType($type)
          ->setColumnType($column_type)
          ->setCharacterSet($charset)
          ->setCollation($collation);

        $table->addColumn($column);
      }

      $database->addTable($table);
    }
  }

  protected function buildEdgeSchemata(PhabricatorLiskDAO $object) {}

  protected function getDatabase($name) {
    $server = $this->getServer();

    $database = $server->getDatabase($this->getNamespacedDatabase($name));
    if (!$database) {
      $database = $this->newDatabase($name);
      $server->addDatabase($database);
    }

    return $database;
  }

  protected function newDatabase($name) {
    return id(new PhabricatorConfigDatabaseSchema())
      ->setName($this->getNamespacedDatabase($name))
      ->setCharacterSet($this->getUTF8Charset())
      ->setCollation($this->getUTF8Collation());
  }

  protected function getNamespacedDatabase($name) {
    $namespace = PhabricatorLiskDAO::getStorageNamespace();
    return $namespace.'_'.$name;
  }

  protected function newTable($name) {
    return id(new PhabricatorConfigTableSchema())
      ->setName($name)
      ->setCollation($this->getUTF8Collation());
  }

  protected function newColumn($name) {
    return id(new PhabricatorConfigColumnSchema())
      ->setName($name);
  }

  private function getDetailsForDataType($data_type) {
    $column_type = null;
    $charset = null;
    $collation = null;

    switch ($data_type) {
      case 'id':
      case 'epoch':
        $column_type = 'int(10) unsigned';
        break;
      case 'phid':
        $column_type = 'varchar(64)';
        $charset = 'binary';
        $collation = 'binary';
        break;
      case 'blob':
        $column_type = 'longblob';
        $charset = 'binary';
        $collation = 'binary';
        break;
      case 'text':
        $column_type = 'longtext';
        $charset = $this->getUTF8Charset();
        $collation = $this->getUTF8Collation();
        break;
      default:
        $column_type = pht('<unknown>');
        $charset = pht('<unknown>');
        $collation = pht('<unknown>');
        break;
    }

    return array($column_type, $charset, $collation);
  }

}