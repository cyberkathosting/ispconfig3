<?php

/*
Copyright (c) 2007-2008, Till Brehm, projektfarm Gmbh and Gabriel Kaufmann, TYPOworx.de

All rights reserved.

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice,
      this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright notice,
      this list of conditions and the following disclaimer in the documentation
      and/or other materials provided with the distribution.
    * Neither the name of ISPConfig nor the names of its contributors
      may be used to endorse or promote products derived from this software without
      specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

//* Check permissions for module
$app->auth->check_module_permissions('monitor');


$app->uses('getconf');
$server_config = $app->getconf->get_server_config($_SESSION['monitor']['server_id'], 'server');


$context = !empty($_GET['context']) ? trim($_GET['context']) : '';

$proxy_url = '';
$http_user = '';
$http_password = '';
if(isset($server_config[$context . '_url']))
{
    $proxy_url = $server_config[$context . '_url'];
    $proxy_url = str_replace('[SERVERNAME]', $server_config['hostname'], $proxy_url);

    if(isset($_GET['url']))
    {
        $proxy_url .= urldecode($_GET['url']);
    }

    $http_user = trim($server_config[$context . '_user']);
    $http_password = trim($server_config[$context . '_password']);
}
else
{
    header('HTTP/1.1 500');
    echo 'Invalid Context-Parameter.';
    exit;
}

$response = null;

try
{
    if(isset($http_user) || isset($http_password))
    {
        $proxy_url = str_replace('://', sprintf('://%s:%s@', $http_user, $http_password), $proxy_url);
    }

    if(empty($proxy_url))
    {
        header('HTTP/1.1 500');
        echo 'Invalid/Empty request.';
        exit;
    }

    $ch = curl_init($proxy_url);
    if($ch)
    {
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip');

        //curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        $response = curl_exec($ch);
        $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if(empty($response_code) || empty($response))
        {
            throw new \Exception('HTTP Sub-Request failed.');
        }

        // HTML-Rewrites
        if(strpos($response, '<html') !== false)
        {
            $response = preg_replace_callback(
                '/( href=)([\'"])([^"\']*)/', function($match) {
                $attribute = trim($match[1]);
                $quoteChar = trim($match[2]);
                $url = trim($match[3]);

                if($url === '.')
                {
                    $url = '/';
                }

                if(strpos($url, '/') === false)
                {
                    $url = '/' . $url;
                }

                return sprintf(
                    ' %s%s%s%surl=%s%',
                    $attribute,
                    $quoteChar,
                    $_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], '?') ? '&' : '?',
                    urlencode($url),
                    $quoteChar
                );
            },
                $response
            );
        }
        echo $response;
    }
    else
    {
        header('HTTP/1.1 500');
        echo 'PHP-Curl error.';
        exit;
    }
}
catch(\Exception $e)
{
    header('HTTP/1.1 503 Service Temporarily Unavailable');
    header('Status: 503 Service Temporarily Unavailable');
    header('Retry-After: 300');
    echo 'Service Temporarely unavailable!';
    exit;
}
