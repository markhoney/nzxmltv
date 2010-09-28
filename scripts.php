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

function createChannelAliases()
{
 $mythtv = mysql_query("SELECT other_names, All_id FROM Channels WHERE other_names IS NOT NULL") or die(mysql_error());
 while ($mythtvrow = mysql_fetch_assoc($mythtv))
 {
  unset($nameArray);
  $nameArray = explode(",", $mythtvrow[other_names]);
  foreach ($nameArray as $name)
  {
   mysql_query("INSERT INTO ChannelAliases (All_id,other_name) VALUES('$mythtvrow[All_id]','" . mysql_escape_string($name) . "')") or die(mysql_error());
  }
 }
}

function createChannelPackageContents()
{
 $mythtv = mysql_query("SELECT package, channels FROM ChannelPackages") or die(mysql_error());
 while ($mythtvrow = mysql_fetch_assoc($mythtv))
 {
  unset($nameArray);
  $nameArray = explode(",", $mythtvrow[channels]);
  foreach ($nameArray as $name)
  {
   $provider = explode("-", $name);
   $channelquery = mysql_query("SELECT All_id from xmltvChannels WHERE " . $provider[0] . "_id='" . $name . "'") or die(mysql_error());
   $channel = mysql_fetch_assoc($channelquery);
   if (strlen($channel[All_id]) == 0)
   {
    echo "Couldn't find channel ID for " . $name . "<br />\r\n";
   } else {
    mysql_query("INSERT INTO ChannelPackageContents (package,All_id) VALUES('$mythtvrow[package]','$channel[All_id]')") or die(mysql_error());
   }
  }
 }
}

function recreateChannelPackageContents()
{
 $mythtv = mysql_query("SELECT provider, package, channels FROM ChannelPackages") or die(mysql_error());
 while ($mythtvrow = mysql_fetch_assoc($mythtv))
 {
  unset($nameArray);
  $nameArray = explode(",", $mythtvrow[channels]);
  foreach ($nameArray as $name)
  {
   mysql_query("INSERT INTO ChannelPackageContents (provider,package,All_id) VALUES('$mythtvrow[provider]','$mythtvrow[package]','$name')") or die(mysql_error());
  }
 }
}


function fixChannelPackageContents()
{
 $packages = mysql_query("SELECT DISTINCT package FROM ChannelPackageContents") or die(mysql_error());
 while ($package = mysql_fetch_assoc($packages))
 {
  unset($channels);
  $ids = mysql_query("SELECT All_id FROM ChannelPackageContents WHERE package='" . $package[package] . "'") or die(mysql_error());
  while ($id = mysql_fetch_assoc($ids))
  {
   $channels[] = $id[All_id];
  }
  $channellist = implode(",", $channels);
  mysql_query("UPDATE ChannelPackages SET channels='" . $channellist . "' WHERE package='" . $package[package] . "'") or die(mysql_error());
 }
}



function oldCreateMythTVScript()
{
 $channum = 0;
 $mythtv = mysql_query("SELECT display_name, other_names, All_id, All_number FROM Channels"); //or die(mysql_error());
 while ($mythtvrow = mysql_fetch_assoc($mythtv))
 {
  unset($nameArray);
  $nameArray = explode(",", $mythtvrow[other_names]);
  foreach ($nameArray as $name)
  {
   $mythTVScript .= "UPDATE channel SET name='" . mysql_escape_string($mythtvrow[display_name]) . "' WHERE name='" . mysql_escape_string($name) . "';\r\n";
   $mythTVScript .= "UPDATE channel SET channum='" . mysql_escape_string($mythtvrow[All_number]) . "' WHERE name='" . mysql_escape_string($name) . "';\r\n";
  }
  $mythTVScript .= "UPDATE channel SET channum='" . mysql_escape_string($mythtvrow[All_number]) . "' WHERE name='" . mysql_escape_string($mythtvrow[display_name]) . "';\r\n";
  $mythTVScript .= "UPDATE channel SET xmltvid='" . $mythtvrow[All_id] . ".xmltv.co.nz" . "' WHERE name='" . mysql_escape_string($mythtvrow[display_name]) . "';\r\n";
 }
 $mythTVScript .= "UPDATE channel SET visible=0 WHERE xmltvid='';\r\n";
 return $mythTVScript;
}


