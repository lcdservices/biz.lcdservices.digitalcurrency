<?php

/**
 * Class CRM_DigitalCurrency_BAO_Processor_Ripple
 *
 * https://xrpl.org/data-api.html#get-account-payments
 */

class CRM_DigitalCurrency_BAO_Processor_Ripple
  extends CRM_DigitalCurrency_BAO_ProcessorCommon {

  public $_url = 'https://data.ripple.com/v2/accounts/';
  public $_currencySymbol = 'XRP';

  /**
   * CRM_DigitalCurrency_BAO_Processor_Bitcoin constructor.
   */
  public function __construct() {
  }

  /**
   * @param $params
   *
   * @return array
   *
   * retrieve transactions and format to return for processing
   * note that this API returns value in Satoshi, so we must convert
   */
  function getTransactions($params) {
    //TODO get address from params
    $address = 'rGeyCsqc6vKXuyTGF39WJxmTRemoV3c97h';
    $params['descending'] = TRUE;
    $start = CRM_Utils_Array::value('start', $params, date('Y-m-d H:i:s', strtotime('-3 day')));
    $params['start'] = CRM_DigitalCurrency_BAO_Import::formatTimestamp(strtotime($start), 'c');
    $urlParams = http_build_query($params);
    $url = $this->_url.$address.'/payments?'.$urlParams;

    $content = json_decode(file_get_contents($url));
    //Civi::log()->debug('getTransactions', array('$url' => $url, 'content' => $content));

    $trxns = [];
    foreach ($content->payments as $trxn) {
      //skip if the destination is other than our address as it indicates outgoing funds
      if ($trxn->destination != $address) {
        continue;
      }

      //get exchange rates
      $exchange = $this->getExchangeRate('USD', $this->_currencySymbol, strtotime($trxn->executed_time));

      $values = [
        'addr_source' => $trxn->source,
        'trxn_hash' => $trxn->tx_hash,
        'value_input' => $trxn->amount,
        'value_input_exch' => $trxn->amount * $exchange,
        'value_output' => $trxn->delivered_amount,
        'value_output_exch' => $trxn->delivered_amount * $exchange,
        'amount' => $trxn->delivered_amount,
        'amount_exch' => $trxn->delivered_amount * $exchange,
        'fee' => number_format($trxn->transaction_cost, 10),
        'fee_exch' => number_format($trxn->transaction_cost * $exchange, 10),
        'timestamp' => strtotime($trxn->executed_time),
      ];

      //DEBUGGGING
      /*if ($trxn->tx_hash == '0FEAF30FC2620386DC16ABD0E31202179998B6BF30AB6C64F14A11C8C085F670') {
        Civi::log()->debug('getTransactions', array('$trxn' => $trxn, '$values' => $values));
      }*/

      $trxns[] = $values;
    }

    return $trxns;
  }
}
