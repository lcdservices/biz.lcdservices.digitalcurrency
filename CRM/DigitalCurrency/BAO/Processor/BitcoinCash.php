<?php

class CRM_DigitalCurrency_BAO_Processor_BitcoinCash
  extends CRM_DigitalCurrency_BAO_ProcessorCommon {

  public $_url = 'https://rest.bitcoin.com/v2/address/transactions/bitcoincash:';
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
    $legacyAddress = '1K1rgZ1dz9w7dsR1HGS1drmzfUHMtqx1Tc';

    //convert limit params
    $limit = CRM_Utils_Array::value('limit', $params, NULL);
    unset($params['limit']);

    $count = 0;
    $cycles = 1;
    $trxns = [];

    //API only allows a max limit of 20, so we need to paginate
    if ($limit && $limit > 10) {
      $cycles = ceil($limit/10);
    }

    for ($x = 0; $x < $cycles; $x++) {
      //increment the page
      $params['page'] = $x;

      $urlParams = http_build_query($params);
      $urlDC = $this->_url . $address . '?' . $urlParams;

      $content = json_decode(file_get_contents($urlDC));
      //Civi::log()->debug('getTransactions', array('urlDC' => $urlDC, 'content' => $content));

      foreach ($content->txs as $trxn) {
        if ($count >= $limit) {
          break;
        }

        //get exchange rates
        $exchange = $this->getExchangeRate('USD', 'BCH', $trxn->time);

        //need to cycle through vout and find the txn that matches our legacy address
        $amount = 0;
        foreach ($trxn->vout as $vout) {
          if ($vout->scriptPubKey->addresses[0] == $legacyAddress) {
            $amount = $vout->value;
          }
        }

        $values = [
          'addr_source' => str_replace('bitcoincash:', '', $trxn->vin[0]->addr),
          'trxn_hash' => $trxn->txid,
          'value_input' => $trxn->valueIn,
          'value_input_exch' => $trxn->valueIn * $exchange,
          'value_output' => $trxn->valueOut,
          'value_output_exch' => $trxn->valueOut * $exchange,
          'amount' => $amount,
          'amount_exch' => $amount * $exchange,
          'fee' => number_format($trxn->fees, 8),
          'fee_exch' => number_format($trxn->fees * $exchange, 8),
          'timestamp' => $trxn->time,
        ];

        $trxns[] = $values;
        $count ++;
      }

    }

    return $trxns;
  }
}
