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
connectToDB();
$providers = array("Telstra" => "TC", "Yahoo" => "YH", "Freeview" => "FV");
$channelsResult = mysql_query("SELECT * FROM xmltvChannels ORDER BY display_name") or die(mysql_error());
echo '<table>';
echo '<tr><th>Channel</th>';
foreach ($providers as $providername => $provider)
{
 echo '<th>' . $providername . '</th>';
}
echo '</tr>';


while ($channelRow = mysql_fetch_assoc($channelsResult))
{
 echo '<tr>';
 echo '<td><a href="' . $channelRow['url'] . '">' . $channelRow['display_name'] . '</a></td>';
 $provider = 'TC';
 $hours = 24;
 $urls[$provider] = telstraDataURL . 'tvg-gridlist-base.cfm?v=l&c=all&h=1&f=' . $hours . '&d=' . $datadate;
 $provider = 'YH';
 list( , $channelSet, $channelID) = explode("-", $channelRow[$provider . '_id']);
 $urls[$provider] = "http://nz.tv.yahoo.com/tv-guide/search/index.html?now=" . strtotime(currentDateSQL) . "&tvrg=" . $channelSet . "&venue=" . $channelID;
 $provider = 'FV'; 
 list( , $channel) = explode("-", $channelRow[$provider . '_id']);
 $urls[$provider] = 'http://listings.tvnz.co.nz/freeview/' . $channel . '_7days.xml';
 foreach ($providers as $provider)
 {
  echo '<td><a href="' . $urls[$provider] . '">' . $channelRow[$provider . '_id'] . '</a></td>';
 }
 echo '</tr>';
}
echo '</table>';

?>