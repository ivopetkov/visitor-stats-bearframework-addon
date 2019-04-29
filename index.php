<?php

/*
 * Visitor stats addon for Bear Framework
 * https://github.com/ivopetkov/visitor-stats-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

use BearFramework\App;

$app = App::get();

$context = $app->contexts->get(__FILE__);

$context->classes
        ->add('IvoPetkov\BearFrameworkAddons\VisitorStats', 'classes/VisitorStats.php');

$app->shortcuts
        ->add('visitorStats', function() {
            return new IvoPetkov\BearFrameworkAddons\VisitorStats();
        });
