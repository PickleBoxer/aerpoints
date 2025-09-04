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

class AerpointsCustomerpointsModuleFrontController extends ModuleFrontController
{
    public $auth = true;
    public $authRedirection = 'my-account';
    public $ssl = true;

    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();

        include_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsCustomer.php');
        include_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsHistory.php');
        include_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsPending.php');

        $customer_id = $this->context->customer->id;
        
        // Get customer points
        //$customer_points = AerpointsCustomer::getCustomerPoints($customer_id);
        $customer_points = AerpointsCustomer::getPointBalance($customer_id);
        
        // Get points history
        $points_history = AerpointsHistory::getCustomerHistory($customer_id);

        // Get pending points
        $pending_points = AerpointsPending::getCustomerPendingPoints($customer_id);

        $this->context->smarty->assign(array(
            'customer_points' => $customer_points,
            'points_history' => $points_history,
            'pending_points' => $pending_points,
            'navigationPipe' => Configuration::get('PS_NAVIGATION_PIPE')
        ));

        $this->setTemplate('customer_points.tpl');
    }

    public function getBreadcrumbLinks()
    {
        $breadcrumb = parent::getBreadcrumbLinks();
        
        $breadcrumb['links'][] = array(
            'title' => $this->l('My account'),
            'url' => $this->context->link->getPageLink('my-account')
        );
        
        $breadcrumb['links'][] = array(
            'title' => $this->l('My Points'),
            'url' => $this->context->link->getModuleLink('aerpoints', 'customerpoints')
        );

        return $breadcrumb;
    }
}
