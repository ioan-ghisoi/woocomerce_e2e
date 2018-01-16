/* eslint-disable func-names, prefer-arrow-callback, no-reserved-keys */
import Globals from '../../config/globals';

const URL = Globals.value.url;
const VAL = Globals.value;
const BACKEND = Globals.selector.backend;

export default function () {
  this.Given(/^I set the viewport and timeout$/, () => {
    this.setDefaultTimeout(120 * 1000);
    browser.setViewportSize({
      width: VAL.resolution_w,
      height: VAL.resolution_h,
    }, true);
    browser.url('http://127.0.0.1/wordpress');
    browser.click("#asd");
  });
  this.Given(/^I go to the backend of Checkout's plugin$/, () => {
    browser.url(URL.wordpress_base + URL.admin_path);
    if (browser.isVisible(BACKEND.admin_username)) {
      browser.setValue(BACKEND.admin_username, VAL.admin.username);
      browser.setValue(BACKEND.admin_password, VAL.admin.password);
      browser.click(BACKEND.admin_sign_in);
    }
    browser.url(URL.wordpress_base + URL.payments_path);
    if (browser.isVisible(BACKEND.plugin.pci.activate_pci)) {
      browser.click(BACKEND.plugin.pci.activate_pci);
    }
    if (browser.isVisible(BACKEND.plugin.non_pci.activate_non_pci)) {
      browser.click(BACKEND.plugin.non_pci.activate_non_pci);
    }
  });
  this.Given(/^I open the (.*) settings$/, (option) => {
    switch (option) {
      case 'non-pci':
        browser.click(BACKEND.plugin.non_pci.settings_non_pci);
        browser.waitUntil(function () {
          return browser.isVisible(BACKEND.plugin.non_pci.public_key);
        }, VAL.timeout_out, 'settings should be loaded');
        break;
      case 'pci':
        browser.click(BACKEND.plugin.pci.settings_pci);
        browser.waitUntil(function () {
          return browser.isVisible(BACKEND.plugin.pci.secret_key);
        }, VAL.timeout_out, 'settings should be loaded');
        break;
      default:
        break;
    }
  });
  this.Given(/^I set the (.*) sandbox keys$/, (option) => {
    switch (option) {
      case 'non-pci':
        browser.setValue(BACKEND.plugin.non_pci.public_key, VAL.admin.non_pci_public_key);
        browser.setValue(BACKEND.plugin.non_pci.secret_key, VAL.admin.non_pci_secret_key);
        browser.setValue(BACKEND.plugin.non_pci.private_shared_key, VAL.admin.non_pci_private_shared_key);
        break;
      case 'pci':
        browser.setValue(BACKEND.plugin.pci.secret_key, VAL.admin.pci_secret_key);
        browser.setValue(BACKEND.plugin.pci.private_shared_key, VAL.admin.pci_private_shared_key);
        break;
      default:
        break;
    }
  });
  this.Given(/^I save the backend settings$/, () => {
    browser.click(BACKEND.plugin.save);
  });
  this.Given(/^I create an account$/, () => {
    browser.url(URL.wordpress_base);
  });
  this.Given(/^I enable the 2 checkout plugins$/, () => {
    browser.url(URL.wordpress_base + URL.payments_path);
    browser.click(BACKEND.plugin.non_pci.settings_non_pci);
    browser.waitUntil(function () {
      return browser.isVisible(BACKEND.plugin.non_pci.public_key);
    }, VAL.timeout_out, 'settings should be loaded');
    if (!browser.isSelected(BACKEND.plugin.non_pci.enable_plugin)) {
      browser.click(BACKEND.plugin.non_pci.enable_plugin);
      browser.click(BACKEND.plugin.save);
    }
    browser.url(URL.wordpress_base + URL.payments_path);
    browser.click(BACKEND.plugin.pci.settings_pci);
    browser.waitUntil(function () {
      return browser.isVisible(BACKEND.plugin.pci.secret_key);
    }, VAL.timeout_out, 'settings should be loaded');
    if (!browser.isSelected(BACKEND.plugin.pci.enable_plugin)) {
      browser.click(BACKEND.plugin.pci.enable_plugin);
      browser.click(BACKEND.plugin.save);
    }
    browser.url(URL.wordpress_base + URL.payments_path);
  });
}
