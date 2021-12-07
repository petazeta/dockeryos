//cfg momey format: currency
import config from './config.js';
import {getCurrentLanguage} from './languages.js';

export function intToMoney(moneyValue) {
  const locale=config.currencyCodeLocale ? config.localeCurrency : getCurrentLanguage();
  moneyValue = moneyValue / 100;
  return new Intl.NumberFormat(locale, {style: 'currency', currency: config.currencyCode}).format(moneyValue);
}

export function moneyToInt(stringNumber) {
  const locale=config.currencyCodeLocale ? config.localeCurrency : getCurrentLanguage();
  return Math.round(parseLocaleNumber(stringNumber, locale) * 100);
}

function parseLocaleNumber(stringNumber, locale) {
    var thousandSeparator = Intl.NumberFormat(locale).format(11111).replace(/\p{Number}/gu, '');
    var decimalSeparator = Intl.NumberFormat(locale).format(1.1).replace(/\p{Number}/gu, '');

    return parseFloat(stringNumber
      .replace(new RegExp('\\' + thousandSeparator, 'g'), '')
      .replace(new RegExp('\\' + decimalSeparator), '.')
    );
}