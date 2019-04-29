<?php

/*
 * Visitor stats addon for Bear Framework
 * https://github.com/ivopetkov/visitor-stats-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

namespace IvoPetkov\BearFrameworkAddons;

use BearFramework\App;
use IvoPetkov\HTML5DOMDocument;

/**
 *
 */
class VisitorStats
{

    /**
     *
     * @var bool 
     */
    private $initialized = false;

    /**
     *
     * @var bool 
     */
    private $filterBots = true;

    /**
     *
     * @var bool 
     */
    private $logCurrentRequest = false;

    /**
     *
     * @var bool 
     */
    private $logClientReadyEvent = false;

    /**
     *
     * @var bool 
     */
    private $enableClientEvents = false;

    /**
     * 
     * @param array $options filterBots, logCurrentRequest, logClientReadyEvent, enableClientEvents
     * @return self
     * @throws \Exception
     */
    public function initialize(array $options = []): self
    {
        if ($this->initialized) {
            throw new \Exception('Visitor stats already initialized!');
        }
        if (isset($options['filterBots'])) {
            $this->filterBots = (int) $options['filterBots'] > 0;
        }
        if (isset($options['logCurrentRequest'])) {
            $this->logCurrentRequest = (int) $options['logCurrentRequest'] > 0;
        }
        if (isset($options['logClientReadyEvent'])) {
            $this->logClientReadyEvent = (int) $options['logClientReadyEvent'] > 0;
        }
        if (isset($options['enableClientEvents'])) {
            $this->enableClientEvents = (int) $options['enableClientEvents'] > 0;
        }
        $this->initialized = true;
        $app = App::get();

        $app->routes
                ->add('/-vs.js', function() {
                    $data = isset($_GET['d']) ? json_decode(urldecode($_GET['d']), true) : null;
                    if (!is_array($data)) {
                        $data = [];
                    }
                    $action = isset($_GET['a']) ? (string) urldecode((string) $_GET['a']) : '';
                    $this->log($action, $data);
                    $response = new App\Response('{}');
                    $response->headers->set($response->headers->make('Content-Type', 'text/javascript; charset=UTF-8'));
                    $response->headers->set($response->headers->make('Cache-Control', 'no-cache, no-store, must-revalidate'));
                    return $response;
                });

        if ($this->logCurrentRequest) {
            if ((string) $app->request->path !== '/-vs.js') {
                $data = [];
                $data['url'] = $app->request->getURL();
                $data['method'] = $app->request->method;
                $referrer = isset($_SERVER['HTTP_REFERER']) ? (string) parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) : '';
                if (!empty($referrer)) {
                    $data['referrer'] = $referrer;
                }
                $this->log('request', $data);
            }
        }

        if ($this->logClientReadyEvent || $this->enableClientEvents) {
            $app->addEventListener('beforeSendResponse', function(App\BeforeSendResponseEventDetails $details) use ($app) {
                $response = $details->response;
                if ($response instanceof App\Response\HTML) {
                    $htmlToInsert = '';
                    if ($this->logClientReadyEvent || $this->enableClientEvents) {
                        // taken from dev/library.js
                        $htmlToInsert .= str_replace('INSERT_URL_HERE', $app->urls->get('/-vs.js'), '<script>var vsjs="undefined"!==typeof vsjs?vsjs:function(){return{log:function(b,c){"undefined"===typeof b&&(b="");"undefined"===typeof c&&(c={});var a=document.createElement("script");a.type="text/javascript";a.async=!0;a.src="INSERT_URL_HERE?a="+encodeURIComponent(b)+"&d="+encodeURIComponent(JSON.stringify(c));var d=document.getElementsByTagName("script")[0];d.parentNode.insertBefore(a,d)}}}();</script>');
                    }
                    if ($this->logClientReadyEvent) {
                        // taken from dev/log-client-ready-event.js
                        $htmlToInsert .= '<script>(function(){var a=function(){var b={};b.url=window.location.toString();var a="";try{a=(new URL(document.referrer)).host}catch(c){}b.referrer=a;vsjs.log("load",b)};"loading"===document.readyState?document.addEventListener("DOMContentLoaded",a):a()})();</script>';
                    }
                    if ($htmlToInsert !== '') {
                        $domDocument = new HTML5DOMDocument();
                        $domDocument->loadHTML($response->content, HTML5DOMDocument::ALLOW_DUPLICATE_IDS);
                        $domDocument->insertHTML($htmlToInsert);
                        $response->content = $domDocument->saveHTML();
                    }
                }
            });
        }
        return $this;
    }

    /**
     * 
     * @param string $action
     * @param array $data
     * @return self
     */
    public function log(string $action, array $data = []): self
    {
        $app = App::get();

//        $anonymizeIP = function($ip) {
//            $v6 = strpos($ip, ':') !== false;
//            $parts = explode($v6 ? ':' : '.', $ip);
//            $partsCount = sizeof($parts);
//            for ($i = $v6 ? 6 : 3; $i < $partsCount; $i++) {
//                $parts[$i] = '*';
//            }
//            return implode($v6 ? ':' : '.', $parts);
//        };
//        $anonymizeIP(isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : ''));
//        isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''

        $dataToWrite = [];
        $dataToWrite[] = 1; // data format version
        $dataToWrite[] = date('H:i:s');
        $dataToWrite[] = $action;
        $dataToWrite[] = $data;

        $app->data->append('visitor-stats/' . date('Y-m-d') . '.jsonlist', json_encode($dataToWrite) . "\n");
        return $this;
    }

}