function createMythTVScript()
{
 //http://www.gossamer-threads.com/lists/mythtv/users/387297
 $channum = 0;
 $mythTVScript = "UPDATE channel SET callsign=name WHERE callsign='SKYBO';<br />\r\n";
 $mythtv = mysql_query("SELECT display_name, other_names, All_id, All_number FROM Channels"); //or die(mysql_error());
 while ($mythtvrow = mysql_fetch_assoc($mythtv))
 {
  unset($nameArray);
  $nameArray = explode(",", $mythtvrow[other_names]);
  //$nameArray[] = $mythtvrow[display_name];
  $mythTVScript .= "UPDATE channel SET callsign='" . mysql_escape_string($mythtvrow[display_name]) . "',channum='" . mysql_escape_string($mythtvrow[All_number]) . "',xmltvid='" . $mythtvrow[All_id] . ".xmltv.co.nz' WHERE name='" . mysql_escape_string($mythtvrow[display_name]) . "'";
  foreach ($nameArray as $name)
  {
   if ($name <> '')
   {
    $mythTVScript .= " OR callsign='" . mysql_escape_string($name) . "' OR name='" . mysql_escape_string($name) . "'";
   }
  }
  $mythTVScript .= ";<br />\r\n";
  //$mythTVScript .= "UPDATE channel SET name=callsign;\r\n";
  //$mythTVScript .= "UPDATE channel SET channum='" . mysql_escape_string($mythtvrow[All_number]) . "',xmltvid='" . $mythtvrow[All_id] . ".xmltv.co.nz' WHERE callsign='" . mysql_escape_string($mythtvrow[display_name]) . "';\r\n";
 }
 $mythTVScript .= "UPDATE channel SET visible=1;<br />\r\n";
 $mythTVScript .= "UPDATE channel SET visible=0 WHERE xmltvid='';<br />\r\n";
 return $mythTVScript;
}

function backcopyTelstraNumbers()
{
 $channels = mysql_query("SELECT All_id, Telstra_number FROM xmltvChannels WHERE Telstra_number IS NOT NULL"); //or die(mysql_error());
 while ($channel = mysql_fetch_assoc($channels))
 {
  //echo "UPDATE Channels SET Telstra_number='" . $channel['Telstra_number'] . "' WHERE All_id='" . $channel['All_id'] . "'";
  $mythtv = mysql_query("UPDATE Channels SET Telstra_number='" . $channel['Telstra_number'] . "' WHERE All_id='" . $channel['All_id'] . "'");
 }
}

function backcopyTelstraGenres()
{
 $channels = mysql_query("SELECT All_id, Telstra_genre FROM xmltvChannels WHERE Telstra_genre IS NOT NULL"); //or die(mysql_error());
 while ($channel = mysql_fetch_assoc($channels))
 {
  //echo "UPDATE Channels SET Telstra_genre='" . $channel['Telstra_genre'] . "' WHERE All_id='" . $channel['All_id'] . "' AND Telstra_genre IS NULL";
  $mythtv = mysql_query("UPDATE Channels SET Telstra_genre='" . $channel['Telstra_genre'] . "' WHERE All_id='" . $channel['All_id'] . "' AND Telstra_genre IS NULL");
 }
}



connectToDB();

//backcopyTelstraGenres();

echo createMythTVScript();

//backcopyTelstraNumbers();

//createChannelAliases();

//createChannelPackageContents();

//fixChannelPackageContents();

//recreateChannelPackageContents();

?>



 