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

  public function registerGroup($data) {
    $name = $data['name'];
    $title = $data['title'];
    $arguments = isset($data['arguments']) ? $data['arguments'] : array();
    MigrateGroup::register($name, $title, $arguments + $this->commonArguments);
  }

  public function registerMigration($data) {
    $class = isset($data['class_name']) ? $data['class_name'] : 'MigrateYamlMigration';
    if (!in_array('MigrateYamlMigration', class_parents($class))) {
      throw new LogicException("The class defined in migration must extend MigrateYamlMigration");
    }

    $data += array(
      'arguments' => array(),
      'dependencies' => array(),
      'unmigrated_mappings' => array(),
    );

    $arguments = $data['arguments'];

    $arguments['mappings'] = $data['mappings'];
    $arguments['source'] = $data['source'];
    $arguments['destination'] = $data['destination'];
    $arguments['map'] = $data['map'];
    $arguments['dependencies'] = $data['dependencies'];
    $arguments['unmigrated_mappings'] = $data['unmigrated_mappings'];
    $arguments['machine_name'] = $data['name'];
    $arguments['group_name'] = $data['group_name'];

    Migration::registerMigration($class, $data['name'], $arguments + $this->commonArguments);
  }

}
