export default {
  value: {
    url: {
      wordpress_base: 'http://127.0.0.1:8082/wordpress',
      admin_path: '/wp-admin',
      admin_path: '/wp-admin',
      payments_path: '/wp-admin/plugins.php',
    },
    admin: {
      username: 'checkout',
      password: 'Checkout17',
      three_d_password: 'Checkout1!',
      pci_secret_key: 'sk_test_d084c5ee-8407-4b10-ae46-f730ad7fe6b0',
      pci_public_key: 'pk_test_2e5eee90-99df-4f12-a934-c49a57a0e0d4',
      pci_private_shared_key: '66265906-1ff3-4b78-a3aa-9837a303e8e6',
      non_pci_secret_key: 'sk_test_fe1ea74c-e9be-4191-9781-76ec04523e29',
      non_pci_public_key: 'pk_test_537c069c-9533-47e3-9a4a-14c55b9781ee',
      non_pci_private_shared_key: 'fc8b91da-a59d-480e-93d7-3dd590948b04',
    },
    guest: {
      email: 'john@smith.com',
      name: 'John',
      lastname: 'Smith',
      address: '42 Ealing Broadway',
      city: 'City',
      county: '1',
      postcode: '85001',
      phone: '07123456789',
    },
    customer: {
      email: 'test@checkout.com',
      name: 'Test',
      lastname: 'Checkout',
      street: '1 Wall Street',
      city: 'London',
      country: 'GB',
      phone: '07987654321',
      password: 'Checkout17',
    },
    card: {
      visa: {
        card_number: '4242424242424242',
        month: '06',
        year: '18',
        cvv: '100',
      },
      mastercard: {
        card_number: '5436031030606378',
        month: '06',
        year: '25',
        cvv: '257',
      },
      amex: {
        card_number: '345678901234564',
        month: '06',
        year: '25',
        cvv: '1051',
      },
      diners: {
        card_number: '30123456789019',
        month: '06',
        year: '25',
        cvv: '257',
      },
      jcb: {
        card_number: '3530111333300000',
        month: '06',
        year: '18',
        cvv: '100',
      },
      discover: {
        card_number: '6011111111111117',
        month: '06',
        year: '18',
        cvv: '100',
      },
    },
  },
  selector: {
    frontend: {

    },
    backend: {
      admin_username: '#user_login',
      admin_password: '#user_pass',
      admin_sign_in: '#wp-submit',
      plugin: {
        save: 'input.button-primary',
        non_pci : {
          enable_plugin: '#woocommerce_woocommerce_checkout_non_pci_enabled',
          settings_non_pci: 'tr.active:nth-child(3) > td:nth-child(2) > div:nth-child(2) > span:nth-child(1) > a:nth-child(1)',
          activate_non_pci: 'tr.inactive:nth-child(3) > td:nth-child(2) > div:nth-child(2) > span:nth-child(1) > a:nth-child(1)',
          secret_key: '#woocommerce_woocommerce_checkout_non_pci_secret_key',
          private_shared_key: '#woocommerce_woocommerce_checkout_non_pci_private_shared_key',
          public_key: '#woocommerce_woocommerce_checkout_non_pci_public_key',
          title: '#woocommerce_woocommerce_checkout_non_pci_title',
          cancel_status_on_void: '#woocommerce_woocommerce_checkout_non_pci_void_status',
          payment_action: '#select2-woocommerce_woocommerce_checkout_non_pci_payment_action-container',
          autocapture_time: '#woocommerce_woocommerce_checkout_non_pci_auto_cap_time',
          new_order_status: '#select2-woocommerce_woocommerce_checkout_non_pci_order_status-container',
          three_d: '#select2-woocommerce_woocommerce_checkout_non_pci_is_3d-container',
          integration: '#select2-woocommerce_woocommerce_checkout_non_pci_integration_type-container',
          save_cards: '#woocommerce_woocommerce_checkout_non_pci_saved_cards',
          lightbox_url: '#woocommerce_woocommerce_checkout_non_pci_logo_url',
          theme: '#woocommerce_woocommerce_checkout_non_pci_theme_color',
          currency_code: '#woocommerce_woocommerce_checkout_non_pci_use_currency_code',
          js_title: '#woocommerce_woocommerce_checkout_non_pci_form_title',
          widget_color: '#woocommerce_woocommerce_checkout_non_pci_widget_color',
          form_button_color: '#woocommerce_woocommerce_checkout_non_pci_form_button_color',
          form_label_color: '#woocommerce_woocommerce_checkout_non_pci_form_button_color_label',
          overlay_shade: '#select2-woocommerce_woocommerce_checkout_non_pci_overlay_shade-container',
          opacity: '#woocommerce_woocommerce_checkout_non_pci_overlay_opacity',
          show_mobile_icons: '#woocommerce_woocommerce_checkout_non_pci_show_mobile_icons',
          payment_mode: '#select2-woocommerce_woocommerce_checkout_non_pci_payment_mode-container',
          js_theme: '#select2-woocommerce_woocommerce_checkout_non_pci_frames_theme-container',
        },
        pci: {
          enable_plugin: '#woocommerce_woocommerce_checkout_pci_enabled',
          activate_pci: 'tr.inactive:nth-child(4) > td:nth-child(2) > div:nth-child(2) > span:nth-child(1) > a:nth-child(1)',
          settings_pci: 'tr.active:nth-child(4) > td:nth-child(2) > div:nth-child(2) > span:nth-child(1) > a:nth-child(1)',
          secret_key: '#woocommerce_woocommerce_checkout_pci_secret_key',
          private_shared_key: '#woocommerce_woocommerce_checkout_pci_public_key',
          cancel_status_on_void: '#woocommerce_woocommerce_checkout_pci_void_status',
          payment_action: '#select2-woocommerce_woocommerce_checkout_pci_payment_action-container',
          autocapture_time: '#woocommerce_woocommerce_checkout_pci_auto_cap_time',
          new_order_status: '#select2-woocommerce_woocommerce_checkout_pci_order_status-container',
          three_d: '#select2-woocommerce_woocommerce_checkout_pci_is_3d-container',
          save_cards: '#woocommerce_woocommerce_checkout_pci_saved_cards',
        }
      },
    },
  },
};
