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

include '../libs/nzxmltv.php';

ob_start();

connectToDB();

DeclareConstants;


/* Generic Functions */

function echoIfNotSilent($string)
{
 echo (runSilently != 'yes' ? $string : NULL);
 ob_flush();
 flush();
}

function sqlOR($orarray, $fieldName)
{
 foreach ($orarray as $or)
 {
  $sqlStatement = $sqlStatement . $fieldName . "='" . $or . "' OR ";
 }
 return substr($sqlStatement, 0, -4);
}

function GetPage($url, $saveTo = NULL, $saveFilename = NULL, $minimumFileSize = 2048) //Get a page of data, either from the local filesystem or a website
{
 if ($saveTo == '' OR $saveTo == NULL)
 {
  unset($saveTo);
 }
 if ($saveFilename == '' OR $saveFilename == NULL)
 {
  $localFilename = $saveTo . basename($url);
 }
 else
 {
  $localFilename = $saveTo . $saveFilename;
 }
 $fileType = strtolower(pathinfo($localFilename, PATHINFO_EXTENSION));
 if (isset($saveTo) AND file_exists($localFilename)) //If the file already exists locally
 {
  $url = $localFilename; // Change the URL to point to the local version
 }
 $datapage = file_get_contents($url); //Get the page content
 if ($datapage <> '' AND $datapage <> FALSE AND strlen($datapage) > $minimumFileSize) //If the page isn't empty
 {
  if (isset($saveTo) AND !file_exists($localFilename))
  {
   if (!file_exists($saveTo)) //If the folder doesn't exist
   {
    mkdir($saveTo, 0777, true); //Recursively make the folder
   }
   file_put_contents($localFilename, $datapage); //Save a copy
   if (verboseOutput == 'yes')
   {
    echoIfNotSilent('Cached file ' . $localFilename . '<br />' . "\n");
   }
  }
  if ($fileType <> 'jpg' AND $fileType <> 'jpeg' AND $fileType <> 'gif' AND $fileType <> 'png')
  {
   $datapage = FixBadCharacters(stripslashes($datapage));
  }
  return $datapage; //Return the contents of the page
 }
}

function DBInsert($table, $sqlArray, $verbose = FALSE)
{
 foreach ($sqlArray as $insert => $value)
 {
  $inserts = $inserts . $insert . ",";
  $values = $values . ((is_null($value) OR trim($value)=='') ? "NULL" : "'" . mysql_real_escape_string(trim(html_entity_decode(stripslashes($value)))) . "'") . ",";
 }
 $sqlQuery = "REPLACE INTO " . $table . " (" . rtrim($inserts, ",") . ") VALUES (" . rtrim($values, ",") . ")";
 if ($verbose)
 {
  //print_r($sqlArray);
  //echoIfNotSilent($sqlQuery . "<br />\r\n");
 }
 mysql_query($sqlQuery) or die(mysql_error());
}

function DBUpdate($table, $sqlArray, $whereArray)
{
 foreach ($sqlArray as $column => $value)
 {
  $values = $values . $column . "=" . ((is_null($value) OR trim($value)=='') ? 'NULL' : "'" . mysql_real_escape_string(trim(html_entity_decode(stripslashes($value)))) . "'") . ",";
 }
 foreach ($whereArray as $column => $value)
 {
  $wheres = $wheres . $column . "=" . ((is_null($value) OR trim($value)=='') ? 'NULL' : "'" . mysql_real_escape_string($value) . "'") . " AND ";
 }
 $sqlQuery = "UPDATE " . $table . " SET " . rtrim($values, ",") . " WHERE " . substr($wheres, 0, -5) . ")";
 //echoIfNotSilent($sqlQuery . "<br />\r\n");
 mysql_query($sqlQuery) or die(mysql_error());
}

function CreateList($inputArray, $element, $separator)
{
 if (is_array($inputArray))
 {
  foreach ($inputArray as $arrayElement)
  {
   if (($element = "") or (empty($element)))
   {
    $item = $arrayElement;
   }
   else
   {
    $item = $arrayElement->$element;
   }
   if (is_string($item))
   {
    if (trim($item) <> "")
    {
     $outputList = $outputList . trim($arrayElement->$element) . $separator;
    }
   }
  }
  return rtrim($outputList, ",");
 }
}

function BlankValue($value, $nullvalue)
{
 $return = (($value == $nullvalue OR trim($value) == "") ? "" : $value);
 return $return;
}







/* Data scraping */

function TelstraGetListings($datadate, $hours, $package) //Get TV listings for a specified date and number of hours
{
 return GetPage(telstraDataURL . 'tvg-gridlist-base.cfm?v=l&c=' . $package . '&h=1&f=' . $hours . '&d=' . $datadate, listingsFolder); //Return the page for relevant day and number of hours (v = view, c = channels, h = hour start, f = number of hours, d = date )
}


/* Channels */

function TelstraGrabChannelPackages()
{
 $optionsURL = telstraDataURL . "tvg-grid-top.cfm";
 preg_match_all('~<option value="pkg-' .
				'(?P<package>.*?)' .
				'">~ism',
				GetPage($optionsURL /*, listingsFolder */), $packages, PREG_SET_ORDER);
 foreach ($packages as $package)
 {
  preg_match_all('~<td rowspan="1000" align="center" valign="top"><img src="' .
				'(?P<url>.*?)' . //Channel Image URL
				'" alt="' .
				'(?P<name>.*?)' . //Name of channel
				'"~ism',
				TelstraGetListings(currentDate, 24, 'pkg-' . urlencode($package[package])), $programs, PREG_SET_ORDER); //1 = URL of channel image, 2 = Name of channel, 4 = Details URL for first programme
  $packageChannels = '';
  foreach ($programs as $program)
  {
   $result = mysql_query("Select TC_id FROM xmltvChannels WHERE display_name='$program[name]'") or die(mysql_error());
   $row = mysql_fetch_assoc($result);
   $packageChannels .= $row[TC_id] . ",";
  }
  $packageChannels = rtrim($packageChannels, ",");
  if ($packageChannels <> "")
  {
   mysql_query("INSERT INTO ChannelPackages (provider,package,channels) VALUES('Telstra','$package[url]','$packageChannels')") or die(mysql_error());
  }
 }
}

function MergeChannelsTables($source) //Merge data from the Channels table (manually updated) into the Channels table (auto-generated).
{
 global $availableSources;
 $availableNumbers = array('Sky', 'Freeview', 'Analogue', 'All');
 $channels = mysql_query("SELECT * FROM Channels WHERE " . $source . "_id IS NOT NULL");
 while ($channel = mysql_fetch_assoc($channels))
 {
  $channel = CleanQuoteArray($channel);
  $saveSources = "";
  foreach ($availableSources as $availableSource)
  {
   $saveSources .= ", " . $availableSource . "_id=" . $channel[$availableSource . '_id'];
  }
  $saveNumbers = "";
  foreach ($availableNumbers as $number)
  {
   $saveNumbers .= ", " . $number . "_number=" . $channel[$number . '_number'];
  }
  mysql_query("UPDATE xmltvChannels SET display_name=" . $channel['display_name'] . $saveSources . $saveNumbers . ", large_icon=" . $channel[large_icon] . ", url=" . $channel['url'] . " WHERE " . $source . "_id=" . $channel[$source . '_id']); // or die(mysql_error());
  foreach(array('small_icon','genre') as $field)
  {
   if ($channel[$field] <> 'NULL')
   {
    mysql_query("UPDATE xmltvChannels SET " . $field . "=" . $channel[$field] . " WHERE " . $source . "_id=" . $channel[$source . '_id']); // or die(mysql_error());
   }
  }
 }
}

function UpdateChannelsTable($display_name, $source, $channel_id, $genre = NULL, $small_icon = NULL)
{
 mysql_query("UPDATE xmltvChannels SET display_name='$display_name' WHERE " . $source . "_id='" . $source . "-" . $channel_id . "'"); // or die(mysql_error());
 mysql_query("UPDATE xmltvChannels SET " . $source . "_id='" . $source . "-" . $channel_id . "' WHERE display_name='$display_name'"); // or die(mysql_error());
 if ($genre <> NULL)
 {
  $extraFields = ',genre,small_icon';
  $extraFieldValues = ",'" . $genre  . "','" . $small_icon . "'";
 }
 mysql_query("INSERT INTO xmltvChannels (" . $source . "_id,display_name" . $extraFields . ") VALUES('" . $source . "-" . $channel_id . "','$display_name'" . $extraFieldValues . ")"); // or die(mysql_error());
}

function TelstraGrabChannelList()
{
 $source = 'TC';
 $provider = 'Telstra';
 $genresURL = telstraDataURL . "tvg-grid-top.cfm";
 $channelsURL = telstraDataURL . "tvg-channel-favourites.cfm?v=ga&mf=1";
 echoIfNotSilent('<p>');
 preg_match_all('~<option value="genre-(?P<code>.*?)">(?P<name>.*?)</option>~ism', GetPage($genresURL, listingsFolder), $genres, PREG_SET_ORDER);
 foreach ($genres as &$genre)
 {
  $genrelist[$genre[code]] = $genre[name];
 }
 preg_match_all('~\'\)"><img src="' .
				'(?P<image>.*?)' .
				'" alt="' .
				'(?P<id>.*?)' .
				'" width="47" height="47" vspace="4" border="0"><a></a><br>' .
				'.*?' .
				'value="' .
				'(?P<name>.*?)' .
				'" genre="' .
				'(?P<genre>.*?)' .
				'"~ism',
				GetPage($channelsURL, listingsFolder), $channels, PREG_SET_ORDER);
 foreach ($channels as $channel)
 {
  $genre = $genrelist[ltrim($channel[genre], "g")];
  mysql_query("UPDATE xmltvChannels SET Telstra_genre='" . $genre . "' WHERE " . $source . "_id='" . $source . "-" . $channel[id] . "'");
  UpdateChannelsTable($channel[id], $source, $channel[name], $genre, mysql_real_escape_string($channel[image]));
  $channelList[$channel[name]] = Array("source" => $source, "name" => $channel[id], "genre" => $genre, "image" => mysql_real_escape_string($channel[image]));
  echoIfNotSilent('Added ' . $provider . ' channel ' . $channel[name] . '<br />' . "\n");
 }
 preg_match_all('~<td rowspan="1000" align="center" valign="top"><img src="' .
				'(?P<image>.*?)' . //Channel Image URL
				'" alt="' .
				'(?P<name>.*?)' . //Name of channel
				'" width="47" height="47">' .
				'.*?' .
				'openBrWindow\(\'' .
				'(?P<url>.*?)' . //Details URL for first programme
				'\',~ism',
				TelstraGetListings(currentDate, 1, 'all'), $programs, PREG_SET_ORDER); //1 = URL of channel image, 2 = Name of channel, 4 = Details URL for first programme
 foreach ($programs as $program)
 {
  preg_match('~<strong>Channel: </strong></td>' .
			'.*?' .
			'<strong>' .
			'(?P<name>.*?)' . //Channel Name
			' \(' .
			'(Channel|Digital|Analogue) ' .
			'(?P<number>.*?)' . //Channel Number
			'\) </strong></td>~ism',
			GetPage(telstraDataURL . $program[url], programsFolder), $channelNumber);
  mysql_query("UPDATE xmltvChannels SET " . $provider . "_number='" . $channelNumber[number] . "' WHERE " . $source . "_id='" . $source . "-" . GrabTelstraID($program[4]) . "'");
  $channelList[GrabTelstraID($program[url])]["number"] = $channelNumber[number];
 }
 MergeChannelsTables($source);
 echoIfNotSilent('</p>' . "\n");
 return $channelList;
}

