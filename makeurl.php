<?php

/*
    This file is part of NZ XMLTV Listings.

    NZ XMLTV Listings is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    NZ XMLTV Listings is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with NZ XMLTV Listings.  If not, see <http://www.gnu.org/licenses/>.
*/

//define("downloadurl", 'http://download.xmltv.co.nz/');

include 'libs/nzxmltv.php';

define("downloadURL", 'http://download.' . parse_url(CurrentURL(), PHP_URL_HOST) . '/');

$submitArray = split(" ",$_POST["submit"]);
$provider = strtolower($submitArray[1]);
$channelset = strtolower($submitArray[2]);
$submitGroup = $provider . "-" . $channelset;
if (isset($_POST[$submitGroup]))
{
 connectToDB();
 switch ($submitArray[2]) {
  case 'Channels':
   $channelsArray = $_POST[$submitGroup];
   $channelList = implode(",", $_POST[$submitGroup]);
  break;
  case 'Packages':
   $channelsArray = Array();
   foreach ($_POST[$submitGroup] as $package)
   {
    $result = mysql_query("SELECT channels FROM ChannelPackages WHERE provider='$submitArray[1]' AND package='$package'") or die(mysql_error());
    $row = mysql_fetch_assoc($result);
	$channelsArray = array_merge($channelsArray, explode(",", $row['channels']));
   }
   $channelsArray = array_unique($channelsArray);
   $channelList = implode(",", $channelsArray);
  break;
 }
 $inputArray = $_POST;
 $inputArray["channels"] = $channelList;
 $inputArray["provider"] = $provider;
 $outputArray = CheckVars($inputArray);
 $outputChannelList = $outputArray["url"]["channels"];
 unset($outputArray["url"]["channels"]);
 foreach ($outputArray['url'] as $variable => $value)
 {
  $xmlFileURL .= "&" . $variable . "=" . $value;
 }
 //$xmlFileURL = htmlentities(/*"?" . */ltrim($xmlFileURL, "&")) . "/" . $outputArray['raw']['filename'];
 //$xmlFileURL = htmlentities("?" . ltrim($xmlFileURL, "&")) . "&filename=" . $outputArray['raw']['filename'];
 //$xmlFileURL = htmlentities(ltrim($xmlFileURL, "&")) . "/" . chunk_split($outputChannelList, 250, "/") . $outputArray['raw']['filename'];
 $xmlFileURL = htmlentities(ltrim($xmlFileURL, "&")) . "/" . str_replace(",", "/", $outputChannelList) . "/" . $outputArray['raw']['filename'];
 
 //setcookie(cookiename, $days . ',' . $channellist, time()+31536000);

 echo "<p>Your XMLTV Data file should start to download immediately.</p>\n<p>If the download doesn't start, and/or if your software supports automated downloading of XMLTV data files, you can use the link/URL below:</p>";
 //echo '<p><input type="submit" value="Copy to Clipboard" onclick="window.clipboardData.setData' . "('Copy to Clipboard','" . downloadURL . $xmlFileURL . "')" . ';" /></p>';
 echo '<p class="downloadurl2"><a href="' . downloadURL . $xmlFileURL . '">' . downloadURL . $xmlFileURL . '</a></p>';
 echo '<iframe width="0" height="0" scrolling="no" frameborder="0" src="' . downloadURL . $xmlFileURL . '"></iframe>';
}
else
{
 echo '<h3>Error</h3>';
 echo '<p>Please use the form at <a href="/">http://xmltv.co.nz/</a> to access the NZ XMLTV listings.</p>';
}

?>