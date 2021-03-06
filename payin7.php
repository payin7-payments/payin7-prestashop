<?php
/**
 * 2015-2016 Copyright (C) Payin7 S.L.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * DISCLAIMER
 *
 * Do not modify this file if you wish to upgrade the Payin7 module automatically in the future.
 *
 * @author    Payin7 S.L. <info@payin7.com>
 * @copyright 2015-2016 Payin7 S.L.
 * @license   http://opensource.org/licenses/GPL-3.0 GNU General Public License, version 3 (GPL-3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/** @noinspection PhpUndefinedClassInspection */

/**
 * Class Payin7
 * @method string l(string $str)
 * @property string identifier
 * @property Smarty smarty
 */
class Payin7 extends PaymentModule
{
    const MODULE_NAME = 'payin7';
    const PLUGIN_VERSION = '1.0.11';
    const MIN_PHP_VER = '5.3.3';

    const SETTINGS_FORM_NAME = 'submitPayin7Settings';

    const CFG_DEFAULT_PAYIN7_API_SERVER_HOSTNAME = 'payin7.com';
    const CFG_DEFAULT_PAYIN7_API_VERSION = 'v1';
    const CFG_DEFAULT_PAYIN7_API_USE_SECURE = true;

    const CFG_DEFAULT_PAYIN7_SBN_ENABLED = true;

    const QUICK_HISTORY_SEND_TIMEOUT = 10;

    const LAST_ORDER_ID_SESS_KEY = 'payin7_last_order_id';

    const PM_UNAV_PLATFORM_UNAVAILABLE = 10;
    const PM_UNAV_CURRENCY_UNSUPPORTED = 11;
    const PM_UNAV_MIN_ORDER_PLATFORM_NOTMET = 12;
    const PM_UNAV_MAX_ORDER_PLATFORM_NOTMET = 13;
    const PM_UNAV_COUNTRY_UNSUPPORTED = 14;
    const PM_UNAV_MIN_ORDER_STORE_NOTMET = 15;
    const PM_UNAV_MAX_ORDER_STORE_NOTMET = 16;
    const PM_UNAV_METHOD_DISABLED = 17;

    const SERVICE_API = 1;
    const SERVICE_BACKEND = 2;
    const SERVICE_FRONTEND = 3;
    const SERVICE_RES = 4;
    const SERVICE_JSAPI = 5;

    const SOURCE_NOTIFY = 'notify';
    const SOURCE_FRONTEND = 'frontend';

    const PAYIN7_ORDER_STATE_ACCEPTED = 'accepted';
    const PAYIN7_ORDER_STATE_REJECTED = 'rejected';
    const PAYIN7_ORDER_STATE_VERIFIED = 'verified';

    const REJECT_COOKIE_NAME = 'p7rj';

    const JSCSSMIN_VER = '1454762769000';

    /** PAID actually means accepted after verification (it may not really be paid for some payment methods */
    const PAYIN7_ORDER_STATE_PAID = 'paid';
    const PAYIN7_ORDER_STATE_CANCELLED = 'cancelled';
    const PAYIN7_ORDER_STATE_ERROROUS = 'errorous';

    private $_service_subdomains = array(
        self::SERVICE_API => 'api',
        self::SERVICE_BACKEND => 'clients',
        self::SERVICE_FRONTEND => 'stores',
        self::SERVICE_RES => 'res',
        self::SERVICE_JSAPI => 'jscore'
    );

    /** @var Context */
    protected $context;

    private $_is14;
    private $_is15up;
    private $_is15;
    protected $_output;

    protected $_use_custom_urls = false;

    protected $_path;
    protected $local_path;

    /** @var Payin7Logger */
    protected $logger;

    /** @var array|null */
    protected $fields_form;

    private $_module_routes = array(
        'orderfinalize_handler' => array(
            'controller' => 'orderfinalize',
            'rule' => 'payin7_orderfinalize/{order_id}',
            'keywords' => array(
                'order_id' => array('regexp' => '[_a-zA-Z0-9_-]+', 'param' => 'order_id'),
            ),
            'params' => array(
                'fc' => 'module',
                'module' => self::MODULE_NAME
            )
        ),
        'ordervalidate_handler' => array(
            'controller' => 'ordervalidate',
            'rule' => 'payin7_ordervalidate/{payment_method}',
            'keywords' => array(
                'payment_method' => array('regexp' => '[_a-zA-Z0-9_-]+', 'param' => 'payment_method'),
            ),
            'params' => array(
                'fc' => 'module',
                'module' => self::MODULE_NAME
            )
        ),
        'ordervalidate_handler2' => array(
            'controller' => 'ordervalidate',
            'rule' => 'payin7_ordervalidate',
            'keywords' => array(),
            'params' => array(
                'fc' => 'module',
                'module' => self::MODULE_NAME
            )
        ),
        'ordercancel2_handler' => array(
            'controller' => 'ordercancel',
            'rule' => 'payin7_ordercancel/{order_id}',
            'keywords' => array(
                'order_id' => array('regexp' => '[_a-zA-Z0-9_-]+', 'param' => 'order_id'),
            ),
            'params' => array(
                'fc' => 'module',
                'module' => self::MODULE_NAME
            )
        ),
        'ordercancel_handler' => array(
            'controller' => 'ordercancel',
            'rule' => 'payin7_ordercancel',
            'keywords' => array(),
            'params' => array(
                'fc' => 'module',
                'module' => self::MODULE_NAME
            )
        ),
        'ordersuccess_handler' => array(
            'controller' => 'ordersuccess',
            'rule' => 'payin7_ordersuccess',
            'keywords' => array(),
            'params' => array(
                'fc' => 'module',
                'module' => self::MODULE_NAME
            )
        ),
        'notify_handler' => array(
            'controller' => 'notify',
            'rule' => 'payin7_notify',
            'keywords' => array(),
            'params' => array(
                'fc' => 'module',
                'module' => self::MODULE_NAME
            )
        ),
    );

    public function __construct()
    {
        $this->name = self::MODULE_NAME;
        $this->tab = 'payments_gateways';
        $this->version = self::PLUGIN_VERSION;
        $this->author = 'payin7';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $this->ps_versions_compliancy = array('min' => '1.4', 'max' => '9999');

        /** @noinspection PhpUndefinedClassInspection */
        parent::__construct();

        /** @noinspection PhpUndefinedMethodInspection */
        $this->displayName = $this->l('Payin7');
        /** @noinspection PhpUndefinedMethodInspection */
        $this->description = $this->l('Finance your Dreams!');

        $this->_is14 = version_compare(_PS_VERSION_, '1.5', '<');
        $this->_is15up = version_compare(_PS_VERSION_, '1.5', '>=');
        $this->_is15 = version_compare(_PS_VERSION_, '1.5', '>=') && version_compare(_PS_VERSION_, '1.6', '<');

        /* Backward compatibility */
        /** @noinspection PhpIncludeInspection */
        if (_PS_VERSION_ < '1.5') {
            /** @noinspection PhpIncludeInspection */
            require _PS_MODULE_DIR_ . $this->name . '/backward_compatibility/backward.php';
        }

        if (!defined('DS')) {
            define('DS', DIRECTORY_SEPARATOR);
        }

        /** @noinspection PhpIncludeInspection */
        require_once __DIR__ . DS . 'classes' . DS . 'logger' . DS . 'Payin7Logger.php';

        $this->logger = Payin7Logger::getInstance()
            ->setFilename($this->getLogFilename())
            ->setDebugEnabled($this->getConfigApiDebugMode());

        // models
        /** @noinspection PhpIncludeInspection */
        require_once __DIR__ . DS . 'models' . DS . 'base.php';

        // tools
        /** @noinspection PhpIncludeInspection */
        require_once __DIR__ . DS . 'classes' . DS . 'tools' . DS . 'shortcuts.php';
        /** @noinspection PhpIncludeInspection */
        require_once __DIR__ . DS . 'classes' . DS . 'tools' . DS . 'inflector.php';
        /** @noinspection PhpIncludeInspection */
        require_once __DIR__ . DS . 'classes' . DS . 'tools' . DS . 'string_utils.php';
        /** @noinspection PhpIncludeInspection */
        require_once __DIR__ . DS . 'classes' . DS . 'tools' . DS . 'unicode.php';
    }

    protected function getLogFilename()
    {
        return _PS_ROOT_DIR_ . DS . 'log' . DS . 'payin7.log';
    }

