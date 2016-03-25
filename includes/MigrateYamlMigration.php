<?php

/**
 * @file
 */

class MigrateYamlMigration extends Migration {

  public function __construct(array $arguments) {
    $mappings = $arguments['mappings'];
    $source = $arguments['source'];
    $destination = $arguments['destination'];
    $map = $arguments['map'];
    unset($arguments['mappings'], $arguments['source'], $arguments['destination'], $arguments['map']);

    parent::__construct($arguments);

    // @todo Set up the migration with all our data.
    // https://www.drupal.org/node/2694159
  }

}