function YahooGrabChannelList($channelSet)
{
 $source = 'YH';
 $provider = 'Yahoo';
 echoIfNotSilent('<p>');
 $channelsURL = "http://nz.tv.yahoo.com/tv-guide/?hour=00&min=00&date=" . date('d') . "&mon=" . date('m') . "&year=" . date('Y') . "&tvrg=" . $channelSet;
 //echo $channelsURL . "<br />\r\n";
 preg_match_all('~<div class="lt-listing-wrapper"><a href="http://nz.tv.yahoo.com/tv-guide/search/index.html\?venue=' .
				'(?P<id>.*?)' .
				'&now=' .
				'.*?' .
				'">' .
				'(?P<name>.*?)' .
				'</a></div>~ism',
				GetPage($channelsURL, listingsFolder), $matches, PREG_SET_ORDER);
 foreach ($matches as $match)
 {
  if ($channelSet . "-" . $match[id] <> "2-20") //If the channel's not a duplicate of TAB Trackside
  {
   UpdateChannelsTable($match[name], $source, $channelSet . "-" . $match[id]);
   $channelList[$channelSet . "-" . $match[is]] = Array("source" => $source, "name" => $match[name]);
   echoIfNotSilent('Added ' . $provider . ' channel ' . $match[name] . '<br />' . "\n");
  }
 }
 MergeChannelsTables($source);
 echoIfNotSilent('</p>' . "\n");
}

function FreeViewGrabChannelList()
{
 $source = 'FV';
 $provider = 'Freeview';
 echoIfNotSilent('<p>');
 $pageXML = new SimpleXMLElement(GetPage('http://freeviewnz.tv/epg_data.php', listingsFolder));
 foreach ($pageXML->item as $channel)
 {
  preg_match('~http://listings.tvnz.co.nz/freeview/(?P<id>.*?)_7days.xml~ism', $channel->url, $tvnzidArray);
  /*if ($channel->name == 'TV3 PLUS 1')
  {
   $tvnzidArray[id] = 'tv3plus1';
  }*/
  UpdateChannelsTable($channel->name, $source, $tvnzidArray[id]);
  echoIfNotSilent('Added ' . $provider . ' channel ' . $channel->name . '<br />' . "\n");
 }
 MergeChannelsTables($source);
 echoIfNotSilent('</p>' . "\n");
}

function SatelliteGrabChannelList()
{

}

function GrabIcons()
{
 echoIfNotSilent('<h1>Caching Icons...</h1>' . "\n" . '<p>' . "\n");
 foreach (array('small', 'large') as $iconSize)
 {
  $result = mysql_query("SELECT " . $iconSize . "_icon FROM xmltvChannels WHERE " . $iconSize . "_icon IS NOT NULL");
  while ($row = mysql_fetch_assoc($result))
  {
   GetPage($row[$iconSize . '_icon'], iconsFolder . $iconSize . '/');
  }
 }
 $result = mysql_query("SELECT icon FROM ProgramRatings WHERE icon IS NOT NULL");
 while ($row = mysql_fetch_assoc($result))
 {
  GetPage($row[icon], ratingsFolder);
 }
 echoIfNotSilent('</p>' . "\n");
}


/* Programs */

function TelstraGrabProgramList($day)
{
 $dataDate = date('Y-m-d', strtotime(currentDateSQL . " +" . $day . " days"));
 if (preg_match_all('~<td valign="top" class="tvgtime">' .
				    '(?P<startalt>.*?)' . //Start Time
				    '</td>' .
				    '.*?' .
				    'onmouseover="return escape\(\'' .
				    '(?P<genres>.*?)' . //Genre(s)
				    '<br><b>' .
				    '(?P<title>.*?)' . //Title
				    '</b><br><b>' .
				    '(?P<channel>.*?)' . //Channel
				    '</b><br><b>' .
				    '(?P<start>.*?)' . //Start Time
				    '-' .
				    '(?P<end>.*?)' . //End Time
				    '</b>' .
				    '.*?' .
				    'openBrWindow\(\'' .
				    '(?P<url>.*?)' . //Details URL
				    '\',' .
				    '.*?' .
				    //'<span class="tvgclassification">\(' .
					'</a>' .
				    '(?P<rating>.*?)' . //Age Classification
				    //'\)</span>~ism',
					'</td>~ism',
				    TelstraGetListings($dataDate, 24, 'all'), $programs, PREG_SET_ORDER) > 0) // 1 = Start Time, 2 = Genre(s), comma delimited, 3 = Title, 4 = Channel, 5 = Start Time, 6 = End Time, 7 = Details URL, 8 = Age Classification
 {
  foreach ($programs as $program)
  {
   unset($listing);
   $listing['file'] = trim($program[url]);
   $programResult = mysql_query("SELECT file FROM xmltvSourcePrograms WHERE file='" . $listing['file'] . "'") or die(mysql_error());
   if (mysql_num_rows($programResult) < 1)
   {
    //$listing['date'] = $dataDate;
    preg_match('~&ds=(?P<date>.*?)&st=~ism', $listing['file'], $dateArray);
    $startdate = $dateArray[date];
    $startTime = FixTime(trim($program[start]));
    $stopTime = FixTime(trim($program[end]));
    $listing['title'] = trim(htmlspecialchars_decode($program[title]));
    $listing['start'] = date('Y-m-d H:i:s', strtotime($startdate . ' ' . $startTime));
    //$listing['stop'] = date('Y-m-d H:i:s', strtotime($startdate . ' ' . $stopTime));
    $stopDate = $startdate;
    if (strtotime($startTime) >= strtotime($stopTime))
    {
     $stopDate = date('Y-m-d', strtotime($startdate . " +1 day"));;
    }
    $listing['stop'] = date('Y-m-d H:i:s', strtotime($stopDate . ' ' . $stopTime));
    $listing['channel_id'] = "TC-" . GrabTelstraID($listing['file']);
	$channelnameresult = mysql_query("SELECT All_id FROM xmltvChannels WHERE  TC_id = '" . $listing['channel_id'] . "'") or die(mysql_error());
	$channelnamerow = mysql_fetch_assoc($channelnameresult);
	$listing['channel'] = $channelnamerow['All_id'];
    //$listing['rating'] = trim($program[8]);
	preg_match('~<span class="tvgclassification">\((?P<rating>.*?)\)</span>~ism', trim($program[rating]), $ratingArray);
	$listing['rating'] = $ratingArray[rating];
    if (preg_match('~<td valign="top"><span class="tvbluesml"><strong class="tvhdrpurple"> <br>' . "\r" .
			       '(?P<title>.*?)' . // Title
			       '</strong></span>' .
			       '.*?' .
			       '<strong class="tvhdrpurple"><img src="' .
			       telstraImagesURL .
			       '(?P<image>.*?)' . // TV Channel Image
			       '" alt="Logo" width="47" height="47"' .
			       '.*?' .
			       '<strong>Channel: </strong></td>' .
			       '.*?' .
			       '<strong>' .
			       '(?P<channel>.*?)' . //Channel Name
			       ' \(' .
			       '(Channel|Digital|Analogue) ' .
			       '(?P<number>.*?)' . //Channel Number
			       '\) </strong></td>' .
			       '.*?' .
			       '<strong>Playing: </strong></td>' .
			       '.*?' .
			       '<td><strong class="tvbluesml">' .
			       '(?P<day>.*?)' . //Broadcast Day
			       ' at ' .
			       '(?P<time>.*?)' . //Broadcast time
			       '<!---' .
			       '.*?' .
			       '---></strong></td>' .
			       '.*' .
			       '<strong>Duration: </strong>' .
			       '.*?' .
			       '<td class="tvbluesml">' .
			       '(?P<duration>.*?)' . //Duration in minutes
			       ' Minutes</td>' .
			       '.*?' .
			       '<strong>Genre: </strong>' .
			       '.*?' .
			       '<td class="tvbluesml">' .
			       '(?P<genre>.*?)' . //Genre
			       '</td>' .
			       '.*?' .
			       '</table>' .
			       '(?P<description>.*?)' . //Description
			       '<br>~ism',
			       GetPage(telstraDataURL . $listing['file'], programsFolder), $details) <> 0) // 1 = Title, 2 = Channel ID, 3 = Channel Name, 4 = Channel Number, 5 = Broadcast Day, 6 = Broadcast time, 7 = Duration in Minutes, 8 = Genre, 9 = Description
    {
	 //$listing['title'] = trim($details[title]);
     if (substr($listing['title'], strlen($listing['title']) - 8, 8) == '&laquo; ') { $listing['title'] = substr($listing['title'], 8, strlen($listing['title']) - 8); }
     if (substr($listing['title'], 0, 8) == ' &raquo;') { $listing['title'] = substr($listing['title'], 0, strlen($listing['title']) - 8); }
     /*preg_match('~ \((.*?)\)$~ism', $listing['title'], $rating);
     if (isset($rating[1]))
     {
      $listing['rating'] = trim($rating[1]);
     }*/
     //$listing['day'] = trim($details[day]);
     //$listing['duration'] = trim($details[duration]);
     $listing['category'] = trim($details[genre]);
     /*
	 if ($listing['category'] == 'undefined, default value')
     {
      unset($listing['category']);
     }
	 */
     $listing['description'] = trim(htmlspecialchars_decode($details[description]));
     /*
	 if ($listing['category'] <> "Radio")
	 {
	  //$listing['quality'] = 'SD';
	 }
	 */
     /*
	 foreach ($listing as $insert => $value)
     {
      $inserts = $inserts . $insert . ",";
      $values = $values . $value . ",";
     }
	 mysql_query("INSERT INTO xmltvSourcePrograms (" . rtrim($inserts, ",") . ") VALUES(" . rtrim($values, ",") . ")") or die(mysql_error());
     */
	 DBInsert("xmltvSourcePrograms", $listing);
	 if (verboseOutput == 'yes')
     {
      echoIfNotSilent('Grabbed program ' . $listing['file'] . '<br />' . "\n");
     }
    }
    else
    {
     if (verboseOutput == 'yes')
     {
      echoIfNotSilent('Failed to grab program ' . $listing['file'] . '<br />' . "\n");
     }
    }
   }
   else
   {
    if (verboseOutput == 'yes')
    {
     echoIfNotSilent('Already grabbed program ' . $listing['file'] . '<br />' . "\n");
    }
   }
  }
 }
 else
 {
  if (verboseOutput == 'yes')
  {
   echoIfNotSilent('Failed to grab any program listings for day ' . $day . '<br />' . "\n");
  }
 }
}