    protected function getPaymentMethods()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return array(
            'seven_days' => array(
                'title' => $this->l('Pay in 7 days')
            ),
            'installments' => array(
                'title' => $this->l('Finance Payment')
            )
        );
    }

    public function install()
    {
        // check the PHP version
        if (!version_compare(phpversion(), self::MIN_PHP_VER, '>=')) {
            return false;
        }

        /** @noinspection PhpUndefinedClassInspection */
        Configuration::updateValue('PAYIN7_API_SERVER_HOSTNAME', self::CFG_DEFAULT_PAYIN7_API_SERVER_HOSTNAME);
        /** @noinspection PhpUndefinedClassInspection */
        Configuration::updateValue('PAYIN7_API_VERSION', self::CFG_DEFAULT_PAYIN7_API_VERSION);
        /** @noinspection PhpUndefinedClassInspection */
        Configuration::updateValue('PAYIN7_API_USE_SECURE', self::CFG_DEFAULT_PAYIN7_API_USE_SECURE);

        /** @noinspection PhpUndefinedClassInspection */
        Configuration::updateValue('PAYIN7_SBN_ENABLED', self::CFG_DEFAULT_PAYIN7_SBN_ENABLED);
        /** @noinspection PhpUndefinedClassInspection */
        Configuration::updateValue('PAYIN7_SBN_SECURE_KEY', '');

        // create the custom payin7 order states
        $this->createCustomOrderStates();

        // previously - PS_OS_CHEQUE
        /** @noinspection PhpUndefinedClassInspection */
        Configuration::updateValue('PAYIN7_ID_ORDER_STATE_PENDING', Configuration::get('PAYIN7_OS_PENDING'));

        // previously - PS_OS_PAYMENT
        /** @noinspection PhpUndefinedClassInspection */
        Configuration::updateValue('PAYIN7_ID_ORDER_STATE_ACCEPTED', Configuration::get('PAYIN7_OS_ACCEPTED'));

        // previously - PS_OS_CANCELED
        /** @noinspection PhpUndefinedClassInspection */
        Configuration::updateValue('PAYIN7_ID_ORDER_STATE_CANCELLED', Configuration::get('PAYIN7_OS_CANCELLED'));

        /** @noinspection PhpUndefinedClassInspection */
        if (!parent::install()) {
            return false;
        }

        $ret = true;

        if ($this->_is15up) {
            /** @noinspection PhpUndefinedMethodInspection */
            $ret = $this->registerHook('displayPaymentEU') &&
                $this->registerHook('actionAdminControllerSetMedia') &&
                $this->registerHook('moduleRoutes');
        }

        /** @noinspection PhpUndefinedMethodInspection */
        /** @noinspection PhpUndefinedClassInspection */
        $ret = $ret &&
            $this->installDb() &&
            $this->registerHook('top') &&
            $this->registerHook('header') &&
            $this->registerHook('payment') &&
            $this->registerHook('paymentReturn') &&
            $this->registerHook('footer') &&
            $this->registerHook('adminOrder') &&
            $this->registerHook('updateOrderStatus') &&
            $this->registerHook('newOrder');

        // manually run upgrades for backward compatibility
        $this->runUpgrades(true);

        return $ret;
    }

    /**
     * Launch upgrade process
     */
    public function runUpgrades($install = false)
    {
        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            foreach (array('1.0.4') as $version) {
                $file = __DIR__ . '/upgrade/install-' . $version . '.php';
                /** @noinspection PhpUndefinedClassInspection */
                if (file_exists($file) && version_compare(Configuration::get('PAYIN7_VERSION'), $version, '<')) {
                    /** @noinspection PhpIncludeInspection */
                    include_once $file;
                    call_user_func('upgrade_module_' . str_replace('.', '_', $version), $this, $install);
                }
            }
        }
    }

    public function createCustomOrderStates()
    {
        /** @noinspection PhpUndefinedClassInspection */
        if (!Configuration::get('PAYIN7_OS_PENDING')) {
            // PAYIN7_OS_PENDING
            /** @noinspection PhpUndefinedClassInspection */
            $OrderState = new OrderState();
            $OrderState->name = array_fill(0, 10, 'ACEPTACION PAGO PAYIN7 EN ESPERA');
            $OrderState->send_email = 0;
            $OrderState->module_name = self::MODULE_NAME;
            $OrderState->invoice = 0;
            $OrderState->logable = 0;
            $OrderState->color = '#2A5E8E';
            $OrderState->unremovable = true;
            /** @noinspection PhpUndefinedMethodInspection */
            $OrderState->save();

            /** @noinspection PhpUndefinedClassInspection */
            /** @noinspection PhpUndefinedFieldInspection */
            Configuration::updateValue('PAYIN7_OS_PENDING', $OrderState->id);
        }

        /** @noinspection PhpUndefinedClassInspection */
        if (!Configuration::get('PAYIN7_OS_ACCEPTED')) {
            // PAYIN7_OS_ACCEPTED
            /** @noinspection PhpUndefinedClassInspection */
            $OrderState = new OrderState();
            $OrderState->name = array_fill(0, 10, 'PAGO PAYIN7 ACEPTADO');
            $OrderState->send_email = 0;
            $OrderState->module_name = self::MODULE_NAME;
            //$OrderState->template = 'payment';
            $OrderState->invoice = 0;
            $OrderState->logable = 0;
            $OrderState->color = '#32CD32';
            $OrderState->unremovable = true;
            /** @noinspection PhpUndefinedMethodInspection */
            $OrderState->save();

            /** @noinspection PhpUndefinedClassInspection */
            /** @noinspection PhpUndefinedFieldInspection */
            Configuration::updateValue('PAYIN7_OS_ACCEPTED', $OrderState->id);
        }

        /** @noinspection PhpUndefinedClassInspection */
        if (!Configuration::get('PAYIN7_OS_CANCELLED')) {
            // PAYIN7_OS_CANCELLED
            /** @noinspection PhpUndefinedClassInspection */
            $OrderState = new OrderState();
            $OrderState->name = array_fill(0, 10, 'PAGO PAYIN7 CANCELADO');
            $OrderState->send_email = 0;
            $OrderState->module_name = self::MODULE_NAME;
            //$OrderState->template = 'order_canceled';
            $OrderState->invoice = 0;
            $OrderState->logable = 0;
            $OrderState->color = '#DC143C';
            $OrderState->unremovable = true;
            /** @noinspection PhpUndefinedMethodInspection */
            $OrderState->save();

            /** @noinspection PhpUndefinedClassInspection */
            /** @noinspection PhpUndefinedFieldInspection */
            Configuration::updateValue('PAYIN7_OS_CANCELLED', $OrderState->id);
        }

        // previously - PS_OS_CHEQUE
        /** @noinspection PhpUndefinedClassInspection */
        Configuration::updateValue('PAYIN7_ID_ORDER_STATE_PENDING', Configuration::get('PAYIN7_OS_PENDING'));

        // previously - PS_OS_PAYMENT
        /** @noinspection PhpUndefinedClassInspection */
        Configuration::updateValue('PAYIN7_ID_ORDER_STATE_ACCEPTED', Configuration::get('PAYIN7_OS_ACCEPTED'));

        // previously - PS_OS_CANCELED
        /** @noinspection PhpUndefinedClassInspection */
        Configuration::updateValue('PAYIN7_ID_ORDER_STATE_CANCELLED', Configuration::get('PAYIN7_OS_CANCELLED'));
    }

    public function uninstall()
    {
        /** @noinspection PhpUndefinedClassInspection */
        Configuration::deleteByName('PAYIN7_API_SERVER_HOSTNAME');
        /** @noinspection PhpUndefinedClassInspection */
        Configuration::deleteByName('PAYIN7_API_SERVER_PORT');
        /** @noinspection PhpUndefinedClassInspection */
        Configuration::deleteByName('PAYIN7_API_INTEGRATION_ID');
        /** @noinspection PhpUndefinedClassInspection */
        Configuration::deleteByName('PAYIN7_API_VERSION');
        /** @noinspection PhpUndefinedClassInspection */
        Configuration::deleteByName('PAYIN7_API_SANDBOX_MODE');
        /** @noinspection PhpUndefinedClassInspection */
        Configuration::deleteByName('PAYIN7_API_USE_SECURE');
        /** @noinspection PhpUndefinedClassInspection */
        Configuration::deleteByName('PAYIN7_API_SANDBOX_KEY');
        /** @noinspection PhpUndefinedClassInspection */
        Configuration::deleteByName('PAYIN7_API_PRODUCTION_KEY');
        /** @noinspection PhpUndefinedClassInspection */
        Configuration::deleteByName('PAYIN7_API_DEBUG_MODE');
        /** @noinspection PhpUndefinedClassInspection */
        Configuration::deleteByName('PAYIN7_PAYMENT_MIN_ORDER_AMOUNT');
        /** @noinspection PhpUndefinedClassInspection */
        Configuration::deleteByName('PAYIN7_PAYMENT_MAX_ORDER_AMOUNT');
        /** @noinspection PhpUndefinedClassInspection */
        Configuration::deleteByName('PAYIN7_ID_ORDER_STATE_PENDING');
        /** @noinspection PhpUndefinedClassInspection */
        Configuration::deleteByName('PAYIN7_ID_ORDER_STATE_ACCEPTED');
        /** @noinspection PhpUndefinedClassInspection */
        Configuration::deleteByName('PAYIN7_ID_ORDER_STATE_CANCELLED');

        /** @noinspection PhpUndefinedClassInspection */
        Configuration::deleteByName('PAYIN7_SBN_ENABLED');
        /** @noinspection PhpUndefinedClassInspection */
        Configuration::deleteByName('PAYIN7_SBN_SECURE_KEY');

        /** @noinspection PhpUndefinedClassInspection */
        Configuration::deleteByName('PAYIN7_OS_PENDING');
        /** @noinspection PhpUndefinedClassInspection */
        Configuration::deleteByName('PAYIN7_OS_ACCEPTED');
        /** @noinspection PhpUndefinedClassInspection */
        Configuration::deleteByName('PAYIN7_OS_CANCELLED');

        $this->uninstallDb();

        /** @noinspection PhpUndefinedClassInspection */
        return parent::uninstall();
    }

    public function reset()
    {
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        if (!$this->uninstall(false)) {
            return false;
        }
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        if (!$this->install(false)) {
            return false;
        }

        return true;
    }

    protected function uninstallDb()
    {
        /** @noinspection PhpUndefinedClassInspection */
        Db::getInstance()->execute('DROP TABLE `' . _DB_PREFIX_ . 'payin7_data`');
        /** @noinspection PhpUndefinedClassInspection */
        Db::getInstance()->execute('DROP TABLE `' . _DB_PREFIX_ . 'payin7_order`');
        /** @noinspection PhpUndefinedClassInspection */
        Db::getInstance()->execute('DROP TABLE `' . _DB_PREFIX_ . 'payin7_order_history`');

        // remove payin7 order states
        $sql = 'DELETE FROM `' . _DB_PREFIX_ . 'order_state_lang` WHERE id_order_state IN (
        SELECT a.id_order_state FROM `' . _DB_PREFIX_ . 'order_state` a WHERE a.module_name = \'' . self::MODULE_NAME . '\')';
        /** @noinspection PhpUndefinedClassInspection */
        Db::getInstance()->execute($sql);

        $sql = 'DELETE FROM `' . _DB_PREFIX_ . 'order_state` WHERE module_name = \'' . self::MODULE_NAME . '\'';
        /** @noinspection PhpUndefinedClassInspection */
        Db::getInstance()->execute($sql);
    }

    protected function installDb()
    {
        $result = true;

        $sql = 'CREATE TABLE `' . _DB_PREFIX_ . 'payin7_data` (
  `data_id` INT(11) NOT NULL AUTO_INCREMENT,
  `data_key` VARCHAR(150) NOT NULL DEFAULT \'\',
  `last_updated` DATETIME NOT NULL,
  `data` MEDIUMTEXT NOT NULL,
  PRIMARY KEY (`data_id`),
  UNIQUE KEY `config_key` (`data_key`)
) ENGINE=' . _MYSQL_ENGINE_ . ' AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;';
        /** @noinspection PhpUndefinedClassInspection */
        $result &= Db::getInstance()->execute($sql);

        $sql = 'CREATE TABLE `' . _DB_PREFIX_ . 'payin7_order` (
  `id_payin7_order` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_order` INT(10) UNSIGNED NOT NULL,
  `payin7_order_sent` TINYINT(4) NOT NULL DEFAULT \'0\',
  `payin7_order_accepted` TINYINT(4) NOT NULL DEFAULT \'0\',
  `payin7_order_identifier` VARCHAR(100) DEFAULT NULL,
  `payin7_sandbox_order` TINYINT(4) NOT NULL DEFAULT \'0\',
  `payin7_access_token` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id_payin7_order`),
  UNIQUE KEY `id_order` (`id_order`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';
        /** @noinspection PhpUndefinedClassInspection */
        $result &= Db::getInstance()->execute($sql);

        $sql = 'CREATE TABLE `' . _DB_PREFIX_ . 'payin7_order_history` (
  `history_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_order` INT(10) UNSIGNED NOT NULL,
  `order_unique_id` VARCHAR(100) NOT NULL,
  `created_on` DATETIME NOT NULL,
  `change_type` ENUM(\'order_state_changed\',\'order_updated\',\'doc_updated\') NOT NULL DEFAULT \'order_state_changed\',
  `data` TEXT,
  PRIMARY KEY (`history_id`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';
        /** @noinspection PhpUndefinedClassInspection */
        $result &= Db::getInstance()->execute($sql);

        return $result;
    }

    public function getContent()
    {
        $this->postProcess();

        $this->context->smarty->assign('module_dir', $this->_path);

        if ($this->_is14) {
            /** @noinspection PhpUndefinedMethodInspection */
            $this->context->smarty->assign(
                array(
                    'formAction' => $_SERVER['REQUEST_URI'],
                    'formConfigValues' => $this->getConfigFormValues(),
                    'selectValues' => array(1, 0),
                    'outputEnvironment' => array($this->l('Enabled'), $this->l('Disabled')),
                )
            );
            /** @noinspection PhpUndefinedMethodInspection */
            $this->_output .= $this->display(__FILE__, 'views/templates/admin/cfg_prestashop_14.tpl');
        } else {
            $this->_output .= $this->context->smarty->fetch($this->local_path . 'views/templates/admin/' . ($this->_is15 ? 'cfg_prestashop_15' : 'config') . '.tpl');
            $this->_output .= $this->displayFormSettings();
        }

        return $this->_output;
    }

    /**
     * Backward compatibility with PS 1.4
     * @return array
     */
    protected function getConfigFormValues()
    {
        /** @noinspection PhpUndefinedClassInspection */
        return array(
            'PAYIN7_API_SERVER_HOSTNAME' => Configuration::get('PAYIN7_API_SERVER_HOSTNAME'),
            'PAYIN7_API_SERVER_PORT' => Configuration::get('PAYIN7_API_SERVER_PORT'),
            'PAYIN7_API_INTEGRATION_ID' => Configuration::get('PAYIN7_API_INTEGRATION_ID'),
            'PAYIN7_API_VERSION' => Configuration::get('PAYIN7_API_VERSION'),
            'PAYIN7_API_SANDBOX_MODE' => Configuration::get('PAYIN7_API_SANDBOX_MODE'),
            'PAYIN7_API_USE_SECURE' => Configuration::get('PAYIN7_API_USE_SECURE'),
            'PAYIN7_API_SANDBOX_KEY' => Configuration::get('PAYIN7_API_SANDBOX_KEY'),
            'PAYIN7_API_PRODUCTION_KEY' => Configuration::get('PAYIN7_API_PRODUCTION_KEY'),
            'PAYIN7_API_DEBUG_MODE' => Configuration::get('PAYIN7_API_DEBUG_MODE'),
            'PAYIN7_PAYMENT_MIN_ORDER_AMOUNT' => Configuration::get('PAYIN7_PAYMENT_MIN_ORDER_AMOUNT'),
            'PAYIN7_PAYMENT_MAX_ORDER_AMOUNT' => Configuration::get('PAYIN7_PAYMENT_MAX_ORDER_AMOUNT'),
            'PAYIN7_SBN_ENABLED' => Configuration::get('PAYIN7_SBN_ENABLED'),
            'PAYIN7_SBN_SECURE_KEY' => Configuration::get('PAYIN7_SBN_SECURE_KEY')
        );
    }

    /**
     * @return Payin7Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    public function postProcess()
    {
        if (Tools::isSubmit(self::SETTINGS_FORM_NAME)) {
            /** @noinspection PhpUndefinedClassInspection */
            Configuration::updateValue('PAYIN7_API_SERVER_HOSTNAME', Tools::getValue('PAYIN7_API_SERVER_HOSTNAME'));
            /** @noinspection PhpUndefinedClassInspection */
            Configuration::updateValue('PAYIN7_API_SERVER_PORT', Tools::getValue('PAYIN7_API_SERVER_PORT'));
            /** @noinspection PhpUndefinedClassInspection */
            Configuration::updateValue('PAYIN7_API_INTEGRATION_ID', Tools::getValue('PAYIN7_API_INTEGRATION_ID'));
            /** @noinspection PhpUndefinedClassInspection */
            Configuration::updateValue('PAYIN7_API_VERSION', Tools::getValue('PAYIN7_API_VERSION'));
            /** @noinspection PhpUndefinedClassInspection */
            Configuration::updateValue('PAYIN7_API_SANDBOX_MODE', Tools::getValue('PAYIN7_API_SANDBOX_MODE'));
            /** @noinspection PhpUndefinedClassInspection */
            Configuration::updateValue('PAYIN7_API_USE_SECURE', Tools::getValue('PAYIN7_API_USE_SECURE'));
            /** @noinspection PhpUndefinedClassInspection */
            Configuration::updateValue('PAYIN7_API_SANDBOX_KEY', Tools::getValue('PAYIN7_API_SANDBOX_KEY'));
            /** @noinspection PhpUndefinedClassInspection */
            Configuration::updateValue('PAYIN7_API_PRODUCTION_KEY', Tools::getValue('PAYIN7_API_PRODUCTION_KEY'));
            /** @noinspection PhpUndefinedClassInspection */
            Configuration::updateValue('PAYIN7_API_DEBUG_MODE', Tools::getValue('PAYIN7_API_DEBUG_MODE'));
            /** @noinspection PhpUndefinedClassInspection */
            Configuration::updateValue('PAYIN7_PAYMENT_MIN_ORDER_AMOUNT', Tools::getValue('PAYIN7_PAYMENT_MIN_ORDER_AMOUNT'));
            /** @noinspection PhpUndefinedClassInspection */
            Configuration::updateValue('PAYIN7_PAYMENT_MAX_ORDER_AMOUNT', Tools::getValue('PAYIN7_PAYMENT_MAX_ORDER_AMOUNT'));
            /** @noinspection PhpUndefinedClassInspection */
            Configuration::updateValue('PAYIN7_SBN_ENABLED', Tools::getValue('PAYIN7_SBN_ENABLED'));
            /** @noinspection PhpUndefinedClassInspection */
            Configuration::updateValue('PAYIN7_SBN_SECURE_KEY', Tools::getValue('PAYIN7_SBN_SECURE_KEY'));
        }
    }

    public function displayFormSettings()
    {
        /** @noinspection PhpUndefinedClassInspection */
        $languages = Language::getLanguages(false);
        foreach ($languages as $k => $language) {
            /** @noinspection PhpUndefinedClassInspection */
            $languages[$k]['is_default'] = (int)$language['id_lang'] == Configuration::get('PS_LANG_DEFAULT');
        }

        $this->context->smarty->assign('form_name', self::SETTINGS_FORM_NAME);
        $this->context->smarty->assign('form_id', self::SETTINGS_FORM_NAME);

        /** @noinspection PhpUndefinedClassInspection */
        $helper = new HelperForm();
        /** @noinspection PhpUndefinedFieldInspection */
        $helper->module = $this;
        /** @noinspection PhpUndefinedFieldInspection */
        $helper->name_controller = 'payin7';
        /** @noinspection PhpUndefinedFieldInspection */
        $helper->identifier = $this->identifier;
        /** @noinspection PhpUndefinedFieldInspection */
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        /** @noinspection PhpUndefinedFieldInspection */
        $helper->languages = $languages;
        /** @noinspection PhpUndefinedClassInspection */
        /** @noinspection PhpUndefinedFieldInspection */
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        /** @noinspection PhpUndefinedClassInspection */
        /** @noinspection PhpUndefinedFieldInspection */
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        /** @noinspection PhpUndefinedFieldInspection */
        $helper->allow_employee_form_lang = true;
        /** @noinspection PhpUndefinedFieldInspection */
        $helper->toolbar_scroll = true;
        /** @noinspection PhpUndefinedFieldInspection */
        $helper->title = $this->displayName;
        /** @noinspection PhpUndefinedFieldInspection */
        $helper->submit_action = self::SETTINGS_FORM_NAME;

        /** @noinspection PhpUndefinedMethodInspection */
        $this->fields_form[0]['form'] = array(
            'tinymce' => false,
            'legend' => array(
                'title' => $this->l('Payin7 Integration Configuration')
            ),
            'input' => array(
                array(
                    'type' => 'select',
                    'name' => 'PAYIN7_API_SANDBOX_MODE',
                    'is_bool' => true,
                    'label' => $this->l('Sandbox mode'),
                    'options' => array(
                        'query' => array(
                            array(
                                'sandbox_mode_id' => 1,
                                'name' => $this->l('Enabled')
                            ),
                            array(
                                'sandbox_mode_id' => 0,
                                'name' => $this->l('Disabled')
                            )
                        ),
                        'id' => 'sandbox_mode_id',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'select',
                    'name' => 'PAYIN7_API_USE_SECURE',
                    'is_bool' => true,
                    'label' => $this->l('Secure communication'),
                    'options' => array(
                        'query' => array(
                            array(
                                'secure_mode_id' => 1,
                                'name' => $this->l('Enabled')
                            ),
                            array(
                                'secure_mode_id' => 0,
                                'name' => $this->l('Disabled')
                            )
                        ),
                        'id' => 'secure_mode_id',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'select',
                    'name' => 'PAYIN7_API_DEBUG_MODE',
                    'is_bool' => true,
                    'label' => $this->l('Debugging'),
                    'options' => array(
                        'query' => array(
                            array(
                                'debug_mode_id' => 1,
                                'name' => $this->l('Enabled')
                            ),
                            array(
                                'debug_mode_id' => 0,
                                'name' => $this->l('Disabled')
                            )
                        ),
                        'id' => 'debug_mode_id',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'select',
                    'name' => 'PAYIN7_SBN_ENABLED',
                    'is_bool' => true,
                    'label' => $this->l('Store Background Notifications'),
                    'options' => array(
                        'query' => array(
                            array(
                                'sbn_enabled_id' => 1,
                                'name' => $this->l('Enabled')
                            ),
                            array(
                                'sbn_enabled_id' => 0,
                                'name' => $this->l('Disabled')
                            )
                        ),
                        'id' => 'sbn_enabled_id',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Integration ID'),
                    'name' => 'PAYIN7_API_INTEGRATION_ID',
                    'col' => 4,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Sandbox API Key'),
                    'name' => 'PAYIN7_API_SANDBOX_KEY',
                    'col' => 4,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Production API Key'),
                    'name' => 'PAYIN7_API_PRODUCTION_KEY',
                    'col' => 4,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Store Background Notifications Secure Key'),
                    'name' => 'PAYIN7_SBN_SECURE_KEY',
                    'col' => 4,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('API Version'),
                    'name' => 'PAYIN7_API_VERSION',
                    'col' => 4,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Server Hostname'),
                    'name' => 'PAYIN7_API_SERVER_HOSTNAME',
                    'col' => 4,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Server Port'),
                    'name' => 'PAYIN7_API_SERVER_PORT',
                    'col' => 4,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Minimum Order Amount'),
                    'name' => 'PAYIN7_PAYMENT_MIN_ORDER_AMOUNT',
                    'col' => 4,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Maximum Order Amount'),
                    'name' => 'PAYIN7_PAYMENT_MAX_ORDER_AMOUNT',
                    'col' => 4,
                )
            ),
            'submit' => array(
                'name' => self::SETTINGS_FORM_NAME,
                'title' => $this->l('Update')
            ),
        );

        /** @noinspection PhpUndefinedClassInspection */
        /** @noinspection PhpUndefinedFieldInspection */
        $helper->fields_value['PAYIN7_API_SERVER_HOSTNAME'] = Configuration::get('PAYIN7_API_SERVER_HOSTNAME');
        /** @noinspection PhpUndefinedClassInspection */
        /** @noinspection PhpUndefinedFieldInspection */
        $helper->fields_value['PAYIN7_API_SERVER_PORT'] = Configuration::get('PAYIN7_API_SERVER_PORT');
        /** @noinspection PhpUndefinedClassInspection */
        /** @noinspection PhpUndefinedFieldInspection */
        $helper->fields_value['PAYIN7_API_INTEGRATION_ID'] = Configuration::get('PAYIN7_API_INTEGRATION_ID');
        /** @noinspection PhpUndefinedClassInspection */
        /** @noinspection PhpUndefinedFieldInspection */
        $helper->fields_value['PAYIN7_API_VERSION'] = Configuration::get('PAYIN7_API_VERSION');
        /** @noinspection PhpUndefinedClassInspection */
        /** @noinspection PhpUndefinedFieldInspection */
        $helper->fields_value['PAYIN7_API_SANDBOX_MODE'] = Configuration::get('PAYIN7_API_SANDBOX_MODE');
        /** @noinspection PhpUndefinedClassInspection */
        /** @noinspection PhpUndefinedFieldInspection */
        $helper->fields_value['PAYIN7_API_USE_SECURE'] = Configuration::get('PAYIN7_API_USE_SECURE');
        /** @noinspection PhpUndefinedClassInspection */
        /** @noinspection PhpUndefinedFieldInspection */
        $helper->fields_value['PAYIN7_API_SANDBOX_KEY'] = Configuration::get('PAYIN7_API_SANDBOX_KEY');
        /** @noinspection PhpUndefinedClassInspection */
        /** @noinspection PhpUndefinedFieldInspection */
        $helper->fields_value['PAYIN7_API_PRODUCTION_KEY'] = Configuration::get('PAYIN7_API_PRODUCTION_KEY');
        /** @noinspection PhpUndefinedClassInspection */
        /** @noinspection PhpUndefinedFieldInspection */
        $helper->fields_value['PAYIN7_API_DEBUG_MODE'] = Configuration::get('PAYIN7_API_DEBUG_MODE');
        /** @noinspection PhpUndefinedClassInspection */
        /** @noinspection PhpUndefinedFieldInspection */
        $helper->fields_value['PAYIN7_PAYMENT_MIN_ORDER_AMOUNT'] = Configuration::get('PAYIN7_PAYMENT_MIN_ORDER_AMOUNT');
        /** @noinspection PhpUndefinedClassInspection */
        /** @noinspection PhpUndefinedFieldInspection */
        $helper->fields_value['PAYIN7_PAYMENT_MAX_ORDER_AMOUNT'] = Configuration::get('PAYIN7_PAYMENT_MAX_ORDER_AMOUNT');
        /** @noinspection PhpUndefinedClassInspection */
        /** @noinspection PhpUndefinedFieldInspection */
        $helper->fields_value['PAYIN7_SBN_ENABLED'] = Configuration::get('PAYIN7_SBN_ENABLED');
        /** @noinspection PhpUndefinedClassInspection */
        /** @noinspection PhpUndefinedFieldInspection */
        $helper->fields_value['PAYIN7_SBN_SECURE_KEY'] = Configuration::get('PAYIN7_SBN_SECURE_KEY');

        /** @noinspection PhpUndefinedMethodInspection */
        return $helper->generateForm($this->fields_form);
    }

    public function hookActionAdminControllerSetMedia()
    {
        $this->context->controller->addCSS($this->_path . 'views/css/payin7_admin' .
            ($this->getConfigApiDebugMode() ? null : '-' . self::JSCSSMIN_VER . '.min') . '.css');
    }

    public function getPayin7SDKTemplateParams()
    {
        return array(
            'payin7_script_src' => json_encode($this->getJsApiUrl('/payin7.js')),
            'js_config' => json_encode($this->getJsConfig())
        );
    }

    public function hookTop()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $this->smarty->assign($this->getPayin7SDKTemplateParams());

        /** @noinspection PhpUndefinedMethodInspection */
        return $this->display(__FILE__, 'views/templates/front/top.tpl');
    }

    public function hookHeader()
    {
        $this->context->controller->addCSS($this->_path . 'views/css/payin7' .
            ($this->getConfigApiDebugMode() ? null : '-' . self::JSCSSMIN_VER . '.min') . '.css');
    }

    public function hookFooter()
    {
        $this->context->controller->addJS($this->_path . 'views/js/utils' .
            ($this->getConfigApiDebugMode() ? null : '-' . self::JSCSSMIN_VER . '.min') . '.js');
    }

    public function hookNewOrder($params)
    {
        // prevent sending an email to the customer upon checking out with Payin7
        // as the order is actually NOT complete yet
        /*if (isset($params['customer'], $params['order'])) {
            $order = $params['order'];

            if ($order->module === self::MODULE_NAME) {
                $customer = $params['customer'];
                $customer->email = null;
            }
        }*/
    }

    private $history_update_enabled = true;

    public function setHistoryUpdateEnabled($enabled)
    {
        $this->history_update_enabled = $enabled;
    }

    public function hookUpdateOrderStatus($params)
    {
        // save the changes and flush the history to payin7 - fast
        if ($this->history_update_enabled && isset($params['newOrderStatus'], $params['id_order'])) {
            $new_status = $params['newOrderStatus'];
            /** @noinspection PhpUndefinedClassInspection */
            $order = new Order($params['id_order']);

            try {
                // load payin7 order
                /** @var \Payin7\Models\OrderModel $payin7o */
                $payin7o = $this->getModelInstance('order');
                /** @noinspection PhpUndefinedFieldInspection */
                $ret = $payin7o->loadPayin7DataById($order->id);

                if ($ret) {
                    // store into history
                    /** @var \Payin7\Models\OrderHistoryModel $historym */
                    $historym = $this->getModelInstance('order_history');
                    $historym->markOrderStateChanged($order, $payin7o->getPayin7OrderIdentifier(), $new_status);

                    /** @var \Payin7\Models\HistorySubmitModel $history_submitter */
                    $history_submitter = $this->getModelInstance('history_submit');
                    $history_submitter->setClientTimeout(self::QUICK_HISTORY_SEND_TIMEOUT);
                    $history_submitter->sendPendingOrderHistory();
                }
            } catch (Exception $e) {
                if ($this->getConfigApiDebugMode()) {
                    throw $e;
                }
            }
        }
    }

    public function getIsPrestashop14()
    {
        return $this->_is14;
    }

    public function hasRejectCookie()
    {
        $n = self::REJECT_COOKIE_NAME;

        /** @noinspection PhpUndefinedMethodInspection */
        return (bool)$this->context->cookie->$n;
    }

    public function setRejectCookie($set = true)
    {
        $n = self::REJECT_COOKIE_NAME;
        /** @noinspection PhpUndefinedMethodInspection */
        $this->context->cookie->$n = ($set ? true : null);
    }

    public function hookAdminOrder($params)
    {
        if (!isset($params['id_order'])) {
            return null;
        }

        // check if a payin7 order
        /** @var \Payin7\Models\OrderModel $porder_model */
        $porder_model = $this->getModelInstance('order');
        $ret = $porder_model->loadPayin7DataById($params['id_order']);

        if (!$ret) {
            return null;
        }

        $this->context->smarty->assign(array(
            'order_type' => $porder_model->getPayin7SandboxOrder() ? 'SANDBOX' : 'LIVE',
            'order_submitted' => $porder_model->getPayin7OrderSent() ? 'YES' : 'NO',
            'order_completed' => $porder_model->getPayin7OrderAccepted() ? 'YES' : 'NO',
            'order_identifier' => $porder_model->getPayin7OrderIdentifier(),
            'order_identifier_js' => json_encode($porder_model->getPayin7OrderIdentifier()),
            'order_payin7_backend_link' => $this->getBackendViewOrderUrl($porder_model,
                $this->getShouldUseSecureConnection())
        ));

        /** @noinspection PhpUndefinedMethodInspection */
        return $this->display(__FILE__, 'views/templates/admin/' . ($this->_is14 ? 'order_tab_14' :
                ($this->_is15 ? 'order_tab_14' : 'order_tab')) . '.tpl');
    }

    public function hookDisplayPaymentEU()
    {
        return $this->hookPayment();
    }

    protected function isPaymentMethodAvailable($payment_method_code, \Payin7\Models\QuoteModel $quote, & $reason)
    {
        $reason = null;

        // validate the platform constraints

        /** @var Payin7\Models\PlatformStatusModel $remote_platform_status */
        $remote_platform_status = $this->getModelInstance('platform_status');
        $remote_platform_status->loadData();

        $platform_is_available = $remote_platform_status->getIsPlatformAvailable() &&
            $remote_platform_status->getIsPaymentMethodAvailable($payment_method_code);

        if (!$platform_is_available) {
            $reason = self::PM_UNAV_PLATFORM_UNAVAILABLE;
            return false;
        }

        $quote_total = $quote->getGrandTotal();

        /** @var Payin7\Models\PlatformConfigModel $remote_platform_config */
        $remote_platform_config = $this->getModelInstance('platform_config');
        $remote_platform_config->loadData();

        // check if the platform constraints are met

        // currency
        if (!$this->canUseForCurrency($payment_method_code, $quote->getCurrencyCode())) {
            $reason = self::PM_UNAV_CURRENCY_UNSUPPORTED;
            return false;
        }

        $payment_method_cfg = $remote_platform_config->getPaymentMethodConfig($payment_method_code);

        $is_customer_disabled = isset($payment_method_cfg['is_disabled']) ?
            (bool)$payment_method_cfg['is_disabled'] :
            null;

        if ($is_customer_disabled) {
            $reason = self::PM_UNAV_METHOD_DISABLED;
            return false;
        }

        $min_order_allowed_platform = isset($payment_method_cfg['minimum_amount']) ?
            (double)$payment_method_cfg['minimum_amount'] :
            null;
        $max_order_allowed_platform = isset($payment_method_cfg['maximum_amount']) ?
            (double)$payment_method_cfg['maximum_amount'] :
            null;
        $supported_countries = isset($payment_method_cfg['supported_countries']) ?
            (array)$payment_method_cfg['supported_countries'] :
            array();

        if ($min_order_allowed_platform && $quote_total < $min_order_allowed_platform) {
            $reason = self::PM_UNAV_MIN_ORDER_PLATFORM_NOTMET;
            //$this->_logger->logWarn('Order platform min not within allowed constraints (min platform allowed: ' .
            //    $min_order_allowed_platform . ', current quote: ' . $quote_total . ')');
            return false;
        }

        if ($max_order_allowed_platform && $quote_total > $max_order_allowed_platform) {
            $reason = self::PM_UNAV_MAX_ORDER_PLATFORM_NOTMET;
            //$this->_logger->logWarn('Order platform max not within allowed constraints (max platform allowed: ' .
            //    $max_order_allowed_platform . ', current quote: ' . $quote_total . ')');
            return false;
        }

        if ($supported_countries) {
            $country = $quote->getBillingAddress()->getCountry();

            if (!in_array($country, $supported_countries)) {
                $reason = self::PM_UNAV_COUNTRY_UNSUPPORTED;
                //$this->_logger->logWarn('Order country not supported, country: ' . $country);
                return false;
            }
        }

        // verify the minimum / maximum allowed
        /** @noinspection PhpUndefinedClassInspection */
        $min_order_allowed = (double)Configuration::get('PAYIN7_PAYMENT_MIN_ORDER_AMOUNT');
        /** @noinspection PhpUndefinedClassInspection */
        $max_order_allowed = (double)Configuration::get('PAYIN7_PAYMENT_MAX_ORDER_AMOUNT');

        if ($min_order_allowed && $quote_total < $min_order_allowed) {
            $reason = self::PM_UNAV_MIN_ORDER_STORE_NOTMET;
            //$this->_logger->logWarn('Order min not within allowed constraints (min allowed: ' .
            //    $min_order_allowed . ', current quote: ' . $quote_total . ')');
            return false;
        }

        if ($max_order_allowed && $quote_total > $max_order_allowed) {
            $reason = self::PM_UNAV_MAX_ORDER_STORE_NOTMET;
            //$this->_logger->logWarn('Order max not within allowed constraints (max allowed: ' .
            //    $max_order_allowed . ', current quote: ' . $quote_total . ')');
            return false;
        }

        return true;
    }

    protected function canUseForCurrency($payment_method, $currency_code)
    {
        /** @var Payin7\Models\PlatformConfigModel $remote_platform_config */
        $remote_platform_config = $this->getModelInstance('platform_config');
        $remote_platform_config->loadData();

        $payment_method_cfg = $remote_platform_config->getPaymentMethodConfig($payment_method);

        $supported_currencies = isset($payment_method_cfg['supported_currencies']) ?
            (array)$payment_method_cfg['supported_currencies'] :
            array();

        return (($supported_currencies && $currency_code && in_array($currency_code, $supported_currencies)) || !$supported_currencies);
    }

    protected function roundPrice($price)
    {
        if (!$price) {
            return null;
        }

        /** @noinspection PhpUndefinedFieldInspection */
        return Tools::ps_round($price, (int)$this->context->currency->decimals *
            (defined('_PS_PRICE_COMPUTE_PRECISION_') ? _PS_PRICE_COMPUTE_PRECISION_ : 1));
    }

    /**
     * @param \Payin7\Models\OrderModel $order_model
     * @param string $source
     * @param bool $update
     * @return \Payin7\Models\OrderSubmitModel
     */
    public function submitOrder(\Payin7\Models\OrderModel $order_model, $source = null, $update = false)
    {
        $this->getLogger()->info(get_class($this) . ': submitorder :: ' . $order_model);

        $source = $source ?: Payin7::SOURCE_FRONTEND;

        $ordered_by_ip_address = \Payin7\Tools\StringUtils::getIpAddress();
        $locale = $this->getCurrentLocaleCode();

        /** @var \Payin7\Models\OrderSubmitModel $order_submit */
        $order_submit = $this->getModelInstance('order_submit');
        $order_submit->setSysinfo($this->getSysinfo());
        $order_submit->setOrderedByIpAddress($ordered_by_ip_address);
        $order_submit->setSource($source);
        $order_submit->setOrder($order_model);
        $order_submit->setLanguageCode($locale);

        return ($update ? $order_submit->updateOrder() : $order_submit->submitOrder(true));
    }

    /**
     * @param \Payin7\Models\QuoteModel $quote
     * @param null $cart
     * @param bool $full_data
     * @return \Payin7\Models\QuoteModel
     */
    public function prepareCartQuote(\Payin7\Models\QuoteModel $quote = null, $full_data = false, $cart = null)
    {
        /** @var \Payin7\Models\QuoteModel $quote */
        $quote = $quote ?: $this->getModelInstance('quote');

        $cart = $cart ?: $this->context->cart;

        if (!$cart) {
            return null;
        }

        /** @noinspection PhpUndefinedMethodInspection */
        $products = $cart->getProducts();
        /** @noinspection PhpUndefinedClassInspection */
        $carrier = new Carrier($cart->id_carrier);
        /** @noinspection PhpUndefinedClassInspection */
        $cust = new Customer($cart->id_customer);

        /** @noinspection PhpUndefinedMethodInspection */
        $shipping_cost = ($this->_is14 ? $cart->getOrderShippingCost() :
            $cart->getTotalShippingCost(null, true, null));

        // order
        $quote->setQuoteId($cart->id);
        /** @noinspection PhpUndefinedFieldInspection */
        $quote->setCurrencyCode($this->context->currency->iso_code);
        /** @noinspection PhpUndefinedFieldInspection */
        $quote->setShippingMethodCode('carrier_' .
            (property_exists($carrier, 'id_reference') ? $carrier->id_reference : $carrier->id));
        /** @noinspection PhpUndefinedFieldInspection */
        $quote->setShippingMethodTitle($carrier->name);
        $quote->setCreatedAt($cart->date_add);
        $quote->setUpdatedAt($cart->date_upd);
        $quote->setShippingAmount($this->roundPrice($shipping_cost));
        /** @noinspection PhpUndefinedMethodInspection */
        $quote->setGrandTotal($this->roundPrice($cart->getOrderTotal()));
        $quote->setOrderedItems(($products ? count($products) : null));
        /** @noinspection PhpUndefinedFieldInspection */
        $quote->setCustomerIsGuest((bool)$cust->is_guest);

        // billing address
        /** @noinspection PhpUndefinedClassInspection */
        $address = new Address($this->context->cart->id_address_invoice);
        /** @noinspection PhpUndefinedClassInspection */
        /** @noinspection PhpUndefinedFieldInspection */
        $user_state = new State($address->id_state);
        /** @noinspection PhpUndefinedClassInspection */
        /** @noinspection PhpUndefinedFieldInspection */
        $user_country = new Country($address->id_country);

        $billing_addr = $quote->getBillingAddress();
        $billing_addr->setCustomerAddressId($this->context->cart->id_address_invoice);
        /** @noinspection PhpUndefinedFieldInspection */
        $billing_addr->setFirstname($address->firstname);
        /** @noinspection PhpUndefinedFieldInspection */
        $billing_addr->setLastname($address->lastname);
        /** @noinspection PhpUndefinedFieldInspection */
        $billing_addr->setCompany($address->company);
        /** @noinspection PhpUndefinedFieldInspection */
        $billing_addr->setStreet1($address->address1);
        /** @noinspection PhpUndefinedFieldInspection */
        $billing_addr->setStreet2($address->address2);
        /** @noinspection PhpUndefinedFieldInspection */
        $billing_addr->setCity($address->city);
        /** @noinspection PhpUndefinedFieldInspection */
        $billing_addr->setCountry($user_country->iso_code);
        /** @noinspection PhpUndefinedFieldInspection */
        $billing_addr->setCountryState($user_state->name);
        /** @noinspection PhpUndefinedFieldInspection */
        $billing_addr->setPostcode($address->postcode);
        /** @noinspection PhpUndefinedFieldInspection */
        $billing_addr->setTelephone1($address->phone);
        /** @noinspection PhpUndefinedFieldInspection */
        $billing_addr->setTelephone2($address->phone_mobile);
        /** @noinspection PhpUndefinedFieldInspection */
        $billing_addr->setTaxVATNumber($address->vat_number);

        if (!$full_data) {
            return $quote;
        }

        // shipping address
        /** @noinspection PhpUndefinedClassInspection */
        $address = new Address($this->context->cart->id_address_delivery);
        /** @noinspection PhpUndefinedClassInspection */
        /** @noinspection PhpUndefinedFieldInspection */
        $user_state = new State($address->id_state);
        /** @noinspection PhpUndefinedClassInspection */
        /** @noinspection PhpUndefinedFieldInspection */
        $user_country = new Country($address->id_country);

        $shipping_addr = $quote->getShippingAddress();
        $shipping_addr->setCustomerAddressId($this->context->cart->id_address_delivery);
        /** @noinspection PhpUndefinedFieldInspection */
        $shipping_addr->setFirstname($address->firstname);
        /** @noinspection PhpUndefinedFieldInspection */
        $shipping_addr->setLastname($address->lastname);
        /** @noinspection PhpUndefinedFieldInspection */
        $shipping_addr->setCompany($address->company);
        /** @noinspection PhpUndefinedFieldInspection */
        $shipping_addr->setStreet1($address->address1);
        /** @noinspection PhpUndefinedFieldInspection */
        $shipping_addr->setStreet2($address->address2);
        /** @noinspection PhpUndefinedFieldInspection */
        $shipping_addr->setCity($address->city);
        /** @noinspection PhpUndefinedFieldInspection */
        $shipping_addr->setCountry($user_country->iso_code);
        /** @noinspection PhpUndefinedFieldInspection */
        $shipping_addr->setCountryState($user_state->name);
        /** @noinspection PhpUndefinedFieldInspection */
        $shipping_addr->setPostcode($address->postcode);
        /** @noinspection PhpUndefinedFieldInspection */
        $shipping_addr->setTelephone1($address->phone);
        /** @noinspection PhpUndefinedFieldInspection */
        $shipping_addr->setTelephone2($address->phone_mobile);
        /** @noinspection PhpUndefinedFieldInspection */
        $shipping_addr->setTaxVATNumber($address->vat_number);

        // products
        if ($products) {
            foreach ($products as $product) {

                /** @noinspection PhpUndefinedClassInspection */
                $link = new Link();
                /** @noinspection PhpUndefinedMethodInspection */
                $url = $link->getProductLink($product);

                /** @noinspection PhpUndefinedClassInspection */
                /** @noinspection PhpUndefinedMethodInspection */
                $image = Image::getCover($product['id_product']);
                /** @noinspection PhpUndefinedMethodInspection */
                $imagePath = $image ? $link->getImageLink($product['link_rewrite'], $image['id_image'], 'home_default') : null;
                $imagePath = $imagePath ? 'http://' . $imagePath : $imagePath;

                /** @var \Payin7\Models\OrderItemModel $pm */
                $pm = $this->getModelInstance('order_item');
                $pm->setId($product['id_product']);
                $pm->setProductId($product['id_product']);
                $pm->setName($product['name']);
                $pm->setProductUrl($url);
                $pm->setImageUrl($imagePath);
                $pm->setShortDescription(isset($product['description_short']) ? $product['description_short'] : null);
                $pm->setFullDescription(isset($product['attributes']) ? $product['attributes'] : null);
                $pm->setIsVirtual(isset($product['is_virtual']) ? $product['is_virtual'] : null);
                $pm->setQtyOrdered($product['cart_quantity']);
                $pm->setPriceInclTax($this->roundPrice($product['price_wt']));
                $pm->setPrice($this->roundPrice($product['price']));
                $pm->setTaxAmount($product['price_wt'] - $product['price']);
                $pm->setPriceBeforeDiscount($this->roundPrice((isset($product['price_without_reduction']) ? $product['price_without_reduction'] : null)));
                $pm->setDiscountAmount($this->roundPrice((isset($product['price_with_reduction_without_tax']) ? $product['price_with_reduction_without_tax'] : null)));
                $pm->setDiscountAmountWithTax($this->roundPrice((isset($product['price_with_reduction']) ? $product['price_with_reduction'] : null)));
                $pm->setShippingAmountWithTax($this->roundPrice(isset($product['additional_shipping_cost']) ? $product['additional_shipping_cost'] : null));
                $pm->setShippingAmount($this->roundPrice($pm->getShippingAmountWithTax()));
                $pm->setRowTotal($this->roundPrice($product['price']));
                $pm->setRowTotalInclTax($this->roundPrice($product['price_wt']));
                $pm->setTaxRate((isset($product['rate']) ? $product['rate'] : null));

                $quote->addItem($pm);

                unset($product);
            }
        }

        // customer
        /** @noinspection PhpUndefinedClassInspection */
        $cust = new Customer($cart->id_customer);
        /** @noinspection PhpUndefinedClassInspection */
        $custlang = new Language($cart->id_lang);
        /** @noinspection PhpUndefinedFieldInspection */
        $gender = ($cust->id_gender == 1 ? \Payin7\Models\CustomerModel::GENDER_MALE :
            ($cust->id_gender == 2 ? \Payin7\Models\CustomerModel::GENDER_FEMALE : null));

        /** @var \Payin7\Models\CustomerModel $customer */
        $customer = $this->getModelInstance('customer');
        $customer->setCustomerId($cart->id_customer);
        $customer->setGender($gender);
        /** @noinspection PhpUndefinedFieldInspection */
        $customer->setPreferredLanguageCode($custlang->iso_code);
        /** @noinspection PhpUndefinedFieldInspection */
        $customer->setCreatedAt($cust->date_add);
        /** @noinspection PhpUndefinedFieldInspection */
        $customer->setUpdatedAt($cust->date_upd);
        /** @noinspection PhpUndefinedFieldInspection */
        $customer->setDob($cust->birthday);
        /** @noinspection PhpUndefinedFieldInspection */
        $customer->setEmail($cust->email);
        /** @noinspection PhpUndefinedFieldInspection */
        $customer->setFirstName($cust->firstname);
        /** @noinspection PhpUndefinedFieldInspection */
        $customer->setLastName($cust->lastname);

        // missing on 1.4
        if (property_exists($cust, 'company')) {
            /** @noinspection PhpUndefinedFieldInspection */
            $customer->setCompany($cust->company);
        }

        $quote->setCustomer($customer);

        return $quote;
    }

    /**
     * @param int|null $id_shop
     * @param bool|null $ssl
     * @param bool $relative_protocol
     * @return string
     */
    protected function getBaseLink($id_shop = null, $ssl = null, $relative_protocol = false)
    {
        static $force_ssl = null;

        if ($ssl === null) {
            if ($force_ssl === null) {
                /** @noinspection PhpUndefinedClassInspection */
                $force_ssl = (Configuration::get('PS_SSL_ENABLED') && Configuration::get('PS_SSL_ENABLED_EVERYWHERE'));
            }
            $ssl = $force_ssl;
        }

        /** @noinspection PhpUndefinedClassInspection */
        if ($id_shop !== null && Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE')) {
            $shop = new Shop($id_shop);
        } else {
            $shop = Context::getContext()->shop;
        }

        if ($relative_protocol) {
            /** @noinspection PhpUndefinedFieldInspection */
            $base = '//' . ($ssl ? $shop->domain_ssl : $shop->domain);
        } else {
            /** @noinspection PhpUndefinedFieldInspection */
            $base = ($ssl ? 'https://' . $shop->domain_ssl : 'http://' . $shop->domain);
        }

        /** @noinspection PhpUndefinedMethodInspection */
        return $base . $shop->getBaseURI();
    }

    public function getModuleLink($controller, array $params = null, $is_secure = null, $custom_route_handle = null)
    {
        $url = null;
        if ($this->_is14) {
            $url = ($is_secure ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . _MODULE_DIR_ .
                $this->name . '/' . $controller . '.php';
            $url .= ($params ? '?' . http_build_query($params, '', '&') : null);
        } else {
            if ($this->_is15up && $this->_use_custom_urls && $custom_route_handle) {
                // append a custom prefix for custom routes matching
                /** @noinspection PhpUndefinedClassInspection */
                $dispatcher = Dispatcher::getInstance();
                /** @noinspection PhpUndefinedFieldInspection */
                $id_lang = Context::getContext()->language->id;
                /** @noinspection PhpUndefinedMethodInspection */
                $url = $this->getBaseLink(null, $is_secure, false) .
                    $dispatcher->createUrl($custom_route_handle, $id_lang, $params);
                return $url;
            }

            /** @noinspection PhpUndefinedMethodInspection */
            $url = $this->context->link->getModuleLink($this->name, $controller, $params, $is_secure);
        }
        return $url;
    }

    public function hookModuleRoutes()
    {
        return $this->_module_routes;
    }

    public function hookPayment()
    {
        // check if we have a reject cookie and skip showing the buttons if true
        $is_rejected = $this->hasRejectCookie();

        if ($is_rejected) {
            return null;
        }

        /** @var Payin7\Models\PlatformConfigModel $remote_platform_config */
        $remote_platform_config = $this->getModelInstance('platform_config');
        $remote_platform_config->loadData();

        // prepare the quote
        $quote = $this->prepareCartQuote();

        $payment_methods = $this->getPaymentMethods();

        $payment_method_public_cfg = array();

        if ($payment_methods) {
            $is_debug = $this->getConfigApiDebugMode();

            foreach ($payment_methods as $method => $method_data) {

                $unav_reason = null;
                $is_available = $this->isPaymentMethodAvailable($method, $quote, $unav_reason);

                if (!$is_available && !$is_debug) {
                    unset($payment_methods[$method]);
                    continue;
                }

                $payment_methods[$method]['code'] = $method;
                $payment_methods[$method]['remote_config'] = $remote_platform_config->getPaymentMethodConfig($method);
                $payment_methods[$method]['is_unavailable'] = !$is_available;
                $payment_methods[$method]['unavailability_reason'] = $unav_reason;
                $payment_methods[$method]['url'] = $this->getModuleLink(
                    'ordervalidate',
                    array('payment_method' => $method),
                    $this->getShouldUseSecureConnection(),
                    'ordervalidate_handler'
                );

                if (isset($payment_methods[$method]['remote_config']['title']) &&
                    $payment_methods[$method]['remote_config']['title']
                ) {
                    $payment_methods[$method]['title'] = $payment_methods[$method]['remote_config']['title'];
                }

                $payment_method_public_cfg[$method]['code'] = $method;
                $payment_method_public_cfg[$method]['logo'] = isset($payment_methods[$method]['remote_config']['logo']) ?
                    $payment_methods[$method]['remote_config']['logo'] : null;

                unset($method, $method_data);
            }
        }

        if ($payment_methods && $quote) {
            /** @noinspection PhpUndefinedMethodInspection */
            $this->smarty->assign(array_merge($this->getPayin7SDKTemplateParams(), array(
                'payment_methods' => $payment_methods,
                'is15up' => $this->_is15up,
                'checkout_options' => json_encode(array(
                    'paymentMethodsCfg' => $payment_method_public_cfg,
                    'submitFormAction' => $this->getModuleLink('ordervalidate', array(),
                        $this->getShouldUseSecureConnection(),
                        'ordervalidate_handler2')
                )),
                'is16up' => version_compare(_PS_VERSION_, '1.6', '>=')
            )));

            /** @noinspection PhpUndefinedMethodInspection */
            return $this->display(__FILE__, 'views/templates/front/checkout.tpl');
        } else {
            return null;
        }
    }

    public function isRequestSecure()
    {
        return (isset($_SERVER['REDIRECT_HTTPS']) && $_SERVER['REDIRECT_HTTPS']) ||
            isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ||
            isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https';
    }

    public function getCurrentUrl()
    {
        $url = ($this->isRequestSecure() ? 'https://' : 'http://') .
            $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . $_SERVER['QUERY_STRING'];
        return $url;
    }

    public function getJsConfig()
    {
        return array_filter(array(
            'apiVersion' => $this->getConfigApiVersion(),
            'adm' => $this->getIsInAdminArea(),
            'key' => $this->getEncryptedClientIndentifierKey(),
            'debug' => $this->getConfigApiDebugMode(),
            'sandbox' => $this->getConfigApiSandboxMode(),
            'platform' => 'prestashop',
            'platformVersion' => _PS_VERSION_,
            'locale' => $this->getCurrentLocaleCode(),
            'u' => $this->getCurrentUrl(),
            'orders' => array(
                'title' => $this->l('Complete Operation')
            )
        ));
    }

    private function getEncryptedClientIndentifierKey()
    {
        return sha1(sha1($this->getConfigApiIntegrationId()) . substr($this->getConfigApiIntegrationId(), 0, 5));
    }

    public function getModelInstance($model_name)
    {
        $fname = __DIR__ . DS . 'models' . DS . $model_name . '.php';

        if (!file_exists($fname)) {
            throw new Exception('Model file not found');
        }

        /** @noinspection PhpIncludeInspection */
        require_once $fname;

        $cls_name = "Payin7\\Models\\" . Payin7\Tools\Inflector::camelize($model_name . '_model');
        $cls = new $cls_name();

        if (!($cls instanceof Payin7\Models\BaseModel)) {
            throw new Exception('Invalid model');
        }

        $cls->initialize($this->context, $this);
        return $cls;
    }

    public function getConfigApiServerHostname($with_defaults = true)
    {
        /** @noinspection PhpUndefinedClassInspection */
        $cfg = Configuration::get('PAYIN7_API_SERVER_HOSTNAME');
        $cfg = $cfg ?: ($with_defaults ? self::CFG_DEFAULT_PAYIN7_API_SERVER_HOSTNAME : $cfg);
        return $cfg;
    }

    public function getConfigIdOrderStateAccepted()
    {
        /** @noinspection PhpUndefinedClassInspection */
        return Configuration::get('PAYIN7_ID_ORDER_STATE_ACCEPTED');
    }

    public function getConfigIdOrderStateCancelled()
    {
        /** @noinspection PhpUndefinedClassInspection */
        return Configuration::get('PAYIN7_ID_ORDER_STATE_CANCELLED');
    }

    public function getConfigIdOrderStatePending()
    {
        /** @noinspection PhpUndefinedClassInspection */
        return Configuration::get('PAYIN7_ID_ORDER_STATE_PENDING');
    }

    public function getConfigApiServerPort()
    {
        /** @noinspection PhpUndefinedClassInspection */
        return Configuration::get('PAYIN7_API_SERVER_PORT');
    }

    public function getConfigApiIntegrationId()
    {
        /** @noinspection PhpUndefinedClassInspection */
        return Configuration::get('PAYIN7_API_INTEGRATION_ID');
    }

    public function getConfigApiVersion($with_defaults = true)
    {
        /** @noinspection PhpUndefinedClassInspection */
        $cfg = Configuration::get('PAYIN7_API_VERSION');
        $cfg = $cfg ?: ($with_defaults ? self::CFG_DEFAULT_PAYIN7_API_VERSION : $cfg);
        return $cfg;
    }

    public function verifySbnPayload(array $data_payload, $signature)
    {
        /** @noinspection PhpUndefinedClassInspection */
        $sbn_enabled = Configuration::get('PAYIN7_SBN_ENABLED');
        /** @noinspection PhpUndefinedClassInspection */
        $sbn_key = Configuration::get('PAYIN7_SBN_SECURE_KEY');

        if (!$sbn_enabled || !$sbn_key || !$signature) {
            return false;
        }

        return (sha1(implode('', $data_payload) . md5($sbn_key)) == $signature);
    }

    public function getConfigApiSandboxMode()
    {
        /** @noinspection PhpUndefinedClassInspection */
        return Configuration::get('PAYIN7_API_SANDBOX_MODE');
    }

    public function getConfigApiUseSecure()
    {
        /** @noinspection PhpUndefinedClassInspection */
        return Configuration::get('PAYIN7_API_USE_SECURE');
    }

    public function getConfigApiKey()
    {
        return ($this->getConfigApiSandboxMode() ? $this->getConfigApiSandboxKey() : $this->getConfigApiProductionKey());
    }

    public function getConfigApiSandboxKey()
    {
        /** @noinspection PhpUndefinedClassInspection */
        return Configuration::get('PAYIN7_API_SANDBOX_KEY');
    }

    public function getConfigApiProductionKey()
    {
        /** @noinspection PhpUndefinedClassInspection */
        return Configuration::get('PAYIN7_API_PRODUCTION_KEY');
    }

    public function getConfigApiDebugMode()
    {
        /** @noinspection PhpUndefinedClassInspection */
        return Configuration::get('PAYIN7_API_DEBUG_MODE');
    }

    public function getApiClientInstance()
    {
        /** @noinspection PhpIncludeInspection */
        require_once __DIR__ . DS . 'classes' . DS . 'api' . DS . 'api_client.php';

        // this is a workaround for prestashop 1.4
        // reregister the __autoload method after including api_client.php above
        // as it will overwrite it with the composer spl_autoload method
        if (function_exists('__autoload')) {
            spl_autoload_register('__autoload');
        }

        $client = new Payin7\API\ApiClient();

        if ($this->getConfigApiDebugMode()) {
            $client->setSslVerification(false);
        }

        $client->setBaseUrl($this->getJsonApiUrl());
        $client->setIntegrationKey($this->getConfigApiIntegrationId());
        $client->setApiKey($this->getConfigApiKey());

        return $client;
    }

    public function getCurrentLocaleCode()
    {
        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            /** @noinspection PhpUndefinedFieldInspection */
            return $this->context->language->iso_code;
        } else {
            global $cookie;
            /** @noinspection PhpUndefinedFieldInspection */
            return $cookie->iso_code;
        }
    }

    public function getBackendViewOrderUrl(Payin7\Models\OrderModel $order, $secure = null)
    {
        $identifier = $order->getPayin7OrderIdentifier();

        if (!$identifier) {
            return null;
        }

        $sandbox_order = (bool)$order->getPayin7SandboxOrder();

        return $this->getServiceUrl(self::SERVICE_BACKEND, '/orders/view/' . $identifier, null, $secure, false, $sandbox_order);
    }

    private function getEncryptedOrderKey($order_access_token)
    {
        return sha1(sha1($this->getConfigApiIntegrationId()) . $order_access_token);
    }

    private function getEncryptedClientKey()
    {
        return base64_encode(\Payin7\Tools\StringUtils::strRot(
            sha1(sha1($this->getConfigApiIntegrationId() . $this->getConfigApiKey()) .
                $this->getConfigApiIntegrationId()) .
            sha1(sha1($this->getConfigApiKey()) .
                $this->getConfigApiIntegrationId())));
    }

    public function getFrontendOrderCompleteUrl(Payin7\Models\OrderModel $order, $is_saved_order = false, $secure = null, $canclose = true)
    {
        $identifier = $order->getPayin7OrderIdentifier();
        $access_token = $order->getPayin7AccessToken();

        if (!$identifier || !$access_token) {
            return null;
        }

        $sandbox_order = (bool)$order->getPayin7SandboxOrder();

        return $this->getServiceUrl(self::SERVICE_FRONTEND, '/orders/complete/' . urlencode($identifier),
            array(
                'ac' => $this->getEncryptedOrderKey($access_token),
                'saved_order' => $is_saved_order,
                'canclose' => (int)$canclose
            ), $secure, false, $sandbox_order);
    }

    public function getBackendUrl($path = null, array $query_params = null, $secure = null)
    {
        return $this->getServiceUrl(self::SERVICE_BACKEND, $path, $query_params, $secure);
    }

    public function getJsApiUrl($path, array $query_params = null)
    {
        return $this->getServiceUrl(self::SERVICE_JSAPI, $path, $query_params, null, true);
    }

    public function getJsonApiUrl($path = null, array $query_params = null, $secure = null)
    {
        return $this->getServiceUrl(self::SERVICE_API, $path, $query_params, $secure);
    }

    public function verifyOrderSecureKey(Payin7\Models\OrderModel $order, $secure_key)
    {
        $is_sandbox_order = $order->getPayin7SandboxOrder();
        $api_key = $is_sandbox_order ? $this->getConfigApiSandboxKey() : $this->getConfigApiProductionKey();
        $secure_key_match = sha1(sha1($order->getPayin7OrderIdentifier() . $api_key) . $api_key);
        return $secure_key == $secure_key_match;
    }

    public function getIsInSecureMode()
    {
        return (
            (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']) ||
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')
        );
    }

    public function getLatestLogLines()
    {
        return $this->readLastFileLines($this->getLogFilename(), 1000, true);
    }

    private function readLastFileLines($filename, $lines, $revers = false)
    {
        if (!file_exists($filename) || !is_readable($filename)) {
            return null;
        }

        $offset = -1;
        $read = '';
        $i = 0;
        $fp = @fopen($filename, 'rb');
        while ($lines && fseek($fp, $offset, SEEK_END) >= 0) {
            $c = fgetc($fp);
            if ($c === "\n" || $c === "\r") {
                $lines--;
                if ($revers) {
                    $read[$i] = isset($read[$i]) ? $read[$i] : null;
                    $read[$i] = strrev($read[$i]);
                    $i++;
                }
            }
            if ($revers) {
                $read[$i] = isset($read[$i]) ? $read[$i] : null;
                $read[$i] .= $c;
            } else {
                $read .= $c;
            }
            $offset--;
        }
        fclose($fp);
        if ($revers) {
            if ($read[$i] === "\n" || $read[$i] === "\r") {
                array_pop($read);
            } else {
                $read[$i] = strrev($read[$i]);
            }
            return implode('', $read);
        }
        return strrev(rtrim($read, "\n\r"));
    }

    public function getIsInAdminArea()
    {
        /** @noinspection PhpUndefinedClassInspection */
        $cookie = new Cookie('psAdmin');
        /** @noinspection PhpUndefinedFieldInspection */
        return ((bool)$cookie->id_employee);
    }

    public function getShouldUseSecureConnection($is_internal_content = false)
    {
        $store_secure = $this->getIsInSecureMode();

        // force secure mode for internal content if store is already in secure mode
        if ($store_secure && $is_internal_content) {
            $secure = true;
        } else {
            $secure = $this->getConfigApiUseSecure();
        }

        return $secure;
    }

    /**
     * @param string $service_type
     * @param string|null $path
     * @param array|null $query_params
     * @param bool|null $secure
     * @param bool $noproto
     * @param bool|null $sandbox
     * @return null|string
     */
    public function getServiceUrl($service_type, $path = null, array $query_params = null, $secure = null, $noproto = false, $sandbox = null)
    {
        $service_subdomain = isset($this->_service_subdomains[$service_type]) ? $this->_service_subdomains[$service_type] : null;

        if (!$service_subdomain) {
            return null;
        }

        $server_port = $this->getConfigApiServerPort();
        $api_ver = ($service_type === self::SERVICE_API ? $this->getConfigApiVersion() : null);
        $sandbox_enabled = null !== $sandbox ? $sandbox : $this->getConfigApiSandboxMode();
        $hostname = $this->getConfigApiServerHostname();

        $is_internal_content = ($service_type === self::SERVICE_RES || $service_type === self::SERVICE_JSAPI);
        $secure = isset($secure) ? $secure : $this->getShouldUseSecureConnection($is_internal_content);

        $params = (array)$query_params;

        $locale = isset($params['locale']) ? $params['locale'] :
            $this->getCurrentLocaleCode();

        $path_locale = null;

        if ($locale && !isset($params['locale'])) {
            if ($service_type === self::SERVICE_API) {
                $params['locale'] = $locale;
            } else if ($service_type === self::SERVICE_FRONTEND ||
                $service_type === self::SERVICE_BACKEND
            ) {
                $path_locale = '/' . $locale;
                unset($params['locale']);
            }
        }

        if ($service_type === self::SERVICE_RES ||
            $service_type === self::SERVICE_JSAPI
        ) {
            unset($params['locale']);
        }

        if (($service_type === self::SERVICE_FRONTEND ||
                $service_type === self::SERVICE_BACKEND) &&
            !isset($params['key'])
        ) {
            $params['key'] = $this->getEncryptedClientKey();
        }

        $params = array_filter($params);

        $url = ($noproto ? '//' : ($secure ? 'https://' : 'http://')) .
            ($service_subdomain ? $service_subdomain . '.' : null) .
            $hostname . ($server_port ? ':' . $server_port : null) .
            ($sandbox_enabled && ($service_type === self::SERVICE_FRONTEND || $service_type === self::SERVICE_BACKEND) ? '/sandbox' : null) .
            $path_locale .
            ($api_ver ? '/' . $api_ver : null);

        if (!$path) {
            return $url . ($params ? '?' . http_build_query($params) : null);
        }

        $url .= $path . ($params ? '?' . http_build_query($params) : null);

        return $url;
    }

    public function getSysinfo()
    {
        /** @noinspection PhpUndefinedClassInspection */
        $db = Db::getInstance();

        // missing on 1.4
        $db_ver = null;

        if (method_exists($db, 'getVersion')) {
            /** @noinspection PhpUndefinedClassInspection */
            $db_ver = Db::getInstance()->getVersion();
        }

        $sysinfo = array_filter(array(
            'platform_ident' => 'prestashop',
            'platform_version' => _PS_VERSION_,
            'plugin_version' => $this->version,
            'preprocessor_version' => phpversion(),
            'preprocessor_sapi' => php_sapi_name() . ' (' . implode(', ', (array)get_loaded_extensions()) . ')',
            'os_ident' => php_uname(),
            'os_version' => php_uname('v'),
            'db_version' => $db_ver,
            'client_user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null,
            'client_env' => json_encode($_SERVER)
        ));

        return $sysinfo;
    }
}
