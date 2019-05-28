<?php

class CRM_DigitalCurrency_BAO_Processor_Bitcoin
  extends CRM_DigitalCurrency_BAO_ProcessorCommon {

  public $_url = 'https://blockchain.info/rawaddr/';
  public $_currencySymbol = 'BTC';
  public $_conversionUnit = 0.00000001;

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

    //setup required params
    $params['offset'] = 0;
    $limit = CRM_Utils_Array::value('limit', $params);
    $cycles = 1;
    $trxns = [];

    //API only allows a max limit of 50, so we need to paginate
    if ($limit && $limit > 50) {
      $cycles = ceil($limit / 50);
      $params['limit'] = 50;
    }

    for ($x = 1; $x <= $cycles; $x++) {
      $urlParams = http_build_query($params);
      $urlDC = $this->_url . $address . '?' . $urlParams;

      $content = json_decode(file_get_contents($urlDC));
      //Civi::log()->debug('getTransactions', array('urlDC' => $urlDC, 'content' => $content));

      foreach ($content->txs as $trxn) {
        //get exchange rates
        $exchange = $this->getExchangeRate('USD', 'BTC', $trxn->time);
        //Civi::log()->debug('getTransactions', array('$exchange' => $exchange));

        $values = [
          'addr_source' => $trxn->inputs[0]->prev_out->addr,
          'trxn_hash' => $trxn->hash,
          'value_input' => $trxn->inputs[0]->prev_out->value,
          'value_input_exch' => $trxn->inputs[0]->prev_out->value * $this->_conversionUnit * $exchange,
          'timestamp' => $trxn->time,
        ];

        $totalOut = 0;
        $trxnOutFound = FALSE;
        foreach ($trxn->out as $out) {
          $totalOut += $out->value;

          if (!empty($out->addr_tag) && $out->addr_tag == 'Internet Archive') {
            $values['amount'] = $out->value * $this->_conversionUnit;
            $values['amount_exch'] = $out->value * $this->_conversionUnit * $exchange;
            $trxnOutFound = TRUE;
          }
        }

        //if no out trxn matching IA was found, this is not a "deposit" and should be skipped
        if (!$trxnOutFound) {
          continue;
        }

        $values['value_output'] = $totalOut;
        $values['value_output_exch'] = $totalOut * $this->_conversionUnit * $exchange;

        $values['fee'] = number_format(($trxn->inputs[0]->prev_out->value - $totalOut) * $this->_conversionUnit, 8);
        $values['fee_exch'] = $values['fee'] * $exchange;

        //Civi::log()->debug('getTransactions', array('$values' => $values));
        $trxns[] = $values;
      }

      //increment the offset
      $params['offset'] = $x * 50;
    }

    return $trxns;
  }
}