function YahooGrabProgramList($day)
{
 foreach (array('0', '2') as $channelSet)
 {
  for ( $hour = 00; $hour <= 23; $hour += 1)
  {
   $programsURL = "http://nz.tv.yahoo.com/tv-guide/1/" . $channelSet . "/" . $day . "/" . $hour . "/";
   //echoIfNotSilent('<a href="' . $programsURL . '">' . $programsURL . "</a><br />\r\n");
   preg_match('~<input type="hidden" id="tvguide-form-start" value="' .
				'(?P<time>.*?)' .
				'"><input value="' .
				'(?P<offset>.*?)' .
				'" type="hidden" id="tvguide-form-offset">~ism',
				GetPage($programsURL, listingsFolder, $channelSet . "-" . $day . "-" . $hour . ".html", 25000), $programlist);
   //echoIfNotSilent($programlist['time'] . "<br />\r\n");
   $yesterday = date('Y-m-d', strtotime(date('Y-m-d', $programlist['time'] + ($programlist['offset'] * 60)) . " +" . strval($day - 1) . " days"));
   $today = date('Y-m-d', strtotime(date('Y-m-d', $programlist['time'] + ($programlist['offset'] * 60)) . " +" . strval($day) . " days"));
   $tomorrow = date('Y-m-d', strtotime(date('Y-m-d', $programlist['time'] + ($programlist['offset'] * 60)) . " +" . strval($day + 1) . " days"));
   preg_match_all('~<h3><a href="/tv-guide/channel/' .
				  '(?P<id>.*?)' .
				  '/">' .
				  '(?P<details>.*?)' .
				  '</ul></div>~ism',
				  GetPage($programsURL, listingsFolder, $channelSet . "-" . $day . "-" . $hour . ".html"), $channels, PREG_SET_ORDER);
   foreach ($channels as $channel)
   {
    //echoIfNotSilent($channel['id'] . "<br />\r\n");
	//echoIfNotSilent(strlen($channel['details']) . "<br />\r\n");
	preg_match_all('~<h4 class="title"><a href="/tv-guide/search/' .
				   '.*?' .
				   '/">' .
				   '(?P<title>.*?)' .
				   '</a></h4>' .
				   '.*?' .
				   '<span class="stamp">' .
				   '(?P<start>.*?)' .
				   ' - ' .
				   '(?P<stop>.*?)' .
				   '</span>' .
				   '.*?' .
				   '<dl class="info">' .
				   '(?P<info>.*?)' .
				   '</dl>' .
				   '.*?' .
				   '<p class="abstract">' .
				   '(?P<description>.*?)' .
				   '</p>~ism',
				   $channel['details'], $programs, PREG_SET_ORDER);
	foreach ($programs as $programDetails)
    {
	 unset($sqlArray);
	 $sqlArray['channel_id'] = "YH-" . $channelSet . "-" . $channel['id'];
	 $sqlArray['file'] = $sqlArray['channel_id'] . " " . $day . " " . $programDetails[start];
	 //echoIfNotSilent($sqlArray['file'] . "<br />\r\n");
	 $channeldetails = mysql_query("SELECT All_id FROM xmltvChannels WHERE  YH_id = '" . $sqlArray['channel_id'] . "'") or die(mysql_error());
	 $channeldetailsrow = mysql_fetch_assoc($channeldetails);
	 preg_match_all('~<dt>' .
					'(?P<item>.*?)' .
					':</dt>' .
					'.*?' .
					'<dd>' .
					'(?P<content>.*?)' .
					'</dd>~ism',
				    $programDetails[info], $info, PREG_SET_ORDER);
	 foreach ($info as $infoitem) //Classified, Genre
	 {
	  $infoarray[$infoitem[item]] = $infoitem[content];
	 }
	 $sqlArray['title'] = $programDetails['title'];
	 $sqlArray['description'] = $programDetails['description'];
	 $sqlArray['category'] = $infoarray['Genre'];
	 $sqlArray['channel'] = $channeldetailsrow['All_id'];
	 $startday = $today;
	 $stopday = $today;
	 /*
	 if (strtotime($programDetails['stop']) < strtotime($programDetails['start']))
	 {
	  if ($hour <= 12)
	  {
	   $startday = $yesterday;
	  }
	  else
	  {
	   $stopday = $tomorrow;
	  }
	 }
	 elseif (strtotime($programDetails['start']) < strtotime(strval($hour) . ":00:00 am"))
	 {
	  $startday = $tomorrow;
	  $stopday = $tomorrow;
	 }
	 */
	 if ($hour > 12)
	 {
	  if (strtotime($programDetails['start']) < strtotime("12:00:00"))
	  {
	   //echoIfNotSilent("Start: " . strtotime($programDetails['start']) . " < " . strtotime("02:00:00") . "<br />\r\n");
	   $startday = $tomorrow;
	  }
	  if (strtotime($programDetails['stop']) < strtotime("12:00:00"))
	  {
	   //echoIfNotSilent("Stop: " . strtotime($programDetails['stop']) . " < " . strtotime("04:00:00") . "<br />\r\n");
	   $stopday = $tomorrow;
	  }
	 }
	 elseif ($hour < 12)
	 {
	  if (strtotime($programDetails['start']) > strtotime("12:00:00"))
	  {
	   //echoIfNotSilent("Start: " . strtotime($programDetails['start']) . " > " . strtotime("20:00:00") . "<br />\r\n");
	   $startday = $yesterday;
	  }
	  if (strtotime($programDetails['stop']) > strtotime("12:00:00"))
	  {
	   //echoIfNotSilent("Stop: " . strtotime($programDetails['stop']) . " > " . strtotime("22:00:00") . "<br />\r\n");
	   $stopday = $yesterday;
	  }
	 }
	 $sqlArray['start'] = date('Y-m-d H:i:s', strtotime($startday . " " . $programDetails['start']));
	 $sqlArray['stop'] = date('Y-m-d H:i:s', strtotime($stopday . " " . $programDetails['stop']));
	 //echoIfNotSilent($sqlArray['start'] . " - " . $sqlArray['stop'] . "<br />\r\n");
	 $sqlArray['rating'] = $infoarray['Classified'];
	 DBInsert("xmltvSourcePrograms", $sqlArray);
	 if (verboseOutput == 'yes')
     {
      echoIfNotSilent('Grabbed program ' . $filename . '<br />' . "\n");
     }
	}
   }
  }
 }
}

function FreeViewGrabProgramList()
{
 $result = mysql_query("SELECT FV_id, All_id FROM xmltvChannels WHERE FV_id IS NOT NULL") or die(mysql_error());
 while ($row = mysql_fetch_assoc($result))
 {
  $tvnzArray = explode("-", $row[FV_id]);
  if ($tvnzArray[1] <> '' AND $tvnzArray[1] <> NULL)
  {
   $programsURL = "http://listings.tvnz.co.nz/freeview/" . $tvnzArray[1] . "_7days.xml";
   /*if ($tvnzArray[1] == 'tv3plus1')
   {
    $programsURL = "http://listings.tvnz.co.nz/freeview/tv3_7days.xml";
   }*/
   $freeviewPage = "";
   $freeviewPage = GetPage($programsURL, listingsFolder);
   if ($freeviewPage <> "")
   {
    $pageXML = new SimpleXMLElement($freeviewPage);
    foreach ($pageXML->channel->programmes->programme as $program)
    {
     $filename = CleanString($tvnzArray[1] . ' ' . $program['datetime_start']);
	 $programResult = mysql_query("SELECT file FROM xmltvSourcePrograms WHERE file='" . $filename . "'") or die(mysql_error());
     if (mysql_num_rows($programResult) < 1)
     {
      /*
	  mysql_query("INSERT INTO xmltvSourcePrograms (file,title,description,channel,start,stop,rating,quality,subtitles,url) VALUES('" . $filename . "','" . CleanString($program->title) . "'," . NullQuoteString(CleanString($program->synopsis)) . ",'" . $row[FV_id] . "','" . FreeViewToSQLTime($program['datetime_start']) . "','" . FreeViewToSQLTime($program['datetime_end']) . "'," . NullQuoteString($program['classification']) . ","  . ($program['hd'] == 'Y' ? "'HDTV'" : "NULL") . ","  . ($program['captioned'] == 'Y' ? "'teletext'" : "NULL") . "," . NullQuoteString(trim($program['website'])) . ")"); //or die(mysql_error());
      if ($tvnzArray[1] == 'tv3plus1')
	  {
	   mysql_query("UPDATE xmltvSourcePrograms SET start=start + INTERVAL 1 HOUR, stop=stop + INTERVAL 1 HOUR WHERE file='" . $filename . "'") or die(mysql_error());
	  }
	  */
	  $freeViewStartTime = FreeViewToSQLTime($program['datetime_start']);
	  $freeViewEndTime = FreeViewToSQLTime($program['datetime_end']);
	  /*if ($tvnzArray[1] == 'tv3plus1')
	  {
	   $freeViewStartTime = date('Y-m-d H:i:s', strtotime($freeViewStartTime . ' +1 hour'));
	   $freeViewEndTime = date('Y-m-d H:i:s', strtotime($freeViewEndTime . ' +1 hour'));
	  }*/
	  $sqlArray['file'] = $filename;
	  $sqlArray['title'] = $program->title;
	  $sqlArray['description'] = $program->synopsis;
	  $sqlArray['channel_id'] = $row['FV_id'];
	  $sqlArray['channel'] = $row['All_id'];
	  $sqlArray['start'] = $freeViewStartTime;
	  $sqlArray['stop'] = $freeViewEndTime;
	  $sqlArray['rating'] = $program['classification'];
	  $sqlArray['quality'] = ($program['hd'] == 'Y' ? "'HD'":"");
	  $sqlArray['subtitles'] = ($program['captioned'] == 'Y' ? "'teletext'":"");
	  $sqlArray['url'] = $program['website'];
	  DBInsert("xmltvSourcePrograms", $sqlArray);
	  //mysql_query("INSERT INTO xmltvSourcePrograms (file,title,description,channel_id,channel,start,stop,rating,quality,subtitles,url) VALUES('" . $filename . "','" . CleanString($program->title) . "'," . NullQuoteString(CleanString($program->synopsis)) . ",'" . $row['FV_id'] . "','" . $row['All_id'] . "','" . $freeViewStartTime . "','" . $freeViewEndTime . "'," . NullQuoteString($program['classification']) . ","  . ($program['hd'] == 'Y' ? "'HD'" : "NULL") . ","  . ($program['captioned'] == 'Y' ? "'teletext'" : "NULL") . "," . NullQuoteString(trim($program['website'])) . ")"); //or die(mysql_error());
	  //
	  if (verboseOutput == 'yes')
      {
       echoIfNotSilent('Grabbed program ' . $filename . '<br />' . "\n");
      }
	 }
	 else
	 {
	  if (verboseOutput == 'yes')
      {
	   echoIfNotSilent('Already grabbed program ' . $filename . '<br />' . "\n");
	  }
     }
	}
   }
  }
  echoIfNotSilent('<h2>Grabbed Freeview data for channel ' . $tvnzArray[1] . '</h2>' . "\n");
 }
}

function GeekNZGrabProgramList()
{
 //GetPage('http://epg.pvr.geek.nz/epg/listings-all.xml.gz', listingsFolder);
 //$fp = gzopen(listingsFolder . 'listings-all.xml.gz', "r");
 $fp = gzopen('http://epg.pvr.geek.nz/epg/listings-all.xml.gz', "r");
 $contents = gzread($fp, 100000000);
 $pageXML = new SimpleXMLElement($contents);
 foreach ($pageXML->programme as $program)
 {
  $start = substr($program['start'], 0, -6);
  $stop = substr($program['stop'], 0, -6);
  mysql_query("INSERT INTO xmltvSourcePrograms (file,title,description,channel,channel_id,start,stop,url) VALUES('" . $program['channel'] . "-" . $start . "','" . CleanString($program->title) . "'," . NullQuoteString(CleanString($program->desc)) . ",'" . $channel . "','" . $program['channel'] . "','" . $start . "','" . $stop . "'," . NullQuoteString(trim($program->url)) . ")"); //or die(mysql_error());
  if (verboseOutput == 'yes')
  {
   echoIfNotSilent('Grabbed program ' . $program['channel'] . "-" . $start . '<br />' . "\n");
  }
 }
 $channelNames = mysql_query("SELECT All_id,geeksky_id FROM xmltvChannels WHERE geeksky_id IS NOT NULL"); //or die(mysql_error());
 while ($channelName = mysql_fetch_assoc($channelNames))
 {
  mysql_query("UPDATE xmltvSourcePrograms SET channel='" . $channelName['All_id'] . "' WHERE channel_id='" . $channelName['geeksky_id'] . ".sky.co.nz'"); //or die(mysql_error());
 }
 $channelNames = mysql_query("SELECT All_id,geekfv_id FROM xmltvChannels WHERE geekfv_id IS NOT NULL"); //or die(mysql_error());
 while ($channelName = mysql_fetch_assoc($channelNames))
 {
  mysql_query("UPDATE xmltvSourcePrograms SET channel='" . $channelName['All_id'] . "' WHERE channel_id='" . $channelName['geekfv_id'] . ".freeviewnz.tv'"); //or die(mysql_error());
 }
}

function SatellitePerlGrabProgramList()
{
 $pageXML = new SimpleXMLElement(GetPage('TVGuide.xml', '/home/administrator/D2XMLTV/'));
 foreach ($pageXML->programme as $program)
 {
  /*if ($current_channel <> $program['channel'])
  {
   $channelrow = mysql_query("SELECT All_id FROM xmltvChannels WHERE satellite_id='" . $program['channel'] . "'");
   $channel_id = mysql_fetch_assoc($channelrow);
   $channel = $channel_id['All_id'];
  }
  $current_channel = $program['channel'];*/
  mysql_query("INSERT INTO xmltvSourcePrograms (file,title,description,channel,channel_id,start,stop,url) VALUES('" . $program['channel'] . "-" . $program['start'] . "','" . CleanString($program->title) . "'," . NullQuoteString(CleanString($program->desc)) . ",'" . $channel . "','" . $program['channel'] . "','" . $program['start'] . "','" . $program['stop'] . "'," . NullQuoteString(trim($program->url)) . ")"); //or die(mysql_error());
  if (verboseOutput == 'yes')
  {
   echoIfNotSilent('Grabbed program ' . $program['channel'] . "-" . $program['start'] . '<br />' . "\n");
  }
 }
 $channelNames = mysql_query("SELECT All_id,satellite_id FROM xmltvChannels WHERE satellite_id IS NOT NULL"); //or die(mysql_error());
 while ($channelName = mysql_fetch_assoc($channelNames))
 {
  mysql_query("UPDATE xmltvSourcePrograms SET channel='" . $channelName['All_id'] . "' WHERE channel_id='" . $channelName['satellite_id'] . "'"); //or die(mysql_error());
 }
}

