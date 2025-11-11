{*
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
*}

{capture name=path}
  <a href="{$link->getPageLink('my-account', true)|escape:'html':'UTF-8'}">{l s='My account' mod='aerpoints'}</a>
  <span class="navigation-pipe">{$navigationPipe}</span>
  <span class="navigation_page">{l s='Fidelity Points' mod='aerpoints'}</span>
{/capture}

<div class="landing-teaser">
  <div class="main-banner">
    <div class="main-title"></div>
  </div>
  <div class="rectangle1">
  <div class="text-premia">{l s='We are about to reward your loyalty like never before.' mod='aerpoints'}</div>
  <div class="text-preziosi">{l s='Your orders are about to become even more valuable.' mod='aerpoints'}</div>
  </div>
  <div class="rectangle2">
  <div class="text-title">{l s='THE NEW AER FIDELITY POINTS PROGRAM 2026 IS COMING SOON.' mod='aerpoints'}</div>
  <div class="text-premi">{l s='Every order will turn into real rewards and exclusive benefits.' mod='aerpoints'}</div>
  <div class="text-scopri">{l s='You will be among the first to discover how to collect points and get unique gifts just for Aer customers.' mod='aerpoints'}</div>
  </div>
  <div class="rectangle3"><button id="open-popup" class="btn-scopri">{l s='I want to be among the first to discover the program!' mod='aerpoints'}</button></div>
  <div class="coin2 c1"></div>
  <div class="coin2 c2"></div>
</div>

<!-- Popup Modal Template (not in DOM) -->
<script type="text/template" id="my-popup-template">
  <div class="modal-popup">
    <div class="modal-popup-banner"></div>
  <div class="modal-popup-title">{l s='Do you want to be among the first?' mod='aerpoints'}</div>
  <div class="modal-popup-desc">{l s='Collecting points and getting unique rewards has never been so easy.' mod='aerpoints'}</div>
  <div class="modal-popup-subtitle">{l s='Sign up now!' mod='aerpoints'}</div>
    <div class="modal-popup-coin1"></div>
    <div class="modal-popup-coin2"></div>
  <div class="modal-popup-label">{l s='Email address' mod='aerpoints'}</div>
    <!-- Start Brevo Form -->
    <form id="sib-form" method="POST"
      action="https://bb42aafa.sibforms.com/serve/MUIFAPJno2kG2mDkdePR9Baihll5WEQUBXUUDpDwDgHDi8c5sEnOl2vIncLa9OzERB6TirhuHptCZ6rN7FUzTz1-xgPEadj5IT3lwOJRRHyiY5YeYVg9xBvMaiJ9vOOdqG0RAnvdRdxKfArneeABep1vZ29can8pagKsan7elHH8T-dhDHkaKB1m55EtjNUflz_W2pb7Y1Gx11xm"
      data-type="subscription">
  <input class="modal-popup-input" type="email" id="EMAIL" name="EMAIL" autocomplete="off" data-required="true" required />
  <div id="email-error" style="display:none;color:#d00;font-size:14px;margin-bottom:1rem;text-align:center;"></div>
      <div class="modal-popup-btn">
        <button class="modal-popup-btn-text" form="sib-form" type="submit">
          {l s='Access exclusive rewards' mod='aerpoints'}
        </button>
      </div>
      <input type="text" name="email_address_check" value="" class="input--hidden">
  <input type="hidden" name="locale" value="{$lang_iso}">
    </form>
    <!-- End Brevo Form -->
  </div>
</script>

<script type="text/javascript">
  $(document).ready(function() {
    $('#open-popup').click(function(e) {
      e.preventDefault();
      $.fancybox.open({
        'padding': 0,
        'width': 460,
        autoScale: true,
        content: $('#my-popup-template').html(),
        type: 'html',
        afterShow: function() {
          var $form = $('.modal-popup form');
          var $input = $form.find('#EMAIL');
          var $error = $form.find('#email-error');
          $form.on('submit', function(ev) {
            var email = $input.val().trim();
            var valid = /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email);
            if (!email) {
              $error.text('Inserisci la tua email.').show();
              $input.focus();
              ev.preventDefault();
              return false;
            } else if (!valid) {
              $error.text('Inserisci un indirizzo email valido.').show();
              $input.focus();
              ev.preventDefault();
              return false;
            } else {
              $error.hide();
            }
          });
          $input.on('input', function() {
            $error.hide();
          });
        }
      });
    });
  });
</script>
