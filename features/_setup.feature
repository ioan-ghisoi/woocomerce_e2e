Feature: Prepare Magento for Tests
      Disable SecretKey Encryption, Create a Product, Set Checkout Keys

@watch
Scenario: I setup Magento for tests
      Given I go to the backend of Checkout's plugin
      Given I set the sandbox keys
      Given I save the backend settings