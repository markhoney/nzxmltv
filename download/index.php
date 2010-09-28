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
$channelIconsArchiveFolder = 'icons/channels/';
$ratingIconsArchiveFolder = 'icons/ratings/';
$dtdsArchiveFolder = 'dtds/';

function sendHeaders($fileName, $fileSize)
{
 header("Content-disposition: attachment; filename=" . $fileName); //Generate HTML header
 if (isset($fileSize))
 {
  header("Content-Length: " . $fileSize);
 }
 header("Content-Type: application/force-download");
 header("Content-Transfer-Encoding: binary");
 header("Pragma: no-cache");
 header("Expires: 0");
}

function CreateChannelXML($id, $number, $display_name, $url, $icon)
{
 //$channelxml  = '  <channel id="' . $channelid . '">' . "\n"; //Add channel ID (from Image name)
 $channelxml  = '  <channel id="' . $id . '">' . "\n"; //Add channel ID (from Image name)
 if (isset($display_name))
 {
  $channelxml .= '    <display-name>' . htmlentities($display_name) . '</display-name>' . "\n"; //Add Channel friendly name
 }
 if (isset($number))
 {
  $channelxml .= '    <display-name>' . $number . '</display-name>' . "\n"; //Add Channel number
 }
 if (isset($icon))
 {
  $channelxml .= '    <icon src="' . $icon . '" />' . "\n"; //Add Icon URL
 }
 if (isset($url))
 {
  $channelxml .= '    <url>' . htmlentities($url) . '</url>' . "\n"; //Add Channel friendly name
 }
 $channelxml .= '  </channel>' . "\n";
 return $channelxml;
}

function CreateChannelInfoXML($id, $number, $display_name)
{
 $channelxml  = '  <channel>' . "\n"; //Add channel ID (from Image name)
 $channelxml .= '    <name>' . $display_name . '</name>' . "\n";
 $channelxml .= '    <channelid>' . $id . '</channelid>' . "\n";
 $channelxml .= '    <virtualchannel>' . $number . '</virtualchannel>' . "\n";
 $channelxml .= '  </channel>' . "\n"; //Add channel ID (from Image name)
 return $channelxml;
}

function CreateNewChannelXML($id, $number, $display_name, $url, $icon)
{
 $channelxml  = '  <channel id="' . $id . '">' . "\n"; //Add channel ID (from Image name)
 if (isset($number))
 {
  $channelxml .= '    <display-name>' . $number . '</display-name>' . "\n"; //Add Channel number
 }
 if (isset($display_name))
 {
  $channelxml .= '    <display-name>' . $display_name . '</display-name>' . "\n"; //Add Channel friendly name
 }
 if (isset($url))
 {
  $channelxml .= '    <url>' . urlencode($url) . '</url>' . "\n"; //Add Channel friendly name
 }
 if (isset($icon))
 {
  $channelxml .= '    <icon src="' . $icon . '" />' . "\n"; //Add Icon URL
 }
 $channelxml .= '  </channel>' . "\n";
 return $channelxml;
}

function createRatingIconName($rating)
{
 switch (substr($rating, 0, 1))
 {
  case 'G':
   $ratingicon = 'G-label.gif';
  break;
  case 'P':
   $ratingicon = 'PG-label.gif';
  break;
  case 'M':
   $ratingicon = 'M-label.gif';
  break;
  case 'A':
    $ratingicon = '18 label.jpg';
  break;
  case 'R':
   $ratingicon = 'R label.jpg';
  break;
  case '1':
   switch (substr($rating, 1, 1))
   {
    case '6':
     $ratingicon = '16 label.jpg';
    break;
    case '8':
     $ratingicon = '18 label.jpg';
    break;
   }
  break;
  case 'P':
   $ratingicon = 'PG-label.gif';
  break;
 }
 //return  . $ratingicon;
 return $ratingicon;
}

function CreateProgramXML($channel, $start, $stop, $title, $description, $category, $rating, $aspect, $quality, $offset, $subtitles, $url, $radio, $ratingsrc)
{
 $programXML = '  <programme channel="' . $channel . '" start="' . $start . $offset . '" stop="' . $stop . $offset . '">' . "\n"; //Write the programme channel and start/end time
 $programXML .= '    <title>' . htmlentities(htmlspecialchars_decode(str_replace('’', "'", $title)), ENT_QUOTES,'UTF-8') . '</title>' . "\n"; //Add the title
 if (isset($description))
 {
  $programXML .= '    <desc>' . htmlentities(htmlspecialchars_decode(str_replace('’', "'", $description)), ENT_QUOTES,'UTF-8') . '</desc>' . "\n"; //Add the description	
 }
 if (isset($category))
 {
  foreach (explode(',', $category) as $category) //Separate the Genres
  {
   $programXML .= '    <category>' . trim($category) . '</category>' . "\n"; //Write the genres
  }
 }
 if (isset($url))
 {
  $programXML .= '    <url>' . htmlentities($url) . '</url>' . "\n"; //Add the description	
 }
 if (isset($aspect) OR isset($quality) OR isset($radio))
 {
  $programXML .= '    <video>' . "\n"; //Add the video info
  if (isset($aspect))
  {
   $programXML .= '      <aspect>' . $aspect . '</aspect>' . "\n";
  }
  if (isset($quality))
  {
   $programXML .= '      <quality>' . $quality . '</quality>' . "\n";
  }
  if (isset($radio))
  {
   $programXML .= '      <present>no</present>' . "\n";
  }
  $programXML .= '    </video>' . "\n";
 }
 if (isset($subtitles))
 {
  $programXML .= '    <subtitles type="' . $subtitles . '" />' . "\n"; //Add the description	
 }
 if (isset($rating))
 {
  $programXML .= '    <rating system="New Zealand">' . "\n"; //Add the rating
  $programXML .= '      <value>' . $rating . '</value>' . "\n";
  switch (substr($rating, 0, 1))
  {
   case 'G':
    $ratingicon = 'G-label.gif';
   break;
   case 'P':
    $ratingicon = 'PG-label.gif';
   break;
   case 'M':
    $ratingicon = 'M-label.gif';
   break;
   case 'A':
    $ratingicon = 'M-label.gif';
   break;
   case 'R':
    $ratingicon = 'R label.jpg';
   break;
   case '1':
    switch (substr($rating, 1, 1))
    {
     case '6':
      $ratingicon = '16 label.jpg';
     break;
	 case '8':
      $ratingicon = '18 label.jpg';
     break;
	}
   break;
   case 'P':
    $ratingicon = 'PG-label.gif';
   break;
  }
  if (isset($ratingicon))
  {
   $programXML .= '      <icon src="' . $ratingsrc . $ratingicon . '" />' . "\n";
  }
  $programXML .= '    </rating>' . "\n";
 }
 $programXML .= '  </programme>' . "\n";
 return $programXML;
}

