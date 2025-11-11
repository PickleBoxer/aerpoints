<?php
/**
* 2007-2025 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2025 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

class AerpointsPromoModuleFrontController extends ModuleFrontController
{
    public $display_column_left = false;

    public function setMedia()
    {
        parent::setMedia();
        $this->addCSS($this->module->getPathUri() . 'views/css/promo.css');
    }

    /**
	 * @see FrontController::initContent()
	 */
	public function initContent()
	{
		parent::initContent();

		$this->setTemplate('promo.tpl');
	}
}
