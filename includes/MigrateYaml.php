<?php

/**
 * @file
 */

class MigrateYaml {

  protected $commonArguments;

  public function __construct($common_arguments) {
    $this->commonArguments = $common_arguments;
  }

  public function register($data) {
    $type = isset($data['type']) ? $data['type'] : '';
    if ($type == 'group') {
      $this->registerGroup($data);
    }
    elseif ($type == 'migration') {
      $this->registerMigration($data);
    }
    else {
      throw new InvalidArgumentException("Invalid data type");
    }
  }

  protected function registerGroup($data) {
    $name = $data['name'];
    $title = $data['title'];
    $arguments = isset($data['arguments']) ? $data['arguments'] : array();
    MigrateGroup::register($name, $title, $arguments);
  }

  protected function registerMigration($data) {
    $class = isset($data['class_name']) ? $data['class_name'] : 'MigrateYamlMigration';
    if ($class != 'MigrateYamlMigration' && !in_array('MigrateYamlMigration', class_parents($class))) {
      throw new LogicException("The class defined in migration must extend MigrateYamlMigration");
    }
    unset($data['class_name']);

    $arguments = isset($data['arguments']) ? $data['arguments'] : array();

    $arguments['mappings'] = $data['mappings'];
    $arguments['source'] = $data['source'];
    $arguments['destination'] = $data['destination'];
    $arguments['map'] = $data['map'];

    Migration::registerMigration($class, $data['name'], $arguments);
  }

}
