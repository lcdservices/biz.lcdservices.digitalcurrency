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
    $params['from'] = CRM_Utils_Array::value('offset', $params, 0);
    $params['to'] = $limit = CRM_Utils_Array::value('limit', $params, NULL);
    unset($params['limit']);
    unset($params['offset']);

    $cycles = 1;
    $trxns = [];

    //API only allows a max limit of 20, so we need to paginate
    if ($limit && $limit > 50) {
      $cycles = ceil($limit/50);
      $params['to'] = 50;
    }

    for ($x = 1; $x <= $cycles; $x++) {
      $urlParams = http_build_query($params);
      $urlDC = $this->_url . $address . '/txs?' . $urlParams;

      $content = json_decode(file_get_contents($urlDC));
      //Civi::log()->debug('getTransactions', array('urlDC' => $urlDC, 'content' => $content));

      //get exchange rates
      $exchange = $this->getExchangeRate();

      foreach ($content->items as $trxn) {
        $values = [
          'addr_source' => str_replace('bitcoincash:', '', $trxn->vin[0]->addr),
          'trxn_hash' => $trxn->txid,
          'value_input' => $trxn->valueIn * 100000000,
          'value_input_exch' => $trxn->valueIn * $exchange,
          'value_output' => $trxn->valueOut * 100000000,
          'value_output_exch' => $trxn->valueOut * $exchange,
          'amount' => $trxn->valueOut,
          'amount_exch' => $trxn->valueOut * $exchange,
          'fee' => number_format($trxn->fees, 8),
          'fee_exch' => number_format($trxn->fees * $exchange, 8),
          'timestamp' => $trxn->time,
        ];

        $trxns[] = $values;
      }

      //increment the offset
      $params['from'] = $x * 50;
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
  function getExchangeRate($country = 'USD', $provider = NULL) {
    $content = json_decode(file_get_contents($this->_urlExchange));
    //Civi::log()->debug('getExchangeRate', array('content' => $content));

    return $content->rate;
  }
}
