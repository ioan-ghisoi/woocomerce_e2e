/* eslint-disable func-names, prefer-arrow-callback */
import chai from 'chai';
import Globals from '../../config/globals';

const URL = Globals.value.url;
const VAL = Globals.value;
const BACKEND = Globals.selector.backend;
const FRONTEND = Globals.selector.frontend;

export default function () {
  this.Given(/^I set the viewport and timeout$/, () => {
    this.setDefaultTimeout(120 * 1000);
    browser.setViewportSize({
      width: VAL.resolution_w,
      height: VAL.resolution_h,
    }, true);
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
      browser.click(BACKEND.plugin.pci.activate_pci)
    }
    if (browser.isVisible(BACKEND.plugin.non_pci.activate_non_pci)) {
      browser.click(BACKEND.plugin.non_pci.activate_non_pci)
    }
    browser.click(BACKEND.plugin.non_pci.settings_non_pci);
    browser.waitUntil(function () {
      return browser.isVisible(BACKEND.plugin.non_pci.secret_key);
    }, VAL.timeout_out, 'settings should be loaded');
  });
  this.Given(/^I set the sandbox keys$/, () => {
    browser.waitUntil(function () {
      return browser.isVisible(BACKEND.plugin.non_pci.public_key);
    }, VAL.timeout_out, 'settings should be loaded');
    browser.setValue(BACKEND.plugin.non_pci.public_key, VAL.admin.non_pci_public_key);
    browser.setValue(BACKEND.plugin.non_pci.secret_key, VAL.admin.non_pci_secret_key);
    browser.setValue(BACKEND.plugin.non_pci.private_shared_key, VAL.admin.non_pci_private_shared_key);
  });
  this.Given(/^I save the backend settings$/, () => {
    browser.click(BACKEND.plugin.save);
  });
}
