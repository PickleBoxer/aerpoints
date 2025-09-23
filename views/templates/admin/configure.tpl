{*
* 2007-2025 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2025 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

<div class="panel">
	<h3><i class="icon icon-trophy"></i> {l s='AerPoints - Loyalty Points System' mod='aerpoints'}</h3>
	<p>
		<strong>{l s='Welcome to AerPoints!' mod='aerpoints'}</strong><br />
		{l s='Configure your product-based loyalty points system to encourage customer engagement.' mod='aerpoints'}<br />
		{l s='Customers earn points on specific products and can redeem them for discounts.' mod='aerpoints'}
	</p>
	<br />
	<div class="row">
		<div class="col-md-6">
			<div class="alert alert-info">
				<h4>{l s='Key Features:' mod='aerpoints'}</h4>
				<ul>
					<li>{l s='Product-specific point earning and redemption' mod='aerpoints'}</li>
					<li>{l s='Configurable point-to-currency conversion' mod='aerpoints'}</li>
					<li>{l s='Order state management (pending until completion)' mod='aerpoints'}</li>
					<li>{l s='Complete transaction history' mod='aerpoints'}</li>
					<li>{l s='Manual point adjustment capabilities' mod='aerpoints'}</li>
				</ul>
			</div>
		</div>
		<div class="col-md-6">
			<div class="alert alert-warning">
				<h4>{l s='Next Steps:' mod='aerpoints'}</h4>
				<ul>
					<li>{l s='Configure your global settings below' mod='aerpoints'}</li>
					<li>{l s='Set points for individual products in the product catalog' mod='aerpoints'}</li>
					<li>{l s='Monitor customer points and history' mod='aerpoints'}</li>
				</ul>
			</div>
		</div>
	</div>
</div>

<div class="panel">
	<h3><i class="icon icon-edit"></i> {l s='Product Points Management' mod='aerpoints'}</h3>
	<div class="row">
		<div class="col-md-6">
			<div class="panel panel-default">
				<div class="panel-heading">
					<h4 class="panel-title">{l s='Individual Product Setup' mod='aerpoints'}</h4>
				</div>
				<div class="panel-body">
					<p>{l s='Configure points for individual products directly in the product edit page.' mod='aerpoints'}</p>
					<a href="{$link->getAdminLink('AdminProducts')|escape:'html':'UTF-8'}" class="btn btn-primary">
						<i class="icon-shopping-cart"></i>
						{l s='Manage Products' mod='aerpoints'}
					</a>
				</div>
			</div>
		</div>
		<div class="col-md-6">
			<div class="panel panel-default">
				<div class="panel-heading">
					<h4 class="panel-title">{l s='Bulk Points Management' mod='aerpoints'}</h4>
				</div>
				<div class="panel-body">
					<p>{l s='View and edit all products with points configuration in one place.' mod='aerpoints'}</p>
					<a href="{$link->getAdminLink('AdminAerpointsProduct')|escape:'html':'UTF-8'}" class="btn btn-success">
						<i class="icon-star"></i>
						{l s='Manage Product Points' mod='aerpoints'}
					</a>
				</div>
			</div>
		</div>
	</div>
</div>
