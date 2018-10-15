<?php

class CRM_DigitalCurrency_BAO_Processor_ProviderCommon {
  public $_urlExchange = 'https://min-api.cryptocompare.com/data/price';

  /**
   * @return null
   *
   * get last exchange rate
   * we typically call this from the provider class so the provider symbol is already known,
   * but we support passing explicitly as well
   *
   * TODO: this supports passing multiple countries but we don't handle that upstream currently
   * TODO: we don't do any checking to determine if the country exists
   *
   * TODO: move all provider-specific methods to use this common method
   */
  function getExchangeRate($currency = 'USD', $provider = NULL) {
    $provider = (!empty($provider)) ? $provider : $this->_currencySymbol;
    $urlParams = http_build_query(array('fsym' => $provider, 'tsyms' => $currency));

    $content = json_decode(file_get_contents($this->_urlExchange.'?'.$urlParams));
    //Civi::log()->debug('getExchangeRate', array('content' => $content));

    return $content->$currency;
  }
}