function AvenardGrabProgramList()
{
 //http://www.avenard.org/iptv/tpg-guide.php
}







/* Fixes */

function FreeViewFixRNZNTimes()
{
 $rnznresult = mysql_query("SELECT file, start, stop FROM tempSourcePrograms WHERE channel = 'national'"); //or die(mysql_error());
 while ($rnznrow = mysql_fetch_assoc($rnznresult))
 {
  $changedTime = FALSE;
  $roundedstart = date('Y-m-d H:i:s', round(strtotime($rnznrow['start']) / (60 * 5)) * 60 * 5);
  if ($rnznrow['start'] <> $roundedstart)
  {
   $rnznrow['start'] = $roundedstart;
   $changedTime = TRUE;
  }
  $roundedstop = date('Y-m-d H:i:s', round(strtotime($rnznrow['stop']) / (60 * 5)) * 60 * 5);
  if ($rnznrow['stop'] <> $roundedstop)
  {
   $rnznrow['stop'] = $roundedstop;
   $changedTime = TRUE;
  }
  if ($changedTime == TRUE)
  {
   mysql_query("UPDATE tempSourcePrograms SET start = '" . $rnznrow['start'] . "', stop = '" . $rnznrow['stop'] . "' WHERE file = '" . $rnznrow['file'] . "'") or die(mysql_error());
   if (verboseOutput == 'yes')
   {
    echoIfNotSilent('Fixed time for Radio NZ National program ' . $rnznrow['file'] . '<br />' . "\n");
   }
  }
 }
}

function ReplaceTV1BBCWorld()
{
 $channel = 'tv1';
 $idsresult = mysql_query("SELECT TC_id, YH_id, FV_id FROM xmltvChannels WHERE All_id = '" . $channel . "'") or die(mysql_error());
 $row = mysql_fetch_assoc($idsresult);
 $availableSources = array('TC', 'YH', 'FV');
 $bbcSource = "TC-BBCV";
 foreach ($availableSources as $source)
 {
  //$channelnameresult = mysql_query("SELECT id FROM xmltvChannels WHERE " . $source . "_id = '" . $row[$source . '_id'] . "'") or die(mysql_error());
  //$channelnamerow = mysql_fetch_assoc($channelnameresult);
  //$channelid = $channelnamerow['id'];
  $bbcresult = mysql_query("SELECT file, start, stop FROM tempSourcePrograms WHERE channel_id = '" . $row[$source . '_id'] . "' AND title LIKE 'BBC World%'"); //or die(mysql_error());
  while ($bbcrow = mysql_fetch_assoc($bbcresult))
  {
   mysql_query("DELETE FROM tempSourcePrograms WHERE file = '" . $bbcrow[file] . "'") or die(mysql_error());
   $programsresult = mysql_query("SELECT * FROM tempSourcePrograms WHERE channel_id = '" . $bbcSource . "' AND stop > '" . $bbcrow[start] . "' AND start < '" . $bbcrow[stop] . "' ORDER BY start") or die(mysql_error());
   unset($programArray);
   while ($programrow = mysql_fetch_assoc($programsresult))
   {
    $programArray[] = $programrow;
   }
   if (count($programArray) >= 1)
   {
    $programArray[0][start] = $bbcrow[start];
    if (count($programArray) >= 2)
    {
     $programArray[count($programArray) - 1][stop] = $bbcrow[stop];
    }
	foreach ($programArray as $program)
	{
	 $program[file] = $row[$source . '_id'] . " " . $program[start];
	 $program[channel] = $row[$source . '_id'];
	 $program = CleanArray($program);
	 mysql_query("INSERT INTO tempSourcePrograms (file,title,description,channel_id,channel,start,stop,category,rating) VALUES('" . $program[file] . "', '" . $program[title] . "', " . NullQuoteString($program[description]) . ", '" . $program[channel] . "', '" . $channel . "', '" . $program[start] . "', '" . $program[stop] . "', " . NullQuoteString($program[category]) . ", " . NullQuoteString($program[rating]) . ")"); //or die(mysql_error());
	 if (verboseOutput == 'yes')
     {
      echoIfNotSilent('Inserted BBC Program ' . $program[file] . '<br />' . "\n");
     }
	}
   }
  }
 }
}

function FixTitles()
{
 $fixesresult = mysql_query("SELECT * FROM ProgramTitleFixes") or die(mysql_error());
 while ($fix = mysql_fetch_assoc($fixesresult))
 {
  mysql_query("UPDATE tempSourcePrograms SET title=$fix[title] WHERE title REGEXP $fix[regex]") or die(mysql_error());
 }
}

function FixProblemChannelTimes()
{
 //$channelsArray[] = "ctv2";
 //$channelsArray[] = "ctv3";
 //$channelsArray[] = "discovery";
 $channels = mysql_query("SELECT All_id FROM xmltvChannels WHERE fix_times = '1'"); //or die(mysql_error());
 while ($channel = mysql_fetch_assoc($channels))
 {
  FixTimes($channel['All_id']);
 }
}

function FixTimes($channel_id)
{
 $programsresult = mysql_query("SELECT file, start, stop FROM tempSourcePrograms WHERE channel_id = '" . $channel_id . "' ORDER BY start"); //or die(mysql_error());
 while ($program = mysql_fetch_assoc($programsresult))
 {
  $programdata[] = $program;
 }
 for ($i = 0; $i < count($programdata) - 1; $i++)
 {
  //if ($programdata[$i]['start'] == date('Y-m-d H:i:s', strtotime($programdata[$i]['stop'] . " -1 minute")) AND $programdata[$i]['stop'] <> $programdata[$i + 1]['start'])
  if ($programdata[$i]['stop'] < $programdata[$i + 1]['start'])
  {
   mysql_query("UPDATE tempSourcePrograms SET stop = '" . $programdata[$i + 1]['start'] . "' WHERE file = '" . $programdata[$i]['file'] . "'") or die(mysql_error());
   if (verboseOutput == 'yes')
   {
    echoIfNotSilent('Fixed time for program ' . $programdata[$i]['file'] . '<br />' . "\n");
   }
  }
  if ($programdata[$i]['stop'] > $programdata[$i + 1]['start']) //If start and stop are one minute apart
  {
   mysql_query("UPDATE tempSourcePrograms SET stop = '" . $programdata[$i + 1]['start'] . "' WHERE file = '" . $programdata[$i]['file'] . "'") or die(mysql_error());
   if (verboseOutput == 'yes')
   {
    echoIfNotSilent('Fixed time for program ' . $programdata[$i]['file'] . '<br />' . "\n");
   }
  }
 }
}

function FixBadCharacters($text)
{
 /*
 $characters['â€œ'] = '"'; // left side double smart quote
 $characters['â€'] = '"'; // right side double smart quote
 $characters['â€˜'] = "'"; // left side single smart quote
 $characters['â€™'] = "'"; // right side single smart quote
 $characters['â€¦'] = "..."; // elipsis
 $characters['â€”'] = "-"; // em dash
 $characters['â€“'] = "-"; // en dash
 */
 $find = array('‘', '’', '“', '”', '…', '—', '–');
 //$find2 = array('â€˜', 'â€™', 'â€œ', 'â€', 'â€¦', 'â€”', 'â€“');
 //$find3 = array('Ã¢Â€Â˜', 'Ã¢Â€Â™', 'Ã¢Â€Âœ', 'Ã¢Â€Â', 'Ã¢Â€Â¦', 'Ã¢Â€Â”', 'Ã¢Â€Â“');
 $find2 = array("\\xe2\\x80\\x98", "\\xe2\\x80\\x99", "\\xe2\\x80\\x9c", "\\xe2\\x80\\x9d", "\\xe2\\x80\\xa6", "\\xe2\\x80\\x94", "\\xe2\\x80\\x93");
 //Ã© = e acute
 $replace = array("'", "'", '"', '"', "...", "-", "-");
 return str_replace($find, $replace, str_replace($find2, $replace, $text));
 //return str_replace($find, $replace, str_replace($find2, $replace, str_replace($find3, $replace, $text)));
}

function FixDBBadCharacters()
{
 /*
 $characters['Ã¢Â€Â'] = "â€";
 $characters['â€œ'] = '"'; // left side double smart quote
 $characters['â€'] = '"'; // right side double smart quote
 $characters['â€˜'] = "'"; // left side single smart quote
 $characters['â€™'] = "'"; // right side single smart quote
 $characters['â€¦'] = "..."; // elipsis
 $characters['â€”'] = "-"; // em dash
 $characters['â€“'] = "-"; // en dash
 */
 /*
 $characters['’'] = "'";
 $characters['‘'] = "'";
 $characters['“'] = '"';
 $characters['”'] = '"';
 $characters['…'] = "...";
 $characters['–'] = "-";
 $characters['—'] = "-";
 print_r($characters);
 unset($characters);
 */
 
 $characters[] = '‘';
 $characters[] = '’';
 $characters[] = '“';
 $characters[] = '”';
 $characters[] = '…';
 $characters[] = '—';
 $characters[] = '–';
 foreach (array("title", "description") as $field)
 {
  mysql_query("UPDATE tempSourcePrograms SET " . $field . "=REPLACE(" . $field . ", '‘', '\'') WHERE " . $field . " LIKE '%€%'") or die(mysql_error());
  mysql_query("UPDATE tempSourcePrograms SET " . $field . "=REPLACE(" . $field . ", '’', '\'') WHERE " . $field . " LIKE '%€%'") or die(mysql_error());
  mysql_query("UPDATE tempSourcePrograms SET " . $field . "=REPLACE(" . $field . ", '“', '\"') WHERE " . $field . " LIKE '%€%'") or die(mysql_error());
  mysql_query("UPDATE tempSourcePrograms SET " . $field . "=REPLACE(" . $field . ", '”', '\"') WHERE " . $field . " LIKE '%€%'") or die(mysql_error());
  mysql_query("UPDATE tempSourcePrograms SET " . $field . "=REPLACE(" . $field . ", '…', '...') WHERE " . $field . " LIKE '%€%'") or die(mysql_error());
  mysql_query("UPDATE tempSourcePrograms SET " . $field . "=REPLACE(" . $field . ", '—', '-') WHERE " . $field . " LIKE '%€%'") or die(mysql_error());
  mysql_query("UPDATE tempSourcePrograms SET " . $field . "=REPLACE(" . $field . ", '–', '-') WHERE " . $field . " LIKE '%€%'") or die(mysql_error());
 
  foreach ($characters as $find => $replace)
  {
   //echo "123";
   //mysql_query("UPDATE tempSourcePrograms SET " . $field . "=REPLACE(" . $field . ", '" . $find . "', '" . $replace . "') WHERE " . $field . " LIKE '%€%'") or die(mysql_error());
  }
 }
}

function RemoveGenericTitles()
{
 $channels = mysql_query("SELECT All_id, remove_generic FROM xmltvChannels WHERE remove_generic IS NOT NULL"); //or die(mysql_error());
 while ($channel = mysql_fetch_assoc($channels))
 {
  RemoveProgram($channel['All_id'], $channel['remove_generic']);
 }
}

function RemoveProgram($channel, $title)
{
 $deleteresult = mysql_query("DELETE FROM tempSourcePrograms WHERE channel = '" . $channel . "' AND title='" . $title . "'") or die(mysql_error());
 if (verboseOutput == 'yes')
 {
  echoIfNotSilent($deleteresult . '<br />' . "\n");
 }

}

