<?php

/**
 * @author Mahmudahsan
 * @license Copyright (c) 2011, Mahmud Ahsan (http://thinkdiff.net)
All rights reserved.

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice,
      this list of conditions and the following disclaimer.

    * Redistributions in binary form must reproduce the above copyright notice,
      this list of conditions and the following disclaimer in the documentation
      and/or other materials provided with the distribution.

    * Neither the name of thinkdiff.net or Mahmud Ahsan may be used to endorse or promote products derived from this
      software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

 * @package moodle linkedIn auth
 *
 * This file has been downloaded from https://github.com/mahmudahsan/Linkedin---Simple-integration-for-your-website
 */

require_once("OAuth.php");

use moodle\auth\linkedin as linkedin;

class LinkedInAuth {
  public $base_url = "http://api.linkedin.com";
  public $secure_base_url = "https://api.linkedin.com";
  public $oauth_callback = "oob";
  public $consumer;
  public $request_token;
  public $access_token;
  public $oauth_verifier;
  public $signature_method;
  public $request_token_path;
  public $access_token_path;
  public $authorize_path;
  public $debug = false;
  
  function __construct($consumer_key, $consumer_secret, $oauth_callback = NULL) {
    
    if($oauth_callback) {
      $this->oauth_callback = $oauth_callback;
    }
    
    $this->consumer = new linkedin\OAuthConsumer($consumer_key, $consumer_secret, $this->oauth_callback);
    $this->signature_method = new linkedin\OAuthSignatureMethod_HMAC_SHA1();
    $this->request_token_path = $this->secure_base_url . "/uas/oauth/requestToken";
    $this->access_token_path = $this->secure_base_url . "/uas/oauth/accessToken";
    $this->authorize_path = $this->secure_base_url . "/uas/oauth/authorize";
    
  }

  function getRequestToken() {
    $consumer = $this->consumer;
    $request = linkedin\OAuthRequest::from_consumer_and_token($consumer, NULL, "GET", $this->request_token_path);
    $request->set_parameter("oauth_callback", $this->oauth_callback);
    $request->sign_request($this->signature_method, $consumer, NULL);
    $headers = " ";
    $url = $request->to_url();
    $response = $this->httpRequest($url, $headers, "GET");
    parse_str($response, $response_params);
    if (isset($response_params['oauth_token']) && isset($response_params['oauth_token_secret'])) {
    $this->request_token = new linkedin\OAuthConsumer($response_params['oauth_token'], $response_params['oauth_token_secret'], 1);
    }
  }

  function generateAuthorizeUrl() {
    $consumer = $this->consumer;
    $request_token = $this->request_token;
    return $this->authorize_path . "?oauth_token=" . $request_token->key;
  }

  function getAccessToken($oauth_verifier) {
    $request = linkedin\OAuthRequest::from_consumer_and_token($this->consumer, $this->request_token, "GET", $this->access_token_path);
    $request->set_parameter("oauth_verifier", $oauth_verifier);
    $request->sign_request($this->signature_method, $this->consumer, $this->request_token);
    $headers = Array();
    $url = $request->to_url();
    $response = $this->httpRequest($url, $headers, "GET");
    parse_str($response, $response_params);
    $this->access_token = new linkedin\OAuthConsumer($response_params['oauth_token'], $response_params['oauth_token_secret'], 1);
  }
  
  function getProfile($resource = "~") {
    $profile_url = $this->base_url . "/v1/people/" . $resource;
    $request = linkedin\OAuthRequest::from_consumer_and_token($this->consumer, $this->access_token, "GET", $profile_url);
    $request->sign_request($this->signature_method, $this->consumer, $this->access_token);
    $auth_header = $request->to_header("https://api.linkedin.com"); # this is the realm
    # This PHP library doesn't generate the header correctly when a realm is not specified.
    # Make sure there is a space and not a comma after OAuth
    // $auth_header = preg_replace("/Authorization\: OAuth\,/", "Authorization: OAuth ", $auth_header);
    // # Make sure there is a space between OAuth attribute
    // $auth_header = preg_replace('/\"\,/', '", ', $auth_header);
    // $response will now hold the XML document
    $response = $this->httpRequest($profile_url, $auth_header, "GET");
    return $response;
  }
  
  function getConnections($resource = "~") {
    $profile_url = $this->base_url . "/v1/people/" . $resource . '/connections';
    $request = linkedin\OAuthRequest::from_consumer_and_token($this->consumer, $this->access_token, "GET", $profile_url);
    $request->sign_request($this->signature_method, $this->consumer, $this->access_token);
    $auth_header = $request->to_header("https://api.linkedin.com"); # this is the realm
    # This PHP library doesn't generate the header correctly when a realm is not specified.
    # Make sure there is a space and not a comma after OAuth
    // $auth_header = preg_replace("/Authorization\: OAuth\,/", "Authorization: OAuth ", $auth_header);
    // # Make sure there is a space between OAuth attribute
    // $auth_header = preg_replace('/\"\,/', '", ', $auth_header);
    if ($debug) {
      echo $auth_header;
    }
    // $response will now hold the XML document
    $response = $this->httpRequest($profile_url, $auth_header, "GET");
    return $response;
  }
  
  function setStatus($status) {
    $status_url = $this->base_url . "/v1/people/~/current-status";
    echo "Setting status...\n";
    $xml = "<current-status>" . htmlspecialchars($status, ENT_NOQUOTES, "UTF-8") . "</current-status>";
    echo $xml . "\n";
    $request = linkedin\OAuthRequest::from_consumer_and_token($this->consumer, $this->access_token, "PUT", $status_url);
    $request->sign_request($this->signature_method, $this->consumer, $this->access_token);
    $auth_header = $request->to_header("https://api.linkedin.com");
    if ($debug) {
      echo $auth_header . "\n";
    }
    $response = $this->httpRequest($profile_url, $auth_header, "GET");
    return $response;
  }
  
  # Parameters should be a query string starting with "?"
  # Example search("?count=10&start=10&company=LinkedIn");
  function search($parameters) {
    $search_url = $this->base_url . "/v1/people-search:(people:(id,first-name,last-name,picture-url,site-standard-profile-request,headline),num-results)" . $parameters;
    //$search_url = $this->base_url . "/v1/people-search?keywords=facebook";

    echo "Performing search for: " . $parameters . "<br />";
    echo "Search URL: $search_url <br />";
    $request = linkedin\OAuthRequest::from_consumer_and_token($this->consumer, $this->access_token, "GET", $search_url);
    $request->sign_request($this->signature_method, $this->consumer, $this->access_token);
    $auth_header = $request->to_header("https://api.linkedin.com");
    if ($debug) {
      echo $request->get_signature_base_string() . "\n";
      echo $auth_header . "\n";
    }
    $response = $this->httpRequest($search_url, $auth_header, "GET");
    return $response;
  }
  
  function httpRequest($url, $auth_header, $method, $body = NULL) {
    if (!$method) {
      $method = "GET";
    };

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array((string) $auth_header)); // Set the headers.

    if ($body) {
      curl_setopt($curl, CURLOPT_POST, 1);
      curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
      curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
      curl_setopt($curl, CURLOPT_HTTPHEADER, array((string) $auth_header, "Content-Type: text/xml;charset=utf-8"));   
    }

    $data = curl_exec($curl);
    if ($this->debug) {
      echo $data . "\n";
    }
    curl_close($curl);
    return $data; 
  }

}
