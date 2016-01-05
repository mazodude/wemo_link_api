<?php
/**
* Wemo Link
*
* Originaly a bash script
* Original author: rich@netmagi.com
*
* Modified 7/13/2014 by Donald Burr
* email: <dburr@DonaldBurr.com>
* web: <http://DonaldBurr.com>
*
* Modified 05/12/2014 by Jack Lawry
* email: <jack@jacklawry.co.uk>
* web: <http://www.jacklawry.co.uk>
*
* Modified 31/05/2015 by Wagner Oliveira
* * Fixed Port parameter and added Support for WeMo Link LED Bulbs
* email: <wbbo@hotmail.com>
* web: <http://guino.home.insightbb.com>
*
* Ported to PHP by Matthew Burns
*/
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
define('WEMO_PORT', '49153');
define('WEMO_IP', '192.168.0.14');
define('WEMO_CACHE', __DIR__.'/cache.json');

class WemoLink
{
    
    private $info;

    function __construct(){

    }

    /**
     * 
     */
    public function wemoInit()
    {
        $this->info = $this->loadCache();
        if ($this->info===false) {
            $this->info = new stdClass();
            $this->info->device = new stdClass();
            $this->info->devices = new stdClass();
            $this->info->device = $this->getSetup();
            $this->info->devices = $this->getDevices();
            $this->writeCache($this->info);
        }
    }

    /**
     * 
     */
    private function loadCache()
    {
        if (file_exists(WEMO_CACHE)) {
            $fp = fopen(WEMO_CACHE, 'r');
            $json = fread($fp, filesize(WEMO_CACHE));
            fclose($fp);
            return json_decode($json);
        } else {
            return false;
        }
    }

    /**
     * 
     */
    private function writeCache($array)
    {
        $fp = fopen(WEMO_CACHE, 'w');
        fwrite($fp, json_encode($array));
        fclose($fp);
    }

