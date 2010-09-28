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

include 'libs/nzxmltv.php';

function CreateChannelsForm($provider)
{
 $channelcolumns = 4;
 $checked = '';
 $whereQuery = " WHERE " . $provider . "_number IS NOT NULL ";
 $andQuery = " AND " . $provider . "_number IS NOT NULL ";
 $orderQuery = " ORDER BY " . $provider . "_number";
 $result = mysql_query("SELECT DISTINCT genre FROM xmltvChannels" . $whereQuery . $orderQuery);
 while ($row = mysql_fetch_assoc($result))
 {
  $i = 0;
  $checkFieldset = strtolower(str_replace(" ", "", $provider . '_' . $row['genre']));
  $checkSetName = strtolower(str_replace(" ", "", $provider . '-channels'));
  $checkGroupName = strtolower(str_replace(" ", "", $provider . '-channels-' . $row['genre']));
  $genreSQL = (is_null($row['genre']) ? ' IS NULL' : "='" . $row['genre'] . "'");
  $channelsResult = mysql_query("SELECT * FROM xmltvChannels WHERE genre" . $genreSQL . $andQuery . $orderQuery) or die(mysql_error());
  $channelForm .= '<fieldset id="' . $checkGroupName . '">' . "\n" . '<legend>' . (is_null($row['genre']) ? 'No Genre' : $row['genre']) . '</legend>' . "\n";
  $channelForm .= '<table><tr>' . "\n";
  if (mysql_num_rows($channelsResult) > 1)
  {
   $channelForm .= '<td colspan="' . $channelcolumns . '">';
   $channelForm .= '<input type="checkbox" id="' . $checkGroupName . '-selectall" onclick="checkAll(document.getElementById(' . "'" . 'xmltvform' . "'" . '), ' . "'" . $checkGroupName . "'" . ', this.checked)" />';
   $channelForm .= '<label for="' . $checkGroupName . '-selectall">Select/Deselect All</label>';
   $channelForm .= '</td>';
   $channelForm .= '</tr><tr>' . "\n";
  }
  while ($channelRow = mysql_fetch_assoc($channelsResult))
  {
   if (is_int($i/$channelcolumns) && $i <> 0)
   {
    $channelForm .= '</tr><tr>' . "\n";
   }
   $i++;
   /*$preferredSources = array('FV', 'YH', 'TC');
   foreach ($preferredSources as $preferredSource)
   {
    if ($channelRow[$preferredSource . '_id'] <> NULL)
	{
	 $source = $preferredSource;
	}
   }
   $sourceid = $channelRow[$source . '_id'];*/
   $sourceid = $channelRow['All_id'];
   $channelRow['display_name'] = htmlentities($channelRow['display_name']);
   $channelForm .= '<td style="padding-left: 40px;"><img src="' . $channelRow['small_icon'] . '" alt="' . $channelRow['display_name'] . '" title="' . $channelRow['display_name'] . '" /></td>' . "\n";
   $channelForm .= '<td><input type="checkbox" class="' . $checkGroupName . '" name="' . $checkSetName . "[]" . '" id="' . strtolower($provider) . '-' . $sourceid . '" value="' . $sourceid . '" title="' . $channelRow['display_name'] . '" /></td>' . "\n";
  }
  $channelForm .= '</tr></table>' . "\n";
  $channelForm .= '</fieldset>' . "\n";
 }
 return $channelForm;
}

function CreateChannelPackagesForm()
{
 //$result = mysql_query("SELECT DISTINCT genre FROM xmltvChannels WHERE " . $provider . "number IS NOT NULL");
 //while ($row = mysql_fetch_assoc($result))
 
 $channelcolumns = 4;
 //$result = mysql_query("SELECT DISTINCT provider FROM ChannelPackages WHERE provider IS NOT NULL");
 $result = mysql_query("SELECT provider, MIN(indexorder) FROM ChannelPackages WHERE provider IS NOT NULL GROUP BY provider ORDER BY indexorder");
 while ($row = mysql_fetch_assoc($result))
 {
  $i = 0;
  $packageForm .= '<fieldset>' . "\n" . '<legend>' . $row['provider'] . '</legend>' . "\n";
  $packageForm .= '<table><tr>' . "\n";
  $packageForm .= '<td colspan="' . $channelcolumns . '">';
  $checkGroup = strtolower($row['provider']) . "-packages";
  $packageForm .= '<input type="checkbox" name="' . $checkGroup . '-selectall" id="' . $checkGroup . '" onclick="checkAll(document.getElementById(' . "'" . 'xmltvform' . "'" . '), ' . "'" . $checkGroup . "'" . ', this.checked)" />';
  $packageForm .= '<label for="' . $checkGroup . '">Select/Deselect All</label>';
  $packageForm .= '</td>';
  $packageForm .= '</tr><tr>' . "\n";
  $packagesResult = mysql_query("SELECT * FROM ChannelPackages WHERE provider='" . $row['provider'] . "' ORDER BY indexorder") or die(mysql_error());
  while ($packageRow = mysql_fetch_assoc($packagesResult))
  {
   if (is_int($i/$channelcolumns) && $i <> 0)
   {
    $packageForm .= '</tr><tr>' . "\n";
   }
   $i++;
   $packageForm .= '<td style="padding-left: 40px;"><img src="' . $packageRow['icon'] . '" alt="' . $packageRow['package'] . '" title="' . $packageRow['package'] . '" width="47" height="47" /></td>' . "\n";
   $packageForm .= '<td><input type="checkbox" class="' . $checkGroup . '" name="' . $checkGroup . '[]" id="' . strtolower($row['provider']) . "-" . strtolower(str_replace(" ", "", $packageRow['package'])) . '" value="' . $packageRow['package'] . '" title="' . $packageRow['package'] . '" /></td>' . "\n";
  }
  $packageForm .= '</tr></table>' . "\n";
  $packageForm .= '</fieldset>' . "\n";
  $packageForm .= '<input type="submit" name="submit" value="Get ' . $row['provider'] . ' Packages XML" />';
 }
 return $packageForm;
}

