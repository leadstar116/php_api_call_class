<?php

namespace App;

class Caller
{
  public $curl;
  public $url = null;
  public $request_method = 'get';
  public $rawResponse = null;
  public $rawResponseHeaders = '';
  public $responseHeaders = null;
  public $response = null;
  public $dataArray = [];

  public $curlError = false;
  public $curlErrorCode = 0;
  public $curlErrorMessage = null;

  private $options = array();

  /**
   * Construct
   *
   * @access public
   * @param  $base_url
   * @throws \ErrorException
   */
  public function __construct($base_url = null)
  {
    if (!extension_loaded('curl')) {
      throw new \ErrorException('cURL library is not loaded');
    }

    $this->curl = curl_init();
    $this->initialize($base_url);
  }

  /**
   * Set Opt
   *
   * @access public
   * @param  $option
   * @param  $value
   *
   * @return boolean
   */
  public function setOpt($option, $value)
  {
    $success = curl_setopt($this->curl, $option, $value);
    if ($success) {
      $this->options[$option] = $value;
    }
    return $success;
  }

  /**
   * Set Url
   *
   * @access public
   * @param  $url
   */
  public function setUrl($url)
  {
    $this->url = $url;
    $this->setOpt(CURLOPT_URL, $this->url);
  }

  /**
   * make
   *
   * @access public
   * @param  $url
   * @param  $request_method
   * @param  $data
   */
  public function make($url, $request_method, $data = false)
  {
    $this->initialize($url, $request_method, $data);
    $this->exec();
  }

  /**
   * root
   *
   * @access public
   * @param  $url
   * @param  $request_method
   * @param  $data
   */
  public function root()
  {
    $this->dataArray = json_decode($this->response);
  }

  /**
   * where
   *
   * @access public
   * @param  $key
   * @param  $operator
   * @param  $value
   */
  public function where($key, $operator, $value)
  {
    $filtered = [];

    foreach ($this->dataArray as $data) {
      switch ($operator) {
        case '=':
          if($data->$key == $value) {
            array_push($filtered, $data);
          }
          break;
        case '!=':
          if($data->$key != $value) {
            array_push($filtered, $data);
          }
          break;
        case '>=':
          if($data->$key >= $value) {
            array_push($filtered, $data);
          }
          break;
        case '<=':
          if($data->$key <= $value) {
            array_push($filtered, $data);
          }
          break;
        case '>':
          if($data->$key > $value) {
            array_push($filtered, $data);
          }
          break;
        case '<':
          if($data->$key < $value) {
            array_push($filtered, $data);
          }
          break;
      }
    }
    print_r($filtered);
  }

  /**
   * Initialize
   *
   * @access private
   * @param  $base_url
   * @param  $request_method
   * @param  $data
   */
  private function initialize($base_url = null, $request_method = "get", $data = false)
  {
    $this->request_method = $request_method;
    $this->setOpt(CURLINFO_HEADER_OUT, true);
    $this->setOpt(CURLOPT_RETURNTRANSFER, true);
    $this->setOpt(CURLOPT_HTTPHEADER, array(
      'Content-Type: application/json',
      'User-Agent: leadstar116' // Github requires User-Agent header. More details at http://developer.github.com/v3/#user-agent-required
    ));
    $this->setUrl($base_url);

    switch ($this->request_method) {
      case "POST":
        $this->setOpt(CURLOPT_POST, true);
        if ($data) {
          $this->setOpt(CURLOPT_POSTFIELDS, $data);
        }
        break;
      case "PUT":
        $this->setOpt(CURLOPT_PUT, true);
        break;
      default:
        if ($data) {
          $url = sprintf("%s?%s", $base_url, http_build_query($data));
          $this->setUrl($url);
        }
    }
  }

  /**
   * Exec
   *
   * @access private
   */
  private function exec()
  {
    $this->rawResponse = curl_exec($this->curl);

    $this->responseHeaders = $this->parseResponseHeaders($this->rawResponseHeaders);
    $this->response = $this->parseResponse($this->responseHeaders, $this->rawResponse);

    $this->curlErrorCode = curl_errno($this->curl);
    $this->curlErrorMessage = curl_error($this->curl);
    $this->curlError = $this->curlErrorCode !== 0;
  }

  /**
   * Parse Headers
   *
   * @access private
   * @param  $raw_headers
   *
   * @return array
   */
  private function parseHeaders($raw_headers)
  {
    $raw_headers = preg_split('/\r\n/', $raw_headers, null, PREG_SPLIT_NO_EMPTY);
    $http_headers = [];

    $raw_headers_count = count($raw_headers);
    for ($i = 1; $i < $raw_headers_count; $i++) {
      if (strpos($raw_headers[$i], ':') !== false) {
        list($key, $value) = explode(':', $raw_headers[$i], 2);
        $key = trim($key);
        $value = trim($value);
        // Use isset() as array_key_exists() and ArrayAccess are not compatible.
        if (isset($http_headers[$key])) {
          $http_headers[$key] .= ',' . $value;
        } else {
          $http_headers[$key] = $value;
        }
      }
    }

    return array(isset($raw_headers['0']) ? $raw_headers['0'] : '', $http_headers);
  }

  /**
   * Parse Response Headers
   *
   * @access private
   * @param  $raw_response_headers
   *
   */
  private function parseResponseHeaders($raw_response_headers)
  {
    $response_header_array = explode("\r\n\r\n", $raw_response_headers);
    $response_header  = '';
    for ($i = count($response_header_array) - 1; $i >= 0; $i--) {
      if (stripos($response_header_array[$i], 'HTTP/') === 0) {
        $response_header = $response_header_array[$i];
        break;
      }
    }

    $response_headers = [];
    list($first_line, $headers) = $this->parseHeaders($response_header);
    $response_headers['Status-Line'] = $first_line;
    foreach ($headers as $key => $value) {
      $response_headers[$key] = $value;
    }
    return $response_headers;
  }

  /**
   * Parse Response
   *
   * @access private
   * @param  $response_headers
   * @param  $raw_response
   *
   * @return mixed
   *   If the response content-type is json:
   *     Returns the json decoder's return value: A stdClass object when the default json decoder is used.
   *   If the response content-type is xml:
   *     Returns the xml decoder's return value: A SimpleXMLElement object when the default xml decoder is used.
   *   If the response content-type is something else:
   *     Returns the original raw response unless a default decoder has been set.
   *   If the response content-type cannot be determined:
   *     Returns the original raw response.
   */
  private function parseResponse($response_headers, $raw_response)
  {
    $response = $raw_response;
    if (isset($response_headers['Content-Type'])) {
      if (preg_match($this->jsonPattern, $response_headers['Content-Type'])) {
        if ($this->jsonDecoder) {
          $args = $this->jsonDecoderArgs;
          array_unshift($args, $response);
          $response = call_user_func_array($this->jsonDecoder, $args);
        }
      } elseif (preg_match($this->xmlPattern, $response_headers['Content-Type'])) {
        if ($this->xmlDecoder) {
          $args = $this->xmlDecoderArgs;
          array_unshift($args, $response);
          $response = call_user_func_array($this->xmlDecoder, $args);
        }
      } else {
        if ($this->defaultDecoder) {
          $response = call_user_func($this->defaultDecoder, $response);
        }
      }
    }

    return $response;
  }
}
