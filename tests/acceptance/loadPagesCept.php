<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('load the front-end of the site');
$I->amOnPage('/');
$I->see('Proudly powered by WordPress'); // page loads up to the footer