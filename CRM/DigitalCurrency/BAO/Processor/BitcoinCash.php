<?php

class CRM_DigitalCurrency_BAO_Processor_BitcoinCash
  extends CRM_DigitalCurrency_BAO_ProcessorCommon {

  public $_url = 'https://bitcoincash.blockexplorer.com/api/addrs/';
  public $_urlExchange = 'https://bitpay.com/api/rates/BCH/USD';
  public $_currencySymbol = 'BCH';

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
    $address = 'qrzeh0y2uv2rdmjmkfqcmd39h9yqjrqwmqzaztef9w';

    //convert limit params
    $params['from'] = 0;
    if (!empty($params['limit'])) {
      $params['to'] = $params['limit'];
      unset($params['limit']);
    }

    $urlParams = http_build_query($params);
    $urlDC = $this->_url.$address.'/txs?'.$urlParams;

    $content = json_decode(file_get_contents($urlDC));
    //Civi::log()->debug('getTransactions', array('urlDC' => $urlDC, 'content' => $content));

    //get exchange rates
    $exchange = $this->getExchangeRate();

    $trxns = array();
    foreach ($content->items as $trxn) {
      $values = array(
        'addr_source' => str_replace('bitcoincash:', '', $trxn->vin[0]->addr),
        'trxn_hash' => $trxn->txid,
        'value_input' => $trxn->valueIn * 100000000,
        'value_input_exch' => $trxn->valueIn * $exchange,
        'value_output' => $trxn->valueOut * 100000000,
        'value_output_exch' => $trxn->valueOut * $exchange,
        'timestamp' => $trxn->time,
      );

      $trxns[] = $values;
    }

    return $trxns;
  }

  /**
   * @return null
   *
   * get last exchange rate
   *
   * TODO: this COULD support passing multiple countries but we don't handle that upstream currently
   * TODO: we don't do any checking to determine if the country exists
   * TODO: to support multiple currencies, remove USD from api URL
   */
  function getExchangeRate($country = 'USD') {
    $content = json_decode(file_get_contents($this->_urlExchange));
    //Civi::log()->debug('getExchangeRate', array('content' => $content));

    return $content->rate;
  }
}
