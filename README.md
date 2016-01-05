# wemo_link_api
A PHP class for connecting and using the WEMO Link lights.

This was a bash script that has been ported and improved.

```php
<?php
  require_once('wemo_link.class.php');
  $wemo = new WemoLink();
  // Setup the connection and all setting from the Wemo
  $wemo->wemoInit();
  
  //Turn On a light
  $light = 'Kitchen'; //This is the name set in the WEMO app
  $brightness = 255; //255 = Brightest, 0 = Off
  $wemo->turnOn($light,$brightness);
  
  //Get status of light
  $wemo->lightStatus($light); // Returns TRUE or FALSE
  
  //Turn Off a light
  $wemo->turnOff($light);
?>
```
This class is still under development. There are still heaps to clean up.
