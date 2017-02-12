<?php
if (!defined('_PS_VERSION_'))
    exit;

class SlackNotifications extends Module
{
    public function __construct()
    {
        $this->name = 'slacknotifications';
        $this->tab = 'administration';
        $this->version = '1.0';
        $this->author = 'Ricardo Zorzo';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_); 
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('Slack Notifications');
        $this->description = $this->l("Sends notifications to slack so you can track everything about your store's activity");
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    }
    public function install()
    {
        if (!parent::install()
            || !$this->setVariables()
            || !$this->registerHook('actionPaymentConfirmation')
            || !$this->registerHook('actionValidateOrder')
            || !$this->registerHook('actionOrderStatusPostUpdate')
            || !$this->registerHook('actionCartSave')
            || !$this->registerHook('actionCustomerAccountAdd')
            || !$this->registerHook('actionProductOutOfStock')
            || !$this->registerHook('actionUpdateQuantity')
        )
            return false;
        return true;
    }
    public function setVariables()
    {
        if (!Configuration::updateValue('SLACKNOTIF_URL', 'https://hooks.slack.com/services/')
            || !Configuration::updateValue('SLACKNOTIF_CHANNEL', '#general')
            || !Configuration::updateValue('SLACKNOTIF_CART', 'New cart')
            || !Configuration::updateValue('SLACKCARTNOTIF_ORDER', 'New order')
            || !Configuration::updateValue('SLACKCARTNOTIF_CUSTOMER', 'New customer')
            || !Configuration::updateValue('SLACKCARTNOTIF_STATUS', 'Order status updated')
            || !Configuration::updateValue('SLACKNOTIF_MINIMUMSTOCK', 1)
            || !Configuration::updateValue('SLACKNOTIF_BOTNAME', 'PrestaShopBot')
            || !Configuration::updateValue('SLACKNOTIF_ICON', ':tada:')
        )
        {
            return false;
        }
        return true;
    }
    
    public function uninstall()
    {
        if (!parent::uninstall())
            return false;
        return true;
    }
    
    public function hookActionPaymentConfirmation($params)
    {
        $settings = [
            'username' => Configuration::get('SLACKNOTIF_BOT'),
            'channel' => Configuration::get('SLACKNOTIF_CHANNEL'),
            'icon' => Configuration::get('SLACKNOTIF_ICON'),
            'link_names' => true
        ];
        $client = new Zorzo\Slack\Client(ConfigurationCore::get('SLACKNOTIF_URL'), $settings);
        $message = Configuration::get('SLACKNOTIF_ORDER');

        $cart = new Cart($params['cart']->id);
        $customer = new Customer($cart->id_customer);
        $amount = $cart->getOrderTotalUsingTaxCalculationMethod($params['cart']->id);
        $attachment = [
            [
                "fallback" => $message,
                "fields" => [
                    [
                        "title" => "Amount",
                        "value" => $amount,
                        "short" => false,
                    ],
                    [
                        "title" => "Customer",
                        "value" => $customer->firstname . " " . $customer->lastname,
                        "short" => false,
                    ],
                ],
            ],
        ];
        
        $products = [];
        foreach ($cart->getProducts() as $value)
        {
            $product = new Product($value['id_product']);
            $products[] = [
                "fallback" => $value['name'],
                "title" => $value['name'],
                "title_link" => $product->getlink(),
                "fields" => [
                    [
                        "title" => "Quantity",
                        "value" => $value['quantity'],
                        "short" => true
                    ],
                    [
                        "title" => "Price",
                        "value" => $value['total_wt'],
                        "short" => true
                    ],
                    [
                        "title" => "Attributes",
                        "value" => $value['attributes'],
                        "short" => false
                    ],
                ],
            ];
        }
        
        $client->attach(array_merge($attachment, $products))->send($message);
    }
    
    public function hookActionValidateOrder($params)
    {
        
    }
    
    public function hookActionOrderStatusPostUpdate($params)
    {
        
    }
    
    public function hookActionCartSave($params)
    {
        
    }
    
    public function hookActionCustomerAccountAdd($params)
    {
        
    }
    
    public function hookActionProductOutOfStock($params)
    {
        
    }
    
    public function hookActionUpdateQuantity($params)
    {
        
    }

    //Prestashop's admin functions and forms
    public function getContent()
    {
        $output = null;
     
        if (Tools::isSubmit('submit'.$this->name))
        {
            if (!Tools::getIsset('SLACKNOTIF_URL')
                || !Tools::getIsset('SLACKNOTIF_CHANNEL')
                || !Tools::getIsset('SLACKNOTIF_BOTNAME') 
                || !Tools::getIsset('SLACKNOTIF_ICON') 
                || !Tools::getIsset('SLACKNOTIF_CART')
                || !Tools::getIsset('SLACKNOTIF_ORDER')
                || !Tools::getIsset('SLACKNOTIF_CUSTOMER')
                || !Tools::getIsset('SLACKNOTIF_STATUS')
                || !Tools::getIsset('SLACKNOTIF_MINIMUMSTOCK')
                || !Configuration::updateValue('SLACKNOTIF_URL', Tools::getValue('SLACKNOTIF_URL'))
                || !Configuration::updateValue('SLACKNOTIF_CHANNEL', Tools::getValue('SLACKNOTIF_CHANNEL'))
                || !Configuration::updateValue('SLACKNOTIF_BOTNAME', Tools::getValue('SLACKNOTIF_BOTNAME'))
                || !Configuration::updateValue('SLACKNOTIF_ICON', Tools::getValue('SLACKNOTIF_ICON'))
                || !Configuration::updateValue('SLACKNOTIF_ICON', Tools::getValue('SLACKNOTIF_CART'))
                || !Configuration::updateValue('SLACKNOTIF_ICON', Tools::getValue('SLACKNOTIF_ORDER'))
                || !Configuration::updateValue('SLACKNOTIF_ICON', Tools::getValue('SLACKNOTIF_STATUS'))
                || !Configuration::updateValue('SLACKNOTIF_ICON', Tools::getValue('SLACKNOTIF_MINIMUMSTOCK')))
                    
            {
                $output .= $this->displayError($this->l('There was a problem updating the settings.'));
                return $output.$this->displayForm();
            }
        }
       
        $output .= $this->displayConfirmation($this->l('Settings updated'));
        return $output.$this->displayForm();
    }
    public function displayForm()
    {
        // Get default language
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
         
        // Init Fields form array
        $fields_form[0]['form'] = [
            'legend' => [
                'title' => $this->l('Settings'),
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Slack WebHook URL'),
                    'name' => 'SLACKNOTIF_URL',
                    'size' => 200,
                    'required' => true,
                    'desc' => $this->l('Full URL to your Slack WebHook URL.'),
                ],  
                [
                    'type' => 'text',
                    'label' => $this->l('Slack Channel'),
                    'name' => 'SLACKNOTIF_CHANNEL',
                    'size' => 50,
                    'required' => true,
                    'desc' => $this->l('The name of the channel you want your notifications to be displayed on.'),
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Bot name'),
                    'name' => 'SLACKNOTIF_BOTNAME',
                    'size' => 50,
                    'required' => true,
                    'desc' => $this->l('The name of the bot displayed on slack.'),
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Bot icon'),
                    'name' => 'SLACKNOTIF_ICON',
                    'size' => 50,
                    'required' => true,
                    'desc' => $this->l('The icon of the bot. Example: :tada:'),
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Order'),
                    'name' => 'SLACKNOTIF_ORDER',
                    'size' => 200,
                    'required' => true,
                    'desc' => $this->l('The message you want to receive when a new order is paid.'),
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Cart'),
                    'name' => 'SLACKNOTIF_CART',
                    'size' => 200,
                    'required' => true,
                    'desc' => $this->l('The message you want to receive when a new cart is created.'),
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Order status'),
                    'name' => 'SLACKNOTIF_STATUS',
                    'size' => 200,
                    'required' => true,
                    'desc' => $this->l('The message you want to receive when an order changes status.'),
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Minimum stock'),
                    'name' => 'SLACKNOTIF_MINIMUMSTOCK',
                    'size' => 20,
                    'required' => true,
                    'desc' => $this->l('The product stock you wanna be alerted when a product goes beyond.'),
                ],
                
            ],
            'submit' => [
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            ]
        ];
         
        $helper = new HelperForm();
         
        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
         
        // Language
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;
         
        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit'.$this->name;
        $helper->toolbar_btn = [
            'save' =>
            [
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
                '&token='.Tools::getAdminTokenLite('AdminModules'),
            ],
            'back' => [
                'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list'),
            ],
        ];
        $helper->tpl_vars = [
            'fields_value' => [
                'SLACKNOTIF_URL' => Configuration::get('SLACKNOTIF_URL'),
                'SLACKNOTIF_MESSAGE' => Configuration::get('SLACKNOTIF_CHANNEL'),
                'SLACKNOTIF_BOT' => Configuration::get('SLACKNOTIF_BOTNAME'),
                'SLACKNOTIF_ICON' => Configuration::get('SLACKNOTIF_ICON'),
                'SLACKNOTIF_ICON' => Configuration::get('SLACKNOTIF_CART'),
                'SLACKNOTIF_ICON' => Configuration::get('SLACKNOTIF_ORDER'),
                'SLACKNOTIF_ICON' => Configuration::get('SLACKNOTIF_STATUS'),
                'SLACKNOTIF_ICON' => Configuration::get('SLACKNOTIF_MINIMUMSTOCK'),
            ],
        ];
         
        return $helper->generateForm($fields_form);
    }
    
}