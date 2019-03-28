<?php

class CRM_DigitalCurrency_BAO_Processor_Bitcoin
  extends CRM_DigitalCurrency_BAO_ProcessorCommon {

  public $_url = 'https://blockchain.info/rawaddr/';
  public $_urlExchange = 'https://blockchain.info/ticker';
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

    //get exchange rates
    $exchange = $this->getExchangeRate();

    $trxns = array();
    foreach ($content->txs as $trxn) {
      $values = array(
        'addr_source' => $trxn->inputs[0]->prev_out->addr,
        'trxn_hash' => $trxn->hash,
        'value_input' => $trxn->inputs[0]->prev_out->value,
        'value_input_exch' => $trxn->inputs[0]->prev_out->value * .00000001 * $exchange,
        'timestamp' => $trxn->time,
      );

      $totalOut = 0;
      foreach ($trxn->out as $out) {
        $totalOut += $out->value;

        if ($out->addr_tag == 'Internet Archive') {
          $values['amount'] = $out->value;
          $values['amount_exch'] = $out->value * .00000001 * $exchange;
        }
      }
      $values['value_output'] = $totalOut;
      $values['value_output_exch'] = $totalOut * .00000001 * $exchange;

      $trxns[] = $values;
    }

    return $trxns;
  }

  /**
   * @return null
   *
   * get last exchange rate
   *
   * TODO: this supports passing multiple countries but we don't handle that upstream currently
   * TODO: we don't do any checking to determine if the country exists
   */
  function getExchangeRate($country = 'USD') {
    $content = json_decode(file_get_contents($this->_urlExchange));
    //Civi::log()->debug('getExchangeRate', array('content' => $content));

    return $content->$country->last;
  }
}
