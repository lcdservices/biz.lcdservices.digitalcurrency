# biz.lcdservices.digitalcurrency

![Screenshot](/images/screenshot.png)

This extension retrieves digital currency records and imports them into CiviCRM or generates a json export file.

The extension is licensed under [AGPL-3.0](LICENSE.txt).

## Requirements

* PHP v5.4+
* CiviCRM (*FIXME: Version number*)

## Installation (Web UI)

This extension has not yet been published for installation via the web UI.

## Installation (CLI, Zip)

Sysadmins and developers may download the `.zip` file for this extension and
install it with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
cd <extension-dir>
cv dl biz.lcdservices.digitalcurrency@https://github.com/FIXME/biz.lcdservices.digitalcurrency/archive/master.zip
```

## Installation (CLI, Git)

Sysadmins and developers may clone the [Git](https://en.wikipedia.org/wiki/Git) repo for this extension and
install it with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
git clone https://github.com/FIXME/biz.lcdservices.digitalcurrency.git
cv en digitalcurrency
```

## Usage

After installing the extension you will need to configure it for your use case. First set the path for use with data export. This can currently be done through the API web explorer (Support > Developer > API Explorer). Select Entity = Setting, Action = create, and in the field selection search for "Digital Currency Export Path." The value should be the full server path name.

Enabling the desired currencies at Administer > Localization > Languages, Currency, Locations. This extension installs four available currencies:

* Bitcoin (BTC)
* Bitcoin Cash (BCH)
* Ethereum (ETH)
* Zcash (ZEC)

Note, for the time being, the currency codes listed above are used to refer to each currency. If at some point ISO-supported symbols and codes are defined, we will migrate to those values.

The extension will also add a new Payment Method titled "Digital Currency." However, you will need to associate the method with your desired account. Navigate to Administer > CiviContribute > Payment Methods and edit the payment method accordingly.

Next, navigate to Administer > CiviContribute > Digital Currency Settings. 

## Known Issues

(* FIXME *)
