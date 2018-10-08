<?php

class CRM_DigitalCurrency_BAO_Processor_Bitcoin {

  public $_url = 'https://blockchain.info/rawaddr/';
  public $_currencySymbol = 'BTC';

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
    $address = '1Archive1n2C579dMsAu3iC6tWzuQJz8dN';
    $urlParams = http_build_query($params);
    $urlDC = $this->_url.$address.'?'.$urlParams;

    $content = json_decode(file_get_contents($urlDC));
    //Civi::log()->debug('getTransactions', array('urlDC' => $urlDC, 'content' => $content));

    $trxns = array();
    foreach ($content->txs as $trxn) {
      $values = array(
        'addr_source' => $trxn->inputs[0]->prev_out->addr,
        'trxn_hash' => $trxn->hash,
        'value_input' => $trxn->inputs[0]->prev_out->value * 0.00000001,
        'timestamp' => $trxn->time,
      );

      $totalOut = 0;
      foreach ($trxn->out as $out) {
        $totalOut += $out->value;
      }
      $values['value_output'] = $totalOut * 0.00000001;

      $trxns[] = $values;
    }

    return $trxns;
  }
}
