INSERT INTO civicrm_currency (name, symbol, numeric_code, full_name)
  SELECT *
  FROM (SELECT 'BTC', '₿', '', 'Bitcoin') AS tmp
  WHERE NOT EXISTS (
    SELECT * FROM civicrm_currency WHERE name = 'BTC'
  );
INSERT INTO civicrm_currency (name, symbol, numeric_code, full_name)
  SELECT *
  FROM (SELECT 'BCH' name, '' symbol, '' numeric_code, 'Bitcoin Cash' full_name) AS tmp
  WHERE NOT EXISTS (
      SELECT * FROM civicrm_currency WHERE name = 'BCH'
  );
INSERT INTO civicrm_currency (name, symbol, numeric_code, full_name)
  SELECT *
  FROM (SELECT 'ETH' name, 'Ξ' symbol, '' numeric_code, 'Ethereum' full_name) AS tmp
  WHERE NOT EXISTS (
      SELECT * FROM civicrm_currency WHERE name = 'ETH'
  );
INSERT INTO civicrm_currency (name, symbol, numeric_code, full_name)
  SELECT *
  FROM (SELECT 'ZEC' name, '' symbol, '' numeric_code, 'Zcash' full_name) AS tmp
  WHERE NOT EXISTS (
      SELECT * FROM civicrm_currency WHERE name = 'ZEC'
  );


