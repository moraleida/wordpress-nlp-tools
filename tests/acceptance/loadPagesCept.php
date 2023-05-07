<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('load the front-end of the site');
$I->amOnPage('/');
$I->see('Hello world!');