<?php
if (!defined('_PS_VERSION_'))
	exit;

include_once(_PS_ROOT_DIR_.'/classes/db/DbQuery.php');

/**
 * Module for display count products in left sidebar.
 *
 * @author Timofey Suchkov <timofey.web@gmail.com>
 */
class LeftSidebarCountProducts extends Module
{
	/**
	 * Initializes module.
	 */
	public function __construct()
	{
		$this->name = 'leftsidebarcountproducts';
		$this->tab = 'front_office_features';
		$this->version = '1.0.0';
		$this->author = 'Timofey Suchkov';
		$this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
		$this->bootstrap = true;

		parent::__construct();

		$this->displayName = $this->l('Left sidebar count products');
		$this->description = $this->l('Module for display count products in left sidebar.');

		$this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
	}

	/**
	 * Install module and added config attributes to ps_configuration table and
	 * registers leftColumn hook.
	 * @return boolean True if module has been successfully install else false.
	 */
	public function install()
	{
		if (!parent::install() ||
			!$this->registerHook('leftColumn') ||
			!Configuration::updateValue('COUNTPRODUCTS_MIN', 0) ||
			!Configuration::updateValue('COUNTPRODUCTS_MAX', 25)
		)
			return false;

		return true;
	}

	/**
	 * Uninstall module and deletes config attributes from ps_configuration table.
	 * @return boolean True if module has been successfully uninstall else false.
	 */
	public function uninstall()
	{
		if (!parent::uninstall() ||
			!Configuration::deleteByName('COUNTPRODUCTS_MIN') ||
			!Configuration::deleteByName('COUNTPRODUCTS_MAX')
		)
			return false;

		return true;
	}

	/**
	 * Module configuration page.
	 * @return mixed
	 */
	public function getContent()
	{
		$output = null;

		if (Tools::isSubmit('submit'.$this->name))
		{
			$count_products_min = strval(Tools::getValue('COUNTPRODUCTS_MIN'));
			$count_products_max = strval(Tools::getValue('COUNTPRODUCTS_MAX'));
			if ($count_products_min == '' ||
				$count_products_max == '' ||
				($count_products_min >= $count_products_max) ||
				!Validate::isGenericName($count_products_min) ||
				!Validate::isGenericName($count_products_max)
			)
				$output .= $this->displayError($this->l('Invalid Configuration value'));
			else
			{
				Configuration::updateValue('COUNTPRODUCTS_MIN', $count_products_min);
				Configuration::updateValue('COUNTPRODUCTS_MAX', $count_products_max);
				$output .= $this->displayConfirmation($this->l('Settings updated'));
			}
		}
		return $output.$this->displayForm();
	}

	/**
	 * Displays form for configure module.
	 * @return mixed
	 */
	public function displayForm()
	{
		// Get default language
		$default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

		// Init Fields form array
		$fields_form[0]['form'] = array(
			'legend' => array(
				'title' => $this->l('Settings'),
			),
			'input' => array(
				array(
					'type' => 'text',
					'label' => $this->l('From'),
					'name' => 'COUNTPRODUCTS_MIN',
					'size' => 20,
					'required' => true
				),
				array(
					'type' => 'text',
					'label' => $this->l('To'),
					'name' => 'COUNTPRODUCTS_MAX',
					'size' => 20,
					'required' => true
				)
			),
			'submit' => array(
				'title' => $this->l('Save'),
				'class' => 'button'
			)
		);

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
		$helper->toolbar_btn = array(
			'save' =>
			array(
				'desc' => $this->l('Save'),
				'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
				'&token='.Tools::getAdminTokenLite('AdminModules'),
			),
			'back' => array(
				'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
				'desc' => $this->l('Back to list')
			)
		);

		// Load current value
		$helper->fields_value['COUNTPRODUCTS_MIN'] = Configuration::get('COUNTPRODUCTS_MIN');
		$helper->fields_value['COUNTPRODUCTS_MAX'] = Configuration::get('COUNTPRODUCTS_MAX');

		return $helper->generateForm($fields_form);
	}

	/**
	 * Displays a module in the left sidebar.
	 * @return mixed
	 */
	public function hookDisplayLeftColumn()
	{
		$count_products_min = strval(Configuration::get('COUNTPRODUCTS_MIN'));
		$count_products_max = strval(Configuration::get('COUNTPRODUCTS_MAX'));

		$count_products = $this->getCountProducts($count_products_min, $count_products_max);
		$this->context->smarty->assign(
			array(
				'count_products_min' => $count_products_min,
				'count_products_max' => $count_products_max,
				'count_products' => $count_products,
				'currency_sign' => $this->context->currency->sign,
			)
		);
		return $this->display(__FILE__, 'leftsidebarcountproducts.tpl');
	}

	/**
	 * Returns count products between a price range.
	 * @param integer $min The min price.
	 * @param integer $max The max price.
	 * @return integer The count of products.
	 */
	protected function getCountProducts($min, $max)
	{
		$sql = new DbQueryCore();
		$sql->select('count(*)')
			->from('product')
			->where('price >= ' . pSQL($min) . ' AND price <= ' . pSQL($max));
		return Db::getInstance()->getValue($sql);
	}
}
