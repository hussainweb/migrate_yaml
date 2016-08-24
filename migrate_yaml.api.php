<?php

/**
 * @file
 * Documentation for hooks defined by Migrate YAML.
 */

/**
 * Register migrations and groups defined by YML files in your module.
 *
 * If you are only defining migrations via YML files, then you only need to
 * implement this hook. It is not necessary to implement hook_migrate_api as
 * migrate_yaml module takes care of correctly registering all migrations.
 *
 * @return array
 *   An associative array with the following keys:
 *   - path: (required) The relative path to YML files defining migrations. The
 *     path is relative to the module implementing this hook.
 *   - groups path: (optional) The relative path to YML files defining migration
 *     groups. The path is relative to the module implementing this hook.
 *   - recursive: (optional) Defaults to TRUE. Set to FALSE if the migrations
 *     and groups path should not be recursively scanned.
 *   - common arguments: (optional) Set to an array with data needed by any
 *     migration. This is merged with the arguments passed to the migration
 *     constructor when initializing a migration.
 *   - group arguments: (optional) Similar to 'common arguments' but passed to
 *     groups instead of the migration classes.
 */
function hook_migrate_yaml_api() {
  // These are our common arguments for all our migrations.
  $common_arguments = array(
    'source_db' => array(
      'username' => 'db-username',
      'password' => 'db-password',
      'host' => 'db-host',
      'name' => 'db-name',
    ),
  );

  $api = array(
    'path' => 'migrations',
    'groups path' => 'migration-groups',
    'recursive' => TRUE,
    'common arguments' => $common_arguments,
  );
  return $api;
}
