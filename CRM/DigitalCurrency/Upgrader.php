<?php
use CRM_DigitalCurrency_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_DigitalCurrency_Upgrader extends CRM_DigitalCurrency_Upgrader_Base {

  // By convention, functions that look like "function upgrade_NNNN()" are
  // upgrade tasks. They are executed in order (like Drupal's hook_update_N).

  /**
   * Example: Run an external SQL script when the module is installed.
   */
  public function install() {
    //$this->executeSqlFile('sql/currency_install.sql');
    $this->executeSqlFile('sql/log_install.sql');

    //configure payment methods
    //TODO extract from provider classes
    $pms = [
      'BTC' => 'Digital Currency: Bitcoin',
      'BCH' => 'Digital Currency: Bitcoin Cash',
      'ETH' => 'Digital Currency: Ethereum',
      'ZEC' => 'Digital Currency: Zcash',
      'XRP' => 'Digital Currency: Ripple',
    ];

    foreach ($pms as $dc => $label) {
      try {
        $opt = civicrm_api3('OptionValue', 'get', [
          'option_group_id' => 'payment_instrument',
          'name' => "digital_currency_{$dc}",
        ]);

        if (empty($opt['count'])) {
          civicrm_api3('OptionValue', 'create', [
            'option_group_id' => 'payment_instrument',
            'label' => $label,
            'name' => "digital_currency_{$dc}",
          ]);
        }
      } catch (CRM_API3_Exception $e) {
      }
    }

    //TODO we should ensure DC org contact records exist and create if they don't

    //convert currency fields to 8 digits
    $currencyFields = [
      'civicrm_contribution' => [
        'non_deductible_amount',
        'total_amount',
        'fee_amount',
        'net_amount',
        'tax_amount',
      ],
      'civicrm_line_item' => [
        'unit_price',
        'line_total',
        'non_deductible_amount',
        'tax_amount',
      ],
      'civicrm_financial_item' => [
        'amount',
      ],
      'civicrm_financial_trxn' => [
        'total_amount',
        'fee_amount',
        'net_amount',
      ],
    ];

    //we are storing as USD, so don't convert...
    /*foreach ($currencyFields as $table => $fields) {
      foreach ($fields as $field) {
        CRM_Core_DAO::executeQuery("
          ALTER TABLE {$table} MODIFY {$field} DECIMAL(20,2);
        ");
      }
    }*/
  }

  /**
   * Example: Work with entities usually not available during the install step.
   *
   * This method can be used for any post-install tasks. For example, if a step
   * of your installation depends on accessing an entity that is itself
   * created during the installation (e.g., a setting or a managed entity), do
   * so here to avoid order of operation problems.
   *
  public function postInstall() {
    $customFieldId = civicrm_api3('CustomField', 'getvalue', array(
      'return' => array("id"),
      'name' => "customFieldCreatedViaManagedHook",
    ));
    civicrm_api3('Setting', 'create', array(
      'myWeirdFieldSetting' => array('id' => $customFieldId, 'weirdness' => 1),
    ));
  }

  /**
   * Example: Run an external SQL script when the module is uninstalled.
   *
  public function uninstall() {
   $this->executeSqlFile('sql/myuninstall.sql');
  }

  /**
   * Example: Run a simple query when a module is enabled.
   *
  public function enable() {
    CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 1 WHERE bar = "whiz"');
  }

  /**
   * Example: Run a simple query when a module is disabled.
   *
  public function disable() {
    CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 0 WHERE bar = "whiz"');
  }

  /**
   * Example: Run a couple simple queries.
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_1100() {
    $this->ctx->log->info('Applying update 1100');
    CRM_Core_DAO::executeQuery("
      ALTER TABLE civicrm_digitalcurrency_log CHANGE value_input value_input DECIMAL(65,8) NOT NULL;
    ");
    CRM_Core_DAO::executeQuery("
      ALTER TABLE civicrm_digitalcurrency_log CHANGE value_output value_output DECIMAL(65,8) NOT NULL;
    ");

    return TRUE;
  }


  /**
   * Example: Run an external SQL script.
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4201() {
    $this->ctx->log->info('Applying update 4201');
    // this path is relative to the extension base dir
    $this->executeSqlFile('sql/upgrade_4201.sql');
    return TRUE;
  } // */


  /**
   * Example: Run a slow upgrade process by breaking it up into smaller chunk.
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4202() {
    $this->ctx->log->info('Planning update 4202'); // PEAR Log interface

    $this->addTask(E::ts('Process first step'), 'processPart1', $arg1, $arg2);
    $this->addTask(E::ts('Process second step'), 'processPart2', $arg3, $arg4);
    $this->addTask(E::ts('Process second step'), 'processPart3', $arg5);
    return TRUE;
  }
  public function processPart1($arg1, $arg2) { sleep(10); return TRUE; }
  public function processPart2($arg3, $arg4) { sleep(10); return TRUE; }
  public function processPart3($arg5) { sleep(10); return TRUE; }
  // */


  /**
   * Example: Run an upgrade with a query that touches many (potentially
   * millions) of records by breaking it up into smaller chunks.
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4203() {
    $this->ctx->log->info('Planning update 4203'); // PEAR Log interface

    $minId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(min(id),0) FROM civicrm_contribution');
    $maxId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(max(id),0) FROM civicrm_contribution');
    for ($startId = $minId; $startId <= $maxId; $startId += self::BATCH_SIZE) {
      $endId = $startId + self::BATCH_SIZE - 1;
      $title = E::ts('Upgrade Batch (%1 => %2)', array(
        1 => $startId,
        2 => $endId,
      ));
      $sql = '
        UPDATE civicrm_contribution SET foobar = whiz(wonky()+wanker)
        WHERE id BETWEEN %1 and %2
      ';
      $params = array(
        1 => array($startId, 'Integer'),
        2 => array($endId, 'Integer'),
      );
      $this->addTask($title, 'executeSql', $sql, $params);
    }
    return TRUE;
  } // */

}
