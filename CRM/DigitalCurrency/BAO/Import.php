<?php

class CRM_DigitalCurrency_BAO_Import {
  //TODO I feel like we need a way to flag that the oldest retrieved record
  //was already logged, to ensure we're not missing any. i.e. to make sure we
  //went back far enough in the transaction history...

  /**
   * @param null $provider
   *
   * main function to process
   */
  static function process($params) {
    $provider = CRM_Utils_Array::value('provider', $params, NULL);
    $method = CRM_Utils_Array::value('method', $params, 'File');

    //setup available params
    $providerParams = array(
      'limit' => CRM_Utils_Array::value('limit', $params, 50),
    );

    //if a single provider is explicitly requested
    if ($provider) {
      $providerClass = 'CRM_DigitalCurrency_BAO_Processor_' . $provider;
      if (class_exists($providerClass)) {
        $class = new $providerClass;
        $trxns = $class->getTransactions($providerParams);
        //Civi::log()->debug('process', array('trxns' => $trxns));

        //clean array
        self::cleanTrxns($trxns);

        $methodFunc = 'process'.$method;
        self::$methodFunc($trxns, $provider);
      }
    }
    else {
      //TODO cycle through all configured providers
    }
  }

  static function processFile($trxns, $provider) {
    //Civi::log()->debug('processFile', array('trxns' => $trxns));

    $content = array();
    foreach ($trxns as $trxn) {
      //check log to ensure it hasn't been imported already
      if (self::isProcessed($trxn)) {
        continue;
      }

      $fee = number_format($trxn['value_input_exch'] - $trxn['value_output_exch'], 2);
      $content[] = array(
        'type' => 'donation',
        'source' => $trxn['addr_source'],
        'funds_transfer_date' => date('Y-m-d H:i:s', $trxn['timestamp']),
        'completion_status' => 'Completed',
        'gross' => $trxn['value_output_exch'],
        'fees' => $fee,
        'net' => $trxn['value_input_exch'],
        'currency' => 'USD',
        'refunded' => NULL,
        'processor_txn_id' => $provider.'-'.$trxn['trxn_hash'],
        'payment_processor' => NULL,
        'payment_method_handle' => 'digital_currency_'.self::mapCurrency($provider),
        'subscription_id' => NULL,
        'last4' => NULL,
        'brand' => NULL,
        'exp_month' => NULL,
        'exp_year' => NULL,
        'country' => NULL,
        'source_url' => NULL,
        'source_url_context' => NULL,
        'contribution_page_id' => NULL,
        'note' => NULL,
      );

      self::logTrxn($trxn, $provider);
    }
    //Civi::log()->debug('processFile', array('$content' => $content));

    $json = json_encode($content);

    //write to file
    $folder = Civi::settings()->get('dc_export_path');
    $fileName = $folder.$provider.'_'.date('YmdHis').'.json';
    file_put_contents($fileName, $json);
  }

  static function processContrib($trxns, $provider) {
    //Civi::log()->debug('processFile', array('trxns' => $trxns));

    foreach ($trxns as $trxn) {
      //check log to ensure it hasn't been imported already
      if (self::isProcessed($trxn)) {
        continue;
      }

      //get Digital Currency contact
      $dcId = civicrm_api3('Contact', 'getvalue', array(
        'organization_name' => $provider,
        'return' => 'id',
      ));

      //setup custom fields
      $custom = array(
        'source_address' => CRM_Core_BAO_CustomField::getCustomFieldID('source_address', 'digital_currency_details'),
        'value_in' => CRM_Core_BAO_CustomField::getCustomFieldID('value_in', 'digital_currency_details'),
        'value_out' => CRM_Core_BAO_CustomField::getCustomFieldID('value_out', 'digital_currency_details'),
        'fee' => CRM_Core_BAO_CustomField::getCustomFieldID('fee', 'digital_currency_details'),
      );

      $fee = number_format($trxn['value_input_exch'] - $trxn['value_output_exch'], 2);
      $params = array(
        'contact_id' => $dcId,
        'financial_type_id' => 'Donation',
        'source' => $trxn['addr_source'],
        'receive_date' => date('Y-m-d H:i:s', $trxn['timestamp']),
        'contribution_status_id' => 'Completed',
        'total_amount' => $trxn['value_output_exch'],
        'fee_amount' => $fee,
        'net_amount' => $trxn['value_input_exch'],
        'currency' => 'USD',
        'trxn_id' => $provider.'-'.$trxn['trxn_hash'],
        'payment_instrument_id' => 'digital_currency_'.self::mapCurrency($provider),
        "custom_{$custom['source_address']}" => $trxn['addr_source'],
        "custom_{$custom['value_in']}" => number_format($trxn['value_input'], 0, '.', ''),
        "custom_{$custom['value_out']}" => number_format($trxn['value_output'], 0, '.', ''),
        "custom_{$custom['fee']}" => number_format($trxn['value_input'] - $trxn['value_output'], 0, '.', ''),
      );
      //Civi::log()->debug('processContrib', array('$params' => $params));

      try {
        civicrm_api3('Contribution', 'create', $params);
      }
      catch (CRM_API3_Exception $e) {
        Civi::log()->debug('processContrib', array('$e' => $e));
      }

      self::logTrxn($trxn, $provider);
    }
  }

  /**
   * @param $provider
   *
   * @return mixed
   *
   * TODO -- provider name to currency should be defined in the provider class and pulled dynamically
   */
  static function mapCurrency($provider) {
    $map = array(
      'Bitcoin' => 'BTC',
      'BitcoinCash' => 'BCH',
      'Etherium' => 'ETH',
      'Zcash' => 'ZEC',
    );

    return CRM_Utils_Array::value($provider, $map);
  }

  /**
   * @param $trxn
   * @param $provider
   *
   * store details about the transaction in the log and mark processed
   * user replace as we may mark a transaction as not processed in order to
   * re-process, in which case we want to update the log rather than add a
   * second one (trxn_hash is unique key)
   */
  static function logTrxn($trxn, $provider) {
    //Civi::log()->debug('logTrxn', array('trxn' => $trxn, 'provider' => $provider));

    CRM_Core_DAO::executeQuery("
      REPLACE INTO civicrm_digitalcurrency_log
      (provider, addr_source, trxn_hash, value_input, value_output, timestamp, is_processed)
      VALUES
      (%1, %2, %3, %4, %5, %6, 1)
    ", array(
      1 => array($provider, 'String'),
      2 => array($trxn['addr_source'], 'String'),
      3 => array($trxn['trxn_hash'], 'String'),
      4 => array($trxn['value_input'], 'String'),
      5 => array($trxn['value_output'], 'String'),
      6 => array(date('Y-m-d H:i:s', $trxn['timestamp']), 'String'),
    ));
  }

  static function isProcessed($trxn) {
    $processed = CRM_Core_DAO::singleValueQuery("
      SELECT id
      FROM civicrm_digitalcurrency_log
      WHERE trxn_hash = %1
        AND is_processed = 1
    ", array(
      1 => array($trxn['trxn_hash'], 'String'),
    ));

    return $processed;
  }

  static function cleanTrxns(&$trxns) {
    foreach ($trxns as &$trxn) {
      if (empty($trxn['addr_source'])) {
        $trxn['addr_source'] = '(source address unknown)';
      }

      foreach ($trxn as &$val) {
        if (empty($val)) {
          $val = 0;
        }
      }
    }
  }
}