function CleanNullValues()
{
 $replacearray[] = "For details please refer to www.wtv.co.nz";
 $replacearray[] = "No Description Available";
 $replacearray[] = "C42 plays back-to-back alternative music 24-hours a day.";
 $replacearray[] = "CCTV International offers a Chinese perspective on international and national events. Available globally, presenting News with in-depth reports, expert analysis and features.";
 $replacearray[] = "Direct feed from CCTV4";
 $replacearray[] = "Direct feed from MBC";
 $replacearray[] = "Direct feed from NHK";
 $replacearray[] = "For all the latest sports news from New Zealand and around the world, catch Sport 365 Headlines.";
 $replacearray[] = "Programme to be determined.";
 $replacearray[] = "STAR PLUS the flagship channel of the STAR network is the undisputed king of Hindi general entertainment in India. Over 65 million viewers tune-in to India's No 1 channel. More info www.startv.com";
 $replacearray[] = "Phone the studio on 09 360 4444 or email the dj studio@georgefm.co.nz   Visit www.georgefm.co.nz for show schedules.";
 $replacearray[] = "100% New Zealand Music";
 $replacearray[] = "TBN is the world's largest religious network and America's most watched faith channel. TBN offers 24 hours of commercial-free inspirational programming that appeal to people in a wide variety of Protestant, Catholic and Messianic Jewish denominations.";
 $replacearray[] = "Direct feed from MBC";
 $replacearray[] = "Wild TV ceased broadcast on July 1st.  We apologise for any inconvenience.";
 $replacearray[] = "For full programme schedules visit: http://www.dw-world.de/";
 $replacearray[] = "What's happening with the weather in your neck of the woods?  We bring you the latest in weather information with regular updates from Metservice.";
 $replacearray[] = "Your tourism guide to Canterbury. 24 hours a day, 7 days a week.";
 //Remove all descriptions where
 foreach ($replacearray as $replace)
 {
  mysql_query("UPDATE tempSourcePrograms SET description=NULL WHERE description= '" . mysql_real_escape_string($replace) . "'") or die(mysql_error());
 }
 mysql_query("UPDATE tempSourcePrograms SET description=NULL WHERE title = 'Song listings on skytv.co.nz'") or die(mysql_error());
 mysql_query("UPDATE tempSourcePrograms SET category=NULL WHERE category = 'undefined, default value'") or die(mysql_error());
 mysql_query("UPDATE tempSourcePrograms SET category='Special Interest' WHERE category = 'Special Interest   '") or die(mysql_error());
}

function FixEmptyTVNZSportExtra()
{
 $sportresult = mysql_query("SELECT * FROM tempSourcePrograms WHERE channel = 'FV-tvnzsportextra'"); //or die(mysql_error());
 if (mysql_num_rows($sportresult) < 1)
 {

 }
}

function FixTitleSuffixes()
{
 foreach (Array("The", "A") as $prefix)
 {
  $sqlQuery = "UPDATE tempSourcePrograms SET title=TRIM(CONCAT('" . $prefix . " ', SUBSTR(title, 0, " . strval(0 - (strlen($prefix) + 2)) . "))) WHERE title LIKE '%, " . $prefix . "'";
  //echoIfNotSilent($sqlQuery . "<br />\r\n");
  mysql_query($sqlQuery) or die(mysql_error());
 }
}

function FixMoviePrefixes()
{
 foreach (Array("Popcorn Sessions:", "MTV Movie:", "World Cinema:", "Short Film:", "British Theatre:", "Classic Film:", "Real View:", "MOVIE:", "Rialto Premiere:") as $prefix)
 {
  $sqlQuery = "UPDATE tempSourcePrograms SET title=TRIM(SUBSTR(title, " .  strval(strlen($prefix) + 1) . ")), category='Movies' WHERE title LIKE '" . mysql_real_escape_string($prefix) . "%'";
  //echoIfNotSilent($sqlQuery . "<br />\r\n");
  mysql_query($sqlQuery) or die(mysql_error());
 }
}

function GetProgramMetadataRepeated()
{
 $metadataSuffix[0]['searchValue'] = " HD";
 $metadataSuffix[0]['metadataField'] = "quality";
 $metadataSuffix[0]['metadataValue'] = "HD";

 $metadataSuffix[1]['searchValue'] = " (WS)";
 $metadataSuffix[1]['metadataField'] = "aspect";
 $metadataSuffix[1]['metadataValue'] = "16:9";

 foreach (Array("title", "description") as $sourceField)
 {
  foreach ($metadataSuffix as $dataset)
  {
   foreach (Array("", ".") as $valueEnd)
   {
    mysql_query("UPDATE tempSourcePrograms SET " . $dataset['metadataField'] . "='" . $dataset['metadataValue'] . "' WHERE " . $sourceField . " LIKE '%" . $dataset['searchValue'] . $valueEnd . "' AND " . $dataset['metadataField'] . " IS NULL") or die(mysql_error());
    mysql_query("UPDATE tempSourcePrograms SET " . $sourceField . "=TRIM(LEFT(" . $sourceField . ", LENGTH(" . $sourceField . ") " . strval(0 - strlen($dataset['searchValue'])) . ")) WHERE " . $sourceField . " LIKE '%" . $dataset['searchValue'] . $valueEnd . "'") or die(mysql_error());
   }
  }
 }
 $metadataPrefix[0]['searchValue'] = "All New ";
 $metadataPrefix[0]['metadataField'] = "premiere";
 $metadataPrefix[0]['metadataValue'] = "1";
 foreach ($metadataPrefix as $dataset)
 {
  foreach (Array("", ".") as $valueEnd)
  {
   mysql_query("UPDATE tempSourcePrograms SET " . $dataset['metadataField'] . "='" . $dataset['metadataValue'] . "' WHERE " . $sourceField . " LIKE '" . $dataset['searchValue'] . $valueEnd . "%' AND " . $dataset['metadataField'] . " IS NULL") or die(mysql_error());
   mysql_query("UPDATE tempSourcePrograms SET " . $sourceField . "=TRIM(RIGHT(" . $sourceField . ", LENGTH(" . $sourceField . ") " . strval(0 - strlen($dataset['searchValue'])) . ")) WHERE " . $sourceField . " LIKE '" . $dataset['searchValue'] . $valueEnd . "%'") or die(mysql_error());
  }
 }
 $sqlQuery = "UPDATE tempSourcePrograms SET year=RIGHT(description, 4), description=TRIM(LEFT(description, LENGTH(description) - 4)) WHERE description RLIKE '(19|20)[0-9]{2}$'";
 //echoIfNotSilent($sqlQuery . "<br />\r\n");
 mysql_query($sqlQuery) or die(mysql_error());
 foreach (Array(".", "") as $yearend)
 {
  $sqlQuery = "UPDATE tempSourcePrograms SET year=CONVERT(SUBSTRING(description, LENGTH(description) " . strval(0 - strlen("1948)" . $yearend) + 2) . ", 4), UNSIGNED INTEGER), description=TRIM(LEFT(description, LENGTH(description) " . strval(0 - strlen("(1984)" . $yearend)) . ")) WHERE description RLIKE '(19|20)[0-9]{2}" . $yearend . "$'";
  //echoIfNotSilent($sqlQuery . "<br />\r\n");
  mysql_query($sqlQuery) or die(mysql_error());
 }

 foreach (Array("Starring:", "Starring ", "Stars:") as $starringtext)
 {
  $programs = mysql_query("SELECT file, description FROM tempSourcePrograms WHERE description RLIKE '" . $starringtext . "[[:alpha:][.space.][.comma.][.hyphen.][.apostrophe.]]+[[.period.]]{0,1}$'") or die(mysql_error());
  while ($program = mysql_fetch_assoc($programs))
  {
   preg_match("~" . $starringtext . "(?P<actors>[a-zA-Z ,\-\']+\.{0,1})$~ism", $program['description'], $result);
   if (!is_null($result))
   {
	$sqlQuery = "UPDATE tempSourcePrograms SET actors='" . mysql_real_escape_string(trim(rtrim(str_replace(", ", ",", $result['actors']), "."))) . "', description=TRIM(LEFT(description, LENGTH(description) " . strval(0 - strlen($starringtext . $result['actors'] . ".")) . ")) WHERE file='" . mysql_real_escape_string($program['file']) . "'";
	//echoIfNotSilent($sqlQuery . "<br />\r\n");
    mysql_query($sqlQuery) or die(mysql_error());
   }
  }
 }
 foreach (Array("Director:", "Dir:", "Directed by ") as $directortext)
 {
  $programs = mysql_query("SELECT file, description FROM tempSourcePrograms WHERE description RLIKE '" . $directortext . "[[:alpha:][.space.][.comma.][.hyphen.][.apostrophe.]]+[[.period.]]{0,1}$'") or die(mysql_error());
  while ($program = mysql_fetch_assoc($programs))
  {
   preg_match("~" . $directortext . "(?P<directors>[a-zA-Z ,\-\']+\.{0,1})$~ism", $program['description'], $result);
   if (!is_null($result))
   {
    $sqlQuery = "UPDATE tempSourcePrograms SET directors='" . mysql_real_escape_string(trim(rtrim(str_replace(", ", ",", $result['directors']), "."))) . "', description=TRIM(LEFT(description, LENGTH(description) " . strval(0 - strlen($directortext . $result['directors'] . ".")) . ")) WHERE file='" . mysql_real_escape_string($program['file']) . "'";
    //echoIfNotSilent($sqlQuery . "<br />\r\n");
    mysql_query($sqlQuery) or die(mysql_error());
   }
  }
 }

 //Description starting with:
 //FV  - 'Jack's Back'.
 //mgm on YH - 2001: or 2001. for films
 //Drama: for YH films
 //Part x:
 //Pt x.
 //Pt 3 of 3. Time:

 //Description ending with:
 // Starring: Julia Roberts. (WS). HD.
 // (2007)
 // Starring: Sean Connery, Michelle Pfeiffer.
 // Starring: Julia Roberts.

 //Thriller: 1988: A jaded New Yorker thinks she has found tranquility in a suburban Arizona town but starts to suspect her husband may be a vicious serial killer as he tries to prove his innocence. Starring: David Keith, Cathy Moriarty
}

function GetProgramMetadata()
{
 $advertArray = Array("Infomercials", "Infomercials.", "Infomercials for your shop at home pleasure.");
 mysql_query("UPDATE tempSourcePrograms SET category='Shopping', description=NULL WHERE " . sqlOR($advertArray, "description")) or die(mysql_error());
 $advertArray = Array("Infomercials", "Home Shopping", "Infomercial 2003");
 mysql_query("UPDATE tempSourcePrograms SET category='Shopping', description=NULL WHERE " . sqlOR($advertArray, "title")) or die(mysql_error());
 foreach (Array(":", ".", " ") as $subtitleend)
 {
  $programs = mysql_query("SELECT file, description FROM tempSourcePrograms WHERE description LIKE '\'%\'" . $subtitleend . "%' AND sub_title IS NULL") or die(mysql_error());
  while ($program = mysql_fetch_assoc($programs))
  {
   preg_match("~^'(?P<subtitle>.*?)'" . $subtitleend . "~ism", $program['description'], $result);
   if (!is_null($result))
   {
    mysql_query("UPDATE tempSourcePrograms SET sub_title='" . mysql_real_escape_string(trim($result['subtitle'])) . "', description=TRIM(RIGHT(description, LENGTH(description) " . strval(0 - strlen("'" . $result['subtitle'] . "'" . $subtitleend)) . ")) WHERE file='" . mysql_real_escape_string($program['file']) . "'") or die(mysql_error());
   }
  }
 }
 foreach (Array(":", ".") as $yearend)
 {
  $sqlQuery = "UPDATE tempSourcePrograms SET year=LEFT(description, 4), description=TRIM(RIGHT(description, LENGTH(description) - 5)) WHERE description RLIKE '^(19|20)[0-9]{2}" . $yearend . "'";
  //echoIfNotSilent($sqlQuery . "<br />\r\n");
  mysql_query($sqlQuery) or die(mysql_error());
 }




 //Description starting with:
 //FV  - 'Jack's Back'.
 //mgm on YH - 2001: or 2001. for films
 //Drama: for YH films
 //Part x:
 //Pt x.
 //Pt 3 of 3. Time:

 //Description ending with:
 // Starring: Julia Roberts. (WS). HD.
 // (2007)
 // Starring: Sean Connery, Michelle Pfeiffer.
 // Starring: Julia Roberts.
}

