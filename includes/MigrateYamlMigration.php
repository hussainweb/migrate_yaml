<?php

/**
 * @file
 */

abstract class MigrateYamlMigration extends Migration {

  public function __construct(array $arguments) {
    $mappings = $arguments['mappings'];
    $source = $arguments['source'];
    $destination = $arguments['destination'];
    $map = $arguments['map'];
    $dependencies = $arguments['dependencies'];
    $unmigrated_mappings = $arguments['unmigrated_mappings'];

    parent::__construct($arguments);

    // Set up the migration with all our data.
    if ($dependencies) {
      $this->addHardDependencies($dependencies);
    }

    $this->source = $this->getSourceFromConfig($source, $arguments);
    $this->destination = $this->getDestinationFromConfig($destination, $arguments);
    $this->map = $this->getMapFromConfig($map, $arguments);

    foreach ($mappings as $destination_field => $mapping) {
      // Set up each mapping.
      $this->processMapping($destination_field, $mapping, $arguments);
    }

    foreach ($unmigrated_mappings as $mapping) {
      $this->processUnmigratedMapping($mapping, $arguments);
    }
  }

  // Figure out a way to support some migrate sources out of the box.
  // https://www.drupal.org/node/2694333
  abstract protected function getSourceFromConfig(array $source, array $arguments);

  protected function getDestinationFromConfig(array $destination, array $arguments) {
    $destination_class = $destination['class'];
    $arguments = isset($destination['arguments']) ? $destination['arguments'] : array();
    $ref = new ReflectionClass($destination_class);
    return $ref->newInstanceArgs($arguments);
  }

  protected function getMapFromConfig(array $map, array $arguments) {
    $destination_class = $arguments['destination']['class'];
    // @todo Add support to make the map class configurable.
    return new MigrateSQLMap(
      $arguments['machine_name'],
      $map['source_key'],
      $destination_class::getKeySchema()
    );
  }

  protected function processMapping($destination_field, $mapping, array $arguments) {
    if (is_string($mapping)) {
      $mapping = array(
        'source_field' => $mapping,
      );
    }

    $field_mapping = $this->addFieldMapping($destination_field, $mapping['source_field']);

    if (isset($mapping['source_migration'])) {
      $field_mapping->sourceMigration($mapping['source_migration']);
    }

    if (isset($mapping['callbacks'])) {
      $callbacks = $mapping['callbacks'];
      if (!is_array($callbacks)) {
        $callbacks = array($callbacks);
      }
      foreach ($callbacks as $callback) {
        $field_mapping->callbacks($callback);
      }
    }
  }

  protected function processUnmigratedMapping(array $mapping, array $arguments) {
    $issue_group = isset($mapping['name']) ? $mapping['name'] : NULL;
    $warn_on_override = isset($mapping['warn_on_override']) ? $mapping['warn_on_override'] : TRUE;
    if (isset($mapping['sources'])) {
      $this->addUnmigratedSources($mapping['sources'], $issue_group, $warn_on_override);
    }
    if (isset($mapping['destinations'])) {
      $this->addUnmigratedDestinations($mapping['destinations'], $issue_group, $warn_on_override);
    }
    // @todo: Add support for sources and destinations beginning with phrase.
    // https://www.drupal.org/node/2694327
  }

}
