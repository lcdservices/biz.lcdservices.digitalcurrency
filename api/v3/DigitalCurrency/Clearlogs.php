<?php
use CRM_DigitalCurrency_ExtensionUtil as E;

/**
 * DigitalCurrency.Import API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_digital_currency_Clearlogs_spec(&$spec) {
  $spec['confirm']['api.required'] = 1;
}

/**
 * DigitalCurrency.Import API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_digital_currency_Clearlogs($params) {
  //Civi::log()->debug('civicrm_api3_digital_currency_Import', array('params' => $params));

  try {
    $result = CRM_DigitalCurrency_BAO_Import::logClear(CRM_Utils_Array::value('provider', $params));

    if ($result) {
      return civicrm_api3_create_success($result, $params, 'DigitalCurrency', 'Clearlogs');
    }
    else {
      throw new API_Exception('Unable to clear logs', 903);
    }
  }
  catch (CRM_API3_Exception $e) {
    throw new API_Exception('Unable to clear logs', 904);
  }
}