function CleanTitlesDescriptions()
{
 $allprograms = mysql_query("SELECT file, title, sub_title, description FROM tempSourcePrograms"); //or die(mysql_error());
 while ($program = mysql_fetch_assoc($allprograms))
 {
  if ($program['category'] == 'undefined, default value')
  {
   unset($listing['category']);
  }
  $listing['description'] = trim(htmlspecialchars_decode($details[description]));
  if (substr($listing['description'], -2) == 'HD')
  {
   $listing['quality'] = 'HD';
   $listing['description'] = trim(substr($listing['description'], 0, -2));
  }
  else if ($listing['category'] <> "Radio")
  {
   //$listing['quality'] = 'SD';
  }
  if (substr($listing['description'], -4) == '(WS)')
  {
   $listing['aspect'] = '16:9';
   $listing['description'] = trim(substr($listing['description'], 0, -4));
  }
  $listing = CleanArray($listing);

	 //At this point, need to clean up...
  //Title
  // Pick the longest one, when ... has been removed from the end of any disagreeing ones
  // Match against the titles that need changing (table ProgramTitleFixes)
  // Remove any title identifiers, such as (HD) and (1975), and convert them to relevant fields (quality, year, aspect, etc). Repeat until no match. If nothing left, undo last match!
  //  Dashes,spaces
  //   Remove
  //
  //Description
  // Remove any excess info from the program description
  //
  //  $10.99 will be charged to your account for each block ordered.
  //   Remove, or maybe add newlines (<br />)
  //  6 hourly blocks starts every 3 hours from 9pm.
  //   Remove, or maybe add newlines (<br />)
  //  www.adultchannel.co.nz for more information
  //   Remove
  //  Dashes,spaces
  //   Remove
  //  Episode 12 of 13:
  //   Convert to episode number
  //  Beginning of description matches title
  //   Remove
  //
  //Rating
  // Generate rating icon link
  //  e.g. http://www.censorship.govt.nz/img/G-label.gif
  // Fix ratings, for AO
  //
  //Genres
  // Match any channels that broadcast exclusively one genre with that genre (table xmltvChannels, field xmltv_category)
  // Match titles against known genres for programs (table ProgramCategories)
  //Any other fixes currently run against the DB, as this is the only place to run them and remain consistent (apart from when importing the source data, which we don't want to taint)
 }
}










/* Program Lookups */

function DoLookups()
{
 LookupMovies();
 LookupPrograms();
}

function LookupMovies()
{
 $movielookup = mysql_query("SELECT DISTINCT title FROM tempPrograms WHERE (channel LIKE 'bo2%' OR channel LIKE 'movies%' OR channel = 'rialto' OR channel = 'mgm' OR channel = 'tcm' OR category='Movies And Features' OR category='Movies' OR category='Movie, Drama') AND TIMEDIFF(stop, start) > '01:29:00.00'"); //or die(mysql_error());
 //or length > 2 hours, but not sports channels?
 while ($moviename = mysql_fetch_assoc($movielookup))
 {
  //echoIfNotSilent($moviename['title'] . "<br />\r\n");
  LookupMovie($moviename['title']);
 }
}

function LookupMovie($moviename)
{
 include_once('../libs/TMDb.php');
 $passmatch = 180;
 $sqlArray['listingtitle'] = $moviename;
 $movielookup = mysql_query("SELECT id, combined, title FROM tmdbMatches WHERE listingtitle = '" . CleanString($sqlArray['listingtitle']) . "'") or die(mysql_error());
 if (mysql_num_rows($movielookup) == 0)
 {
  $tmdb = new TMDb(theMovieDBAPIKey);
  $movies = json_decode($tmdb->searchMovie($sqlArray['listingtitle']));
  if ($movies[0] == "Nothing found.")
  {
   $sqlArray['id'] = 0;
  }
  else
  {
   $match = 0;
   $scorematch = 0;
   $namematch = 0;
   $bestscore = $movies[0]->score;
   foreach ($movies as $movie)
   {
    similar_text(strtolower($sqlArray['listingtitle']), strtolower($movie->name), $percentmatch[0]);
    similar_text(strtolower($sqlArray['listingtitle']), strtolower($movie->alternative_name), $percentmatch[1]);
	$scorepercent = $movie->score * 100 / $bestscore;
    foreach ($percentmatch as $namepercent)
	{
     $totalscore = $namepercent + $scorepercent;
	 if ($totalscore > $match)
     {
	  $match = $totalscore;
	  $namematch = $namepercent;
	  $scorematch = $scorepercent;
	  $movieid = $movie->id;
	  $movietitle = $movie->name;
	 }
    }
    $sqlArray['title'] = $movietitle;
    $sqlArray['score'] = $scorematch;
    $sqlArray['similar'] = $namematch;
    $sqlArray['combined'] = $match;
    $sqlArray['id'] = $movieid;
   }
  }
  DBInsert("tmdbMatches", $sqlArray);
  //if (($scorematch > 80) and ($namematch > 80) and ($match > $passmatch)) //90, 90 and 180?
  if ($match > $passmatch) //90, 90 and 180?
  {
   $lookupMovie = true;
  }
 }
 else
 {
  $moviedetails = mysql_fetch_assoc($movielookup);
  if ($moviedetails['combined'] > $passmatch)
  {
   $sqlArray['id'] = $moviedetails['id'];
   $tmdb = new TMDb(theMovieDBAPIKey);
   $sqlArray['title'] = $moviedetails['title'];
   $lookupMovie = true;
  }
 }
 if ($lookupMovie == true)
 {
  $movielookup = mysql_query("SELECT id FROM tmdbMovies WHERE id = '" . $sqlArray['id'] . "'") or die(mysql_error());
  if (mysql_num_rows($movielookup) == 0)
  {
   unset($sqlArray['listingtitle']);
   unset($sqlArray['score']);
   unset($sqlArray['similar']);
   unset($sqlArray['combined']);
   $movieobject = json_decode($tmdb->getMovie($sqlArray['id']));
   $separator = ",";
   $sqlArray['title'] = $movieobject[0]->name;
   $sqlArray['poster'] = $movieobject[0]->posters[0]->image->url;
   $sqlArray['fanart'] = $movieobject[0]->backdrops[0]->image->url;
   $sqlArray['sub_title'] = $movieobject[0]->tagline;
   $sqlArray['description'] = BlankValue($movieobject[0]->overview, "No overview found.");
   $sqlArray['genres'] = CreateList($movieobject[0]->genres, "name", $separator);
   $sqlArray['language'] = $movieobject[0]->language;
   $sqlArray['runtime'] = BlankValue($movieobject[0]->runtime, 0);
   $sqlArray['released'] = $movieobject[0]->released;
   $sqlArray['certification'] = $movieobject[0]->certification;
   $sqlArray['rating'] = BlankValue($movieobject[0]->rating, 0);
   $sqlArray['countries'] = CreateList($movieobject[0]->countries, "code", $separator);
   foreach ($movieobject[0]->cast as $castmember)
   {
    switch ($castmember->department)
	{
     case "Directing":
      $directors[] = $castmember->name;
      break;
     case "Writing":
      $writers[] = $castmember->name;
      break;
     case "Actors":
      $actors[] = $castmember->name;
      break;
    }
   }
   $sqlArray['directors'] = CreateList($directors, "", $separator);
   $sqlArray['writers'] = CreateList($writers, "", $separator);
   $sqlArray['actors'] = CreateList($actors, "", $separator);
   $sqlArray['url'] = $movieobject[0]->homepage;
   $sqlArray['trailer'] = $movieobject[0]->trailer;
   $sqlArray['imdb'] = $movieobject[0]->imdb_id;
   DBInsert("tmdbMovies", $sqlArray);
   //echoIfNotSilent($sqlArray['title'] . " (" . $sqlArray['listingname'] . ") " . $sqlArray['combined'] . " (" . $sqlArray['score'] . "/" . $sqlArray['similar'] . ")<br />\r\n");
  }
 }
 $movielookup = mysql_query("SELECT * FROM tmdbMovies JOIN tmdbMatches ON tmdbMovies.id=tmdbMatches.id WHERE tmdbMatches.listingtitle = '" . CleanString($sqlArray['listingtitle']) . "' AND tmdbMatches.combined > '" . $passmatch . "'") or die(mysql_error());
 //$movielookup = mysql_query("SELECT tmdbMovies.*, tmdbMatches.listingtitle FROM tmdbMovies, tmdbMatches ON tmdbMovies.id=tmdbMatches.id WHERE tmdbMatches.listingtitle = '" . CleanString($sqlArray['listingtitle']) . "' AND tmdbMatches.combined > '" . $passmatch . "'") or die(mysql_error());
 $moviedetails = mysql_fetch_assoc($movielookup);
 if (!empty($moviedetails))
 {
  print_r($moviedetails);
  echoIfNotSilent("\r\n<br /><br /><br />\r\n");
  //mysql_query("UPDATE xmltvSourcePrograms SET title='" . $moviedetails['title'] . "' WHERE title='" . $moviedetails['listingtitle'] . "' AND ");
 }
}

function LookupPrograms()
{
 $programlookup = mysql_query("SELECT DISTINCT description, title, sub_title FROM tempPrograms WHERE title='The Simpsons'") or die(mysql_error());
 while ($programname = mysql_fetch_assoc($programlookup))
 {
  //echoIfNotSilent($programname['description'] . "<br />\r\n");
  $programdetails = LookupProgram($programname['title']);
  if (!is_null($programdetails))
  {
   if (is_null($programname['sub_title']))
   {
    $episodedetails = MatchEpisodeByDescription($programdetails['id'], $programname['description']);
   }
   else
   {
    $episodedetails = MatchEpisodeBySubTitle($programdetails['id'], $programname['sub_title']);
   }
  }
 }
}

