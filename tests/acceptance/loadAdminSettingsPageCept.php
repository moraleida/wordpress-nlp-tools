<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('load the plugin settings page and see all options');
$I->loginAs('admin', '123456');
$I->amOnAdminPage('/');
$I->seeElement('li#toplevel_page_nlptools' );
$I->click( '#toplevel_page_nlptools > a' );
$I->see('Natural Language Processing Tools Settings');
$I->see('Thank you for creating with WordPress.'); // Page loads up to the wp footer