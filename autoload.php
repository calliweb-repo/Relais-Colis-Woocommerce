<?php

\spl_autoload_register(function ($class) {
  static $map = array (
  'RelaisColisWoocommerce\\Relais_Colis_Woocommerce_Loader' => 'relais-colis-woocommerce.php',
  'RelaisColisWoocommerce\\Relais_Colis_Woocommerce' => 'class-relais-colis-woocommerce.php',
  'RelaisColisWoocommerce\\Tests\\Relais_Colis_Woocommerce_Tests' => 'tests/class-relais-colis-woocommerce-tests.php',
  'RelaisColisWoocommerce\\WPFw\\Api\\WP_API_Base' => 'includes/wpfw/api/class-wp-api-base.php',
  'RelaisColisWoocommerce\\WPFw\\Api\\WP_API_Request' => 'includes/wpfw/api/interface-wp-api-request.php',
  'RelaisColisWoocommerce\\WPFw\\Api\\WP_API_Response' => 'includes/wpfw/api/abstract-wp-api-response.php',
  'RelaisColisWoocommerce\\WPFw\\Api\\WP_API_JSON_Request' => 'includes/wpfw/api/abstract-wp-api-json-request.php',
  'RelaisColisWoocommerce\\WPFw\\Api\\WP_API_JSON_Response' => 'includes/wpfw/api/abstract-wp-api-json-response.php',
  'RelaisColisWoocommerce\\WPFw\\Api\\WP_API_XML_Request' => 'includes/wpfw/api/abstract-wp-api-xml-request.php',
  'RelaisColisWoocommerce\\WPFw\\Api\\WP_API_XML_Response' => 'includes/wpfw/api/abstract-wp-api-xml-response.php',
  'RelaisColisWoocommerce\\WPFw\\Traits\\Singleton' => 'includes/wpfw/traits/trait-singleton.php',
  'RelaisColisWoocommerce\\WPFw\\Traits\\Plugin_Action_Links' => 'includes/wpfw/traits/trait-plugin-action-links.php',
  'RelaisColisWoocommerce\\WPFw\\Traits\\Plugin_Activation_Control' => 'includes/wpfw/traits/trait-plugin-activation-control.php',
  'RelaisColisWoocommerce\\WPFw\\Utils\\WP_Log' => 'includes/wpfw/utils/class-wp-logger.php',
  'RelaisColisWoocommerce\\WPFw\\Utils\\WP_Helper' => 'includes/wpfw/utils/class-wp-helper.php',
  'RelaisColisWoocommerce\\WPFw\\Utils\\WP_Admin_Notices_Manager' => 'includes/wpfw/utils/class-wp-admin-notices-manager.php',
  'RelaisColisWoocommerce\\WPFw\\WP_PLoad' => 'includes/wpfw/abstract-wp-plugin-loader.php',
  'RelaisColisWoocommerce\\WPFw\\WP_Plugin' => 'includes/wpfw/abstract-wp-plugin.php',
  'RelaisColisWoocommerce\\RCAPI\\WP_Relais_Colis_API' => 'includes/rcapi/class-wp-relais-colis-api.php',
  'RelaisColisWoocommerce\\RCAPI\\WP_Relais_Colis_API_Exception' => 'includes/rcapi/class-wp-relais-colis-api-exception.php',
  'RelaisColisWoocommerce\\RCAPI\\WP_Relais_Colis_Response_Factory' => 'includes/rcapi/class-wp-relais-colis-response-factory.php',
  'RelaisColisWoocommerce\\RCAPI\\WP_Relais_Colis_Request_Factory' => 'includes/rcapi/class-wp-relais-colis-request-factory.php',
  'RelaisColisWoocommerce\\RCAPI\\WP_RC_Enseigne' => 'includes/rcapi/trait-wp-rc-enseigne.php',
  // RESPONSES
  'RelaisColisWoocommerce\\RCAPI\\WP_Relais_Colis_Error_Response' => 'includes/rcapi/responses/class-wp-relais-colis-error-response.php',
  'RelaisColisWoocommerce\\RCAPI\\WP_Relais_Colis_Response' => 'includes/rcapi/responses/abstract-wp-relais-colis-response.php',
  'RelaisColisWoocommerce\\RCAPI\\WP_RC_Get_Configuration_Response' => 'includes/rcapi/responses/class-wp-rc-get-configuration-response.php',
  'RelaisColisWoocommerce\\RCAPI\\WP_RC_Place_Advertisement_Response' => 'includes/rcapi/responses/class-wp-rc-place-advertisement-response.php',
  'RelaisColisWoocommerce\\RCAPI\\WP_RC_B2C_Place_Return_Response' => 'includes/rcapi/responses/class-wp-rc-btoc-place-return-response.php',
  'RelaisColisWoocommerce\\RCAPI\\WP_RC_Etiquette_Generate_Response' => 'includes/rcapi/responses/class-wp-rc-etiquette-generate-response.php',
  'RelaisColisWoocommerce\\RCAPI\\WP_RC_Transport_Generate_Response' => 'includes/rcapi/responses/class-wp-rc-transport-generate-response.php',
  'RelaisColisWoocommerce\\RCAPI\\WP_RC_Get_Infos_Response' => 'includes/rcapi/responses/class-wp-rc-get-infos-response.php',
  'RelaisColisWoocommerce\\RCAPI\\WP_RC_C2C_Get_Packages_Price_Response' => 'includes/rcapi/responses/class-wp-rc-ctoc-get-packages-price-response.php',
  'RelaisColisWoocommerce\\RCAPI\\WP_RC_Get_Data_Evts_Response' => 'includes/rcapi/responses/class-wp-rc-get-data-evts-response.php',
  // REQUESTS
  'RelaisColisWoocommerce\\RCAPI\\WP_Relais_Colis_Request' => 'includes/rcapi/requests/abstract-wp-relais-colis-request.php',
  'RelaisColisWoocommerce\\RCAPI\\WP_RC_B2C_Get_Configuration' => 'includes/rcapi/requests/class-wp-rc-btoc-get-configuration.php',
  'RelaisColisWoocommerce\\RCAPI\\WP_RC_C2C_Get_Configuration' => 'includes/rcapi/requests/class-wp-rc-ctoc-get-configuration.php',
  'RelaisColisWoocommerce\\RCAPI\\WP_RC_Bulk_Generate' => 'includes/rcapi/requests/class-wp-rc-bulk-generate.php',
  'RelaisColisWoocommerce\\RCAPI\\WP_RC_Generate' => 'includes/rcapi/requests/abstract-wp-rc-generate.php',
  'RelaisColisWoocommerce\\RCAPI\\WP_RC_B2C_Generate' => 'includes/rcapi/requests/class-wp-rc-btoc-generate.php',
  'RelaisColisWoocommerce\\RCAPI\\WP_RC_C2C_Generate' => 'includes/rcapi/requests/class-wp-rc-ctoc-generate.php',
  'RelaisColisWoocommerce\\RCAPI\\WP_RC_Place_Advertisement_Request' => 'includes/rcapi/requests/abstract-wp-rc-place-advertisement-request.php',
  'RelaisColisWoocommerce\\RCAPI\\WP_RC_B2C_Relay_Place_Advertisement' => 'includes/rcapi/requests/class-wp-rc-btoc-relay-place-advertisement.php',
  'RelaisColisWoocommerce\\RCAPI\\WP_RC_B2C_Home_Place_Advertisement' => 'includes/rcapi/requests/class-wp-rc-btoc-home-place-advertisement.php',
  'RelaisColisWoocommerce\\RCAPI\\WP_RC_C2C_Relay_Place_Advertisement' => 'includes/rcapi/requests/class-wp-rc-ctoc-relay-place-advertisement.php',
  'RelaisColisWoocommerce\\RCAPI\\WP_RC_Place_Return' => 'includes/rcapi/requests/abstract-wp-rc-btoc-place-return.php',
  'RelaisColisWoocommerce\\RCAPI\\WP_RC_Place_Return_V2' => 'includes/rcapi/requests/class-wp-rc-btoc-place-return-v2.php',
  'RelaisColisWoocommerce\\RCAPI\\WP_RC_Place_Return_V3' => 'includes/rcapi/requests/class-wp-rc-btoc-place-return-v3.php',
  'RelaisColisWoocommerce\\RCAPI\\WP_RC_C2C_Get_Infos' => 'includes/rcapi/requests/class-wp-rc-ctoc-get-infos.php',
  'RelaisColisWoocommerce\\RCAPI\\WP_RC_C2C_Get_Packages_Price' => 'includes/rcapi/requests/class-wp-rc-ctoc-get-packages-price.php',
  'RelaisColisWoocommerce\\RCAPI\\WP_RC_Transport_Generate' => 'includes/rcapi/requests/class-wp-rc-transport-generate.php',
  'RelaisColisWoocommerce\\RCAPI\\WP_RC_Get_Data_Evts' => 'includes/rcapi/requests/class-wp-rc-get-data-evts.php',


);

  if (isset($map[$class])) {
    require_once __DIR__ . '/' . $map[$class];
  }
}, true, false);