function LookupProgram($programname)
{
 include_once('../libs/TVDB.php');
 $passmatch = 80;
 $sqlArray['listingtitle'] = $programname;
 $programlookup = mysql_query("SELECT id, title, similar FROM tvdbMatches WHERE listingtitle = '" . CleanString($sqlArray['listingtitle']) . "'") or die(mysql_error());
 if (mysql_num_rows($programlookup) == 0)
 {
  $tvShows = TV_Shows::search($programname);
  if (empty($tvShows[0]))
  {
   $sqlArray['id'] = 0;
  }
  else
  {
   $match = 0;
   foreach ($tvShows as $tvShow)
   {
    similar_text(strtolower($programname), strtolower($tvShow->seriesName), $percentmatch);
    if ($percentmatch > $match)
    {
	 $match = $percentmatch;
	 $programid = $tvShow->id;
	 $programtitle = $tvShow->seriesName;
	}
   }
   $sqlArray['listingtitle'] = $programname;
   $sqlArray['title'] = $programtitle;
   $sqlArray['id'] = $programid;
   $sqlArray['similar'] = $match;
  }
  DBInsert("tvdbMatches", $sqlArray);
  //echoIfNotSilent($scorematch . " - " . $namematch);
  if ($match > $passmatch)
  {
   $lookupProgram = true;
  }
 }
 else
 {
  $programdetails = mysql_fetch_assoc($programlookup);
  if ($programdetails['similar'] > $passmatch)
  {
   $sqlArray['id'] = $programdetails['id'];
   $sqlArray['title'] = $programdetails['title'];
   $lookupProgram = true;
  }
 }
 if ($lookupProgram == true)
 {
  $programlookup = mysql_query("SELECT id FROM tvdbPrograms WHERE id = '" . $sqlArray['id'] . "'") or die(mysql_error());	
  if (mysql_num_rows($programlookup) == 0)
  {
   unset($sqlArray['listingtitle']);
   unset($sqlArray['similar']);
   $programobject = TV_Shows::findById($sqlArray['id']);
   $separator = ",";
   $sqlArray['title'] = $programobject->seriesName;
   $sqlArray['description'] = $programobject->overview;
   $sqlArray['genres'] = CreateList($programobject->genres, "", $separator);
   $sqlArray['runtime'] = BlankValue($programobject->runtime, 0);
   $sqlArray['released'] = $programobject->firstAired;
   $sqlArray['rating'] = BlankValue($programobject->rating, 0);
   $sqlArray['actors'] = CreateList($programobject->actors, "", $separator);
   $sqlArray['imdb'] = $programobject->imdbId;
   /*$sqlArray['poster'] = $programobject->posters[0]->image->url;
   $sqlArray['fanart'] = $programobject->backdrops[0]->image->url;
   $sqlArray['language'] = $programobject->language;
   $sqlArray['certification'] = $programobject->certification;
   $sqlArray['countries'] = CreateList($programobject->countries, "code", $separator);
   $sqlArray['url'] = $programobject->homepage;
   $sqlArray['trailer'] = $programobject->trailer;*/
   DBInsert("tvdbPrograms", $sqlArray);
   //echoIfNotSilent($sqlArray['title'] . " (" . $sqlArray['listingname'] . ") " . $sqlArray['combined'] . " (" . $sqlArray['score'] . "/" . $sqlArray['similar'] . ")<br />\r\n");
  }
 }
 //TIMEDIFF(stop, start)
 /*
 if ($programdetails['updated'])
 {
 */
 $programlookup = mysql_query("SELECT * FROM tvdbPrograms JOIN tvdbMatches ON tvdbPrograms.id=tvdbMatches.id WHERE tvdbMatches.listingtitle = '" . CleanString($programname) . "'") or die(mysql_error());
 $programdetails = mysql_fetch_assoc($programlookup);
 if (!empty($programdetails)) //Need a column for last lookup time, and only look for new episodes if more than a week old.
 {
  return $programdetails;
 }
  //LookupEpisodes($sqlArray['id']);
 /*
 }
 */
}

function LookupEpisodes($programid)
{
 $programobject = TV_Shows::findById($programid);
 $season = 0;
 $missingSeason = 0;
 $missingEposide = 0;
 do
 {
  $season = $season + 1;
  $missingSeason = $missingSeason + 1;
  $episode = 0;
  do
  {
   $episode = $episode + 1;
   $episodelookup = mysql_query("SELECT id FROM tvdbEpisodes WHERE id = '" . $sqlArray['id'] . "' AND season='" . $season . "' AND episode='" . $episode . "'") or die(mysql_error());	
   if (mysql_num_rows($episodelookup) == 0)
   {
	$tvEpisode = $programobject->getEpisode($season, $episode);
    if (empty($tvEpisode))
    {
     $missingEpisode = $missingEpisode + 1;
    }
    else
    {
	 $programlookup = mysql_query("SELECT id FROM tvdbEpisodes WHERE id = '" . $tvEpisode->id . "'") or die(mysql_error());
	 if (mysql_num_rows($programlookup) == 0)
	 {
	  unset($sqlArray);
	  $sqlArray['id'] = $tvEpisode->id;
	  $sqlArray['program_id'] = $programid;
	  $sqlArray['season'] = $tvEpisode->season;
	  $sqlArray['episode'] = $tvEpisode->episode;
	  $sqlArray['description'] = $tvEpisode->overview;
	  $sqlArray['title'] = $tvEpisode->name;
	  $sqlArray['released'] = $tvEpisode->firstAired;
	  $sqlArray['actors'] = CreateList($tvEpisode->guestStars, "", $separator);
	  $sqlArray['directors'] = CreateList($tvEpisode->directors, "", $separator);
	  $sqlArray['writers'] = CreateList($tvEpisode->writers, "", $separator);
	  $sqlArray['imdb'] = $tvEpisode->imdbId;
	  DBInsert("tvdbEpisodes", $sqlArray);
	 }
     $missingEpisode = 0;
	 $missingSeason = 0;
    }
   }
   else
   {
    $missingEpisode = 0;
    $missingSeason = 0;
   }
  }
  while ($missingEpisode < 2);
 }
 while ($missingSeason < 1);
}

function MatchEpisodeBySubTitle($programid, $subtitle)
{

}

function MatchEpisodeByDescription($programid, $episodedescription)
{
 $episodelookup = mysql_query("SELECT id FROM tvdbEpisodeMatches WHERE listingdescription = '" . mysql_real_escape_string($episodedescription) . "'") or die(mysql_error());
 if (mysql_num_rows($episodelookup) == 0)
 {
  $sqlArray['listingdescription'] = $episodedescription;
  $match = 0;
  unset($seasonnumber);
  unset($episodenumber);
  $skipWordArray = array("a", "the", "to", "and", "him", "her", "he", "she", "for", "when", "that", "of", "if", "");
  $removePunctuationArray = array(".", ",", "!", ":", "?", "/", "\\");
  foreach ($removePunctuationArray as $punctuation)
  {
   $wordlist = str_replace($punctuation, " ", $episodedescription);
  }
  //echoIfNotSilent($episodedescription . "<br />\r\n");
  $descriptionWordArray = explode(" ", $wordlist);
  //$descriptionWordArray = preg_split ("/\s+/", $wordlist);
  //print_r($descriptionWordArray);
  foreach ($descriptionWordArray as $word)
  {
   if (!in_array($word, $skipWordArray))
   {
    $wordArray[] = strtolower($word);
   }
  }
  $wordArray = array_unique($wordArray);
  $descriptionlength = strlen(implode("", $wordArray));
  $episodeslookup = mysql_query("SELECT id, season, episode, title, description FROM tvdbEpisodes WHERE program_id = '" . $programid . "'") or die(mysql_error());
  if (mysql_num_rows($episodeslookup) > 0)
  {
   while ($episode = mysql_fetch_assoc($episodeslookup))
   {
    $percentmatch = 0;
    foreach (Array(strtolower($episode['title']), strtolower($episode['description'])) as $episodetext)
    {
     if ((!is_null($episodetext)) and ($episodetext <> ""))
     {
      foreach ($wordArray as $word)
      {
       if (strpos($episodetext, $word) <> FALSE)
       {
        $percentmatch += strlen($word);
       }
      }
	 }
    }
    $percentmatch = ($percentmatch / $descriptionlength) * 100;
    if ($percentmatch > $sqlArray['similar'])
    {
     $sqlArray['similar'] = $percentmatch;
     $sqlArray['id'] = $episode['id'];
     $sqlArray['title'] = $episode['title'];
     $sqlArray['description'] = $episode['description'];
    }
   }
   DBInsert("tvdbEpisodeMatches", $sqlArray);
  }
 }
 else
 {
  $episode = mysql_fetch_assoc($episodelookup);
 }
 return $episodeid;
}





/* Merge */

