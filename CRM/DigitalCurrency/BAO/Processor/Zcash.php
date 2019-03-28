<?php

class CRM_DigitalCurrency_BAO_Processor_Zcash
  extends CRM_DigitalCurrency_BAO_ProcessorCommon {

  public $_url = 'https://api.zcha.in/v2/mainnet/accounts/';
  public $_currencySymbol = 'ZEC';
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
    //TODO get address/apikey from params
    $address = 't1ZmpK4QFcvyQZ3ghTgSboBW8b4HgiZHQF9';

    //setup required params
    $params['offset'] = 0;

    $urlParams = http_build_query($params);
    $urlDC = $this->_url.$address.'/recv?'.$urlParams;

    $content = json_decode(file_get_contents($urlDC));
    //Civi::log()->debug('getTransactions', array('urlDC' => $urlDC, 'content' => $content));

    //get exchange rates
    $exchange = $this->getExchangeRate();

    $trxns = array();
    foreach ($content as $trxn) {
      $values = array(
        'addr_source' => $trxn->vin[0]->retrievedVout->scriptPubKey->addresses[0],
        'trxn_hash' => $trxn->hash,
        'value_input' => $trxn->vin[0]->retrievedVout->valueZat,
        'value_input_exch' => $trxn->vin[0]->retrievedVout->value * $exchange,
        'value_output' => $trxn->vout[0]->valueZat,
        'value_output_exch' => $trxn->vout[0]->value * $exchange,
        'timestamp' => $trxn->timeStamp,
      );

      $totalOut = $totalOutCvt = 0;
      foreach ($trxn->vout as $out) {
        $totalOut += $out->valueZat;
        $totalOutCvt += $out->value;
      }
      $values['value_output'] = $totalOut;
      $values['value_output_exch'] = $totalOutCvt * $exchange;

      $trxns[] = $values;
    }

    return $trxns;
  }
}
