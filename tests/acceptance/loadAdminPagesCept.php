<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('load the dashboard of the site');
$I->loginAs('admin', '123456');
$I->amOnAdminPage('/');
$I->see('Thank you for creating with WordPress.'); // Page loads up to the wp footer