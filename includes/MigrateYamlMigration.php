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
    $key_schema_args = (!empty($arguments['destination']['key_schema_arguments'])) ? $arguments['destination']['key_schema_arguments'] : array();
    $schema = call_user_func_array(array($destination_class, 'getKeySchema'), $key_schema_args);

    // @todo Add support to make the map class configurable.
    return new MigrateSQLMap(
      $arguments['machine_name'],
      $map['source_key'],
      $schema
    );
  }

  protected function processMapping($destination_field, $mapping, array $arguments) {
    if (is_string($mapping)) {
      $mapping = array(
        'source_field' => $mapping,
      );
    }

    if (!isset($mapping['source_field'])) {
      $mapping['source_field'] = NULL;
    }

    $field_mapping = $this->addFieldMapping($destination_field, $mapping['source_field']);

    if (isset($mapping['default_value'])) {
      $field_mapping->defaultValue($mapping['default_value']);
    }

    if (isset($mapping['source_migration'])) {
      $field_mapping->sourceMigration($mapping['source_migration']);
    }

    if (isset($mapping['callbacks'])) {
      $callbacks = $mapping['callbacks'];
      if (!is_array($callbacks)) {
        $callbacks = array($callbacks);
      }
      foreach ($callbacks as $callback) {
        if (substr($callback, 0, 2) == '::') {
          $callback = array($this, substr($callback, 2));
        }
        $field_mapping->callbacks($callback);
      }
    }

    if (isset($mapping['separator'])) {
      $field_mapping->separator($mapping['separator']);
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
    if (isset($mapping['destinations_beginning_with'])) {
      $skip_fields = isset($mapping['skip_fields']) ? $mapping['skip_fields'] : array();
      $this->addUnmigratedDestinationsBeginningWith($mapping['destinations_beginning_with'], $issue_group, $warn_on_override, $skip_fields);
    }
    if (isset($mapping['destinations_callback'])) {
      $skip_fields = isset($mapping['skip_fields']) ? $mapping['skip_fields'] : array();
      $method = 'getDestinations' . $mapping['destinations_callback'];
      if (method_exists($this, $method)) {
        $fields = $this->$method();
        $fields = array_diff($fields, $skip_fields);
        $this->addUnmigratedDestinations($fields, $issue_group, $warn_on_override);
      }
      else {
        drupal_set_message(t('Method %method could not be found on the migration object.', array('%method' => $method)), 'error');
      }
    }
  }

  /**
   * Helper method to mark all fields beginning with a prefix as DNM.
   *
   * @param string $prefix
   *   Prefix to search for and mark as not for migration.
   * @param string $issue_group
   *   (Optional) Group to mark these mappings.
   * @param bool $warn_on_override
   *   (Optional) Warn if the mapping is being overridden.
   * @param array $skip_fields
   *   (Optional) Array of fields to skip from being marked as DNM.
   */
  public function addUnmigratedDestinationsBeginningWith($prefix, $issue_group = NULL, $warn_on_override = TRUE, array $skip_fields = array()) {
    $unmigrated_fields = array();
    $fields = $this->destination->fields();
    $prefix_len = strlen($prefix);
    foreach ($fields as $field_name => $value) {
      if (substr($field_name, 0, $prefix_len) == $prefix && !in_array($field_name, $skip_fields)) {
        $unmigrated_fields[] = $field_name;
      }
    }

    if (!empty($unmigrated_fields)) {
      $this->addUnmigratedDestinations($unmigrated_fields, $issue_group, $warn_on_override);
    }
  }

}