function CreateNewProgramXML($channel, $start, $stop, $title, $description, $category, $aspect, $quality, $offset)
{
 $programXML = '  <programme channel="' . $channel . '" start="' . $start . $offset . '" stop="' . $stop . $offset . '">' . "\n"; //Write the programme channel and start/end time
 $programXML .= '    <title>' . $title . '</title>' . "\n"; //Add the title
 if (isset($description))
 {
  $programXML .= '    <desc>' . $description . '</desc>' . "\n"; //Add the description	
 }
 if (isset($rating))
 {
  $programXML .= '    <rating system="New Zealand">' . "\n"; //Add the rating
  $programXML .= '      <value>' . $rating . '</value>' . "\n";
  $programXML .= '    </rating>' . "\n";
 }
 if (isset($category))
 {
  foreach (explode(',', $category) as $category) //Separate the Genres
  {
   $programXML .= '    <category>' . trim($category) . '</category>' . "\n"; //Write the genres
  }
 }
 if (isset($aspect) OR isset($quality))
 {
  $programXML .= '    <video>' . "\n"; //Add the video info
  if (isset($aspect))
  {
   $programXML .= '      <aspect>' . $aspect . '</aspect>' . "\n";
  }
  if (isset($quality))
  {
   $programXML .= '      <quality>' . $quality . '</quality>' . "\n";
  }
  $programXML .= '    </video>' . "\n";
 }
 $programXML .= '  </programme>' . "\n";
 return $programXML;
}

function CreateXMLHeader($xmlDate, $offset, $startDate, $endDate, $requiredChannels, $provider, $dtd)
{
 $header = '<?xml version="1.0" encoding="ISO-8859-1"?>' . "\n"; //Write the XML header
 $header .= '<!DOCTYPE tv SYSTEM "' . $dtd . '">' . "\n";
 $header .= '<tv date="' . $xmlDate . $offset . '" source-info-name="NZ XMLTV Listings from ' . $startDate . ' to ' . $endDate . ' for channels ' . $requiredChannels . ' with Provider ' . $provider . '" source-info-url="' . htmlentities(CurrentURL()) . '" generator-info-name="NZ XMLTV Generator" generator-info-url="http://xmltv.co.nz/site/about/">' . "\n";
 return $header;
}

function CreateNewXMLHeader($xmlDate, $offset, $startDate, $endDate, $requiredChannels, $provider, $dtd)
{
 $header = '<?xml version="1.0" encoding="ISO-8859-1"?>' . "\n"; //Write the XML header
 $header .= '<!DOCTYPE tv SYSTEM "' . $dtd . '">' . "\n";
 $header .= '<tv date="' . $xmlDate . $offset . '" source-info-name="NZ XMLTV Listings from ' . $startDate . ' to ' . $endDate . ' for channels ' . $requiredChannels . ' with Provider ' . $provider . '" source-info-url="' . urlencode(CurrentURL()) . '" generator-info-name="NZ XMLTV Generator" generator-info-url="http://xmltv.co.nz/site/about/">' . "\n";
 return $header;
}

function CreateXMLFooter()
{
 return '</tv>';
}

function CreateMXFHeader($xmlDate, $offset, $startDate, $endDate, $requiredChannels, $provider, $dtd)
{
  $header = <<<EOT
<?xml version="1.0" encoding="utf-16"?>
<MXF>
 <Assembly name="mcepg">
  <NameSpace name="Microsoft.MediaCenter.Guide">
   <Type name="GuideImage" />
   <Type name="Lineup" />
   <Type name="Channel" parentFieldName="Lineup" />
   <Type name="Service" />
   <Type name="ScheduleEntry" groupName="ScheduleEntries" />
   <Type name="Program" />
  </NameSpace>
 </Assembly>
 <Assembly name="mcstore">
  <NameSpace name="Microsoft.MediaCenter.Store">
   <Type name="Provider" />
   <Type name="UId" parentFieldName="target" />
  </NameSpace>
 </Assembly>
 <Providers>
EOT;
 $header .= '		<Provider id="xmltv.co.nz" name="' . 'xmltv.co.nz' . '" displayName="' . 'xmltv.co.nz' . '" copyright="' . 'Left' . '" />';
 $header .= <<<EOT
 </Providers>
 <With provider="xmltv.co.nz">
  <GuideImages>
   <GuideImage id="i2" uid="!Image!XYZNetworkLogo" imageUrl="http://www.fakeurl.com/images/image2.jpg" />
  </GuideImages>
EOT;
 return $header;
}

function CreateMXFFooter()
{
 return ' </With>
</MXF>';
}

function CreateChannelMXF($id, $number, $display_name, $url, $icon)
{
 $channelMXF  = <<<EOT
  <Lineups>
   <Lineup name="xmltv.co.nz" id="xmltv.co.nz" uid="!Lineup!xmltv.co.nz" primaryProvider="!MCLineup!MainLineup">
    <channels>
     <Channel lineup="l1" uid="!Channel!BSEinet_tc-24j2_j2" number="-1" subNumber="0" service="s0" />
     <Channel lineup="l1" uid="!Channel!BSEinet_tc-anim_animal_planet" number="-1" subNumber="0" service="s1" />
    </channels>
   </Lineup>
  </Lineups>
EOT;
 return $channelMXF;
}

