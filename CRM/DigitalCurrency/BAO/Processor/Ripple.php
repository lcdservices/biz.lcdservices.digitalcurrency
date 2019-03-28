<?php

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
    $urlParams = http_build_query($params);
    $url = $this->_url.$address.'/payments?'.$urlParams;

    $content = json_decode(file_get_contents($url));
    //Civi::log()->debug('getTransactions', array('$url' => $url, 'content' => $content));

    //get exchange rates
    $exchange = $this->getExchangeRate('USD', $this->_currencySymbol);

    $trxns = [];
    foreach ($content->payments as $trxn) {
      $values = [
        'addr_source' => $trxn->source,
        'trxn_hash' => $trxn->tx_hash,
        'value_input' => $trxn->amount,
        'value_input_exch' => $trxn->amount * $exchange,
        'value_output' => $trxn->delivered_amount,
        'value_output_exch' => $trxn->delivered_amount * $exchange,
        'timestamp' => strtotime($trxn->executed_time),
        'fee' => $trxn->transaction_cost,
        'fee_exch' => $trxn->transaction_cost * $exchange,
      ];

      $trxns[] = $values;
    }

    return $trxns;
  }
}