    /**
     * 
     */
    private function getSetup()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://".WEMO_IP.":".WEMO_PORT."/setup.xml");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        return $this->parseXML($response);
    }

    /**
     * 
     */
    private function getDevices()
    {
        $UDN = $this->info->device->device->UDN;
        $data = '<?xml version="1.0"?><s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:GetEndDevices xmlns:u="urn:Belkin:service:bridge:1"><ReqListType>SCAN_LIST</ReqListType><DevUDN>'.$UDN.'</DevUDN></u:GetEndDevices></s:Body></s:Envelope>';

        $headers_array = array(
                    'Content-type: text/xml; charset="utf-8"',
                    'SOAPACTION: "urn:Belkin:service:bridge:1#GetEndDevices"',
                    'Accept: ',
                );
        $url = "http://".WEMO_IP.":".WEMO_PORT."/upnp/control/bridge1";
        $response = $this->sendCurl($url,$data,$headers_array);

        // Fix the xml
        $response = str_replace('&lt;', '<', $response);
        $response = str_replace('&gt;', '>', $response);
        $response = str_replace('&quot;', '"', $response);
        $response = str_replace(':', '', $response);
        // To be valid xml, the xml string must be at the front
        $response = str_replace('<?xml version="1.0" encoding="utf-8"?>', '', $response);
        $response = '<?xml version="1.0" encoding="utf-8"?>'.$response;

        // return $response;
        $response = $this->parseXML($response)->sBody->uGetEndDevicesResponse->DeviceLists
                        ->DeviceLists->DeviceList->DeviceInfos;
        return $response;
    }

    /**
     * 
     */
    public function turnOn($light,$brightness=255)
    {
        $lightname = $light;
        $light = $this->bulbNameToID($light);
        $isgroup = 'NO';
        $data = '<?xml version="1.0"?><s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:SetDeviceStatus xmlns:u="urn:Belkin:service:bridge:1"><DeviceStatusList>&lt;?xml version=&quot;1.0&quot; encoding=&quot;UTF-8&quot;?&gt;&lt;DeviceStatus&gt;&lt;DeviceID&gt;'.$light.'&lt;/DeviceID&gt;&lt;CapabilityID&gt;10008&lt;/CapabilityID&gt;&lt;CapabilityValue&gt;'.$brightness.':0&lt;/CapabilityValue&gt;&lt;IsGroupAction&gt;'.$isgroup.'&lt;/IsGroupAction&gt;&lt;/DeviceStatus&gt;</DeviceStatusList></u:SetDeviceStatus></s:Body></s:Envelope>';
        $headers_array = array(
                    'Content-type: text/xml; charset="utf-8"',
                    'SOAPACTION: "urn:Belkin:service:bridge:1#SetDeviceStatus"',
                    'Accept: ',
                );
        $url = "http://".WEMO_IP.":".WEMO_PORT."/upnp/control/bridge1";
        $response = $this->sendCurl($url,$data,$headers_array);
        $this->getStatus($lightname);
    }

    /**
     * 
     */
    public function turnOff($light)
    {
        $lightname = $light;
        $light = $this->bulbNameToID($light);
        $isgroup = 'NO';
        $data = '<?xml version="1.0"?><s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:SetDeviceStatus xmlns:u="urn:Belkin:service:bridge:1"><DeviceStatusList>&lt;?xml version=&quot;1.0&quot; encoding=&quot;UTF-8&quot;?&gt;&lt;DeviceStatus&gt;&lt;DeviceID&gt;'.$light.'&lt;/DeviceID&gt;&lt;CapabilityID&gt;10008&lt;/CapabilityID&gt;&lt;CapabilityValue&gt;0:0&lt;/CapabilityValue&gt;&lt;IsGroupAction&gt;'.$isgroup.'&lt;/IsGroupAction&gt;&lt;/DeviceStatus&gt;</DeviceStatusList></u:SetDeviceStatus></s:Body></s:Envelope>';
        $headers_array = array(
                    'Content-type: text/xml; charset="utf-8"',
                    'SOAPACTION: "urn:Belkin:service:bridge:1#SetDeviceStatus"',
                    'Accept: ',
                );
        $url = "http://".WEMO_IP.":".WEMO_PORT."/upnp/control/bridge1";
        $response = $this->sendCurl($url,$data,$headers_array);
        $this->getStatus($lightname);
    }

    /**
     * 
     */
    public function lightStatus($light)
    {
        $response = $this->getStatus($light);
        // Fix the xml
        $response = str_replace('&lt;', '<', $response);
        $response = str_replace('&gt;', '>', $response);
        $response = str_replace('&quot;', '"', $response);
        $response = str_replace(':', '', $response);
        // To be valid xml, the xml string must be at the front
        $response = str_replace('<?xml version="1.0" encoding="utf-8"?>', '', $response);
        $response = '<?xml version="1.0" encoding="utf-8"?>'.$response;
        $response = $this->parseXML($response)->sBody->uGetDeviceStatusResponse->DeviceStatusList
                        ->DeviceStatusList->DeviceStatus->CapabilityValue[0];
        if ($response) {
            $status = explode(',', $response);
            $onOff = $status[0];
            $brightness = substr($status[1], 0, -1);
            echo json_encode(array(
                    'status' => $onOff,
                    'brightness' => $brightness
                ));
        }
    }

    /**
     * 
     */
    private function getStatus($light)
    {
        $light = $this->bulbNameToID($light);
        $data = '<?xml version="1.0"?><s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:GetDeviceStatus xmlns:u="urn:Belkin:service:bridge:1"><DeviceIDs>'.$light.'</DeviceIDs></u:GetDeviceStatus></s:Body></s:Envelope>';
        $headers_array = array(
                    'Content-type: text/xml; charset="utf-8"',
                    'SOAPACTION: "urn:Belkin:service:bridge:1#GetDeviceStatus"',
                    'Accept: ',
                );
        $url = "http://".WEMO_IP.":".WEMO_PORT."/upnp/control/bridge1";
        $response = $this->sendCurl($url,$data,$headers_array);
        return $response;
    }

    /**
     * 
     */
    private function sendCurl($url,$data,$headers_array)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch,CURLOPT_HTTPHEADER, $headers_array);
        $response = trim(curl_exec($ch));
        curl_close($ch);
        return $response;
    }

    /**
     * 
     */
    private function bulbNameToID($light)
    {
        foreach ($this->info->devices->DeviceInfo as $key => $value) {
            if ($value->FriendlyName==$light) {
                return $value->DeviceID;
            }
        }
        return false;
    }

    /**
     * 
     */
    private function parseXML($string)
    {
        $object = simplexml_load_string($string);
        return $object;
    }

    /**
     * 
     */
    public function ping()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://".WEMO_IP.":".WEMO_PORT);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if($httpcode >= 200 && $httpcode <500){
            return true;
        } else {
            return false;
        }
    }
}
?>