function CreateProgramMXF($channel, $start, $stop, $title, $description, $category, $rating, $aspect, $quality, $offset, $subtitles, $url, $radio, $ratingsrc)
{
$programMXF = <<<EOT
		<Keywords>
EOT;
	
			$programXML .= '<Keyword id="g1" word="General" />';
			
/*			<Keyword id="c100" word="All" />
			<Keyword id="g2" word="Educational" />
			<Keyword id="c200" word="All" />
			<Keyword id="g3" word="Kids" />
			<Keyword id="c300" word="All" />
			<Keyword id="g4" word="Lifestyle" />
			<Keyword id="c400" word="All" />
			<Keyword id="g5" word="Movies" />
			<Keyword id="c500" word="All" />
			<Keyword id="g6" word="News" />
			<Keyword id="c600" word="All" />
			<Keyword id="g7" word="Series" />
			<Keyword id="c700" word="All" />
			<Keyword id="g8" word="Special" />
			<Keyword id="c800" word="All" />
			<Keyword id="g9" word="Sports" />
			<Keyword id="c900" word="All" />
			<Keyword id="c101" word="Action/Adventure" />
			<Keyword id="c102" word="Comedy" />
			<Keyword id="c103" word="Documentary/Bio" />
			<Keyword id="c104" word="Drama" />
			<Keyword id="c105" word="Educational" />
			<Keyword id="c106" word="Family/Children" />
			<Keyword id="c107" word="Movies" />
			<Keyword id="c108" word="Music" />
			<Keyword id="c109" word="News" />
			<Keyword id="c110" word="Sci-Fi/Fantasy" />
			<Keyword id="c111" word="Soap" />
			<Keyword id="c112" word="Sports" />
			<Keyword id="c113" word="Other" />
			<Keyword id="c201" word="Arts" />
			<Keyword id="c202" word="Biography" />
			<Keyword id="c203" word="Documentary" />
			<Keyword id="c204" word="How-to" />
			<Keyword id="c205" word="Science" />
			<Keyword id="c206" word="Other" />
			<Keyword id="c301" word="Adventure" />
			<Keyword id="c302" word="Animated" />
			<Keyword id="c303" word="Comedy" />
			<Keyword id="c304" word="Educational" />
			<Keyword id="c305" word="Special" />
			<Keyword id="c306" word="Other" />
			<Keyword id="c401" word="Adults Only" />
			<Keyword id="c402" word="Collectibles" />
			<Keyword id="c403" word="Cooking" />
			<Keyword id="c404" word="Exercise" />
			<Keyword id="c405" word="Health" />
			<Keyword id="c406" word="Home and Garden" />
			<Keyword id="c407" word="Outdoors" />
			<Keyword id="c408" word="Religious" />
			<Keyword id="c409" word="Other" />
			<Keyword id="c501" word="Action and Adventure" />
			<Keyword id="c502" word="Adults Only" />
			<Keyword id="c503" word="Children" />
			<Keyword id="c504" word="Comedy" />
			<Keyword id="c505" word="Drama" />
			<Keyword id="c506" word="Fantasy" />
			<Keyword id="c507" word="Horror" />
			<Keyword id="c508" word="Musical" />
			<Keyword id="c509" word="Mystery" />
			<Keyword id="c510" word="Romance" />
			<Keyword id="c511" word="Science Fiction" />
			<Keyword id="c512" word="Suspense" />
			<Keyword id="c513" word="Western" />
			<Keyword id="c514" word="Other" />
			<Keyword id="c601" word="Business" />
			<Keyword id="c602" word="Current Events" />
			<Keyword id="c603" word="Interview" />
			<Keyword id="c604" word="Public Affairs" />
			<Keyword id="c605" word="Sports" />
			<Keyword id="c606" word="Weather" />
			<Keyword id="c607" word="Other" />
			<Keyword id="c701" word="Action/Adventure" />
			<Keyword id="c702" word="Children" />
			<Keyword id="c703" word="Comedy" />
			<Keyword id="c704" word="Cooking" />
			<Keyword id="c705" word="Drama" />
			<Keyword id="c706" word="Educational" />
			<Keyword id="c707" word="Game Show" />
			<Keyword id="c708" word="How-to" />
			<Keyword id="c709" word="Music" />
			<Keyword id="c710" word="Reality" />
			<Keyword id="c711" word="Soap Opera" />
			<Keyword id="c712" word="Talk Show" />
			<Keyword id="c713" word="Travel" />
			<Keyword id="c714" word="Other" />
			<Keyword id="c715" word="Top Rated" />
			<Keyword id="c801" word="Awards/Event" />
			<Keyword id="c802" word="Holiday" />
			<Keyword id="c803" word="Music" />
			<Keyword id="c804" word="Religious" />
			<Keyword id="c805" word="Sports" />
			<Keyword id="c806" word="Other" />
			<Keyword id="c901" word="Baseball" />
			<Keyword id="c902" word="Basketball" />
			<Keyword id="c903" word="Boxing" />
			<Keyword id="c904" word="Football" />
			<Keyword id="c905" word="Golf" />
			<Keyword id="c906" word="Hockey" />
			<Keyword id="c907" word="Outdoor" />
			<Keyword id="c908" word="Racing" />
			<Keyword id="c909" word="Soccer" />
			<Keyword id="c910" word="Tennis" />
			<Keyword id="c911" word="Cricket" />
			<Keyword id="c912" word="AFL" />
			<Keyword id="c913" word="NRL" />
			<Keyword id="c914" word="Rugby Union" />
			<Keyword id="c915" word="Netball" />
			<Keyword id="c916" word="Bowling" />
			<Keyword id="c917" word="Water Sports" />
			<Keyword id="c918" word="Extreme Sports" />
			<Keyword id="c919" word="Other" />
*/

$programXML .= <<<EOT
 		</Keywords>
		<KeywordGroups>
			<KeywordGroup groupName="g1" uid="!KeywordGroup!g1" keywords="c100,c101,c102,c103,c104,c105,c106,c107,c108,c109,c110,c111,c112,c113" />
			<KeywordGroup groupName="g2" uid="!KeywordGroup!g2" keywords="c200,c201,c202,c203,c204,c205,c206" />
			<KeywordGroup groupName="g3" uid="!KeywordGroup!g3" keywords="c300,c301,c302,c303,c304,c305,c306" />
			<KeywordGroup groupName="g4" uid="!KeywordGroup!g4" keywords="c400,c401,c402,c403,c404,c405,c406,c407,c408,c409" />
			<KeywordGroup groupName="g5" uid="!KeywordGroup!g5" keywords="c500,c501,c502,c503,c504,c505,c506,c507,c508,c509,c510,c511,c512,c513,c514" />
			<KeywordGroup groupName="g6" uid="!KeywordGroup!g6" keywords="c600,c601,c602,c603,c604,c605,c606,c607" />
			<KeywordGroup groupName="g7" uid="!KeywordGroup!g7" keywords="c700,c701,c702,c703,c704,c705,c706,c707,c708,c709,c710,c711,c712,c713,c714,c715" />
			<KeywordGroup groupName="g8" uid="!KeywordGroup!g8" keywords="c800,c801,c802,c803,c804,c805,c806" />
			<KeywordGroup groupName="g9" uid="!KeywordGroup!g9" keywords="c900,c901,c902,c903,c904,c905,c906,c907,c908,c909,c910,c911,c912,c913,c914,c915,c916,c917,c918,c919" />
		</KeywordGroups>
		<GuideImages />
		<SeriesInfos>
			<SeriesInfo uid="!Series!T_295834558" id="si1" title="Night Train" shortTitle="Night Train" />
			<SeriesInfo uid="!Series!T_567929385" id="si2" title="Up &amp; At 'Em" shortTitle="Up &amp; At 'Em" />
			<SeriesInfo uid="!Series!T_1982367151" id="si3" title="00's Are Awesome" shortTitle="00's Are Awesome" />
			<SeriesInfo uid="!Series!T_912834096" id="si4" title="63 - Our Music" shortTitle="63 - Our Music" />
			<SeriesInfo uid="!Series!T_728409992" id="si5" title="63 Spotlight" shortTitle="63 Spotlight" />
			<SeriesInfo uid="!Series!T_n174433102" id="si2199" title="A view of what's on TV right now." shortTitle="A view of what's on TV right now." />
		</SeriesInfos>
		<Affiliates>
			<Affiliate uid="!MCAffiliate!Seven_Network" name="Seven Network" />
		</Affiliates>
		<Services>
			<Service id="s0" uid="!Service!inet_tc-24j2_j2" name="J2" callSign="J2" />
			<Service id="s1" uid="!Service!inet_tc-anim_animal_planet" name="Animal Planet" callSign="Animal Planet" />
			<Service id="s2" uid="!Service!inet_tc-art2_the_arts_channel" name="The Arts Channel" callSign="The Arts Channel" />
			<Service id="s3" uid="!Service!inet_tc-bbcv_bbc_world" name="BBC World" callSign="BBC World" />
			<Service id="s4" uid="!Service!inet_tc-cbnc_cnbc" name="CNBC" callSign="CNBC" />
			<Service id="s5" uid="!Service!inet_tc-cbtv_canterbury_tv" name="Canterbury TV" callSign="Canterbury TV" />
			<Service id="s6" uid="!Service!inet_tc-cmd1_comedy_central" name="Comedy Central" callSign="Comedy Central" />
			<Service id="s7" uid="!Service!inet_tc-cnns_cnn" name="CNN" callSign="CNN" />
			<Service id="s8" uid="!Service!inet_tc-criv_crime_&amp;_investigation" name="Crime &amp; Investigation" callSign="Crime &amp; Investigation" />
			<Service id="s9" uid="!Service!inet_tc-crtn_cartoon_network" name="Cartoon Network" callSign="Cartoon Network" />
			<Service id="s10" uid="!Service!inet_tc-disc_discovery" name="Discovery" callSign="Discovery" />
			<Service id="s11" uid="!Service!inet_tc-disct_discovery_travel_&amp;_living" name="Discovery Travel &amp; Living" callSign="Discovery Travel &amp; Living" />
			<Service id="s12" uid="!Service!inet_tc-doco_documentary_channel" name="Documentary Channel" callSign="Documentary Channel" />
			<Service id="s13" uid="!Service!inet_tc-echl_en" name="E!" callSign="E!" />
			<Service id="s14" uid="!Service!inet_tc-espn_espn" name="ESPN" callSign="ESPN" />
			<Service id="s15" uid="!Service!inet_tc-food_food_television" name="Food Television" callSign="Food Television" />
			<Service id="s16" uid="!Service!inet_tc-fstv_fashion_tv" name="Fashion TV" callSign="Fashion TV" />
			<Service id="s17" uid="!Service!inet_fv-parliamenttv_parliament_tv" name="Parliament TV" callSign="Parliament TV" />
			<Service id="s18" uid="!Service!inet_tc-hist_the_history_channel" name="The History Channel" callSign="The History Channel" />
			<Service id="s19" uid="!Service!inet_tc-juic_juice_tv" name="Juice TV" callSign="Juice TV" />
			<Service id="s20" uid="!Service!inet_tc-liv1_the_living_channel" name="The Living Channel" callSign="The Living Channel" />
			<Service id="s21" uid="!Service!inet_tc-mgmm_mgm" name="MGM" callSign="MGM" />
			<Service id="s22" uid="!Service!inet_tc-mov3_sky_movie_greats" name="Sky Movie Greats" callSign="Sky Movie Greats" />
			<Service id="s23" uid="!Service!inet_tc-movi_sky_movies" name="Sky Movies" callSign="Sky Movies" />
			<Service id="s24" uid="!Service!inet_tc-mtv1_mtv" name="MTV" callSign="MTV" />
			<Service id="s25" uid="!Service!inet_tc-natg_national_geographic" name="National Geographic" callSign="National Geographic" />
			<Service id="s26" uid="!Service!inet_tc-news_sky_news_nz" name="Sky News NZ" callSign="Sky News NZ" />
			<Service id="s27" uid="!Service!inet_tc-nick_nickelodeon" name="Nickelodeon" callSign="Nickelodeon" />
			<Service id="s28" uid="!Service!inet_tc-pepg_prime" name="Prime" callSign="Prime" affiliate="!MCAffiliate!Seven_Network" />
			<Service id="s29" uid="!Service!inet_tc-shin_shine_tv" name="Shine TV" callSign="Shine TV" />
			<Service id="s30" uid="!Service!inet_tc-sky1_the_box" name="The BOX" callSign="The BOX" />
			<Service id="s31" uid="!Service!inet_tc-sky2_vibe" name="Vibe" callSign="Vibe" />
			<Service id="s32" uid="!Service!inet_tc-skyb_sky_box_office_201" name="Sky Box Office 201" callSign="Sky Box Office 201" />
			<Service id="s33" uid="!Service!inet_tc-smtp_sky_movies_2" name="SKY Movies 2" callSign="SKY Movies 2" />
			<Service id="s34" uid="!Service!inet_tc-spee_sky_box_office_preview" name="Sky Box Office Preview" callSign="Sky Box Office Preview" />
			<Service id="s35" uid="!Service!inet_fv-stratos_stratos" name="Stratos" callSign="Stratos" />
			<Service id="s36" uid="!Service!inet_tc-sund_rialto_channel" name="Rialto Channel" callSign="Rialto Channel" />
			<Service id="s37" uid="!Service!inet_tc-tbn_tbn" name="TBN" callSign="TBN" />
			<Service id="s38" uid="!Service!inet_tc-tcm1_tcm" name="TCM" callSign="TCM" />
			<Service id="s39" uid="!Service!inet_fv-tvone_tv_one" name="TV One" callSign="TV One" />
			<Service id="s40" uid="!Service!inet_fv-tv2_tv2" name="TV2" callSign="TV2" />
			<Service id="s41" uid="!Service!inet_fv-c4_c4" name="C4" callSign="C4" />
			<Service id="s42" uid="!Service!inet_tc-uktv_uk_tv" name="UK TV" callSign="UK TV" />
			<Service id="s43" uid="!Service!inet_tc-weat_weather" name="Weather" callSign="Weather" />
			<Service id="s44" uid="!Service!inet_fv-tv3_tv3" name="TV3" callSign="TV3" />
			<Service id="s45" uid="!Service!inet_tc-wtv1_wtv_japanese_tv1" name="WTV Japanese TV1" callSign="WTV Japanese TV1" />
			<Service id="s46" uid="!Service!inet_tc-wtv5_wtv_chinese_tv4" name="WTV Chinese TV4" callSign="WTV Chinese TV4" />
			<Service id="s47" uid="!Service!inet_tc-mosaic_scan" name="Scan" callSign="Scan" />
		</Services>
		<Programs>
			<Program id="1" uid="!Program!1521533272_n580373510_20091002100000_295834558" series="si1" title="Night Train" description="All aboard. It's non-stop till dawn with a continuous mix of our music till sunrise" shortDescription="All aboard. It's non-stop till dawn with a continuous mix of our music till sunrise" episodeTitle="" originalAirdate="2009-10-02T10:00:00" isMusic="true" isSeries="true" keywords="c108,c113,c201,g1,g2" seasonNumber="0" episodeNumber="0" mpaaRating="1" />
			<Program id="2" uid="!Program!1521533272_n580373510_20091002110000_295834558" series="si1" title="Night Train" description="All aboard. It's non-stop till dawn with a continuous mix of our music till sunrise" shortDescription="All aboard. It's non-stop till dawn with a continuous mix of our music till sunrise" episodeTitle="" originalAirdate="2009-10-02T11:00:00" isMusic="true" isSeries="true" keywords="c108,c113,c201,g1,g2" seasonNumber="0" episodeNumber="0" mpaaRating="1" />
			<Program id="3" uid="!Program!1521533272_n580373510_20091002170000_295834558" series="si1" title="Night Train" description="All aboard. It's non-stop till dawn with a continuous mix of our music till sunrise" shortDescription="All aboard. It's non-stop till dawn with a continuous mix of our music till sunrise" episodeTitle="" originalAirdate="2009-10-02T17:00:00" isMusic="true" isSeries="true" keywords="c108,c113,c201,g1,g2" seasonNumber="0" episodeNumber="0" mpaaRating="1" />
			<Program id="4" uid="!Program!1521533272_n580373510_20091002180000_567929385" series="si2" title="Up &amp; At 'Em" description="Pump up the jam bunnies! A power packed fun hour of cardio friendly classics to get you moving" shortDescription="Pump up the jam bunnies! A power packed fun hour of cardio friendly classics to get you moving" episodeTitle="" originalAirdate="2009-10-02T18:00:00" isMusic="true" isSeries="true" keywords="c108,c113,c201,g1,g2" seasonNumber="0" episodeNumber="0" mpaaRating="1" />
			<Program id="5" uid="!Program!1521533272_n580373510_20091002190000_1982367151" series="si3" title="00's Are Awesome" description="Like David Tua said! Let the spirit of the 2000s wash over you with the back to back clips from the new century" shortDescription="Like David Tua said! Let the spirit of the 2000s wash over you with the back to back clips from the new century" episodeTitle="" originalAirdate="2009-10-02T19:00:00" isMusic="true" isSeries="true" keywords="c108,c113,c201,g1,g2" seasonNumber="0" episodeNumber="0" mpaaRating="1" />
			<Program id="6" uid="!Program!1521533272_n580373510_20091002193000_912834096" series="si4" title="63 - Our Music" description="63 the greatest music videos of all time, plus the best of our music, from right now." shortDescription="63 the greatest music videos of all time, plus the best of our music, from right now." episodeTitle="" originalAirdate="2009-10-02T19:30:00" isMusic="true" isSeries="true" keywords="c108,c113,c201,g1,g2" seasonNumber="0" episodeNumber="0" mpaaRating="1" />
			<Program id="7" uid="!Program!1521533272_n580373510_20091002200000_728409992" series="si5" title="63 Spotlight" description="Spend 30 minutes soaking up the best clips from Ben Harper!" shortDescription="Spend 30 minutes soaking up the best clips from Ben Harper!" episodeTitle="" originalAirdate="2009-10-02T20:00:00" isMusic="true" isSeries="true" keywords="c108,c113,c201,g1,g2" seasonNumber="0" episodeNumber="0" mpaaRating="1" />
			<Program id="8" uid="!Program!1521533272_n580373510_20091002203000_912834096" series="si4" title="63 - Our Music" description="63 the greatest music videos of all time, plus the best of our music, from right now." shortDescription="63 the greatest music videos of all time, plus the best of our music, from right now." episodeTitle="" originalAirdate="2009-10-02T20:30:00" isMusic="true" isSeries="true" keywords="c108,c113,c201,g1,g2" seasonNumber="0" episodeNumber="0" mpaaRating="1" />
			<Program id="9" uid="!Program!1521533272_n580373510_20091002210000_1203672334" series="si6" title="Cafe 63" description="It's a 62's own coffee group- a two hour mix of classic clips &amp; the latest music around to top off the morning" shortDescription="It's a 62's own coffee group- a two hour mix of classic clips &amp; the latest music around to top off the morning" episodeTitle="" originalAirdate="2009-10-02T21:00:00" isMusic="true" isSeries="true" keywords="c108,c113,c201,g1,g2" seasonNumber="0" episodeNumber="0" mpaaRating="1" />
			<Program id="10" uid="!Program!1521533272_n580373510_20091002230000_912834096" series="si4" title="63 - Our Music" description="63 the greatest music videos of all time, plus the best of our music, from right now." shortDescription="63 the greatest music videos of all time, plus the best of our music, from right now." episodeTitle="" originalAirdate="2009-10-02T23:00:00" isMusic="true" isSeries="true" keywords="c108,c113,c201,g1,g2" seasonNumber="0" episodeNumber="0" mpaaRating="1" />
			<Program id="10558" uid="!Program!1521533272_n1480720963_229173890_424138002" title="No Program Data" description="No Program Information is available for this timeslot." shortDescription="No Program Information is available for this timeslot." episodeTitle="" originalAirdate="2009-10-03T05:30:00" seasonNumber="0" episodeNumber="0" />
		</Programs>
		<ScheduleEntries service="s0">
			<ScheduleEntry program="1" startTime="2009-10-02T10:00:00" duration="3600" isRepeat="false" tvRating="3" />
			<ScheduleEntry program="2" duration="21600" isRepeat="false" tvRating="3" />
			<ScheduleEntry program="3" duration="3600" isRepeat="false" tvRating="3" />
			<ScheduleEntry program="4" duration="3600" isRepeat="false" tvRating="3" />
			<ScheduleEntry program="5" duration="1800" isRepeat="false" tvRating="3" />
			<ScheduleEntry program="6" duration="1800" isRepeat="false" tvRating="3" />
			<ScheduleEntry program="7" duration="1800" isRepeat="false" tvRating="3" />
			<ScheduleEntry program="8" duration="1800" isRepeat="false" tvRating="3" />
			<ScheduleEntry program="9" duration="7200" isRepeat="false" tvRating="3" />
			<ScheduleEntry program="10" duration="7200" isRepeat="false" tvRating="3" />
			<ScheduleEntry program="179" duration="21600" isRepeat="false" tvRating="3" />
		</ScheduleEntries>
		<ScheduleEntries service="s1">
			<ScheduleEntry program="180" startTime="2009-10-02T10:30:00" duration="3600" isRepeat="false" tvRating="4" />
			<ScheduleEntry program="181" duration="3600" isRepeat="false" tvRating="4" />
			<ScheduleEntry program="182" duration="3600" isRepeat="false" tvRating="4" />
			<ScheduleEntry program="183" duration="3600" isRepeat="false" tvRating="4" />
			<ScheduleEntry program="184" duration="3600" isRepeat="false" tvRating="4" />
			<ScheduleEntry program="185" duration="3600" isRepeat="false" tvRating="4" />
			<ScheduleEntry program="186" duration="3600" isRepeat="false" tvRating="4" />
			<ScheduleEntry program="187" duration="3600" isRepeat="false" tvRating="4" />
			<ScheduleEntry program="188" duration="3600" isRepeat="false" tvRating="4" />
			<ScheduleEntry program="189" duration="3600" isRepeat="false" tvRating="4" />
			<ScheduleEntry program="190" duration="3600" isRepeat="false" tvRating="4" />
			<ScheduleEntry program="10545" duration="2700" isRepeat="false" tvRating="3" />
		</ScheduleEntries>
		<ScheduleEntries service="s47">
			<ScheduleEntry program="10546" startTime="2009-10-01T11:00:00" duration="86400" isRepeat="false" tvRating="3" />
			<ScheduleEntry program="10547" duration="86400" isRepeat="false" tvRating="3" />
			<ScheduleEntry program="10548" duration="86400" isRepeat="false" tvRating="3" />
			<ScheduleEntry program="10549" duration="86400" isRepeat="false" tvRating="3" />
			<ScheduleEntry program="10550" duration="86400" isRepeat="false" tvRating="3" />
			<ScheduleEntry program="10551" duration="86400" isRepeat="false" tvRating="3" />
			<ScheduleEntry program="10552" duration="86400" isRepeat="false" tvRating="3" />
			<ScheduleEntry program="10553" duration="86400" isRepeat="false" tvRating="3" />
			<ScheduleEntry program="10554" duration="86400" isRepeat="false" tvRating="3" />
			<ScheduleEntry program="10555" duration="86400" isRepeat="false" tvRating="3" />
		</ScheduleEntries>
		<Lineups>
			<Lineup name="Big Screen EPG" id="l1" uid="!Lineup!BSE" primaryProvider="!MCLineup!MainLineup">
				<channels>
					<Channel lineup="l1" uid="!Channel!BSEinet_tc-24j2_j2" number="-1" subNumber="0" service="s0" />
					<Channel lineup="l1" uid="!Channel!BSEinet_tc-anim_animal_planet" number="-1" subNumber="0" service="s1" />
					<Channel lineup="l1" uid="!Channel!BSEinet_tc-art2_the_arts_channel" number="-1" subNumber="0" service="s2" />
					<Channel lineup="l1" uid="!Channel!BSEinet_tc-bbcv_bbc_world" number="-1" subNumber="0" service="s3" />
					<Channel lineup="l1" uid="!Channel!BSEinet_tc-cbnc_cnbc" number="-1" subNumber="0" service="s4" />
					<Channel lineup="l1" uid="!Channel!BSEinet_tc-cbtv_canterbury_tv" number="-1" subNumber="0" service="s5" />
					<Channel lineup="l1" uid="!Channel!BSEinet_tc-cmd1_comedy_central" number="-1" subNumber="0" service="s6" />
					<Channel lineup="l1" uid="!Channel!BSEinet_tc-cnns_cnn" number="-1" subNumber="0" service="s7" />
					<Channel lineup="l1" uid="!Channel!BSEinet_tc-criv_crime_&amp;_investigation" number="-1" subNumber="0" service="s8" />
					<Channel lineup="l1" uid="!Channel!BSEinet_tc-crtn_cartoon_network" number="-1" subNumber="0" service="s9" />
					<Channel lineup="l1" uid="!Channel!BSEinet_tc-disc_discovery" number="-1" subNumber="0" service="s10" />
					<Channel lineup="l1" uid="!Channel!BSEinet_tc-disct_discovery_travel_&amp;_living" number="-1" subNumber="0" service="s11" />
					<Channel lineup="l1" uid="!Channel!BSEinet_tc-doco_documentary_channel" number="-1" subNumber="0" service="s12" />
					<Channel lineup="l1" uid="!Channel!BSEinet_tc-echl_en" number="-1" subNumber="0" service="s13" />
					<Channel lineup="l1" uid="!Channel!BSEinet_tc-espn_espn" number="-1" subNumber="0" service="s14" />
					<Channel lineup="l1" uid="!Channel!BSEinet_tc-food_food_television" number="-1" subNumber="0" service="s15" />
					<Channel lineup="l1" uid="!Channel!BSEinet_tc-fstv_fashion_tv" number="-1" subNumber="0" service="s16" />
					<Channel lineup="l1" uid="!Channel!BSEinet_fv-parliamenttv_parliament_tv" number="-1" subNumber="0" service="s17" />
					<Channel lineup="l1" uid="!Channel!BSEinet_tc-hist_the_history_channel" number="-1" subNumber="0" service="s18" />
					<Channel lineup="l1" uid="!Channel!BSEinet_tc-juic_juice_tv" number="-1" subNumber="0" service="s19" />
					<Channel lineup="l1" uid="!Channel!BSEinet_tc-liv1_the_living_channel" number="-1" subNumber="0" service="s20" />
					<Channel lineup="l1" uid="!Channel!BSEinet_tc-mgmm_mgm" number="-1" subNumber="0" service="s21" />
					<Channel lineup="l1" uid="!Channel!BSEinet_tc-mov3_sky_movie_greats" number="-1" subNumber="0" service="s22" />
					<Channel lineup="l1" uid="!Channel!BSEinet_tc-movi_sky_movies" number="-1" subNumber="0" service="s23" />
					<Channel lineup="l1" uid="!Channel!BSEinet_tc-mtv1_mtv" number="-1" subNumber="0" service="s24" />
					<Channel lineup="l1" uid="!Channel!BSEinet_tc-natg_national_geographic" number="-1" subNumber="0" service="s25" />
					<Channel lineup="l1" uid="!Channel!BSEinet_tc-news_sky_news_nz" number="-1" subNumber="0" service="s26" />
					<Channel lineup="l1" uid="!Channel!BSEinet_tc-nick_nickelodeon" number="-1" subNumber="0" service="s27" />
					<Channel lineup="l1" uid="!Channel!BSEinet_tc-pepg_prime" number="-1" subNumber="0" service="s28" />
					<Channel lineup="l1" uid="!Channel!BSEinet_tc-shin_shine_tv" number="-1" subNumber="0" service="s29" />
					<Channel lineup="l1" uid="!Channel!BSEinet_tc-sky1_the_box" number="-1" subNumber="0" service="s30" />
					<Channel lineup="l1" uid="!Channel!BSEinet_tc-sky2_vibe" number="-1" subNumber="0" service="s31" />
					<Channel lineup="l1" uid="!Channel!BSEinet_tc-skyb_sky_box_office_201" number="-1" subNumber="0" service="s32" />
					<Channel lineup="l1" uid="!Channel!BSEinet_tc-smtp_sky_movies_2" number="-1" subNumber="0" service="s33" />
					<Channel lineup="l1" uid="!Channel!BSEinet_tc-spee_sky_box_office_preview" number="-1" subNumber="0" service="s34" />
					<Channel lineup="l1" uid="!Channel!BSEinet_fv-stratos_stratos" number="-1" subNumber="0" service="s35" />
					<Channel lineup="l1" uid="!Channel!BSEinet_tc-sund_rialto_channel" number="-1" subNumber="0" service="s36" />
					<Channel lineup="l1" uid="!Channel!BSEinet_tc-tbn_tbn" number="-1" subNumber="0" service="s37" />
					<Channel lineup="l1" uid="!Channel!BSEinet_tc-tcm1_tcm" number="-1" subNumber="0" service="s38" />
					<Channel lineup="l1" uid="!Channel!BSEinet_fv-tvone_tv_one" number="-1" subNumber="0" service="s39" />
					<Channel lineup="l1" uid="!Channel!BSEinet_fv-tv2_tv2" number="-1" subNumber="0" service="s40" />
					<Channel lineup="l1" uid="!Channel!BSEinet_fv-c4_c4" number="-1" subNumber="0" service="s41" />
					<Channel lineup="l1" uid="!Channel!BSEinet_tc-uktv_uk_tv" number="-1" subNumber="0" service="s42" />
					<Channel lineup="l1" uid="!Channel!BSEinet_tc-weat_weather" number="-1" subNumber="0" service="s43" />
					<Channel lineup="l1" uid="!Channel!BSEinet_fv-tv3_tv3" number="-1" subNumber="0" service="s44" />
					<Channel lineup="l1" uid="!Channel!BSEinet_tc-wtv1_wtv_japanese_tv1" number="-1" subNumber="0" service="s45" />
					<Channel lineup="l1" uid="!Channel!BSEinet_tc-wtv5_wtv_chinese_tv4" number="-1" subNumber="0" service="s46" />
					<Channel lineup="l1" uid="!Channel!BSEinet_tc-mosaic_scan" number="-1" subNumber="0" service="s47" />
				</channels>
			</Lineup>
		</Lineups>
	</With>
</MXF>
EOT;

}