//GUI Functions

function CreateGUIGeneric()
{
 /*$channels = array(maxDays, '');
 if (isset($_COOKIE[cookiename]))
 {
  $channels = explode(',', $_COOKIE[cookiename]);
 }
 $days = array_shift($channels);*/
 $guicode =  '  <fieldset>' . "\n";
 $guicode .= '   <p>' . "\n";
 $dayshint = 'The number of days, after the current day, to grab listings for';
 $guicode .= '   <label for="days" title="' . $dayshint . '">Get listings for the next</label> ';
 $guicode .= '   <select name="days" id="days" title="' . $dayshint . '">' . "\n";
 for ($i = 0; $i <= maxDays; $i++) {
  $selected = '';
  if ($i == 7)
  {
   $selected = ' selected="selected"';
  }
  $guicode .= '    <option value="' . $i . '"' . $selected . '>' . $i . '</option>' . "\n";
 }
 $guicode .= '   </select>' . "\n";
 $guicode .= '   <label for="days" title="' . $dayshint . '"> days</label>' . "\n"; // <input type="submit" name="submit" value="Submit" />
 $guicode .= '   </p>' . "\n";
 $guicode .= '   <table style="margin-bottom: 20px;"><tr><th></th><th></th><th></th></tr><tr><td style="vertical-align: top;">' . "\n";
 $guicode .= '    <span style="padding-right: 10px;">Compression</span><br />' . "\n";
 foreach (array('none' => 'Just download the XML file uncompressed (this will still use browser-based transparent gzip compression for the xml file, if supported by your browser)', 'zip' => 'Compress the XML file into a zip archive, with additional files such as icons') as $compressionType => $compressionHint)
 {
  //$guicode .= '    <input type="radio" name="archive" value="' . $compressionType . '" id="archive' . $compressionType . '"' . ($compressionType == 'none' ? 'checked="checked" onclick="hideElement(' . "'iconoptions'" . '); hideElement(' . "'xmloptions'" . '); showElement(' . "'linkoptions'" . ');"' : 'onclick="showElement(' . "'iconoptions'" . '); showElement(' . "'xmloptions'" . '); hideElement(' . "'linkoptions'" . ');"') . 'title="' . $compressionHint . '" /> <label for="compress' . $compressionType . '" title="' . $compressionHint . '">' . $compressionType . '</label><br />' . "\n";
  $guicode .= '    <input type="radio" name="archive" value="' . $compressionType . '" id="archive' . $compressionType . '" ' . ($compressionType == 'none' ? 'checked="checked" onclick="showElement(' . "'iconoptions'" . '); showElement(' . "'linkoptions'" . ');" ' : 'onclick="hideElement(' . "'iconoptions'" . '); hideElement(' . "'linkoptions'" . ');" ') . 'title="' . $compressionHint . '" /> <label for="archive' . $compressionType . '" title="' . $compressionHint . '">' . $compressionType . '</label><br />' . "\n";
 }
 $guicode .= '    </td><td id="linkoptions" style="vertical-align: top;">' . "\n";
 $guicode .= '    <span style="padding-right: 10px;">XML Links</span><br />' . "\n";
 foreach (array('local' => 'URLs within the XML file point to locally saved files, such as icons and DTDs. These can be downloaded to your PC with the compression option', 'remote' => 'URLs within the XML file point to original sources, such as telstra.co.nz and lyngsat-logo.com', 'xmltv' => 'URLs within the XML file point to cached file locations on the xmltv.co.nz website') as $fileType => $fileHint)
 {
  $guicode .= '    <input type="radio" name="links" value="' . $fileType . '" id="links' . $fileType . '" title="' . $fileHint . '" ' . ($fileType == 'remote' ? 'checked="checked" ' : '') . ' /> <label for="links' . $fileType . '" title="' . $fileHint . '">' . $fileType . '</label><br />' . "\n";
 }
 /*
 $guicode .= '   </td><td id="xmloptions" style="display: none; vertical-align: top;">' . "\n";
 $guicode .= '    <span style="padding-right: 10px;">XML Files</span><br />' . "\n";
 $channelinfohint = 'Include XML file of channels and their numbers';
 $guicode .= '    <input type="checkbox" name="files[]" value="channelinfo" id="channelinfo" title="' . $channelinfohint . '" checked="checked" /> <label for="channelinfo" title="' . $channelinfohint . '">channelinfo.xml</label><br />' . "\n";
 $dtdshint = 'Include DTDs (Document Type Definitions) for XML file and channelinfo XML file';
 $guicode .= '    <input type="checkbox" name="files[]" value="dtds" id="dtds" title="' . $dtdshint . '" checked="checked" /> <label for="dtds" title="' . $dtdshint . '">XML DTDs</label>' . "\n";
 */
 $guicode .= '   </td><td id="iconoptions" style="vertical-align: top;">' . "\n";
 $guicode .= '    <span style="padding-right: 10px;">Icons</span><br />' . "\n";
 foreach (array(/*'none' => 'No channel icons',*/ 'small' => '52 x 39', 'large' => '132 x 99') as $iconType => $iconHint)
 {
  $guicode .= '    <input type="radio" name="icons" value="' . $iconType . '" id="icons' . $iconType . '" title="' . $iconHint . '" ' .  ($iconType == 'large' ? 'checked="checked"' : '') . ' /> <label for="icons' . $iconType . '" title="' . $iconHint . '">' . $iconType . '</label><br />' . "\n";
 }
 /*
 $ratingiconshint = 'Include icons for program ratings, e.g. PG, 18+';
 $guicode .= '    <input type="checkbox" name="files[]" value="ratingicons" id="ratingicons" title="' . $ratingiconshint . '"  checked="checked"/> <label for="ratingicons" title="' . $ratingiconshint . '">Rating icons</label>' . "\n";
 */
 $guicode .= '   </td></tr></table>' . "\n";
 $offsethint = 'Time offset for all times in the XML file. May be required for some PVR software';
 $guicode .= '   <p><label for="offset" title="' . $offsethint . '">Time Offset</label>: ' . "\n";
 $guicode .= '   <select name="offset" id="offset" title="' . $offsethint . '">' . "\n";
 foreach (array('none', '+1200', '+1300', 'NZT', 'NZST', 'NZDT', 'auto') as $value)
 {
  $guicode .= '    <option value="' . $value . '" onclick="hideElement(' . "'customoffset'" . ');" ' . ($value == 'none' ? 'selected="selected"' : '') . '>' . $value . '</option>' . "\n";
 }
 $value = 'custom';
 $guicode .= '    <option value="' . $value . '" onclick="showElement(' . "'customoffset'" . ');">' . $value . '</option>' . "\n";
 $guicode .= '   </select>' . "\n";
 $customoffsethint = '';
 $guicode .= '   <input type="text" name="customoffset" value="+0000" id="customoffset" title="' . $customoffsethint . '" style="display: none;" />' . "\n";
 $guicode .= '   </p>' . "\n";
 $filenamehint = 'Name of the XML file to be downloaded. Normally has an extension of .xml or .xmltv';
 $guicode .= '   <p><label for="filename" title="' . $filenamehint . '">Download filename:</label> ' . "\n";
 $guicode .= '   <input type="text" name="filename" value="TVGuide.xml" id="filename" title="' . $filenamehint . '" /><br /><br />' . "\n";
 /*
 $newxmlhint = 'Use new XMLTV 0.6 format (not generally supported by PVR software)';
 $guicode .= '     <label for="newxml" title="' . $newxmlhint . '">Use new (experimental) XMLTV format</label> <input type="checkbox" name="files[]" value="newxml" id="newxml" title="' . $newxmlhint . '" /><br />' . "\n";
 */
 $guicode .= '   </p>' . "\n";
 $guicode .= '  </fieldset>' . "\n";
 return $guicode;
}

function CreateChannelTabs($providers)
{
 $tabs = '<form id="xmltvform" name="xmltvform" enctype="multipart/form-data" method="post" action="/download/">' . "\n";
 $tabs .= CreateGUIGeneric();
 $tabs .= '<div class="tabber">' . "\n";
 foreach ($providers as $provider)
 {
  $tabs .= '<div class="tabbertab" title="' . $provider . '">' . "\n";
  $tabs .= CreateChannelsForm(strtolower($provider));
  $tabs .= '<input type="submit" name="submit" value="Get ' . $provider . ' Channels XML" />' . "\n";
  $tabs .= '</div>' . "\n";
 }
 $tabs .= '<div class="tabbertab" title="Packages">' . "\n";
 $tabs .= CreateChannelPackagesForm();
 $tabs .= '</div>' . "\n";
 $tabs .= '</div>' . "\n";
 $tabs .= '</form>' . "\n";
 return $tabs;
}

connectToDB();



echo CreateChannelTabs(array("All", "Sky", "Telstra", "Freeview", "Analogue"));


?>



 