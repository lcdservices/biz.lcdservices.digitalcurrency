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
function _civicrm_api3_digital_currency_Import_spec(&$spec) {
  $spec['provider']['api.required'] = 1;
  $spec['provider']['options'] = CRM_DigitalCurrency_BAO_ProcessorCommon::getProviders();

  $spec['method']['api.default'] = 'Contrib';
  $spec['method']['options'] = ['Contrib', 'File'];

  $spec['limit']['description'] = 'Optionally indicate how many records should be retrieved and processed. The API will default to 50.';

  $spec['start']['description'] = 'Start date to pull data from. Not supported by all processors.';
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
function civicrm_api3_digital_currency_Import($params) {
  //Civi::log()->debug('civicrm_api3_digital_currency_Import', array('params' => $params));

  try {
    $result = CRM_DigitalCurrency_BAO_Import::process($params);

    if ($result) {
      return civicrm_api3_create_success($result, $params, 'DigitalCurrency', 'process');
    }
    else {
      throw new API_Exception('Unable to process transactions', 901);
    }
  }
  catch (CRM_API3_Exception $e) {
    throw new API_Exception('Unable to process transactions', 902);
  }
}