if (isset($_GET["channels"]) and isset($_GET["provider"]))
{
 $inputArray = $_GET;
 $inputArray["provider"] = $_GET["provider"];
 $inputArray["channels"] = str_replace("/", ",", $inputArray["channels"]);
 connectToDB();
 //$inputArray["files"] = explode(",", $inputArray["files"]);
 $outputArray = CheckVars($inputArray);
 if (isset($outputArray)) // If we have valid settings for the file
 {
  $xmlStartDate = date('YmdHis');
  $startDate = currentDateSQL;
  $endDate = date('Y-m-d H:i:s', strtotime($startDate . " +" . ($outputArray['raw']['days'] + 1) . "days"));
  if (isset($outputArray['raw']['newxml']))
  {
   $dtdURL = 'http://xmltv.cvs.sourceforge.net/*checkout*/xmltv/xmltv/todo/xmltv-0.6.dtd';
  }
  else
  {
   $dtdURL = 'http://xmltv.cvs.sourceforge.net/*checkout*/xmltv/xmltv/xmltv.dtd';
  }
  $dtdLocation = 'dtds/' . basename(parse_url($dtdURL, PHP_URL_PATH));
  $channelinfodtdLocation = 'dtds/channelinfo.dtd';
  $ratingsrc = $ratingIconsArchiveFolder;
  if (isset($outputArray['raw']['links'])) //If we're linking to remote files, rather than local files with a relative path
  {
   switch ($outputArray['raw']['links'])
   {
	case 'remote':
	 $dtdLocation = $dtdURL;
	 $channelinfodtdLocation = xmltvURL . $channelinfodtdLocation;
	 $ratingsrc = 'http://www.censorship.govt.nz/img/';
	break;
	case 'xmltv':
     $dtdLocation = xmltvURL . $dtdLocation;
	 $channelinfodtdLocation = xmltvURL . $channelinfodtdLocation;
	 $ratingsrc = xmltvURL . $ratingIconsArchiveFolder;
    break;
   }
  }
  if (isset($outputArray['raw']['newxml']))
  {
   $xmlOutput = CreateNewXMLHeader($xmlStartDate, $outputArray['raw']['offset'], currentDate, $endDate, $outputArray['url']['channels'], $outputArray['raw']['provider']);
  }
  else
  {
   $xmlOutput = CreateXMLHeader($xmlStartDate, $outputArray['raw']['offset'], currentDate, $endDate, $outputArray['url']['channels'], $outputArray['raw']['provider'], $dtdLocation);
  }
  $channelInfoOutput = '<?xml version="1.0" standalone="yes"?>' . "\n" . '<!DOCTYPE newdataset SYSTEM "' . $channelinfodtdLocation . '">' . "\n" . '<newdataset>' . "\n";
  //$preferredSources = array('YH', 'TC', 'FV');
  foreach ($outputArray['raw']['channels'] as $inputChannel)
  {
   $channelResult = mysql_query("SELECT type FROM xmltvChannels WHERE All_id='" . $inputChannel . "'");
   if (mysql_num_rows($channelResult) == 1)
   {
    $channelRow = mysql_fetch_assoc($channelResult);
    $channels[$inputChannel] = $channelRow['type'];
   }
  }
   /*foreach ($preferredSources as $preferredSource)
   {
    if ($channelRow[$preferredSource . '_id'] <> NULL)
	{
	 $selectedSource = $channelRow[$preferredSource . '_id'];
	}
   }
   $channels[$selectedSource] = $channelRow['type'];
  }*/
  if (is_array($channels))
  {
   foreach ($channels as $channel => $channelType)
   {
    $channelArray = explode("-", $channel);
	$channelResult = mysql_query("SELECT * FROM xmltvChannels WHERE All_id='" . $channel . "'");
    $channelRow = mysql_fetch_assoc($channelResult);
    foreach (array('large', 'small') as $iconSize)
    {
     $iconsArray[$iconSize . '-' . $channel] = $iconSize . '/' . basename(parse_url($channelRow[$iconSize . '_icon'], PHP_URL_PATH));
    }
    $channelIcon = $channelIconsArchiveFolder . $outputArray['raw']['icons'] . '/' . basename(parse_url($channelRow[$outputArray['raw']['icons'] . '_icon'], PHP_URL_PATH));
    if (isset($outputArray['raw']['icons']) AND isset($outputArray['raw']['links']))
    {
     switch ($outputArray['raw']['links'])
     {
	  case 'remote':
	   $channelIcon = $channelRow[$outputArray['raw']['icons'] . '_icon'];
	  break;
	  case 'xmltv':
       $channelIcon = xmltvURL . $channelIcon;
      break;
	 }
    }
    if (isset($outputArray['raw']['newxml']))
    {
     $xmlOutput .= CreateNewChannelXML($channel, $channelRow['All_number'], $channelRow['display_name'], $channelRow['url'], $channelIcon);
    }
    else
    {
     $xmlOutput .= CreateChannelXML($channelRow[All_id] . ".xmltv.co.nz", $channelRow['All_number'], $channelRow['display_name'], $channelRow['url'], $channelIcon);
    }
    $channelInfoOutput .= CreateChannelInfoXML($channel, $channelRow[ucwords($outputArray['raw']['provider']) . '_number'], $channelRow['display_name']);
   }
  }
  $channelInfoOutput .= '</newdataset>' . "\n";
  if (is_array($channels))
  {
  foreach ($channels as $channel => $channelType)
  {
   unset($radio);
   if ($channelType == 'radio' OR $channelType == 'music')
   {
	$radio = TRUE;
   }
   //$programsResult = mysql_query("SELECT * FROM xmltvSourcePrograms WHERE channel_id='" . $channel . "' AND stop >= '" . $startDate . "' AND start <= '" . $endDate . "' ORDER BY channel, start") or die(mysql_error());
   $programsResult = mysql_query("SELECT * FROM xmltvPrograms WHERE channel='" . $channel . "' AND stop >= '" . $startDate . "' AND start <= '" . $endDate . "' ORDER BY channel, start") or die(mysql_error());
   if (isset($outputArray['raw']['newxml']))
   {
    while ($programRow = mysql_fetch_assoc($programsResult))
    {
	 $xmlOutput .= CreateNewProgramXML($programRow['channel_id'], MySQLToXMLDateTime($programRow['start']), MySQLToXMLDateTime($programRow['stop']), $programRow['title'], $programRow['description'], $programRow['category'], $programRow['rating'], $programRow['aspect'], $programRow['quality'], $outputArray['raw']['offset'], $programRow['subtitles'], $programRow['url'], $radio, $ratingsrc);
	}
   }
   else
   {
    while ($programRow = mysql_fetch_assoc($programsResult))
    {
     $xmlOutput .= CreateProgramXML($programRow['channel'] . ".xmltv.co.nz", MySQLToXMLDateTime($programRow['start']), MySQLToXMLDateTime($programRow['stop']), $programRow['title'], $programRow['description'], $programRow['category'], $programRow['rating'], $programRow['aspect'], $programRow['quality'], $outputArray['raw']['offset'], $programRow['subtitles'], $programRow['url'], $radio, $ratingsrc);
	}
   }
  }
  }
  $xmlOutput .= CreateXMLFooter();
  $xmlOutput = xmlEntities($xmlOutput);
  switch ($outputArray['raw']['archive'])
  {
   case 'zip':
	include '../libs/zipstream.php';
    $zip = new ZipStream($outputArray['raw']['filename'] . '.zip');
    $zip->add_file($outputArray['raw']['filename'], $xmlOutput);
	foreach ($iconsArray as $channelIcon)
	{	
	 $iconPathFilename = iconsFolder . $channelIcon;
	 if (file_exists($iconPathFilename))
	 {
	  $zip->add_file_from_path($channelIconsArchiveFolder . $channelIcon, $iconPathFilename);
	 }
	}
	foreach (array('channelinfo.dtd', 'xmltv.dtd', 'xmltv-0.6.dtd') as $dtdFilename)
	{
	 if (file_exists(dtdsFolder . $dtdFilename))
	 {
	  $zip->add_file_from_path($dtdsArchiveFolder . $dtdFilename, dtdsFolder . $dtdFilename);
	 }
	}
	if ($handle = opendir(ratingsFolder))
    {
     while (false !== ($file = readdir($handle)))
     {
      if ($file != "." && $file != "..")
      {
	   $zip->add_file_from_path($ratingIconsArchiveFolder . $file, ratingsFolder . $file);
	  }
	 }
	}
    $zip->add_file('channelinfo.xml', $channelInfoOutput);
    $zip->finish();
   break;
   case 'gzip':
    echo "Not implemented yet";
   break;
   default:
    //ob_start('ob_gzhandler');
    sendHeaders($outputArray['raw']['filename'], strlen($xmlOutput));
    echo $xmlOutput;
   break;
  }
 }
 else
 {
  // Problem with the data
 }
}
elseif (isset($_GET["all"]))
{
 // Not enough data given (minimum is channels and provider)
 if ($_GET["all"] == "yes")
 {
  connectToDB();
  $dtdLocation = 'http://xmltv.cvs.sourceforge.net/viewvc/xmltv/xmltv/xmltv.dtd';
  $ratingsrc = 'http://www.censorship.govt.nz/img/';
  $xmlOutput = CreateXMLHeader($xmlStartDate, "", currentDate, $endDate, "all", "all", $dtdLocation);
  /*
  foreach (Array("TC", "YH", "FV") as $channelSource)
  {
   $channelResult = mysql_query("SELECT * FROM xmltvChannels WHERE " . $channelSource . "_id IS NOT NULL");
   while ($channelRow = mysql_fetch_assoc($channelResult))
   {
    $xmlOutput .= CreateChannelXML($channelRow[$channelSource . '_id'] . ".xmltv.co.nz", $channelRow['All_number'], $channelRow['display_name'], $channelRow['url'], $channelRow['large_icon']);
   }
  }
  */
  $channelResult = mysql_query("SELECT * FROM xmltvChannels WHERE All_id IS NOT NULL ORDER BY All_number");
  while ($channelRow = mysql_fetch_assoc($channelResult))
  {
   foreach (Array("FV", "TC", "YH") as $channelSource)
   {
    if (!empty($channelRow[$channelSource . '_id']))
	{
	 $xmlOutput .= CreateChannelXML($channelRow['All_id'] . "-" . $channelRow[$channelSource . '_id'] . ".xmltv.co.nz", $channelRow['All_number'], $channelRow['display_name'] . "-" . $channelSource, $channelRow['url'], $channelRow['large_icon']);
	}
   }
  }
  $programsResult = mysql_query("SELECT * FROM xmltvSourcePrograms WHERE channel IS NOT NULL ORDER BY channel, channel_id, start") or die(mysql_error());
  while ($programRow = mysql_fetch_assoc($programsResult))
  {
   $xmlOutput .= CreateProgramXML($programRow['channel'] . "-" . $programRow['channel_id'] . ".xmltv.co.nz", MySQLToXMLDateTime($programRow['start']), MySQLToXMLDateTime($programRow['stop']), $programRow['title'], $programRow['description'], $programRow['category'], $programRow['rating'], $programRow['aspect'], $programRow['quality'], "", $programRow['subtitles'], $programRow['url'], $radio, $ratingsrc);
  }
  $xmlOutput .= CreateXMLFooter();
  $xmlOutput = xmlEntities($xmlOutput);
  sendHeaders("TVGuide.xml", strlen($xmlOutput));
  echo $xmlOutput;
 }
}

 /*
 */

?>