<?php

/*
 * Visitor stats addon for Bear Framework
 * https://github.com/ivopetkov/visitor-stats-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

/**
 * @runTestsInSeparateProcesses
 */
class VisitorStatsTest extends BearFramework\AddonTests\PHPUnitTestCase
{

    /**
     * 
     */
    public function testInitialize()
    {
        $app = $this->getApp();
        $app->visitorStats->initialize([
            'filterBots' => true,
            'logCurrentRequest' => false,
            'enableClientEvents' => true,
            'logClientReadyEvent' => true
        ]);
        $request = new \BearFramework\App\Request();
        $request->method = 'GET';
        $request->path->set('/-vs.js');
        $request->query->set($request->query->make('a', 'test-action'));
        $request->query->set($request->query->make('d', json_encode(['some-data' => 'some-value'])));
        $response = $app->routes->getResponse($request);
        $this->assertTrue($response instanceof \BearFramework\App\Response);
    }

    /**
     * 
     */
    public function testLog()
    {
        $app = $this->getApp();
        $app->visitorStats->log('test-action', ['some-data' => 'some-value']);
    }

}
