<?php

return array(
  'dc_export_path' => array(
    'title' => 'Digital Currency Export Path',
    'group_name' => 'Digital Currency',
    'group' => 'digitalcurrency',
    'name' => 'dc_export_path',
    'type' => 'String',
    'default' => 1,
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Folder path for digital currency exports. Provide full server path.',
    'help_text' => '',
  ),
  'dc_logging' => array(
    'title' => 'Digital Currency Logging',
    'group_name' => 'Digital Currency',
    'group' => 'digitalcurrency',
    'name' => 'dc_logging',
    'type' => 'Boolean',
    'default' => 1,
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'When enabled, the API will log all transactions received and processed from the external provider. This is used to prevent duplicate processing. Logging should only be disabled during testing/development.',
    'help_text' => '',
  ),
);
