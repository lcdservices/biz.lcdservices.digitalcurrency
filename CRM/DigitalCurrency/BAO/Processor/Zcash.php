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
    $params['offset'] = $params['offset'] ?? 0;
    $params['sort'] = 'timestamp';
    $params['direction'] = 'descending';
    $limit = CRM_Utils_Array::value('limit', $params);
    $cycles = 1;
    $trxns = [];

    //API only allows a max limit of 20, so we need to paginate
    if ($limit && $limit > 20) {
      $cycles = ceil($limit/20);
      $params['limit'] = 20;
    }

    for ($x = 1; $x <= $cycles; $x++) {
      $urlParams = http_build_query($params);
      $urlDC = $this->_url . $address . '/recv?' . $urlParams;

      $content = json_decode(file_get_contents($urlDC));
      //Civi::log()->debug('getTransactions', array('urlDC' => $urlDC, 'content' => $content));

      foreach ($content as $trxn) {
        //get exchange rates based on timestamp
        $exchange = $this->getExchangeRate('USD', 'ZEC', $trxn->timestamp);

        $source = (!empty($trxn->vin[0]->retrievedVout->scriptPubKey->addresses[0])) ?
          $trxn->vin[0]->retrievedVout->scriptPubKey->addresses[0] : '';

        //determine the transferred amount
        $value = 0;
        foreach ($trxn->vout as $vout) {
          if ($vout->scriptPubKey->addresses[0] == $address) {
            $value = $vout->value;
          }
        }

        $values = [
          'addr_source' => $source,
          'trxn_hash' => $trxn->hash,
          'value_input' => $trxn->vin[0]->retrievedVout->valueZat,
          'value_input_exch' => $trxn->vin[0]->retrievedVout->value * $exchange,
          'value_output' => $trxn->vout[0]->valueZat,
          'value_output_exch' => $trxn->vout[0]->value * $exchange,
          'amount' => $value,
          'amount_exch' => $value * $exchange,
          'fee' => number_format($trxn->fee, 8),
          'fee_exch' => number_format($trxn->fee * $exchange, 8),
          'timestamp' => $trxn->timestamp,
        ];
        //Civi::log()->debug('getTransactions', array('$values' => $values));

        $trxns[] = $values;
      }

      //increment the offset
      $params['offset'] = $x * 20;
    }

    return $trxns;
  }
}
