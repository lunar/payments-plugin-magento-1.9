/// <reference types="cypress" />

'use strict';

import { LunarTestHelper } from './test_helper.js';

export var TestMethods = {

    /** Admin & frontend user credentials. */
    StoreUrl: (Cypress.env('ENV_ADMIN_URL').match(/^(?:http(?:s?):\/\/)?(?:[^@\n]+@)?(?:www\.)?([^:\/\n?]+)/im))[0],
    AdminUrl: Cypress.env('ENV_ADMIN_URL'),
    RemoteVersionLogUrl: Cypress.env('REMOTE_LOG_URL'),

    /** Construct some variables to be used bellow. */
    ShopName: 'magento19',
    LunarName: 'lunar',
    PaymentMethodsAdminUrl: '/system_config/edit/section/payment/',
    OrdersPageAdminUrl: '/sales_order',
    ConfigCurrencyAdminUrl: '/system_config/edit/section/currency/#currency_options-link',

    /**
     * Login to admin backend account
     */
    loginIntoAdminBackend() {
        cy.goToPage(this.AdminUrl);
        cy.loginIntoAccount('input[name="login[username]"]', 'input[name="login[password]"]', 'admin');
    },
    /**
     * Login to client|user frontend account
     */
    loginIntoClientAccount() {
        cy.goToPage(this.StoreUrl + '/customer/account/login');
        cy.loginIntoAccount('input[name="login[username]"]', 'input[name="login[password]"]', 'client');
    },

    /**
     * Modify Lunar settings
     * @param {String} captureMode
     */
    changeLunarCaptureMode(captureMode) {
        /** Go to Lunar payment method. */
        cy.goToPage(this.PaymentMethodsAdminUrl);

        LunarTestHelper.setPositionRelativeOn('.content-header-floating');

        /** Select capture mode. */
        cy.get('#payment_lunar_payment_action').select(captureMode);

        /** Save. */
        cy.get('.scalable.save').first().click();
    },

    /**
     * Make payment with specified currency and process order
     *
     * @param {String} currency
     * @param {String} lunarAction
     * @param {Boolean} partialAmount
     */
     payWithSelectedCurrency(currency, lunarAction, partialAmount = false) {
        /** Make an instant payment. */
        it(`makes a Lunar payment with "${currency}"`, () => {
            this.makePaymentFromFrontend(currency);
        });

        /** Process last order from admin panel. */
        it(`process (${lunarAction}) an order from admin panel`, () => {
            this.processOrderFromAdmin(lunarAction, partialAmount);
        });
    },

    /**
     * Make an instant payment
     * @param {String} currency
     */
    makePaymentFromFrontend(currency) {
        /**
         * Go to specific product page.
         */
        cy.goToPage(this.StoreUrl + '/elizabeth-knit-top-596.html');

        this.changeShopCurrency(currency);

        cy.get('#swatch21 > .swatch-label > img').click();
        cy.get('#swatch80 > .swatch-label').click();

        /** Add to cart. */
        cy.get('.add-to-cart-buttons .button.btn-cart').click();

        /** Go to onepage checkout. */
        cy.goToPage(this.StoreUrl + '/checkout/onepage');

        /** Continue. */
        cy.get('#billing-buttons-container > button').click();
        cy.get('#s_method_flatrate_flatrate').click();
        cy.get('#shipping-method-buttons-container > button').click();


        /** Choose Lunar. */
        cy.get(`input[value*=${this.LunarName}]`).click();

        /** Continue. */
        cy.get('#payment-buttons-container > button').click();

        /** Get amount. */
        cy.get('strong > .price').then($grandTotal => {
            var expectedAmount = LunarTestHelper.filterAndGetAmountInMinor($grandTotal, currency);
            cy.window().then($win => {
                expect(expectedAmount).to.eq(Number($win.lunarminoramount));
            });
        });

        /** Show lunar popup. */
        cy.get('#review-buttons-container > button').click();

        /**
         * Fill in Lunar popup.
         */
         LunarTestHelper.fillAndSubmitLunarPopup();

        cy.get('.page-title > h1').should('contain', 'Your order has been received.');
    },

    /**
     * Process last order from admin panel
     * @param {String} lunarAction
     * @param {Boolean} partialAmount
     */
    processOrderFromAdmin(lunarAction, partialAmount = false) {
        /** Go to admin orders page. */
        cy.goToPage(this.OrdersPageAdminUrl);

        /** Remove fixed header. */
        cy.get('.content-header-floating').then(($fixedHeader) => {
            $fixedHeader.remove();
        });

        /** Click on first (latest in time) order from orders table. */
        cy.get('#sales_order_grid_table > tbody > tr').first().click();

        /**
         * Take specific action on order
         */
        this.lunarActionOnOrderAmount(lunarAction, partialAmount);
    },

    /**
     * Capture an order amount
     * @param {String} lunarAction
     * @param {Boolean} partialAmount
     */
     lunarActionOnOrderAmount(lunarAction, partialAmount = false) {

        /** Remove fixed header. */
        cy.get('.content-header-floating').then(($fixedHeader) => {
            $fixedHeader.remove();
        });

        switch (lunarAction) {
            case 'capture':
                cy.get('button[onclick*="sales_order_invoice"]').click();
                cy.get('button.scalable.save.submit-button').click();
                break;
            case 'refund':
                /** Access invoices table by removing display:none from it. */
                cy.get('#sales_order_view_tabs_order_invoices_content').invoke('show');
                cy.get('#order_invoices_table tbody tr').first().click();
                /** Make credit memo. */
                cy.get('button[onclick*="sales_order_creditmemo"]').first().click();
                /** Submit. */
                cy.get('button[onclick*="submitCreditMemo\("]').click();
                break;
            case 'void':
                cy.get('button[onclick*="voidPayment"]').click();
                cy.on('window:alert',($alert)=>{
                    expect($alert).to.contains('Are you sure you want to void the payment?');
                 });
                break;
        }

        /** Check if success message. */
        cy.get('#messages .success-msg').should('be.visible');
    },

    /**
     * Change shop currency in frontend
     */
    changeShopCurrency(currency) {
        /**
         * Get first select (there are two selects with same ID in DOM)
         * Get option value URL to switch with in page
         */
        cy.get('#select-currency').first().then($select => {
            cy.window().then($win => {
                $win.location.href = $select.find(`option[value*=${currency}]`).val();
            });
        });
    },

    /**
     * Get Shop & Lunar versions and send log data.
     */
    logVersions() {
        /** Go to payment methods page. */
        cy.goToPage(this.StoreUrl + '/downloader');

        cy.loginIntoAccount('input[name="username"]', 'input[name="password"]', 'admin');

        /** Get framework version. */
        cy.get('#connect_packages_0 table tbody tr .a-center').first().then($frameworkVersion => {
            var frameworkVersion = ($frameworkVersion.text()).replace(/[^0-9.]/g, '');
            cy.wrap(frameworkVersion).as('frameworkVersion');
        });

        /** Get Lunar version. */
        cy.get('#connect_packages_0 table tr td').contains('Lunar_Payment').parent().then($pluginVersion => {
            var pluginVersion = ($pluginVersion.text()).replace(/[^0-9.]/g, '');
            cy.wrap(pluginVersion).as('pluginVersion');
        });


        /** Get global variables and make log data request to remote url. */
        cy.get('@frameworkVersion').then(frameworkVersion => {
            cy.get('@pluginVersion').then(pluginVersion => {

                cy.request('GET', this.RemoteVersionLogUrl, {
                    key: frameworkVersion,
                    tag: this.ShopName,
                    view: 'html',
                    ecommerce: frameworkVersion,
                    plugin: pluginVersion
                }).then((resp) => {
                    expect(resp.status).to.eq(200);
                });
            });
        });
    },
}