function MergePrograms()
{
 echoIfNotSilent('<h2>Emptying Table tempSourcePrograms...</h2>' . "\n");
 mysql_query("TRUNCATE TABLE tempSourcePrograms") or die(mysql_error());
 echoIfNotSilent('<h2>Copying Table xmltvSourcePrograms into tempSourcePrograms...</h2>' . "\n");
 //mysql_query("INSERT INTO tempSourcePrograms SELECT * FROM xmltvSourcePrograms WHERE channel IS NOT NULL AND channel <> '' ORDER BY start, channel_id") or die(mysql_error());
 $copycolumns = implode(",", array("file", "channel", "channel_id", "start", "stop", "title", "description", "category", "rating", "quality", "subtitles", "url"));
 mysql_query("INSERT INTO tempSourcePrograms (" . $copycolumns . ") SELECT " . $copycolumns . " FROM xmltvSourcePrograms WHERE channel IS NOT NULL AND channel <> '' ORDER BY start, channel, channel_id") or die(mysql_error());
 echoIfNotSilent('<h2>Fixing Program Listings...</h2>' . "\n");
 RunFixes();
 echoIfNotSilent('<h2>Emptying Table tempPrograms...</h2>' . "\n");
 mysql_query("TRUNCATE TABLE tempPrograms") or die(mysql_error());
 echoIfNotSilent('<h2>Amalgamating program data into table tempPrograms...</h2>' . "\n");
 $programsresult = mysql_query("SELECT channel, start, stop,
	GROUP_CONCAT(DISTINCT title SEPARATOR '^') AS title,
	GROUP_CONCAT(DISTINCT description SEPARATOR '^') AS description,
	GROUP_CONCAT(DISTINCT channel_id SEPARATOR '^') AS channel_id,
	GROUP_CONCAT(DISTINCT category SEPARATOR '^') AS category,
	GROUP_CONCAT(DISTINCT rating SEPARATOR '^') AS rating,
	GROUP_CONCAT(DISTINCT aspect SEPARATOR '^') AS aspect,
	GROUP_CONCAT(DISTINCT quality SEPARATOR '^') AS quality,
	GROUP_CONCAT(DISTINCT subtitles SEPARATOR '^') AS subtitles,
	GROUP_CONCAT(DISTINCT url SEPARATOR '^') AS url
	FROM tempSourcePrograms GROUP BY channel, start, stop ORDER BY channel, stop, start") or die(mysql_error());
 while ($programrow = mysql_fetch_assoc($programsresult))
 {
  $channels = explode("^", $programrow['channel_id']);
  foreach ($programrow as &$programvalue)
  {
   $programvalue = strstrb($programvalue, '^');
  }
  /*
  foreach ($channels as $channel)
  {
   $programrow[substr($channel, 0, 2) . "_id"] = $channel;
  }
  */
  $dbprogramscheck = mysql_query("SELECT channel, start, stop, title FROM tempPrograms WHERE channel = '" . $programrow['channel'] . "' AND start < '" . $programrow['stop'] . "' AND stop > '" . $programrow['start'] . "'") or die(mysql_error());
  if (mysql_num_rows($dbprogramscheck) >= 1)
  {
   $dbprogramrow = mysql_fetch_assoc($dbprogramscheck);
   $clashcount++;
   echoIfNotSilent($dbprogramrow['channel'] . " (" . $dbprogramrow['start'] . " - " . $dbprogramrow['stop'] . ") " . $dbprogramrow['title'] . "<br />" . $programrow['channel_id'] . " (" . $programrow['start'] . " - " . $programrow['stop'] . ") " . $programrow['title'] . "<br /><br />\r\n");
  }
  //$programrow = CleanArray($programrow);
  //if ($programrow['description'] == "No Description Available")
  //{
   //unset($programrow['description']);
  //}
  //mysql_query("INSERT into tempPrograms (title, description, TC_id, YH_id, FV_id, channel, start, stop, category, rating, aspect, quality, subtitles, url) VALUES ('" . $programrow['title'] . "'," . NullQuoteString($programrow['description']) . "," . NullQuoteString($programrow['TC_id']) . "," . NullQuoteString($programrow['YH_id']) . "," . NullQuoteString($programrow['FV_id']) . ",'" . $programrow['channel_name'] . "','" . $programrow['start'] . "','" . $programrow['stop'] . "'," . NullQuoteString($programrow['category']) . "," . NullQuoteString($programrow['rating']) . "," . NullQuoteString($programrow['aspect']) . "," . NullQuoteString($programrow['quality']) . "," . NullQuoteString($programrow['subtitles']) . "," . NullQuoteString($programrow['url']) . ")"); //or die(mysql_error());
  //echoIfNotSilent(print_r($programrow) . "<br />\r\n");
  unset($programrow['channel_id']);
  DBInsert("tempPrograms", $programrow);


  $dbprograms = mysql_query("SELECT channel FROM tempPrograms WHERE channel = '" . $programrow['channel'] . "' AND start='" . $programrow['start'] . "'") or die(mysql_error());
  if (mysql_num_rows($dbprograms) < 1)
  {
   //mysql_query("INSERT into tempPrograms (title, description, channel, start, stop, category, rating, aspect, quality, subtitles, url) VALUES ('" . $programrow['title'] . "'," . NullQuoteString($programrow['description']) . ",'" . $programrow['channel'] . "','" . $programrow['start'] . "','" . $programrow['stop'] . "'," . NullQuoteString($programrow['category']) . "," . NullQuoteString($programrow['rating']) . "," . NullQuoteString($programrow['aspect']) . "," . NullQuoteString($programrow['quality']) . "," . NullQuoteString($programrow['subtitles']) . "," . NullQuoteString($programrow['url']) . ")") or die(mysql_error());

   if (verboseOutput == 'yes')
   {
    echoIfNotSilent('Merged program ' . $programrow['channel'] . " " . $programrow['start'] . '<br />' . "\n");
   }
  }
  else
  {
   //mysql_query("UPDATE tempPrograms SET title = '" . $programrow['title'] . "', description = " . NullQuoteString($programrow['description']) . ", TC_id = " . NullQuoteString($programrow['TC_id']) . ", YH_id = " . NullQuoteString($programrow['YH_id']) . ", FV_id = " . NullQuoteString($programrow['FV_id']) . ", category = " . NullQuoteString($programrow['category']) . ", rating = " . NullQuoteString($programrow['rating']) . ", aspect = " . NullQuoteString($programrow['aspect']) . ", quality = " . NullQuoteString($programrow['quality']) . ", subtitles = " . NullQuoteString($programrow['subtitles']) . ", url = " . NullQuoteString($programrow['url']) . " WHERE channel_name = '" . $programrow['channel_name'] . "' AND start = '" . $programrow['start'] . "' AND stop = '" . $programrow['stop'] . "'") or die(mysql_error());
   foreach (array("channel", "start", "stop") as $element)
   {
    $whererow[$element] = $programrow[$element];
	unset($programrow[$element]);
   }
   //mysql_query("UPDATE tempPrograms SET title = '" . $programrow['title'] . "', description = " . NullQuoteString($programrow['description']) . ", category = " . NullQuoteString($programrow['category']) . ", rating = " . NullQuoteString($programrow['rating']) . ", aspect = " . NullQuoteString($programrow['aspect']) . ", quality = " . NullQuoteString($programrow['quality']) . ", subtitles = " . NullQuoteString($programrow['subtitles']) . ", url = " . NullQuoteString($programrow['url']) . " WHERE channel = '" . $programrow['channel'] . "' AND start = '" . $programrow['start'] . "' AND stop = '" . $programrow['stop'] . "'") or die(mysql_error());
   DBInsert("tempPrograms", $programrow, $whererow);
  }
 }
 echoIfNotSilent("<h3>Total Clashes: " . $clashcount . "</h3>");
}

function CopyMerge()
{
 echoIfNotSilent('<h2>Emptying Table xmltvPrograms...</h2>' . "\n");
 mysql_query("TRUNCATE TABLE xmltvPrograms") or die(mysql_error());
 echoIfNotSilent('<h2>Copying Table tempPrograms into xmltvPrograms...</h2>' . "\n");
 mysql_query("INSERT INTO xmltvPrograms SELECT * FROM tempPrograms") or die(mysql_error());
}

function CheckMerge()
{
 $channelsresult = mysql_query("SELECT DISTINCT channel FROM xmltvPrograms");
 while ($channelrow = mysql_fetch_assoc($channelsresult))
 {
  $programsresult = mysql_query("SELECT start, stop FROM xmltvPrograms WHERE channel = '" . $channelrow['channel'] . "' ORDER BY start");
  while ($programrow = mysql_fetch_assoc($programsresult))
  {
   if ($programrow['start'] <> $stop)
   {
    echo $channelrow['channel'] . " - " . $programrow['start'] . "<br />\r\n";
   }
   $stop = $programrow['stop'];
  }
 }
}











/* Running options */

function RunFixes()
{
 FixDBBadCharacters();
 echoIfNotSilent('<h3>Fixed bad characters</h3>' . "\n");
 ReplaceTV1BBCWorld();
 echoIfNotSilent('<h3>Inserted BBC Programs to TV1</h3>' . "\n");
 FreeViewFixRNZNTimes();
 echoIfNotSilent('<h3>Fixed Radio NZ National Times</h3>' . "\n");
 FixProblemChannelTimes();
 echoIfNotSilent('<h3>Fixed program stop times</h3>' . "\n");
 RemoveGenericTitles();
 echoIfNotSilent('<h3>Removed generic programs</h3>' . "\n");
 FixTitleSuffixes();
 FixMoviePrefixes();
 echoIfNotSilent('<h3>Fixed title naming</h3>' . "\n");
 CleanNullValues();
 echoIfNotSilent('<h3>Created Null values</h3>' . "\n");
 GetProgramMetadata();
 for ($i = 0; $i <= 5; $i++)
 {
  GetProgramMetadataRepeated();
 }
 echoIfNotSilent('<h3>Grabbed data from program titles/descriptions</h3>' . "\n");
}

function GrabChannelLists()
{
 echoIfNotSilent('<h1>Grabbing Channels...</h1>' . "\n");
 mysql_query("TRUNCATE TABLE xmltvChannels");
 mysql_query("INSERT INTO xmltvChannels SELECT * FROM Channels ORDER BY All_id");
 TelstraGrabChannelList();
 YahooGrabChannelList(1);
 YahooGrabChannelList(2);
 FreeViewGrabChannelList();
 GrabIcons();
}

function GrabChannelPackages()
{
 //mysql_query("TRUNCATE TABLE ChannelPackages");
 TelstraGrabChannelPackages();
}

function GrabPrograms()
{
 echoIfNotSilent('<h2>Emptying Table xmltvSourcePrograms...</h2>' . "\n");
 mysql_query("TRUNCATE TABLE xmltvSourcePrograms") or die(mysql_error());
 echoIfNotSilent('<h1>Grabbing Programs...</h1>' . "\n");
 for ($i = 0; $i <= maxDays; $i++)
 {
  echoIfNotSilent('<h2>Grabbing Yahoo data for day ' . $i . '...</h2>' . "\n");
  YahooGrabProgramList($i);
  echoIfNotSilent('<h3>Grabbed Yahoo data for day ' . $i . '.</h3>' . "\n");
  echoIfNotSilent('<h2>Grabbing Telstra data for day ' . $i . '...</h2>' . "\n");
  TelstraGrabProgramList($i);
  echoIfNotSilent('<h3>Grabbed Telstra data for day ' . $i . '.</h3>' . "\n");
 }
 echoIfNotSilent('<h2>Grabbing Freeview data...</h2>' . "\n");
 FreeViewGrabProgramList();
 echoIfNotSilent('<h3>Grabbed Freeview data.</h3>' . "\n");
 //echoIfNotSilent('<h2>Grabbing Satellite Perl data...</h2>' . "\n");
 //SatellitePerlGrabProgramList();
 //echoIfNotSilent('<h3>Grabbed Satellite Perl data.</h3>' . "\n");
}

function RemoveOldPrograms()
{
 echoIfNotSilent('<h1>Removing Old Programs...</h1>' . "\n");
 $programsResult = mysql_query("SELECT file FROM xmltvSourcePrograms WHERE stop < '" . currentDateSQL . "'") or die(mysql_error());
 while ($programRow = mysql_fetch_assoc($programsResult))
 {
  $filenamePath = programsFolder . $programRow['file'];
  if (file_exists($filenamePath))
  {
   if (filemtime($filenamePath) < strtotime(currentDate . " -7 days"))
   {
    unlink($filenamePath);
   }
  }
  if (verboseOutput == 'yes')
  {
   echoIfNotSilent('Removed file ' . $programRow['file'] . '<br />' . "\n");
  }
 }
 mysql_query("DELETE FROM xmltvSourcePrograms WHERE stop < '" . currentDateSQL . "'") or die(mysql_error());
 mysql_query("DELETE FROM xmltvPrograms WHERE stop < '" . currentDateSQL . "'") or die(mysql_error());
 if ($handle = opendir(programsFolder))
 {
  while (false !== ($file = readdir($handle)))
  {
   if ($file != "." && $file != ".." && filemtime(programsFolder . $file) < strtotime(currentDate . " -7 days"))
   {
    $filesResult = mysql_query("SELECT file FROM xmltvSourcePrograms WHERE file='" . $file . "'") or die(mysql_error());
    if (mysql_num_rows($filesResult) < 1)
    {
     unlink(programsFolder . $file);
	 if (verboseOutput == 'yes')
     {
      echoIfNotSilent('Removed file ' . $file . '<br />' . "\n");
     }
    }
   }
  }
 }
 if ($handle = opendir(xmltvFolder . 'listings/'))
 {
  while (false !== ($file = readdir($handle)))
  {
   $folderPath = xmltvFolder . 'listings/' . $file;
   if ($file != "." && $file != ".." && strtotime($file) < strtotime(currentDate) AND is_dir($folderPath))
   {
    if ($handle2 = opendir($folderPath))
    {
     while (false !== ($file2 = readdir($handle2)))
     {
      if ($file2 != "." && $file2 != "..")
	  {
	   unlink($folderPath . '/' . $file2);
	  }
	 }
	}
	rmdir($folderPath);
   }
  }
 }
 echoIfNotSilent('<h2>Old programs removed</h2>' . "\n");
}














/* Get options */

if ($_GET["silent"] == "yes")
{
 define("runSilently", 'yes');
}
else
{
 define("runSilently", 'no');
}

if ($_GET["verbose"] == "yes")
{
 define("verboseOutput", 'yes');
}
else
{
 define("verboseOutput", 'no');
}

if (isset($_GET["day"]))
{
 $day = (int)$_GET["day"];
 if (is_int($day))
 {
  if ($day >= 0 AND $day < maxDays)
  {
   //if (strtotime($datadate) > strtotime(lastfulldatadate)) {$cachedata = false;}
  }
 }
}

switch ($_GET["cache"])
{
 case 'none':
  define("listingsFolder", '');
  define("programsFolder", '');
 break;
 case 'programs':
  define("listingsFolder", '');
  define("programsFolder", xmltvFolder . 'programs/');
 break;
 default:
  define("listingsFolder", xmltvFolder . 'listings/' . currentDate . '/');
  define("programsFolder", xmltvFolder . 'programs/');
 break;
}

if ($_GET["password"] == cachePassword)
{
 if ($_GET["all"] == "yes")
 {
  //GrabChannelLists();
  /*GrabChannelPackages();*/ // Do not run, as it wipes the existing table, which has been updated manually after this script has been run.
  GrabPrograms();
  RemoveOldPrograms();
  //RunFixes();
  MergePrograms();
  DoLookups();
  CopyMerge();
 }
 if ($_GET["fixes"] == "yes")
 {
  //GrabChannelLists();
  /*GrabChannelPackages();*/ // Do not run, as it wipes the existing table, which has been updated manually after this script has been run.
  //GrabPrograms();
  //RemoveOldPrograms();
  //RunFixes();
  MergePrograms();
  DoLookups();
  CopyMerge();
 }
 else
 {
  if ($_GET["channels"] == "yes")
  {
   GrabChannelLists();
  }
  if ($_GET["programs"] == "yes")
  {
   GrabPrograms();
   RemoveOldPrograms();
   //RunFixes();
  }
  if ($_GET["geek"] == "yes")
  {
   GeekNZGrabProgramList();
  }
  if ($_GET["movietest"] == "yes")
  {
   LookupMovies();
  }
  if ($_GET["tvtest"] == "yes")
  {
   LookupPrograms();
  }
  if ($_GET["satelliteperl"] == "yes")
  {
   SatellitePerlGrabProgramList();
  }
  if ($_GET["clearcache"] == "yes")
  {
   RemoveOldPrograms();
  }
  if ($_GET["icons"] == "yes")
  {
   GrabIcons();
  }
  if ($_GET["packages"] == "yes")
  {
   GrabChannelPackages();
  }
  if ($_GET["merge"] == "yes")
  {
   MergePrograms();
  }
  if ($_GET["check"] == "yes")
  {
   CheckMerge();
  }
 }
 echoIfNotSilent('<h1>All done!</h1>');
}
else
{
 echoIfNotSilent('<h1>Incorrect Password or No Password Given</h1>');
}
?>
