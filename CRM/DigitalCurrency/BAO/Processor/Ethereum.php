<?php

class CRM_DigitalCurrency_BAO_Processor_Ethereum
  extends CRM_DigitalCurrency_BAO_ProcessorCommon {

  //https://etherscan.io/apis#accounts
  public $_url = 'http://api.etherscan.io/api';
  public $_currencySymbol = 'ETH';
  public $_conversionUnit = 0.000000000000000001;

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
    $address = '0xFA8E3920daF271daB92Be9B87d9998DDd94FEF08';
    $apikey = '7ISG85H4NMXSG9VXQ58DTQVDWE46QT4PMF';

    //setup required params
    $params['module'] = 'account';
    $params['action'] = 'txlist';
    $params['address'] = $address;
    $params['page'] = 1;
    $params['sort'] = 'desc';
    $params['apikey'] = $apikey;

    //convert limit param; set default = 50
    $params['offset'] = CRM_Utils_Array::value('limit', $params, 50);
    unset($params['limit']);

    $urlParams = http_build_query($params);
    $urlDC = $this->_url.'?'.$urlParams;

    $content = json_decode(file_get_contents($urlDC));
    //Civi::log()->debug('getTransactions', array('urlDC' => $urlDC, 'content' => $content));

    $trxns = array();
    foreach ($content->result as $trxn) {
      //get exchange rates
      $exchange = $this->getExchangeRate('USD', 'ETH', $trxn->timeStamp);

      //anything other than 'Ox' is an altcoin... I think...
      if ($trxn->input == '0x') {
        $values = [
          'addr_source' => $trxn->from,
          'trxn_hash' => $trxn->hash,
          'value_input' => $trxn->value,
          'value_input_exch' => $trxn->value * $this->_conversionUnit * $exchange,
          'value_output' => $trxn->value - ($trxn->gasUsed * $trxn->gasPrice),
          'value_output_exch' => ($trxn->value - ($trxn->gasUsed * $trxn->gasPrice)) * $this->_conversionUnit * $exchange,
          'amount' => $trxn->value * $this->_conversionUnit,
          'amount_exch' => $trxn->value * $this->_conversionUnit * $exchange,
          'fee' => number_format(($trxn->gasUsed * $trxn->gasPrice * $this->_conversionUnit), 10),
          'fee_exch' => number_format(($trxn->gasUsed * $trxn->gasPrice * $this->_conversionUnit) * $exchange, 10),
          'timestamp' => $trxn->timeStamp,
        ];

        $trxns[] = $values;
      }
    }

    return $trxns;
  }
}
